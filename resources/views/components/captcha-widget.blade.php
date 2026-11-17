{{--
    ویجت کپچای ابری — hCaptcha / Cloudflare Turnstile
    ==================================================================
    props:
      point  — نام نقطه (admin_login | seller_login | ...)؛ برای data-captcha-token
      driver — 'hcaptcha' | 'turnstile' (پیش‌فرض: درایورِ نقطه)

    مکانیزم توکن:
      • JS کالبک سراسری توکن را در hidden input با data-captcha-token="{{ point }}"
        می‌گذارد (فرم‌های fetch/FormData مثل ورود فروشنده همان را می‌خوانند).
      • رویداد سفارشی «sp-captcha-token» هم dispatch می‌شود؛ ویوهای Livewire
        به این رویداد گوش می‌دهند و پراپرتی captchaToken کامپوننت را set می‌کنند.
--}}
@props(['point' => 'admin_login', 'driver' => null])

@php
    $driver = $driver ?? \App\Services\Captcha\CaptchaManager::driver($point);
    $isHcaptcha = $driver === 'hcaptcha';
    $siteKey = $isHcaptcha
        ? \App\Services\SecuritySettings::hcaptchaSiteKey()
        : \App\Services\SecuritySettings::turnstileSiteKey();
@endphp

@if ($isHcaptcha || $driver === 'turnstile')
    <div data-captcha-cloud data-captcha-driver="{{ $driver }}" dir="ltr">
        <div class="{{ $isHcaptcha ? 'h-captcha' : 'cf-turnstile' }}"
             data-sitekey="{{ $siteKey }}"
             data-callback="spCaptchaTokenCallback"
             data-expired-callback="spCaptchaTokenExpired"
             data-error-callback="spCaptchaTokenExpired"
             data-theme="auto"
             data-tabindex="0"></div>

        <input type="hidden" name="captcha_token" value="" data-captcha-token="{{ $point }}">

        <script src="{{ $isHcaptcha ? 'https://js.hcaptcha.com/1/api.js' : 'https://challenges.cloudflare.com/turnstile/v0/api.js' }}" async defer></script>
    </div>
@endif

@once
    @push('scripts')
        <script>
            // توکن ویجت ابری → همهٔ hidden inputها + رویداد سفارشی برای Livewire
            window.spCaptchaTokenCallback = function (token) {
                var inputs = document.querySelectorAll('[data-captcha-token]');
                for (var i = 0; i < inputs.length; i++) { inputs[i].value = token; }
                document.dispatchEvent(new CustomEvent('sp-captcha-token', { detail: token || '' }));
            };
            window.spCaptchaTokenExpired = function () {
                var inputs = document.querySelectorAll('[data-captcha-token]');
                for (var i = 0; i < inputs.length; i++) { inputs[i].value = ''; }
                document.dispatchEvent(new CustomEvent('sp-captcha-token', { detail: '' }));
            };
            // ریست کامل ویجت‌ها — بعد از هر تلاش ناموفق (مثل captcha_refresh سرور)
            window.spCaptchaReset = function () {
                try { if (window.hcaptcha) { hcaptcha.reset(); } } catch (e) {}
                try { if (window.turnstile) { turnstile.reset(); } } catch (e) {}
                window.spCaptchaTokenExpired();
            };
            // سراسری — رفرش همهٔ ویجت‌های کپچا (تصویری/ریاضی + ابری) بعد از خطای AJAX
            window.spCaptchaRefreshAll = window.spCaptchaRefreshAll || function () {
                document.querySelectorAll('[data-captcha]').forEach(function (field) {
                    var img   = field.querySelector('.cap-image__img');
                    var input = field.querySelector('.cap-input__field');
                    if (img && img.src) { img.src = img.src.split('?')[0] + '?_=' + Date.now(); }
                    if (input) { input.value = ''; }
                });
                window.spCaptchaReset();
            };
        </script>
    @endpush
@endonce
