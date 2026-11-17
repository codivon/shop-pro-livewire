<?php

namespace Tests\Feature\Security;

use App\Livewire\Admin\Auth\Login;
use App\Services\Captcha\CaptchaManager;
use App\Services\Captcha\MathCaptchaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/*
 * کپچاهای چندگانه — درایور به تفکیک نقطه
 * ==================================================================
 * هر نقطه فقط «یک» درایور: image | math | hcaptcha | turnstile
 * این تست‌ها سه قرارداد کلیدی را قفل می‌کنند:
 *  ۱) Livewire ۳ کلید قاعدهٔ کپچا باید «نام پراپرتی» باشد
 *     (رگرسیون ارور «No property found for validation: [captcha_token]»)
 *  ۲) درایور math برای نقاط ورود — گیت واقعی با پاسخ درست عبور می‌شود
 *  ۳) درایورهای ابری fail-closed هستند (کلید ناقص ⇒ رد قطعی)
 */
class CaptchaDriversTest extends TestCase
{
    use RefreshDatabase;

    private function enableCaptcha(): void
    {
        option_update('security_captcha_enabled', 'on');
    }

    private function mathHash(string $answer): string
    {
        return hash('sha256', config('app.key').'|'.$answer);
    }

    /* =====================================================================
     *  CaptchaManager — نگاشت درایور → فیلد/پراپرتی
     * ===================================================================== */

    #[Test]
    public function captcha_manager_reads_configured_driver_per_point(): void
    {
        $this->assertSame('image', CaptchaManager::driver('admin_login'));

        option_update('security_captcha_driver_admin_login', 'math');
        $this->assertSame('math', CaptchaManager::driver('admin_login'));
        $this->assertSame('captcha_answer', CaptchaManager::fieldFor('math'));
        $this->assertSame('captchaAnswer', CaptchaManager::propertyFor('math'));

        option_update('security_captcha_driver_admin_login', 'turnstile');
        $this->assertSame('captcha_token', CaptchaManager::fieldFor('turnstile'));
        $this->assertSame('captchaToken', CaptchaManager::propertyFor('turnstile'));

        option_update('security_captcha_driver_admin_login', 'hcaptcha');
        $this->assertSame('captcha_token', CaptchaManager::fieldFor('hcaptcha'));

        // درایور نامعتبر ⇒ برمی‌گردد به تصویری (پیش‌فرض امن)
        option_update('security_captcha_driver_admin_login', 'reCaptcha');
        $this->assertSame('image', CaptchaManager::driver('admin_login'));
    }

    /* =====================================================================
     *  ورود مدیر (Livewire) — کلید قاعده = نام پراپرتی (camelCase)
     * ===================================================================== */

    #[Test]
    public function admin_login_math_driver_validates_captcha_answer_property(): void
    {
        $this->enableCaptcha();
        option_update('security_captcha_driver_admin_login', 'math');

        Livewire::test(Login::class)
            ->set('username', 'someone')
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertHasErrors(['captchaAnswer' => 'required']);
    }

    #[Test]
    public function admin_login_turnstile_driver_validates_captcha_token_property(): void
    {
        // رگرسیون «No property found for validation: [captcha_token]» —
        // اگر کلید قاعده snake_case باشد Livewire ۳ EXCEPTION می‌دهد
        $this->enableCaptcha();
        option_update('security_captcha_driver_admin_login', 'turnstile');

        Livewire::test(Login::class)
            ->set('username', 'someone')
            ->set('password', 'secret1234')
            ->call('authenticate')
            ->assertHasErrors(['captchaToken' => 'required']);
    }

    /* =====================================================================
     *  کپچای ریاضی — نرمال‌سازی ارقام فارسی + یک‌بارمصرف + انقضا
     * ===================================================================== */

    #[Test]
    public function math_captcha_verifies_persian_digits_and_is_single_use(): void
    {
        $this->withSession([
            MathCaptchaService::SESSION_KEY => [
                'hash'     => $this->mathHash('12345'),
                'expires'  => now()->addSeconds(300)->getTimestamp(),
                'attempts' => 0,
            ],
        ]);

        $service = app(MathCaptchaService::class);

        $this->assertTrue($service->verify('۱۲۳۴۵'));  // ارقام فارسی نرمال می‌شود
        $this->assertFalse($service->verify('12345')); // یک‌بارمصرف — سشن flush شد
    }

    #[Test]
    public function math_captcha_rejects_expired_challenge(): void
    {
        $this->withSession([
            MathCaptchaService::SESSION_KEY => [
                'hash'     => $this->mathHash('999'),
                'expires'  => now()->subSeconds(1)->getTimestamp(),
                'attempts' => 0,
            ],
        ]);

        $this->assertFalse(app(MathCaptchaService::class)->verify('999'));
    }

    /* =====================================================================
     *  ورود مشتری (فرانت — POST /login) — درایور math
     * ===================================================================== */

    #[Test]
    public function customer_login_math_driver_requires_captcha_answer(): void
    {
        $this->enableCaptcha();
        // نقطهٔ customer_login پیش‌فرضش «off» است — صریح روشن شود
        option_update('security_captcha_customer_login', 'on');
        option_update('security_captcha_driver_customer_login', 'math');

        // بدون پاسخ — خطای فیلدِ درایور (captcha_answer)
        $response = $this->post('/login', [
            'username'    => '09121234567',
            'rendered_at' => (string) now()->subSeconds(10)->getTimestamp(),
        ]);

        $response->assertSessionHasErrors(['captcha_answer']);
    }

    #[Test]
    public function customer_login_math_driver_passes_gate_with_correct_answer(): void
    {
        $this->enableCaptcha();
        // نقطهٔ customer_login پیش‌فرضش «off» است — صریح روشن شود
        option_update('security_captcha_customer_login', 'on');
        option_update('security_captcha_driver_customer_login', 'math');

        // پاسخ درست — از گیت کپچا عبور می‌کند (کاربر ناموجود ⇒ جریان ثبت‌نام موبایل)
        $response = $this->withSession([
            MathCaptchaService::SESSION_KEY => [
                'hash'     => $this->mathHash('12345'),
                'expires'  => now()->addSeconds(300)->getTimestamp(),
                'attempts' => 0,
            ],
        ])->post('/login', [
            'username'       => '09121234567',
            'captcha_answer' => '12345',
            'rendered_at'    => (string) now()->subSeconds(10)->getTimestamp(),
        ]);

        $response->assertRedirect(); // قدم بعدی جریان ورود/ثبت‌نام
    }

    /* =====================================================================
     *  ورود فروشنده (POST /sellers/login) — درایور math
     * ===================================================================== */

    #[Test]
    public function seller_login_math_driver_requires_captcha_answer(): void
    {
        // کنترلر فروشنده با چندفروشندگی خاموش ۴۰۴ می‌دهد — سیستم صریحاً روشن شود
        option_update('multi_vendor_system_status', 'true');
        $this->enableCaptcha();
        // نقطهٔ seller_login صریحاً روشن شود (پیش‌فرضش از سوییچ قدیمی چندفروشندگی مشتق می‌شود)
        option_update('security_captcha_seller_login', 'on');
        option_update('security_captcha_driver_seller_login', 'math');

        // postJson — عین ارسال AJAX واقعی فرم فروشنده (X-Requested-With)
        $response = $this->postJson(route('seller.login_check'), [
            'username' => '09121234567',
            'password' => 'secret1234',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['captcha_answer']);
    }

    /* =====================================================================
     *  درایورهای ابری — fail-closed + siteverify موفق
     * ===================================================================== */

    #[Test]
    public function turnstile_fails_closed_without_secret(): void
    {
        option_update('captcha_turnstile_site_key', '');
        option_update('captcha_turnstile_secret', '');

        $this->assertFalse(app(\App\Services\Captcha\TurnstileService::class)->verify('any-token'));
    }

    #[Test]
    public function turnstile_verifies_with_cloudflare_success(): void
    {
        option_update('captcha_turnstile_site_key', 'site-key');
        option_update('captcha_turnstile_secret', 'secret-key');

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $this->assertTrue(app(\App\Services\Captcha\TurnstileService::class)->verify('valid-token'));

        Http::assertSent(function ($request) {
            return $request['secret'] === 'secret-key'
                && $request['response'] === 'valid-token';
        });
    }

    #[Test]
    public function hcaptcha_fails_closed_when_cloudflare_style_request_fails(): void
    {
        option_update('captcha_hcaptcha_site_key', 'site-key');
        option_update('captcha_hcaptcha_secret', 'secret-key');

        Http::fake([
            'api.hcaptcha.com/*' => Http::response(['success' => false], 200),
        ]);

        $this->assertFalse(app(\App\Services\Captcha\HCaptchaService::class)->verify('bad-token'));
    }
}
