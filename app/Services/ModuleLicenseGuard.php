<?php

namespace App\Services;

use App\Models\InstalledModule;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * گارد لایسنس ماژول‌ها — سخت‌سازی امنیتی (Fail-Closed)
 * ==================================================================
 *
 * حفرهٔ ریشه‌ای که این سرویس می‌بندد:
 *   • قبلاً گاردِ هر پکیج، منبع حقیقت را «تاریخ دیتابیس فروشگاه» می‌دانست و
 *     با قطع سرور بازار به همان تاریخ برمی‌گشت (fail-open) — نتیجه: با قطع
 *     سرور یا دستکاری دیتابیس، پکیج‌ها به‌کار ادامه می‌دادند.
 *
 * معماری جدید:
 *   ۱) «پاکت لایسنس امضاشده» (Signed Envelope) — سرور بازار با کلید خصوصی EC
 *      پاکت {valid, expires_at, domain, subscription, grace_hours, ...} را امضا
 *      می‌کند؛ کلاینت با کلید عمومیِ جاسازی‌شده امضا را راستی‌آزمایی می‌کند.
 *      تاریخ دیتابیس محلی دیگر هیچ نقشی در قضاوت ندارد (فقط آینهٔ نمایشی).
 *   ۲) Fail-Closed با «مهلت» (grace): قطع سرور ⇒ شمارش مهلت (پیش‌فرض ۲۴ ساعت
 *      از خودِ پاکت امضاشده می‌آید — سرور کنترل می‌کند) ⇒ پس از مهلت بسته می‌شود.
 *   ۳) ضد-دستکاری:
 *      • پاکت با امضای نامعتبر ⇒ بسته (tampered/envelope_invalid).
 *      • تاریخ انقضا در دیتابیس دیگر خوانده نمی‌شود؛ فقط پاکت امضاشده.
 *      • لنگرهای مهلت در دیتابیس + فایل storage نگه‌داری می‌شوند؛ پاک‌سازی
 *        آنها ناهنجاری است ولی در نهایت «پیری پاکت» (envelope_max_age_hours)
 *        قطع‌کنندهٔ قطعی است — چون پاکتِ تازه فقط از سرور امضاشده می‌آید.
 *      • یکپارچگی فایل‌ها با مانیفست امضاشدهٔ سرور (module.lock) بررسی می‌شود؛
 *        حذف/تغییر حتی یک فایل (حتی view) ⇒ بستن پکیج.
 *   ۴) بستن راه‌های فرار قدیمی: فلگ license_skip دیگر هیچ جا خوانده نمی‌شود؛
 *      دور زدن CLI هم فقط برای allowlistِ مشخص (نصب/نگهداشت هسته) مجاز است.
 *
 * حالت‌ها: active / grace / closed / not_installed
 */
class ModuleLicenseGuard
{
    public const STATE_ACTIVE       = 'active';
    public const STATE_GRACE        = 'grace';
    public const STATE_CLOSED       = 'closed';
    public const STATE_NOT_INSTALLED = 'not_installed';

    public const REASON_NO_LICENSE       = 'no_license';
    public const REASON_NOT_INSTALLED    = 'not_installed';
    public const REASON_EXPIRED          = 'expired';
    public const REASON_REVOKED          = 'revoked';
    public const REASON_DOMAIN_MISMATCH  = 'domain_mismatch';
    public const REASON_ENVELOPE_INVALID = 'envelope_invalid';
    public const REASON_ENVELOPE_MISSING = 'envelope_missing';
    public const REASON_TAMPERED         = 'tampered';
    public const REASON_INTEGRITY_FAILED = 'integrity_failed';
    public const REASON_SERVER_REJECTED  = 'server_rejected';
    public const REASON_GRACE_EXPIRED    = 'grace_expired';
    public const REASON_STALE_ENVELOPE   = 'stale_envelope';

    /**
     * عمر مجاز پاکت (ساعت) — پس از این مدت پاکتِ بدون به‌روزرسانی آنلاین بسته می‌شود.
     * ضد «مسدودسازی دائمی API + ریست لنگرهای مهلت».
     */
    private const ENVELOPE_MAX_AGE_HOURS = 30 * 24;

    /** TTL کش «آخرین چک» — حداکثر به اندازهٔ فاصلهٔ چک؛ کش هرگز حکم لایسنس نمی‌دهد */
    private const LAST_CHECK_CACHE_MINUTES = 24 * 60;

    /**
     * ضد-توفان provision — بعد از یک تلاش ناموفق provision مانیفست، حداقل این
     * مدت (دقیقه) تا تلاش بعدی صبر می‌شود. provision ناموفق هرگز بحرانی نیست
     * (مانیفستِ غایب پکیج را نمی‌بندد)؛ تکرارش در هر ریکوئست روی هاست اشتراکی
     * یعنی تماس HTTP به بازار + دو خط لاگ، هر ~۵ ثانیه — سیلاب لاگ و سوزاندن
     * منابع دو سرور. ۱۵ دقیقه فاصلهٔ امن: ترمیم مانیفست حداکثر ۱۵ دقیقه عقب
     * می‌افتد، ولی سیلاب صفر می‌شود.
     */
    private const PROVISION_BACKOFF_MINUTES = 15;

    /**
     * allowlist کنسول — کامندهای نگهداشت/نصب هسته هرگز نباید به گارد بخورند
     * (نصب/تمدید خودِ پکیج باید وقتی پکیج بسته است هم کار کند). بقیهٔ کامندهای
     * ماژول‌ها گیت می‌خورند.
     */
    private const CLI_ALLOW_PATTERNS = [
        '/^packages:/',
        '/^queue:/',
        '/^(migrate|db:)/',
        '/^(optimize|config:|route:|view:|event:|cache:)/',
        '/^schedule:run/',
        '/^(serve|tinker|about|list|help|test)/',
        '/^(updater|shop:|theme|app:fix|backup|sitemap|torob|clear:tmp|dump-autoload|storage:link|key:generate|pail)/',
        '/^(docs:|export:|import:|clean:|get:|product:|newsletter|permission)/',
    ];

    // کلید عمومی امضا فقط در LicenseEnvelopeVerifier نگه‌داری می‌شود (تک منبع حقیقت)

    public function __construct(
        private PackageApiService $api,
        private ModuleIntegrityService $integrity,
        private LicenseEnvelopeVerifier $verifier
    ) {}

    /* ===================================================================
     *  API عمومی
     * =================================================================== */

    /**
     * قضاوت کامل وضعیت لایسنس یک ماژول.
     *
     * @return array{
     *   ok: bool,
     *   state: string,
     *   reason: ?string,
     *   message: ?string,
     *   grace_until: ?string,
     *   expires_at: ?string,
     *   subscription_expires_at: ?string,
     *   module: ?InstalledModule,
     * }
     */
    public function status(string $slug): array
    {
        // سوییچ توسعهٔ محلی — در تولید هرگز روشن نمی‌شود
        if (! (bool) config('packages.license.enforce', true)) {
            return $this->pack(true, self::STATE_ACTIVE, null, null, null, null, null);
        }

        // allowlist کنسول — نصب/نگهداشت هسته هرگز قفل نشود
        if (app()->runningInConsole() && $this->isAllowedConsoleCommand()) {
            $module = $this->resolveModule($slug);

            return $this->pack(true, self::STATE_ACTIVE, null, null, null, null, $module);
        }

        $module = $this->resolveModule($slug);

        if (!$module) {
            return $this->pack(false, self::STATE_NOT_INSTALLED, self::REASON_NOT_INSTALLED, 'ماژول نصب نشده است.', null, null, null);
        }

        // ---------- ۱) یکپارچگی فایل‌ها (manifest امضاشده) ----------
        $integrity = $this->integrity->verify($module->name);

        $reason = (string) ($integrity['reason'] ?? '');

        // فایل module.lock «حذف» شده ⇒ دستکاری قطعی (حتی اگر قبلاً provision شده)
        if ($reason === ModuleIntegrityService::REASON_MANIFEST_MISSING && $this->hadManifest($module)) {
            $this->logEvent($module, 'tamper_detected', ['source' => 'manifest_deleted', 'module' => $module->name]);
            $this->markClosed($module, self::REASON_TAMPERED, 'فایل یکپارچگی پکیج (module.lock) حذف شده است — پکیج بسته شد.');

            return $this->pack(false, self::STATE_CLOSED, self::REASON_TAMPERED, 'فایل یکپارچگی پکیج (module.lock) حذف شده است — پکیج بسته شد.', null, null, $module);
        }

        // امضای مانیفست با کلیدهای عمومی فروشگاه تأیید نمی‌شود ⇒ معمولاً یعنی کلید
        // امضای سرور عوض شده (نه دستکاری). مانیفستِ تازه از خود سرور (منبع حقیقت)
        // provision می‌شود؛ اگر تأمین نشد ⇒ fail-closed با پیامِ راهنمای چرخش کلید.
        // توجه: تشخیص دستکاری فایل‌ها از بین نمی‌رود — بعد از provision تازه، هش
        // تک‌تک فایل‌ها دوباره با مانیفستِ امضاشدهٔ سرور مقایسه می‌شود.
        if ($reason === ModuleIntegrityService::REASON_MANIFEST_SIGNATURE_INVALID) {
            $this->provisionManifest($module);

            $integrity = $this->integrity->verify($module->name);

            if (($integrity['ok'] ?? false) === false) {
                $newReason = (string) ($integrity['reason'] ?? '');
                $message   = (string) ($integrity['message'] ?? 'امضای مانیفست یکپارچگی معتبر نیست.');

                if ($newReason !== ModuleIntegrityService::REASON_MANIFEST_MISMATCH) {
                    $message .= ' اگر کلید امضای سرور آپدیت‌کننده تعویض شده است، کلید عمومی متناظر را در پنل فروشگاه (تنظیمات برنامه‌نویس ← تب آپدیت‌کننده ← «کلید عمومی راستی‌آزمایی لایسنس») یا در .env فروشگاه (PACKAGE_LICENSE_PUBLIC_KEY) به‌روز کنید — راهنما: README بخش «چرخش کلید امضا».';
                }

                $this->logEvent($module, 'integrity_failed', ['reason' => $reason, 'after_reprovision' => $newReason]);
                $this->markClosed($module, self::REASON_INTEGRITY_FAILED, $message);

                return $this->pack(false, self::STATE_CLOSED, self::REASON_INTEGRITY_FAILED, $message, null, null, $module);
            }

            // ترمیم موفق — وضعیت تازه را ثبت می‌کنیم تا provision دوباره در چک آنلاین تکرار نشود
            $reason = (string) ($integrity['reason'] ?? '');
        }

        // شکست یکپارچگی دیگر (ناهم‌خوانی هش فایل‌ها / مانیفست خالی) ⇒ بستن
        if (($integrity['ok'] ?? false) === false && ! in_array($reason, [ModuleIntegrityService::REASON_MANIFEST_MISSING], true)) {
            $this->logEvent($module, 'integrity_failed', $integrity);
            $this->markClosed($module, self::REASON_INTEGRITY_FAILED, (string) ($integrity['message'] ?? 'یکپارچگی فایل‌های پکیج نقض شده است.'));

            return $this->pack(false, self::STATE_CLOSED, self::REASON_INTEGRITY_FAILED, (string) ($integrity['message'] ?? 'یکپارچگی فایل‌های پکیج نقض شده است.'), null, null, $module);
        }

        // آیا مانیفست باید از سرور provision شود؟ (نصب قدیمی یا امضای نامعتبر هنوز ترمیم‌نشده)
        $manifestMissing = in_array($reason, [
            ModuleIntegrityService::REASON_MANIFEST_MISSING,
            ModuleIntegrityService::REASON_MANIFEST_SIGNATURE_INVALID,
        ], true);

        // ---------- ۲) «اول اشتراک، بعد لایسنس» — کلید لایسنس محلی خالی است؟ ----------
        // پکیج‌های «رایگانِ داخل اشتراک» ممکن است کلید تکی نداشته باشند؛ منبع حقیقت
        // سرور بازار است (با توکن آپدیت پروژه). اگر پکیج جزو اشتراک نباشد، حکم
        // امضاشدهٔ رد می‌آید و باید لایسنسِ تکیِ خود پکیج خریداری شود.
        if (empty($module->license_key)) {
            return $this->statusWithoutLicenseKey($module, $manifestMissing);
        }

        // ---------- ۳) پاکت امضاشده (منبع حقیقت) ----------
        $envelope = $this->readEnvelope($module);

        $verdict = null;

        if ($envelope !== null) {
            $verdict = $this->judgeEnvelope($module, $envelope);

            // حکم قطعی امضاشده: بسته‌شدن‌ها بی‌شرط اعمال می‌شوند
            if ($verdict['ok'] === false) {
                $this->markClosed($module, $verdict['reason'], $verdict['message']);

                return $this->pack(false, self::STATE_CLOSED, $verdict['reason'], $verdict['message'], null, $verdict['expires_at'], $module, $verdict['subscription_expires_at'] ?? null);
            }
        }

        // ---------- ۴) چک دوره‌ای آنلاین ----------
        $intervalHours = (int) ($envelope['check_interval_hours'] ?? config('packages.license.check_interval_hours', 24));
        $due           = $envelope === null || $this->dueForCheck($module, $intervalHours);

        if ($due) {
            $result = $this->runOnlineCheck($module, $manifestMissing);

            if ($result !== null) {
                // چک آنلاین حکم قطعی داد (فعال/بسته)
                if ($result['ok'] === false) {
                    $this->markClosed($module, $result['reason'], $result['message']);

                    return $this->pack(false, self::STATE_CLOSED, $result['reason'], $result['message'], null, $result['expires_at'] ?? null, $module);
                }

                $envelope = $this->readEnvelope($module);
                $verdict  = $envelope !== null ? $this->judgeEnvelope($module, $envelope) : null;

                if ($verdict !== null && $verdict['ok'] === false) {
                    return $this->pack(false, self::STATE_CLOSED, $verdict['reason'], $verdict['message'], null, $verdict['expires_at'], $module);
                }

                // چک موفق — پاکت تازه، مهلت پاک می‌شود
                $this->clearGrace($module);
                $this->markState($module, self::STATE_ACTIVE, null);

                return $this->pack(true, self::STATE_ACTIVE, null, null, null, $verdict['expires_at'] ?? null, $module, $verdict['subscription_expires_at'] ?? null);
            }

            // $result === null ⇒ سرور در دسترس نبود ⇒ مسیر مهلت
            return $this->handleGrace($module, $envelope);
        }

        // ---------- ۵) پاکت سالم و چک تازه ⇒ فعال ----------
        // فرصت provision مانیفست برای نصب‌های قدیمی که module.lock ندارند
        if ($manifestMissing) {
            $this->provisionManifest($module);
        }

        $this->markState($module, self::STATE_ACTIVE, null);

        return $this->pack(true, self::STATE_ACTIVE, null, null, null, $verdict['expires_at'] ?? null, $module, $verdict['subscription_expires_at'] ?? null);
    }

    /**
     * سازگاری با فراخوانی‌های قبلی (providerهای ماژول‌ها) — true یعنی «مجاز».
     */
    public function check(string $slug): bool
    {
        return $this->status($slug)['ok'];
    }

    /**
     * «اول اشتراک» — قضاوت ماژولی که کلید لایسنسِ محلی ندارد.
     *
     * پکیج‌های رایگانِ داخل اشتراکِ طرح‌محور ممکن است کلید لایسنس تکی نداشته باشند؛
     * منبع حقیقت «سرور بازار» است — با توکن آپدیتِ پروژه از سرور پرسیده می‌شود:
     *   • اشتراکِ فعالِ طرح‌محور این پکیج را بپوشاند ⇒ سرور لایسنسِ رایگان صادر
     *     می‌کند و پاکت امضاشدهٔ معتبر برمی‌گردد (کلید صادرشده خودکار روی ماژول
     *     provision می‌شود و قضاوت‌های بعدی از مسیر عادی لایسنس می‌رود).
     *   • جزو اشتراک نباشد ⇒ حکم امضاشدهٔ رد ⇒ پکیج بسته می‌شود و باید لایسنس
     *     تکیِ خود پکیج خریداری شود.
     *   • سرور در دسترس نباشد ⇒ fail-closed — ماژولِ بی‌کلید حق مهلت (grace) ندارد.
     *
     * @param  bool  $manifestMissing  خروجی گام یکپارچگی (برای provision در چک آنلاین)
     */
    private function statusWithoutLicenseKey(InstalledModule $module, bool $manifestMissing): array
    {
        // پاکت ذخیره‌شدهٔ قبلی (صدور اشتراک یا حکم رد) — بدون تماس شبکه قضاوت می‌شود
        $envelope = $this->readEnvelope($module);

        if ($envelope !== null) {
            $verdict = $this->judgeEnvelope($module, $envelope);

            if ($verdict['ok'] === false) {
                $this->markClosed($module, $verdict['reason'], $verdict['message']);

                return $this->pack(false, self::STATE_CLOSED, $verdict['reason'], $verdict['message'], null, $verdict['expires_at'], $module, $verdict['subscription_expires_at'] ?? null);
            }

            // پاکت معتبرِ قبلی — فقط پیری پاکت/چک دوره‌ای مثل مسیر اصلی
            $intervalHours = (int) ($envelope['check_interval_hours'] ?? config('packages.license.check_interval_hours', 24));

            if (!$this->dueForCheck($module, $intervalHours)) {
                if ($manifestMissing) {
                    $this->provisionManifest($module);
                }

                $this->markState($module, self::STATE_ACTIVE, null);

                return $this->pack(true, self::STATE_ACTIVE, null, null, null, $verdict['expires_at'], $module, $verdict['subscription_expires_at'] ?? null);
            }

            $result = $this->runOnlineCheck($module, $manifestMissing);

            if ($result !== null && $result['ok']) {
                $fresh        = $this->readEnvelope($module);
                $freshVerdict = $fresh !== null ? $this->judgeEnvelope($module, $fresh) : null;

                if ($freshVerdict !== null && $freshVerdict['ok'] === false) {
                    $this->markClosed($module, $freshVerdict['reason'], $freshVerdict['message']);

                    return $this->pack(false, self::STATE_CLOSED, $freshVerdict['reason'], $freshVerdict['message'], null, $freshVerdict['expires_at'], $module, $freshVerdict['subscription_expires_at'] ?? null);
                }

                $this->clearGrace($module);
                $this->markState($module, self::STATE_ACTIVE, null);

                return $this->pack(true, self::STATE_ACTIVE, null, null, null, $freshVerdict['expires_at'] ?? $verdict['expires_at'], $module, $freshVerdict['subscription_expires_at'] ?? $verdict['subscription_expires_at'] ?? null);
            }

            if ($result !== null) {
                $this->markClosed($module, $result['reason'], $result['message']);

                return $this->pack(false, self::STATE_CLOSED, $result['reason'], $result['message'], null, $result['expires_at'] ?? null, $module);
            }

            // چک دوره‌ای ناموفق ولی پاکت معتبرِ قبلی داریم ⇒ مهلت عادی (مثل مسیر اصلی)
            return $this->handleGrace($module, $envelope);
        }

        // هیچ پاکت ذخیره‌شده‌ای نیست — اولین پرسش از سرور
        $result = $this->runOnlineCheck($module, $manifestMissing);

        if ($result !== null && $result['ok']) {
            $envelope = $this->readEnvelope($module);
            $verdict  = $envelope !== null ? $this->judgeEnvelope($module, $envelope) : null;

            if ($verdict !== null && $verdict['ok'] === false) {
                $this->markClosed($module, $verdict['reason'], $verdict['message']);

                return $this->pack(false, self::STATE_CLOSED, $verdict['reason'], $verdict['message'], null, $verdict['expires_at'], $module);
            }

            $this->clearGrace($module);
            $this->markState($module, self::STATE_ACTIVE, null);

            return $this->pack(true, self::STATE_ACTIVE, null, null, null, $verdict['expires_at'] ?? null, $module, $verdict['subscription_expires_at'] ?? null);
        }

        if ($result !== null) {
            // حکم امضاشدهٔ رد سرور — نه اشتراکی که بپوشاند، نه لایسنسی که بخورد
            $this->markClosed($module, $result['reason'], $result['message']);

            return $this->pack(false, self::STATE_CLOSED, $result['reason'], $result['message'], null, $result['expires_at'] ?? null, $module);
        }

        // سرور در دسترس نبود و کلید محلی هم نداریم ⇒ fail-closed بدون مهلت
        $this->markClosed($module, self::REASON_NO_LICENSE, 'ماژول فاقد لایسنس است.');

        return $this->pack(false, self::STATE_CLOSED, self::REASON_NO_LICENSE, 'ماژول فاقد لایسنس است — برای فعال‌سازی، اتصال به سرور بازار لازم است (اشتراکِ فعال) یا خرید لایسنسِ تکی.', null, null, $module);
    }

    /**
     * قضاوت پاکت امضاشده — حکم قطعی آفلاین.
     *
     * @return array{ok: bool, reason: ?string, message: ?string, expires_at: ?string, subscription_expires_at: ?string}
     */
    private function judgeEnvelope(InstalledModule $module, array $envelope): array
    {
        $expiresAt = $envelope['expires_at'] ?? null;
        $subscriptionExpiresAt = null;

        $subscriptionCovered = false;

        if (is_array($envelope['subscription'] ?? null) && !empty($envelope['subscription']['covered'])) {
            $subscriptionCovered = true;
            $subscriptionExpiresAt = $envelope['subscription']['subscription_expires_at'] ?? null;
        }

        // ۱) حکم خودِ سرور
        if (empty($envelope['valid'])) {
            $reason  = self::REASON_REVOKED;
            $message = (string) ($envelope['reason'] ?? 'لایسنس معتبر نیست.');

            if (str_contains($message, 'منقضی')) {
                $reason = self::REASON_EXPIRED;
            } elseif (str_contains($message, 'اشتراک') || str_contains($message, 'لایسنس فعال')) {
                // ردِ «نه لایسنس دارد، نه اشتراک می‌پوشاند» — reason درست برای UI
                $reason = self::REASON_NO_LICENSE;
            }

            return ['ok' => false, 'reason' => $reason, 'message' => $message, 'expires_at' => $expiresAt, 'subscription_expires_at' => $subscriptionExpiresAt];
        }

        // ۲) قفل دامنه — دامنهٔ درخواست باید با پاکت امضاشده بخواند
        if (!$this->runningInWeb()) {
            // در کنسول دامنه‌ای وجود ندارد — چک دامنه فقط در وب
        } else {
            $envelopeDomain = $this->normalizeDomain((string) ($envelope['domain'] ?? ''));
            $currentDomain  = $this->normalizeDomain((string) request()->getHost());

            if ($envelopeDomain !== '' && $currentDomain !== '' && !$this->domainMatches($envelopeDomain, $currentDomain)) {
                return [
                    'ok'        => false,
                    'reason'    => self::REASON_DOMAIN_MISMATCH,
                    'message'   => 'لایسنس این پکیج برای دامنهٔ فعلی سایت (' . $currentDomain . ') صادر نشده است. دامنهٔ ثبت‌شده در بازار: ' . $envelopeDomain . ' — برای تغییر دامنه از پنل بازار اقدام کنید.',
                    'expires_at' => $expiresAt,
                    'subscription_expires_at' => $subscriptionExpiresAt,
                ];
            }
        }

        // ۳) انقضا — فقط از پاکت امضاشده؛ تاریخ دیتابیس محلی خوانده نمی‌شود
        $effectiveExpiry = $subscriptionCovered ? $subscriptionExpiresAt : ($expiresAt ?? $subscriptionExpiresAt);

        if ($effectiveExpiry !== null && $effectiveExpiry !== '') {
            try {
                $expiry = Carbon::parse($effectiveExpiry);
            } catch (Throwable) {
                return ['ok' => false, 'reason' => self::REASON_ENVELOPE_INVALID, 'message' => 'پاکت لایسنس نامعتبر است.', 'expires_at' => $expiresAt, 'subscription_expires_at' => $subscriptionExpiresAt];
            }

            if ($expiry->isPast()) {
                // تاریخ انقضا شمسی با ارقام فارسی (نیاز محصول)
                return ['ok' => false, 'reason' => self::REASON_EXPIRED, 'message' => 'لایسنس این پکیج منقضی شده است (' . JalaliDate::format($expiry) . '). برای تمدید به بازار مراجعه کنید.', 'expires_at' => $expiresAt, 'subscription_expires_at' => $subscriptionExpiresAt];
            }
        }

        // ۴) پیری پاکت — ضد «مسدودسازی دائمی سرور + ریست لنگرهای مهلت»
        $maxAgeHours = (int) config('packages.license.envelope_max_age_hours', self::ENVELOPE_MAX_AGE_HOURS);

        try {
            $issuedAt = Carbon::parse((string) ($envelope['issued_at'] ?? ''));

            if ($issuedAt->diffInHours(now()) > $maxAgeHours) {
                return [
                    'ok'        => false,
                    'reason'    => self::REASON_STALE_ENVELOPE,
                    'message'   => 'اعتبارسنجی آنلاین لایسنس مدت‌ها انجام نشده است. برای ادامهٔ کار، ارتباط با سرور بازار باید برقرار شود.',
                    'expires_at' => $expiresAt,
                    'subscription_expires_at' => $subscriptionExpiresAt,
                ];
            }
        } catch (Throwable) {
            // issued_at خراب — پاکت باید دوباره از سرور تازه شود؛ در چک دوره‌ای انجام می‌شود
        }

        return ['ok' => true, 'reason' => null, 'message' => null, 'expires_at' => $expiresAt, 'subscription_expires_at' => $subscriptionExpiresAt];
    }

    /**
     * چک آنلاین با سرور — پاکت را می‌گیرد، امضا را می‌سنجد و ذخیره می‌کند.
     *
     * @return array{ok: bool, reason: string, message: string, expires_at?: ?string}|null
     *         null یعنی سرور در دسترس نبود ⇒ مسیر مهلت (grace)
     */
    private function runOnlineCheck(InstalledModule $module, bool $manifestMissing): ?array
    {
        /* ضد-استورم: بعد از استقرار، همهٔ ماژول‌ها در یک بوت می‌خواهند چک کنند؛
         * هر ماژول حداکثر هر ۹۰ ثانیه یک تلاش شبکه — تلاش‌های بین‌آن‌ها بدون
         * تماس شبکه وارد مسیر مهلت (grace) می‌شوند. */
        $attemptKey = 'packages.guard.check_attempt.' . Str::kebab($module->slug);

        try {
            if (Cache::has($attemptKey)) {
                return null;
            }

            Cache::put($attemptKey, 1, now()->addSeconds(90));
        } catch (Throwable) {
        }

        try {
            $response = $this->api->verifyLicense($module->slug, $module->license_key);

            $envelope  = $response['envelope'] ?? null;
            $signature = $response['envelope_signature'] ?? null;

            // سرور بدون پاکت امضاشده (نسخهٔ قدیمی بازار) — حکم قابل‌اتکا نیست؛ مثل قطع سرور (مهلت)
            if (!is_array($envelope) || !is_string($signature) || $signature === '') {
                Log::warning('ModuleLicenseGuard: پاسخ verifyLicense بدون پاکت امضاشده — سرور بازار باید به‌روزرسانی شود.', ['slug' => $module->slug]);

                return null;
            }

            if (!$this->verifySignature($envelope, $signature)) {
                $this->logEvent($module, 'tamper_detected', ['source' => 'online_envelope_signature']);
                $this->clearGrace($module);

                return ['ok' => false, 'reason' => self::REASON_ENVELOPE_INVALID, 'message' => 'امضای پاسخ سرور معتبر نیست — پاسخ دستکاری‌شده است.'];
            }

            // پاکت معتبر — ذخیره در دیتابیس + فایل
            $this->storeEnvelope($module, $envelope, $signature);
            $this->storeLastCheck($module);

            // آینهٔ نمایشی تاریخ انقضا در دیتابیس (برای UI؛ قضاوت از پاکت)
            $mirror = [];
            if (!empty($envelope['expires_at'])) {
                $mirror['license_expires_at'] = $envelope['expires_at'];
            }

            // صدور خودکارِ اشتراک — کلیدِ صادرشدهٔ سرور روی ماژول provision می‌شود
            // تا قضاوت‌های بعدی از مسیر عادی لایسنس بروند (و حکم بماند)
            if (empty($module->license_key) && !empty($envelope['license_key'])) {
                $mirror['license_key'] = (string) $envelope['license_key'];
            }

            if (!empty($mirror)) {
                try {
                    $module->forceFill($mirror)->save();
                } catch (Throwable) {
                    // ستون‌های جدید ممکن است migrate نشده باشند — بحرانی نیست
                }
            }

            // حکم پاکت تازه
            $verdict = $this->judgeEnvelope($module, $envelope);

            if ($verdict['ok'] === false) {
                return [
                    'ok'      => false,
                    'reason'  => $verdict['reason'],
                    'message' => $verdict['message'],
                    'expires_at' => $verdict['expires_at'],
                ];
            }

            // فرصت provision مانیفست برای نصب‌های قدیمی
            if ($manifestMissing) {
                $this->provisionManifest($module);
            }

            $this->logEvent($module, 'verified_online', ['issued_at' => $envelope['issued_at'] ?? null]);

            return ['ok' => true, 'reason' => '', 'message' => null, 'expires_at' => $verdict['expires_at']];
        } catch (PackageApiRejectedException $e) {
            // سرور در دسترس بود و رد کرد — حکم قطعی
            $this->logEvent($module, 'server_rejected', ['status' => $e->status, 'message' => $e->getMessage()]);

            return [
                'ok'      => false,
                'reason'  => self::REASON_SERVER_REJECTED,
                'message' => 'سرور بازار این پکیج را تأیید نکرد: ' . $e->getMessage(),
                'expires_at' => null,
            ];
        } catch (PackageApiUnreachableException | RuntimeException $e) {
            // سرور در دسترس نبود (قطع شبکه / تایم‌اوت / 5xx) ⇒ مهلت
            Log::warning('ModuleLicenseGuard: API unreachable for ' . $module->slug . ': ' . $e->getMessage());

            return null;
        } catch (Throwable $e) {
            Log::warning('ModuleLicenseGuard: unexpected error for ' . $module->slug . ': ' . $e->getMessage());

            return null;
        }
    }

    /* ===================================================================
     *  مهلت (grace)
     * =================================================================== */

    /**
     * مدیریت دورهٔ مهلت هنگام قطع سرور — لنگر دیتابیس + فایل، قدیمی‌ترین ملاک.
     */
    private function handleGrace(InstalledModule $module, ?array $envelope): array
    {
        $graceStart = $this->graceAnchor($module);

        if ($graceStart === null) {
            $graceStart = now();
            $this->writeGraceAnchors($module, $graceStart);
            $this->logEvent($module, 'grace_started', ['until' => $graceStart->copy()->addHours($this->graceHours($envelope))->toDateTimeString()]);
        }

        $graceUntil = $graceStart->copy()->addHours($this->graceHours($envelope));

        if (now()->greaterThan($graceUntil)) {
            $this->markClosed($module, self::REASON_GRACE_EXPIRED, 'ارتباط با سرور بازار بیش از دورهٔ مهلت (' . $this->graceHours($envelope) . ' ساعت) برقرار نشد و پکیج بسته شد.');

            return $this->pack(false, self::STATE_CLOSED, self::REASON_GRACE_EXPIRED, 'ارتباط با سرور بازار بیش از دورهٔ مهلت (' . $this->graceHours($envelope) . ' ساعت) برقرار نشد و پکیج بسته شد. پس از برقراری ارتباط و تمدید، پکیج به‌صورت خودکار باز می‌شود.', $graceUntil->toDateTimeString(), $envelope['expires_at'] ?? null, $module);
        }

        $this->markState($module, self::STATE_GRACE, null);

        // تاریخ پایان مهلت شمسی با ارقام فارسی (نیاز محصول)
        return $this->pack(true, self::STATE_GRACE, null, 'ارتباط با سرور بازار موقتاً برقرار نیست؛ پکیج تا پایان دورهٔ مهلت (' . JalaliDate::format($graceUntil) . ') فعال است.', $graceUntil->toDateTimeString(), $envelope['expires_at'] ?? null, $module);
    }

    private function graceHours(?array $envelope): int
    {
        $hours = (int) ($envelope['grace_hours'] ?? 0);

        return $hours > 0 ? $hours : (int) config('packages.license.grace_hours', 24);
    }

    /**
     * خواندن لنگر مهلت — قدیمی‌ترینِ دیتابیس/فایل؛ اگر یکی غایب باشد از دیگری ترمیم می‌شود.
     */
    private function graceAnchor(InstalledModule $module): ?CarbonInterface
    {
        $candidates = [];

        try {
            if ($module->grace_started_at) {
                $candidates[] = Carbon::parse($module->grace_started_at->toDateTimeString());
            }
        } catch (Throwable) {
        }

        try {
            $file = $this->graceStatePath($module->slug);

            if (is_file($file)) {
                $json = json_decode((string) file_get_contents($file), true);

                if (is_array($json) && !empty($json['grace_started_at'])) {
                    $candidates[] = Carbon::parse((string) $json['grace_started_at']);
                }
            }
        } catch (Throwable) {
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $a->timestamp <=> $b->timestamp);

        return $candidates[0];
    }

    private function writeGraceAnchors(InstalledModule $module, CarbonInterface $when): void
    {
        try {
            $module->forceFill(['grace_started_at' => $when->toDateTimeString()])->save();
        } catch (Throwable) {
            // ستون‌ها migrate نشده‌اند — فایل لنگر می‌ماند
        }

        try {
            $path = $this->graceStatePath($module->slug);
            @mkdir(dirname($path), 0755, true);
            file_put_contents($path, json_encode([
                'grace_started_at' => $when->toDateTimeString(),
                'updated_at'       => now()->toDateTimeString(),
            ], JSON_UNESCAPED_UNICODE));
        } catch (Throwable) {
        }
    }

    private function clearGrace(InstalledModule $module): void
    {
        try {
            $module->forceFill(['grace_started_at' => null])->save();
        } catch (Throwable) {
        }

        try {
            $file = $this->graceStatePath($module->slug);

            if (is_file($file)) {
                @unlink($file);
            }
        } catch (Throwable) {
        }
    }

    private function graceStatePath(string $slug): string
    {
        return storage_path('app/packages/license-state/' . Str::kebab($slug) . '.json');
    }

    /* ===================================================================
     *  پاکت لایسنس — خواندن/ذخیره/راستی‌آزمایی امضا
     * =================================================================== */

    /**
     * پاکت معتبرِ ذخیره‌شده (دیتابیس یا فایل — امضادار بودن شرط است؛ تازه‌ترین انتخاب می‌شود).
     *
     * @return array<string,mixed>|null
     */
    private function readEnvelope(InstalledModule $module): ?array
    {
        $candidates = [];

        try {
            $raw = $module->license_envelope;

            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);

                if (is_array($decoded)) {
                    $candidates[] = $decoded;
                }
            }
        } catch (Throwable) {
        }

        try {
            $file = $this->envelopeStatePath($module->slug);

            if (is_file($file)) {
                $decoded = json_decode((string) file_get_contents($file), true);

                if (is_array($decoded)) {
                    $candidates[] = $decoded;
                }
            }
        } catch (Throwable) {
        }

        $valid = [];

        foreach ($candidates as $candidate) {
            $env  = $candidate['envelope'] ?? null;
            $sig  = $candidate['signature'] ?? null;

            if (is_array($env) && is_string($sig) && $this->verifySignature($env, $sig)) {
                $valid[] = $candidate;
            }
        }

        if ($valid === []) {
            return null;
        }

        // تازه‌ترین پاکتِ امضاشده
        usort($valid, function ($a, $b) {
            $ta = strtotime((string) ($a['envelope']['issued_at'] ?? '0')) ?: 0;
            $tb = strtotime((string) ($b['envelope']['issued_at'] ?? '0')) ?: 0;

            return $tb <=> $ta;
        });

        return $valid[0]['envelope'];
    }

    private function storeEnvelope(InstalledModule $module, array $envelope, string $signature): void
    {
        $payload = json_encode(['envelope' => $envelope, 'signature' => $signature], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $module->forceFill(['license_envelope' => $payload])->save();
        } catch (Throwable $e) {
            Log::warning('ModuleLicenseGuard: ذخیرهٔ پاکت در دیتابیس ناموفق — ' . $e->getMessage());
        }

        try {
            $path = $this->envelopeStatePath($module->slug);
            @mkdir(dirname($path), 0755, true);
            file_put_contents($path, $payload);
        } catch (Throwable $e) {
            Log::warning('ModuleLicenseGuard: ذخیرهٔ پاکت در فایل ناموفق — ' . $e->getMessage());
        }
    }

    private function envelopeStatePath(string $slug): string
    {
        return storage_path('app/packages/license-state/' . Str::kebab($slug) . '.envelope.json');
    }

    /**
     * راستی‌آزمایی امضای پاکت با کلید عمومی جاسازی‌شده (ECDSA-SHA256).
     * منطق در LicenseEnvelopeVerifier (کلاس مستقل) است — اینجا فقط delegate
     * تا سازگاری فراخوانی‌های قبلی حفظ شود؛ وابستگی‌ها یک‌طرفه‌اند و حلقهٔ DI وجود ندارد.
     *
     * @param  array<string,mixed>  $envelope
     */
    public function verifySignature(array $envelope, string $signatureBase64): bool
    {
        return $this->verifier->verifySignature($envelope, $signatureBase64);
    }

    /**
     * JSON کانونیکی — باید عیناً با سرور (PackageLicenseEnvelopeService) یکی باشد:
     * ksort بازگشتی + بدون escape.
     */
    public static function canonicalJson(array $payload): string
    {
        $sorted = $payload;
        self::deepSort($sorted);

        return json_encode($sorted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function deepSort(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                self::deepSort($value);
            }
        }
    }

    private function normalizeDomain(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $host = parse_url($url, PHP_URL_HOST) ?: preg_replace('#^https?://#', '', $url);
        $host = preg_replace('#^www\.#', '', (string) $host);

        return trim((string) $host, '/');
    }

    private function domainMatches(string $allowed, string $current): bool
    {
        return $allowed === $current || str_ends_with($current, '.' . $allowed);
    }

    private function runningInWeb(): bool
    {
        try {
            return ! app()->runningInConsole() && request()->getHost() !== null && request()->getHost() !== '';
        } catch (Throwable) {
            return false;
        }
    }

    /* ===================================================================
     *  وضعیت‌ها/رویدادها/کش
     * =================================================================== */

    private function markState(InstalledModule $module, string $state, ?string $reason): void
    {
        try {
            $module->forceFill([
                'license_state'  => $state,
                'license_reason' => $reason,
            ])->save();
        } catch (Throwable) {
            // ستون‌های migrate نشده — ساکت
        }
    }

    private function markClosed(InstalledModule $module, string $reason, string $message): void
    {
        $wasClosed = $module->license_state === self::STATE_CLOSED;

        $this->markState($module, self::STATE_CLOSED, $reason);

        if (!$wasClosed) {
            $this->logEvent($module, 'closed', ['reason' => $reason, 'message' => $message]);
            Log::warning('ModuleLicenseGuard: پکیج بسته شد — ' . $module->slug . ' (' . $reason . ')');
        }
    }

    private function logEvent(InstalledModule $module, string $event, array $details = []): void
    {
        try {
            \App\Models\ModuleLicenseEvent::create([
                'module_slug' => $module->slug,
                'event'       => $event,
                'details'     => $details,
            ]);
        } catch (Throwable) {
            // جدول هنوز migrate نشده — لاگ می‌کنیم
            Log::info('ModuleLicenseEvent: ' . $module->slug . ' ' . $event . ' ' . json_encode($details, JSON_UNESCAPED_UNICODE));
        }
    }

    private function storeLastCheck(InstalledModule $module): void
    {
        try {
            $module->forceFill(['last_api_check_at' => now()])->save();
        } catch (Throwable) {
        }

        Cache::put($this->lastCheckKey($module->slug), now()->timestamp, now()->addMinutes(self::LAST_CHECK_CACHE_MINUTES));
    }

    private function dueForCheck(InstalledModule $module, int $intervalHours): bool
    {
        // زمان حقیقی چک از دیتابیس خوانده می‌شود (کش حکم نمی‌دهد؛ فقط کمکی)
        try {
            if ($module->last_api_check_at) {
                return now()->diffInHours($module->last_api_check_at) >= $intervalHours;
            }
        } catch (Throwable) {
        }

        $cached = Cache::get($this->lastCheckKey($module->slug));

        if ($cached) {
            return now()->timestamp - (int) $cached >= $intervalHours * 3600;
        }

        return true;
    }

    private function lastCheckKey(string $slug): string
    {
        return 'packages.guard.last_check.' . Str::kebab($slug);
    }

    /**
     * آیا این ماژول قبلاً مانیفست provision شده؟ (ستون integrity_verified_at)
     * اگر بله و الان module.lock غایب است ⇒ حذف فایل ⇒ دستکاری.
     */
    private function hadManifest(InstalledModule $module): bool
    {
        try {
            return $module->integrity_verified_at !== null;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * provision مانیفست برای نصب‌های قدیمی (به‌ازای «نسخهٔ نصب‌شده») + ثبت مُهر.
     *
     * ضد-توفان: بعد از شکست، یک پنجرهٔ backoff ۱۵ دقیقه‌ای؛ در این پنجره هیچ
     * تماس شبکه‌ای و هیچ لاگی — بدون این گارد، هر ریکوئست وبِ فروشگاهی که
     * مانیفستش provision نشده (مثلاً نسخهٔ نصب‌شده روی بازار موجود نیست)
     * یک جفت خطا می‌ساخت و لاگ چندساعتی به هزاران رکورد می‌رسید.
     */
    private function provisionManifest(InstalledModule $module): void
    {
        $backoffKey = 'packages.guard.provision_backoff.' . Str::kebab($module->slug);

        try {
            if (Cache::has($backoffKey)) {
                return;
            }
        } catch (Throwable) {
        }

        try {
            $signed = $this->api->fetchManifest($module->slug, $module->version);

            if ($this->integrity->storeManifest($module->name, $signed)) {
                try {
                    $module->forceFill(['integrity_verified_at' => now()])->save();
                } catch (Throwable) {
                }

                try {
                    Cache::forget($backoffKey);
                } catch (Throwable) {
                }

                $this->logEvent($module, 'manifest_provisioned', ['version' => $module->version]);
            }
        } catch (Throwable $e) {
            // provision ناموفق (سرور در دسترس نیست / نسخه یافت نشد) — بحرانی نیست؛
            // بعد از پنجرهٔ backoff دوباره تلاش می‌شود. مانیفستِ غایب هرگز پکیج را
            // نمی‌بندد؛ فقط «حذفِ مانیفستِ provision‌شده» دستکاری است.
            try {
                Cache::put($backoffKey, 1, now()->addMinutes(self::PROVISION_BACKOFF_MINUTES));
            } catch (Throwable) {
            }

            Log::info('ModuleLicenseGuard: provision مانیفست ناموفق برای ' . $module->slug . ' — تلاش بعدی تا ' . self::PROVISION_BACKOFF_MINUTES . ' دقیقه — ' . $e->getMessage());
        }
    }

    /* ===================================================================
     *  کمکی
     * =================================================================== */

    private function resolveModule(string $slug): ?InstalledModule
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        // ۱) تطبیق دقیق slug
        $module = InstalledModule::where('slug', $slug)->first();

        if ($module) {
            return $module;
        }

        // ۲) تطبیق kebab-case با ستون name (CustomerClub ↔ customer-club)
        $kebab = Str::kebab($slug);

        $module = InstalledModule::where('slug', $kebab)
            ->orWhere('name', 'like', '%' . str_replace('-', '', ucwords($kebab, '-')) . '%')
            ->first();

        if ($module) {
            return $module;
        }

        // ۳) تطبیق معکوس: slug پاکت‌های بازار 'customer-club' با name 'CustomerClub'
        $camel = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug))));

        return InstalledModule::where('name', $camel)
            ->orWhere('slug', 'like', "%{$kebab}%")
            ->first();
    }

    private function isAllowedConsoleCommand(): bool
    {
        $argv = implode(' ', $_SERVER['argv'] ?? []);

        if ($argv === '' || $argv === 'artisan') {
            return true; // بدون کامند مشخص (بوت اپ) — اجازه
        }

        foreach (self::CLI_ALLOW_PATTERNS as $pattern) {
            if (preg_match($pattern, $argv)) {
                return true;
            }
        }

        return false;
    }

    private function pack(
        bool $ok,
        string $state,
        ?string $reason,
        ?string $message,
        ?string $graceUntil,
        ?string $expiresAt,
        ?InstalledModule $module = null,
        ?string $subscriptionExpiresAt = null
    ): array {
        return [
            'ok'                      => $ok,
            'state'                   => $state,
            'reason'                  => $reason,
            'message'                 => $message,
            'grace_until'             => $graceUntil,
            'expires_at'              => $expiresAt,
            'subscription_expires_at' => $subscriptionExpiresAt,
            'module'                  => $module,
        ];
    }

    /* ===================================================================
     *  بنر دورهٔ مهلت — در همهٔ صفحات پکیج‌ها نمایش داده می‌شود
     * =================================================================== */

    /**
     * HTML بنر RTL برای تزریق در پاسخ‌های دورهٔ مهلت (grace) — بدون وابستگی به
     * layout پکیج‌ها (middleware آن را قبل از </body> تزریق می‌کند).
     */
    public static function graceBannerHtml(string $message, ?string $graceUntil): string
    {
        // تاریخ پایان مهلت شمسی با ارقام فارسی (نیاز محصول)
        $until = $graceUntil !== null && $graceUntil !== '' ? ' — پایان مهلت: ' . JalaliDate::format($graceUntil) : '';
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $safeUntil   = htmlspecialchars($until, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!-- package-license-grace-banner -->
<div dir="rtl" style="position:fixed;bottom:0;left:0;right:0;z-index:2147483647;background:#b45309;color:#fff;padding:10px 18px;font-family:iranyekan,Tahoma,Vazirmatn,sans-serif;font-size:13px;text-align:center;box-shadow:0 -2px 10px rgba(0,0,0,.25);">
    <strong>[!] هشدار لایسنس:</strong> {$safeMessage}{$safeUntil}
</div>
HTML;
    }
}
