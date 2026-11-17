<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * سرویس یکپارچگی فایل‌های ماژول — سخت‌سازی امنیتی
 * ==================================================================
 *
 * «همهٔ فایل‌های پکیج به هم وابسته‌اند»: مانیفست امضاشدهٔ سرور (module.lock)
 * حاوی SHA-256 تک‌تک فایل‌های پکیج است — شامل PHP، Blade، CSS، JS و config.
 * حذف یا تغییر حتی یک فایل (حتی یک view) ⇒ امضای مانیفست معتبر می‌ماند ولی
 * هش‌ها نخوانند ⇒ پکیج بسته می‌شود. خودِ مانیفست هم امضاشده است؛ ساخت/دستکاری
 * آن روی هاست مشتری بدون کلید خصوصی بازار ناممکن است.
 *
 * عملکرد: راستی‌آزمایی کامل در cache miss (حداکثر هر ۶۰ دقیقه یک‌بار در هر ریکوئست
 * واقعی). کلید کش شامل snapshot (تعداد فایل + حجم کل) است تا حذف فایل، کش را
 * بی‌اثر کند.
 *
 * این سرویس دیگر ModuleLicenseGuard را تزریق نمی‌کند — قبلاً تزریق دوطرفه
 * (گارد ⇄ یکپارچگی) حلقهٔ DI و بازگشت بی‌نهایت می‌ساخت؛ امضا حالا از طریق
 * LicenseEnvelopeVerifier (کلاس مستقل بدون وابستگی) راستی‌آزمایی می‌شود.
 */
class ModuleIntegrityService
{
    private const CACHE_TTL_MINUTES = 60;

    public const REASON_MANIFEST_MISSING  = 'manifest_missing';
    public const REASON_MANIFEST_INVALID  = 'manifest_invalid';
    public const REASON_MANIFEST_MISMATCH = 'manifest_mismatch';

    /** فایل هست ولی امضایش با کلیدهای عمومی فروشگاه تأیید نمی‌شود (معمولاً: کلید سرور عوض شده) */
    public const REASON_MANIFEST_SIGNATURE_INVALID = 'manifest_signature_invalid';

    public function __construct(private LicenseEnvelopeVerifier $verifier) {}

    /**
     * مسیر مانیفست یک ماژول نصب‌شده.
     */
    public function manifestPath(string $moduleName): string
    {
        return base_path('Modules/' . $moduleName . '/module.lock');
    }

    /**
     * ذخیرهٔ مانیفست امضاشدهٔ دریافتی از سرور (GET /packages/{slug}/manifest).
     *
     * @param  array{manifest?: array<string,mixed>, signature?: string}  $signed
     */
    public function storeManifest(string $moduleName, array $signed): bool
    {
        $manifest  = $signed['manifest'] ?? null;
        $signature = $signed['signature'] ?? null;

        if (!is_array($manifest) || !is_string($signature) || $signature === '') {
            return false;
        }

        if (empty($manifest['files']) || !is_array($manifest['files'])) {
            return false;
        }

        try {
            $path = $this->manifestPath($moduleName);

            @mkdir(dirname($path), 0755, true);

            file_put_contents($path, json_encode([
                'manifest'  => $manifest,
                'signature' => $signature,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            $this->forgetCache($moduleName);

            return true;
        } catch (Throwable $e) {
            Log::warning('ModuleIntegrity: ذخیرهٔ مانیفست ناموفق — ' . $e->getMessage());

            return false;
        }
    }

    /**
     * خواندن مانیفست ذخیره‌شده (فقط اگر امضایش معتبر باشد).
     *
     * @return array{manifest: array<string,mixed>, signature: string}|null
     */
    public function readManifest(string $moduleName): ?array
    {
        $state = $this->readManifestState($moduleName);

        return $state['state'] === 'ok' ? $state['signed'] : null;
    }

    /**
     * حالت دقیق مانیفست — «missing» (فایل نیست یا ساختار خراب) را از
     * «invalid_signature» (فایل هست ولی امضایش با کلیدهای مورد اعتماد تأیید
     * نمی‌شود) جدا می‌کند؛ این دو در گارد مسیرهای متفاوتی دارند:
     *   • missing + سابقهٔ provision  ⇒ حذف فایل = دستکاری (بستن فوری)
     *   • invalid_signature          ⇒ معمولاً یعنی کلید سرور عوض شده؛ provision
     *     تازه از خود سرور (منبع حقیقت) انجام می‌شود و در صورت شکست، پیامِ
     *     راهنما برای به‌روزرسانی PACKAGE_LICENSE_PUBLIC_KEY نمایش داده می‌شود.
     *
     * @return array{state: 'missing'|'invalid_signature'|'ok', signed: ?array{manifest: array<string,mixed>, signature: string}}
     */
    public function readManifestState(string $moduleName): array
    {
        try {
            $path = $this->manifestPath($moduleName);

            if (! is_file($path)) {
                return ['state' => 'missing', 'signed' => null];
            }

            $decoded = json_decode((string) file_get_contents($path), true);

            if (! is_array($decoded) || ! isset($decoded['manifest'], $decoded['signature'])) {
                return ['state' => 'missing', 'signed' => null];
            }

            if (! $this->verifier->verifySignature($decoded['manifest'], (string) $decoded['signature'])) {
                Log::warning('ModuleIntegrity: امضای مانیفست با هیچ‌یک از کلیدهای عمومی مورد اعتماد تأیید نشد — اگر کلید امضای سرور تعویض شده، کلید عمومی جدید را در پنل فروشگاه (تنظیمات برنامه‌نویس ← تب آپدیت‌کننده) یا در env فروشگاه (PACKAGE_LICENSE_PUBLIC_KEY) به‌روز کنید. اثرانگشت کلیدهای مورد اعتماد فعلی را با اثرانگشت «کلید امضای لایسنس» در پنل آپدیت‌کننده مقایسه کنید.', [
                    'module'                => $moduleName,
                    'trusted_keys'          => $this->verifier->trustedKeyCount(),
                    'trusted_fingerprints'  => $this->verifier->trustedFingerprints(),
                ]);

                return ['state' => 'invalid_signature', 'signed' => $decoded];
            }

            return ['state' => 'ok', 'signed' => $decoded];
        } catch (Throwable) {
            return ['state' => 'missing', 'signed' => null];
        }
    }

    /**
     * راستی‌آزمایی یکپارچگی فایل‌های ماژول.
     *
     * @return array{ok: bool, reason: ?string, message: ?string, file_count: int, mismatches: array<int,string>}
     */
    public function verify(string $moduleName): array
    {
        $moduleName = trim($moduleName);

        if ($moduleName === '') {
            return ['ok' => false, 'reason' => self::REASON_MANIFEST_INVALID, 'message' => 'نام ماژول نامشخص است.', 'file_count' => 0, 'mismatches' => []];
        }

        $basePath = base_path('Modules/' . $moduleName);

        if (!is_dir($basePath)) {
            return ['ok' => false, 'reason' => self::REASON_MANIFEST_MISMATCH, 'message' => 'پوشهٔ ماژول یافت نشد.', 'file_count' => 0, 'mismatches' => []];
        }

        $state = $this->readManifestState($moduleName);

        if ($state['state'] === 'missing') {
            return ['ok' => false, 'reason' => self::REASON_MANIFEST_MISSING, 'message' => 'مانیفست یکپارچگی یافت نشد یا امضای آن معتبر نیست.', 'file_count' => 0, 'mismatches' => []];
        }

        if ($state['state'] === 'invalid_signature') {
            $prints = $this->verifier->trustedFingerprints();

            return [
                'ok'         => false,
                'reason'     => self::REASON_MANIFEST_SIGNATURE_INVALID,
                'message'    => 'امضای مانیفست یکپارچگی معتبر نیست — کلید عمومی فروشگاه با کلید خصوصی سرور هم‌خوان نیست (کلید سرور تعویض شده است).'
                    . ($prints !== [] ? ' اثرانگشت کلیدهای مورد اعتماد فعلی فروشگاه: ['.implode(' | ', $prints).'] — اگر با اثرانگشت «کلید امضای لایسنس» در پنل آپدیت‌کننده یکی نیست، کلید عمومی جدید را در تنظیمات برنامه‌نویس (تب آپدیت‌کننده) ذخیره کنید.' : ''),
                'file_count' => 0,
                'mismatches' => [],
            ];
        }

        $signed   = $state['signed'];
        $manifest = $signed['manifest'];
        $files    = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];

        if ($files === []) {
            return ['ok' => false, 'reason' => self::REASON_MANIFEST_INVALID, 'message' => 'مانیفست خالی است.', 'file_count' => 0, 'mismatches' => []];
        }

        // snapshot ارزان برای ابطال کش: تعداد + حجم کل فایل‌ها
        $snapshot = $this->snapshot($basePath, array_keys($files));

        $cacheKey = 'packages.integrity.' . md5($moduleName . '|' . md5(json_encode($signed)) . '|' . md5((string) json_encode($snapshot)));

        $cached = Cache::get($cacheKey);

        if (is_array($cached) && isset($cached['ok'])) {
            return $cached;
        }

        // راستی‌آزمایی کامل هش‌ها
        $mismatches = [];

        foreach ($files as $relativePath => $expectedHash) {
            $absolute = $basePath . '/' . ltrim((string) $relativePath, '/');

            if (!is_file($absolute)) {
                $mismatches[] = 'حذف‌شده: ' . $relativePath;

                continue;
            }

            $actual = @hash_file('sha256', $absolute);

            if ($actual === false || strtolower($actual) !== strtolower((string) $expectedHash)) {
                $mismatches[] = 'تغییریافته: ' . $relativePath;
            }
        }

        $result = [
            'ok'         => $mismatches === [],
            'reason'     => $mismatches === [] ? null : self::REASON_MANIFEST_MISMATCH,
            'message'    => $mismatches === [] ? null : 'فایل‌های پکیج تغییر کرده یا حذف شده‌اند: ' . implode('؛ ', array_slice($mismatches, 0, 5)) . (count($mismatches) > 5 ? ' و ' . (count($mismatches) - 5) . ' مورد دیگر' : ''),
            'file_count' => count($files),
            'mismatches' => array_slice($mismatches, 0, 20),
        ];

        if ($result['ok']) {
            Cache::put($cacheKey, $result, now()->addMinutes(self::CACHE_TTL_MINUTES));
        } else {
            Log::warning('ModuleIntegrity: نقض یکپارچگی در ' . $moduleName, ['mismatches' => $result['mismatches']]);
        }

        return $result;
    }

    public function forgetCache(string $moduleName): void
    {
        // کلید کش شامل snapshot است؛ تغییر فایل‌ها خودبه‌خود کلید را عوض می‌کند.
        // این متد فقط برای فراخوانی‌های صریح (نصب/آپدیت) است.
        try {
            $manifest = $this->readManifest($moduleName);

            if ($manifest === null) {
                return;
            }

            $basePath = base_path('Modules/' . $moduleName);
            $snapshot = $this->snapshot($basePath, array_keys($manifest['manifest']['files'] ?? []));

            Cache::forget('packages.integrity.' . md5($moduleName . '|' . md5(json_encode($manifest)) . '|' . md5((string) json_encode($snapshot))));
        } catch (Throwable) {
        }
    }

    /**
     * snapshot ارزان: شمارش و حجم کل فایل‌های داخل مانیفست.
     *
     * @param  array<int, string>  $relativePaths
     * @return array{count: int, size: int}
     */
    private function snapshot(string $basePath, array $relativePaths): array
    {
        $count = 0;
        $size  = 0;

        foreach ($relativePaths as $relativePath) {
            $absolute = $basePath . '/' . ltrim((string) $relativePath, '/');

            if (is_file($absolute)) {
                $count++;
                $size += (int) (@filesize($absolute) ?: 0);
            }
        }

        return ['count' => $count, 'size' => $size];
    }

    /**
     * آیا ماژولِ نصب‌شده مانیفست دارد؟ (برای provision در نصب‌های قدیمی)
     */
    public function hasManifest(string $moduleName): bool
    {
        return is_file($this->manifestPath($moduleName));
    }
}
