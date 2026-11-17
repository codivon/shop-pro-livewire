<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * راستی‌آزمایی امضای پاکت لایسنس — کلاس مستقل بدون وابستگی
 * ==================================================================
 *
 * چرا این کلاس جدا شد؟
 *   قبلاً verifySignature داخل ModuleLicenseGuard بود و ModuleIntegrityService
 *   برای امضای مانیفست، ModuleLicenseGuard را در سازنده تزریق می‌کرد؛ در حالی که
 *   ModuleLicenseGuard هم ModuleIntegrityService را تزریق می‌کرد ⇒ حلقهٔ وابستگی
 *   دوطرفه در Container ⇒ «Infinite recursion» روی هر درخواست (خطای
 *   Maximum call stack size) و سرریز لاگ چندمگابایتی روی هاست اشتراکی.
 *
 *   حالا زنجیره یک‌طرفه است:
 *     ModuleLicenseGuard      → PackageApiService + ModuleIntegrityService + LicenseEnvelopeVerifier
 *     ModuleIntegrityService  → LicenseEnvelopeVerifier  (بدون حلقه)
 *
 * منطق امضا دست‌نخورده مانده: ECDSA-SHA256 روی JSON کانونیکی (ksort بازگشتی +
 * بدون escape) — دقیقاً قرارداد سرور بازار.
 *
 * چرخش کلید :
 *   امضا با «هر یک» از کلیدهای عمومی مورد اعتماد تأیید می‌شود؛ ترتیب امتحان:
 *     ۱) تنظیمات پنل — option('packages_license_public_key')
 * (فیلد «کلید عمومی راستی‌آزمایی لایسنس» در تنظیمات برنامه‌نویس؛
 *        برای هاست اشتراکی که ویرایش .env در آن دردسر دارد. نوشتن در دیتابیس است
 *        و نیازی به پاک‌کردن کش پیکربندی ندارد و بلافاصله اعمال می‌شود.)
 *     ۲) config('packages.license.public_key')  ← env PACKAGE_LICENSE_PUBLIC_KEY
 *     ۳) config('packages.license.public_key_previous') ← env ..._PREVIOUS
 *     ۴) کلید عمومی جاسازی‌شده در همین کلاس (آخرین پناه — همیشه معتبر)
 *   به این ترتیب تعویض کلید در سرور آپدیت‌کننده، فروشگاه را نمی‌شکند: اول کلید
 *   عمومیِ جدید در پنل فروشگاه یا .env ثبت می‌شود، بعد کلید خصوصی سرور عوض می‌شود.
 */
class LicenseEnvelopeVerifier
{
    /** کلید عمومی EC P-256 جفت با کلید خصوصی سرور بازار */
    public const PUBLIC_KEY_B64 = 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEKQhNcMFLj5OvD+igbMqb1v/Gyl9Rmgzloc62fGS1SuLWoAnRJE/eL1lMUSHVFooAB5n/egVes853OhVqJFkSvg==';

    /**
     * راستی‌آزمایی امضای پاکت/مانیفست با کلیدهای عمومی مورد اعتماد (ECDSA-SHA256).
     * امضا معتبر است اگر «حداقل یکی» از کلیدهای نامزد امضا را تأیید کند.
     *
     * @param  array<string,mixed>  $envelope
     */
    public function verifySignature(array $envelope, string $signatureBase64): bool
    {
        if (! function_exists('openssl_verify')) {
            Log::error('LicenseEnvelopeVerifier: افزونهٔ openssl در دسترس نیست — راستی‌آزمایی لایسنس ناممکن است.');

            return false;
        }

        $signature = base64_decode($signatureBase64, true);

        if ($signature === false) {
            return false;
        }

        $candidates = $this->candidatePublicKeys();

        if ($candidates === []) {
            Log::error('LicenseEnvelopeVerifier: هیچ کلید عمومی قابل‌استفاده‌ای یافت نشد.');

            return false;
        }

        $canonical = self::canonicalJson($envelope);

        foreach ($candidates as $pem) {
            $key = openssl_pkey_get_public($pem);

            if ($key === false) {
                continue;
            }

            if (openssl_verify($canonical, $signature, $key, OPENSSL_ALGO_SHA256) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * تعداد کلیدهای عمومی مورد اعتماد فعلی — برای لاگ تشخیصی.
     */
    public function trustedKeyCount(): int
    {
        return count($this->candidatePublicKeys());
    }

    /**
     * اثرانگشت تک‌تک کلیدهای عمومی مورد اعتماد فعلی — برای لاگ و پیام خطا؛
     * مدیر می‌تواند مستقیم با اثرانگشت «کلید امضای لایسنس» در پنل آپدیت‌کننده
     * مقایسهٔ چشمی کند و بفهمد کدام سمت کلیدش کهنه است.
     *
     * @return array<int, string>
     */
    public function trustedFingerprints(): array
    {
        $prints = [];

        foreach ($this->candidatePublicKeys() as $pem) {
            $print = self::fingerprintPem($pem);

            if ($print !== '' && ! in_array($print, $prints, true)) {
                $prints[] = $print;
            }
        }

        return $prints;
    }

    /**
     * کلید عمومی ثبت‌شده در تنظیمات پنل (دیتابیس) .
     * خواندن از option() در برابر پیش‌نصب/خطای دیتابیس ایمن شده (خالی = تنظیم نشده).
     */
    public function settingsPublicKey(): string
    {
        try {
            return trim((string) option('packages_license_public_key', ''));
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * آیا مقدار خامِ داده‌شده یک کلید عمومی قابل‌استفاده است؟ (اعتبارسنجی فیلد پنل)
     * هر سه فرمت پذیرفته است: خطِ تک‌خطی .env با دنبالهٔ \n، PEM چندخطی، Base64 خام.
     */
    public function isValidPublicKey(string $raw): bool
    {
        if (! function_exists('openssl_pkey_get_public')) {
            return false;
        }

        $pem = $this->normalizePublicKey(trim($raw));

        return $pem !== '' && openssl_pkey_get_public($pem) !== false;
    }

    /**
     * اثرانگشت SHA-256 کلید عمومی (۱۶ رقم اول، چهارچارگاهی) — برای نمایش در پنل؛
     * با الگوریتم اثرانگشتِ پنل آپدیت‌کننده (LicenseSigningKeyService) یکی است تا
     * مدیر بتواند دو طرف را با مقایسهٔ چشمی تطبیق دهد.
     */
    public static function fingerprintPem(string $pem): string
    {
        if (preg_match('/-----BEGIN [A-Z ]+-----\s*(.*?)\s*-----END [A-Z ]+-----/s', $pem, $m)) {
            $der = base64_decode((string) preg_replace('/\s+/', '', $m[1]), true);

            if ($der !== false && $der !== '') {
                return implode(':', str_split(substr(hash('sha256', $der), 0, 16), 4));
            }
        }

        return '';
    }

    /**
     * وضعیت تک‌تک منابع کلید عمومی — برای نمایش در تنظیمات برنامه‌نویس.
     *
     * @return array<int, array{source:string, set:bool, valid:bool, fingerprint:?string}>
     */
    public function candidateSources(): array
    {
        $rows = [];

        $sources = [
            'settings'     => ['تنظیمات پنل (دیتابیس)', $this->settingsPublicKey()],
            'env'          => ['.env فروشگاه (جدید)', (string) config('packages.license.public_key', '')],
            'env_previous' => ['.env فروشگاه (قبلی — دورهٔ گذار)', (string) config('packages.license.public_key_previous', '')],
            'embedded'     => ['کلید جاسازی‌شده در کد', self::PUBLIC_KEY_B64],
        ];

        foreach ($sources as $key => [$label, $raw]) {
            $raw = trim($raw);
            $pem = $raw === '' ? '' : $this->normalizePublicKey($raw);
            $valid = $pem !== '' && function_exists('openssl_pkey_get_public') && openssl_pkey_get_public($pem) !== false;

            $rows[] = [
                'source'      => $key,
                'label'       => $label,
                'set'         => $raw !== '',
                'valid'       => $valid,
                'fingerprint' => $valid ? self::fingerprintPem($pem) : null,
            ];
        }

        return $rows;
    }

    /**
     * کلیدهای عمومی نامزد (PEM) — بدون تکرار، به ترتیب اولویت:
     * تنظیمات پنل (دیتابیس) ← env جدید ← env قبلی (دورهٔ گذار) ← کلید جاسازی‌شده.
     *
     * @return array<int, string>
     */
    public function candidatePublicKeys(): array
    {
        $candidates = [];

        $rawKeys = [
            $this->settingsPublicKey(),
            (string) config('packages.license.public_key', ''),
            (string) config('packages.license.public_key_previous', ''),
            self::PUBLIC_KEY_B64,
        ];

        foreach ($rawKeys as $raw) {
            $pem = $this->normalizePublicKey($raw);

            if ($pem !== '' && ! in_array($pem, $candidates, true)) {
                $candidates[] = $pem;
            }
        }

        return $candidates;
    }

    /**
     * نرمال‌سازی مقدار کلید عمومی به PEM استاندارد؛ سه فرمت پذیرفته می‌شود:
     *   ۱) PEM چندخطی (یا تک‌خطی با دنبالهٔ literal \n — فرمت .env)
     *   ۲) Base64 خامِ DER (بدون هدر — همان فرمت PUBLIC_KEY_B64)
     *   ۳) رشتهٔ خالی → خروجی خالی
     */
    private function normalizePublicKey(string $body): string
    {
        $body = trim($body);

        if ($body === '') {
            return '';
        }

        if (str_contains($body, 'BEGIN')) {
            // دنباله‌های literal \n (فرمت تک‌خطی .env) به newline واقعی تبدیل می‌شود
            return trim(str_replace(['\\r\\n', '\\n', '\\r'], ["\r\n", "\n", "\n"], $body));
        }

        $body = str_replace(["-----BEGIN PUBLIC KEY-----\n", "-----END PUBLIC KEY-----", "\n"], '', $body);

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split($body, 64, "\n") . "-----END PUBLIC KEY-----\n";
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
}
