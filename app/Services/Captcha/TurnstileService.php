<?php

namespace App\Services\Captcha;

use App\Services\SecuritySettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * درایور Cloudflare Turnstile
 * ==================================================================
 * ویجت سمت کلاینت (challenges.cloudflare.com/turnstile) + راستی‌آزمایی
 * سمت سرور با siteverify. کلیدها از تب «امنیت» تنظیمات برنامه‌نویس
 * خوانده می‌شوند.
 *
 * قرارداد امنیتی: fail-closed — کلید ناقص، توکن خالی، خطای شبکه یا
 * پاسخ غیرقابل‌اعتماد همگی = رد.
 */
class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /** کلیدها ست شده باشند؟ (بدون آن‌ها ویجت رندر نمی‌شود و verify همیشه رد می‌کند) */
    public function configured(): bool
    {
        return SecuritySettings::turnstileSiteKey() !== ''
            && SecuritySettings::turnstileSecret() !== '';
    }

    public function siteKey(): string
    {
        return SecuritySettings::turnstileSiteKey();
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
                    'secret'   => SecuritySettings::turnstileSecret(),
                    'response' => $token,
                    'remoteip' => (string) (request()?->ip() ?? ''),
                ]);

            if (! $response->successful()) {
                Log::warning('Turnstile siteverify HTTP error', ['status' => $response->status()]);

                return false;
            }

            return (bool) ($response->json('success') ?? false);
        } catch (Throwable $e) {
            Log::warning('Turnstile siteverify failed', ['error' => $e->getMessage()]);

            return false; // قطع شبکه با سرور Cloudflare = رد (fail-closed)
        }
    }
}
