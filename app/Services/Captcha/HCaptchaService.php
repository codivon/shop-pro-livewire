<?php

namespace App\Services\Captcha;

use App\Services\SecuritySettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * درایور hCaptcha
 * ==================================================================
 * ویجت سمت کلاینت (js.hcaptcha.com) + راستی‌آزمایی سمت سرور با
 * siteverify. کلیدها از تب «امنیت» تنظیمات برنامه‌نویس خوانده می‌شوند.
 *
 * قرارداد امنیتی: fail-closed — کلید ناقص، توکن خالی، خطای شبکه یا
 * پاسخ غیرقابل‌اعتماد همگی = رد.
 */
class HCaptchaService
{
    private const VERIFY_URL = 'https://api.hcaptcha.com/siteverify';

    /** کلیدها ست شده باشند؟ (بدون آن‌ها ویجت رندر نمی‌شود و verify همیشه رد می‌کند) */
    public function configured(): bool
    {
        return SecuritySettings::hcaptchaSiteKey() !== ''
            && SecuritySettings::hcaptchaSecret() !== '';
    }

    public function siteKey(): string
    {
        return SecuritySettings::hcaptchaSiteKey();
    }

    public function verify(?string $token): bool
    {
        $token = trim((string) $token);

        if ($token === '' || ! $this->configured()) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->connectTimeout(5)
                ->post(self::VERIFY_URL, [
                    'secret'   => SecuritySettings::hcaptchaSecret(),
                    'response' => $token,
                    'remoteip' => (string) (request()?->ip() ?? ''),
                ]);

            if (! $response->successful()) {
                Log::warning('hCaptcha siteverify HTTP error', ['status' => $response->status()]);

                return false;
            }

            return (bool) ($response->json('success') ?? false);
        } catch (Throwable $e) {
            Log::warning('hCaptcha siteverify failed', ['error' => $e->getMessage()]);

            return false; // قطع شبکه با سرور hCaptcha = رد (fail-closed)
        }
    }
}
