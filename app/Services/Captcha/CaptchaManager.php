<?php

namespace App\Services\Captcha;

use App\Services\CaptchaService;
use App\Services\SecuritySettings;
use Illuminate\Http\Request;

/*
 * مدیر مرکزی کپچا
 * ==================================================================
 * تا قبل از این فاز فقط یک کپچا وجود داشت (تصویری حرف‌عددی CaptchaService).
 * حالا هر نقطهٔ کپچا (ورود مدیر، ثبت‌نام، ورود مشتری، ورود فروشنده، دیدگاه‌ها)
 * می‌تواند یکی از چهار درایور را داشته باشد:
 *
 *   image     — تصویری حرف‌عددی (پیش‌فرض؛ عینِ رفتار قبل)
 *   math      — ریاضی پیشرفتهٔ چندعملیاتی (MathCaptchaService)
 *   hcaptcha  — hCaptcha ابری (HCaptchaService)
 *   turnstile — Cloudflare Turnstile ابری (TurnstileService)
 *
 * «هر نقطه فقط یک درایور» — انتخاب در تب «امنیت» تنظیمات برنامه‌نویس.
 * کلیدها هم همان‌جا ثبت می‌شوند (options table).
 *
 * سازگاری: پیش‌فرضِ همهٔ نقاط «image» است — یعنی هیچ رفتار فعلی‌ای با
 * این فاز تغییر نمی‌کند مگر مدیر خودش درایور نقطه‌ای را عوض کند.
 */
class CaptchaManager
{
    public const DRIVERS = ['image', 'math', 'hcaptcha', 'turnstile'];

    public const DRIVER_LABELS = [
        'image'     => 'تصویری حرف‌عددی',
        'math'      => 'ریاضی پیشرفته',
        'hcaptcha'  => 'hCaptcha',
        'turnstile' => 'Cloudflare Turnstile',
    ];

    /** درایورهای ابری — به کلید site/secret نیاز دارند */
    public const CLOUD_DRIVERS = ['hcaptcha', 'turnstile'];

    /**
     * درایور فعال نقطه — اگر کپچای آن نقطه خاموش باشد null
     * (برای ویوها: null یعنی هیچ ویجتی رندر نشود).
     */
    public static function activeDriver(string $point): ?string
    {
        return SecuritySettings::captchaEnabled($point) ? self::driver($point) : null;
    }

    /**
     * درایور انتخابی نقطه — پیش‌فرض «image» .
     */
    public static function driver(string $point): string
    {
        $driver = (string) option('security_captcha_driver_'.$point, 'image');

        return in_array($driver, self::DRIVERS, true) ? $driver : 'image';
    }

    /**
     * نام فیلد فرم متناظر با درایور.
     *  image → captcha | math → captcha_answer | ابری → captcha_token
     */
    public static function fieldFor(string $driver): string
    {
        return match ($driver) {
            'math'                  => 'captcha_answer',
            'hcaptcha', 'turnstile' => 'captcha_token',
            default                 => 'captcha',
        };
    }

    /**
     * نام «پراپرتی» متناظر با درایور برای کامپوننت‌های Livewire.
     * ==================================================================
     * Livewire ۳ در validate() کلید قاعده را دقیقاً با نام پراپرتی عمومی
     * مقایسه می‌کند (بدون تبدیل snake_case به camelCase) — در نتیجه کلید
     * قاعدهٔ «captcha_token» با پراپرتی «captchaToken» EXCEPTION
     * «No property found for validation» می‌دهد. فرم‌های HTTP معمولی
     * fieldFor را می‌خواهند، کامپوننت‌های Livewire propertyFor را.
     */
    public static function propertyFor(string $driver): string
    {
        return match ($driver) {
            'math'                  => 'captchaAnswer',
            'hcaptcha', 'turnstile' => 'captchaToken',
            default                 => 'captcha',
        };
    }

    /**
     * خواندن مقدار درایور از درخواست. برای درایورهای ابری نام اصلی
     * سرویس (h-captcha-response / cf-turnstile-response) هم پذیرفته
     * می‌شود تا اگر فرمی مستقیم از hidden input خودِ ویجت می‌فرستد هم کار کند.
     */
    public static function requestValue(string $driver, ?Request $request = null): string
    {
        $request ??= request();

        if ($driver === 'hcaptcha') {
            return (string) ($request->input('captcha_token')
                ?? $request->input('h-captcha-response')
                ?? '');
        }

        if ($driver === 'turnstile') {
            return (string) ($request->input('captcha_token')
                ?? $request->input('cf-turnstile-response')
                ?? '');
        }

        return (string) $request->input(self::fieldFor($driver));
    }

    /**
     * راستی‌آزمایی نقطه بر اساس درایورِ آن نقطه. نقطهٔ خاموش همیشه
     * «موفق» برمی‌گردد (تماس گیرنده لازم نیست خودش گیت بزند).
     */
    public static function verify(string $point, ?string $value): bool
    {
        if (! SecuritySettings::captchaEnabled($point)) {
            return true;
        }

        return self::verifyDriver(self::driver($point), $value);
    }

    /**
     * راستی‌آزمایی مستقیم با یک درایور — برای نقاطی که گیتِ نقطه را
     * خودشان جداگانه مدیریت می‌کنند.
     */
    public static function verifyDriver(string $driver, ?string $value): bool
    {
        $value = trim((string) $value);

        return match ($driver) {
            'math'      => app(MathCaptchaService::class)->verify($value),
            'hcaptcha'  => app(HCaptchaService::class)->verify($value),
            'turnstile' => app(TurnstileService::class)->verify($value),
            default     => app(CaptchaService::class)->verify($value),
        };
    }

    /** پیام خطای مناسب هر درایور (برای پاسخ‌های JSON و سشن‌های خطا) */
    public static function failureMessage(string $driver): string
    {
        return match ($driver) {
            'math'      => 'حاصل عبارت ریاضی اشتباه است یا منقضی شده؛ عبارت جدید را حل کنید.',
            'hcaptcha'  => 'تأیید hCaptcha ناموفق بود؛ لطفاً دوباره تلاش کنید.',
            'turnstile' => 'تأیید Cloudflare Turnstile ناموفق بود؛ لطفاً دوباره تلاش کنید.',
            default     => 'کد امنیتی وارد شده اشتباه یا منقضی شده است.',
        };
    }

    /** پیام وایلیدیشنِ «required» فیلد درایور */
    public static function requiredMessage(string $driver): string
    {
        return match ($driver) {
            'math'                  => 'حاصل عبارت ریاضی را وارد کنید.',
            'hcaptcha', 'turnstile' => 'لطفاً ابتدا چالش ضدربات را کامل کنید.',
            default                 => 'کد امنیتی را وارد کنید.',
        };
    }
}
