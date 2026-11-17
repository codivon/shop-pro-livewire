<?php

namespace App\Services;

use App\Support\MultiVendorSettings;

/*
 * لایهٔ مرکزی تنظیمات امنیتی
 *
 * همهٔ کلیدها در جدول options نگهداری می‌شوند و از تب «امنیت» در
 * تنظیمات برنامه‌نویس (/admin/{prefix}/developer) مدیریت می‌شوند.
 * مقدار پیش‌فرض هر کلید دقیقاً معادل رفتار پیش‌فرض است تا
 * با فعال/غیرفعال کردن هیچ‌چیزِ موجود خراب نشود.
 */
class SecuritySettings
{
    /* نقاط کپچا */
    /*
     * نقطهٔ seller_login اضافه شد. پیش‌فرضِ آن از
     * سوییچ قدیمی تب چندفروشندگی (seller_login_captcha) مشتق می‌شود تا
     * پنل‌های فعلی هیچ تغییری نکنند؛ بعد از اولین ذخیرهٔ تب امنیت،
     * انتخاب صریح مدیر ملاک است.
     */
    public const CAPTCHA_POINTS = ['admin_login', 'register', 'customer_login', 'seller_login', 'comments'];

    /* نقاط قفل تلاش‌ها */
    public const LOCKOUT_POINTS = ['admin', 'customer', 'comments'];

    /* کانال‌های 2FA */
    public const TWO_FACTOR_CHANNELS = ['email', 'sms', 'both'];

    /* ================= کپچا ================= */

    /** کلید اصلی کپچا — پیش‌فرض: از config/security.php (رفتار قبلی) */
    public static function captchaMasterEnabled(): bool
    {
        return option(
            'security_captcha_enabled',
            config('security.login_captcha', true) ? 'on' : 'off'
        ) === 'on';
    }

    /** آیا کپچا برای این نقطه فعال است؟ (کلید اصلی × کلید نقطه) */
    public static function captchaEnabled(string $point): bool
    {
        if (! in_array($point, self::CAPTCHA_POINTS, true) || ! self::captchaMasterEnabled()) {
            return false;
        }

        $defaults = [
            'admin_login'   => 'on',  // قبلاً هم روی ورود مدیر فعال بود
            'register'      => 'on',  // قبلاً هم روی ثبت‌نام فعال بود
            'customer_login' => 'off', // نقطهٔ جدید
            'seller_login'  => MultiVendorSettings::loginCaptchaEnabled() ? 'on' : 'off', // پیش‌فرض از سوییچ چندفروشندگی
            'comments'      => 'off', // نقطهٔ جدید
        ];

        return option('security_captcha_'.$point, $defaults[$point] ?? 'off') === 'on';
    }

    /* ================= درایورهای کپچا ================= */

    /**
     * درایور انتخابی نقطه — پیش‌فرض «image» یعنی رفتار تصویری کلاسیک.
     * هر نقطه فقط یک درایور دارد (تصویری / ریاضی / hCaptcha / Turnstile).
     */
    public static function captchaDriver(string $point): string
    {
        return \App\Services\Captcha\CaptchaManager::driver($point);
    }

    /* کلیدهای hCaptcha */

    public static function hcaptchaSiteKey(): string
    {
        return trim((string) option('captcha_hcaptcha_site_key'));
    }

    public static function hcaptchaSecret(): string
    {
        return trim((string) option('captcha_hcaptcha_secret'));
    }

    /* کلیدهای Cloudflare Turnstile */

    public static function turnstileSiteKey(): string
    {
        return trim((string) option('captcha_turnstile_site_key'));
    }

    public static function turnstileSecret(): string
    {
        return trim((string) option('captcha_turnstile_secret'));
    }

    /** تعداد عملوند عبارت ریاضی (۲ تا ۴) — هرچه بیشتر، پیچیده‌تر */
    public static function mathOperands(): int
    {
        return min(4, max(2, (int) option('captcha_math_operands', '3')));
    }

    /** تلهٔ زمان: حداقل ثانیهٔ پرکردن فرم — ۰ یعنی غیرفعال */
    public static function timeTrapSeconds(): int
    {
        return max(0, (int) option('security_time_trap_seconds', (string) BotDetector::MIN_FORM_SECONDS));
    }

    /* ================= هانی‌پات ================= */

    public static function honeypotEnabled(): bool
    {
        return option('security_honeypot_enabled', 'on') === 'on';
    }

    /* ================= قفل تلاش‌ها ================= */

    public static function lockoutEnabled(): bool
    {
        return option('security_lockout_enabled', 'on') === 'on';
    }

    /** سقف تلاش برای نقطهٔ admin|customer|comments */
    public static function lockoutMax(string $point): int
    {
        $defaults = ['admin' => LoginThrottle::MAX_ATTEMPTS, 'customer' => 5, 'comments' => 5];

        return max(1, (int) option('security_lockout_'.$point.'_max', (string) ($defaults[$point] ?? 5)));
    }

    /** پنجرهٔ دقیقه‌ای شمارش تلاش‌ها */
    public static function lockoutMinutes(string $point): int
    {
        $defaults = ['admin' => 15, 'customer' => 1, 'comments' => 10];

        return max(1, (int) option('security_lockout_'.$point.'_minutes', (string) ($defaults[$point] ?? 10)));
    }

    /** پنجره به ثانیه */
    public static function lockoutWindowSeconds(string $point): int
    {
        return self::lockoutMinutes($point) * 60;
    }

    /* ================= هدرهای امنیتی ================= */

    public static function headersEnabled(): bool
    {
        return option('security_headers_enabled', 'on') === 'on';
    }

    public static function hstsEnabled(): bool
    {
        return option('security_hsts_enabled', 'off') === 'on';
    }

    /* ================= تلهٔ ربات ================= */

    /**
     * کلید اصلی تلهٔ ربات — از تب «امنیت» تنظیمات برنامه‌نویس.
     * خاموش ⇒ هیچ آی‌پی‌ای مسدود نمی‌شود، مسدودی‌های موجود هم بی‌اثر می‌شوند
     * و تله‌گذاری جدید (مسیرهای طعمه + هانی‌پات) ثبت نمی‌شود.
     * (مشکل گزارش‌شده: حذف دستی رکورد bot_traps تا ۱۰ دقیقه اثر نداشت چون لیست در کش است؛
     * این کلید راه‌قطعِ سراسری برای کنترل کامل این قابلیت است.)
     */
    public static function botTrapEnabled(): bool
    {
        return option('security_bot_trap_enabled', 'on') === 'on';
    }

    /* ================= ورود دومرحله‌ای مدیر (2FA) ================= */

    public static function twoFactorEnabled(): bool
    {
        return option('admin_2fa_enabled', 'off') === 'on';
    }

    /** email | sms | both */
    public static function twoFactorChannel(): string
    {
        $channel = (string) option('admin_2fa_channel', 'email');

        return in_array($channel, self::TWO_FACTOR_CHANNELS, true) ? $channel : 'email';
    }

    /** شمارهٔ مقصد پیامک 2FA — از تنظیمات پیامک پنل (admin_mobile_number) */
    public static function twoFactorMobile(): ?string
    {
        $mobile = trim((string) option('admin_mobile_number'));

        return $mobile !== '' ? $mobile : null;
    }

    /** ایمیل مقصد کد (همیشه موجود است؛ در نبود ایمیل، 2FA ایمیلی غیرفعال می‌شود) */
    public static function twoFactorEmailTargets(\App\Models\Admin $admin): ?string
    {
        return filled($admin->email) ? (string) $admin->email : null;
    }
}
