{{--
    کپچای چنددرایوره — کامپوننت مشترک فرم‌های عمومی
    ======================================================================
    props:
      point  — نام نقطهٔ کپچا (customer_login | register | comments | ...).
               اگر داده شود، درایور از تنظیمات تب «امنیت» خوانده می‌شود و
               خروجی بر اساس همان درایور رندر می‌شود (نقطهٔ خاموش = هیچ).
      driver — اجبارِ درایور (اختیاری؛ فقط با point معنا دارد)
      name   — نام فیلد برای درایور تصویریِ بدون نقطه (پیش‌فرض captcha —
               رفتار قدیمی فرم تماس و ... حفظ می‌شود)

    طراحی (بازطراحی مدرن):
      • سه جزء هم‌ارتفاع: فیلد پاسخ + تصویر چالش + دکمهٔ گردش تازه.
      • همهٔ استایل‌ها زیر [data-captcha] با importantهای هدفمند — تا CSSِ
        فرم‌های تم‌ها (مثل ‎#main .account-box .content-account form input‎
        با float/radius خودش) چیدمان ویجت را نشکند.
      • کلاس‌های cap-image__img / cap-input__field / data-captcha قرارداد
        JS سراسری (spCaptchaRefreshAll) هستند — تغییر ندهید.
--}}
@props(['point' => null, 'driver' => null])

@php
    /* فاز ۱۸۵ — رفع «Undefined variable $point»:
       وقتی کامپوننت بدون ‎point فراخوانی می‌شود (فرم تماس، بازیابی رمز و …)
       متغیر تعریف‌نشده بود و در PHP 8 خطای ErrorException می‌داد.
       با @props هر دو پراپ با مقدار پیش‌فرض همیشه تعریف‌شده‌اند؛
       رفتار قبلی (بدون point = کپچای تصویری ساده) دقیقاً حفظ شده است. */

    $name = $attributes->get('name', 'captcha');

    $driverAware = filled($point);
    $captchaDriver = $driverAware
        ? ($driver ?? \App\Services\Captcha\CaptchaManager::activeDriver($point))
        : 'image';

    $isMath = $driverAware && $captchaDriver === 'math';
    $inputName = $isMath ? 'captcha_answer' : $name;
    $imageRoute = $isMath ? 'captcha.math' : 'captcha.image';

    // گارد امن — در هر زمینه‌ای که ViewErrorBag شیر نشده (مثل رندر خام کامپوننت)
    // نباید به خاطر نبود ‎$errors فاتال بدهد
    $__capErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
@endphp

@if (! $driverAware || filled($captchaDriver))
    @if ($driverAware && ($captchaDriver === 'hcaptcha' || $captchaDriver === 'turnstile'))
        {{-- ویجت ابری — hCaptcha / Cloudflare Turnstile --}}
        <x-captcha-widget :point="$point" :driver="$captchaDriver" />
    @else
        <div class="cap-field" data-captcha>
            <label class="cap-label">{{ $isMath ? 'حاصل عبارت ریاضی' : 'کد امنیتی' }} <span class="cap-req">*</span></label>

            <div class="cap-row {{ $__capErrors->has($inputName) ? 'cap-row--error' : '' }}">
                <div class="cap-input">
                    <i class="mdi {{ $isMath ? 'mdi-calculator-variant-outline' : 'mdi-shield-key-outline' }}" aria-hidden="true"></i>
                    <input type="text"
                           name="{{ $inputName }}"
                           class="cap-input__field {{ $isMath ? 'cap-input__field--math' : '' }}"
                           placeholder="{{ $isMath ? '؟' : '• • • • •' }}"
                           autocomplete="off"
                           autocapitalize="off"
                           spellcheck="false"
                           maxlength="{{ $isMath ? 7 : 5 }}"
                           inputmode="{{ $isMath ? 'numeric' : 'text' }}"
                           aria-label="{{ $isMath ? 'پاسخ عبارت ریاضی' : 'کد امنیتی' }}"
                           dir="ltr">
                </div>

                <div class="cap-image {{ $isMath ? 'cap-image--math' : '' }}">
                    <img src="{{ route($imageRoute) }}" alt="{{ $isMath ? 'عبارت ریاضی' : 'کد امنیتی' }}" class="cap-image__img">
                    <span class="cap-image__loading"><i class="mdi mdi-loading"></i></span>
                </div>

                {{-- دکمهٔ تولید کد جدید — خارج از تصویر است تا روی کدها نیفتد (فاز ۲۰۱) --}}
                <button type="button" class="cap-refresh" title="تولید کد جدید" aria-label="تولید کد جدید">
                    <i class="mdi mdi-refresh" aria-hidden="true"></i>
                </button>
            </div>

            @if($__capErrors->has($inputName))
                <p class="cap-error"><i class="mdi mdi-alert-circle-outline"></i> {{ $__capErrors->first($inputName) }}</p>
            @endif
        </div>
    @endif
@endif

@once
    @push('styles')
        <style>
            /* ============ بازطراحی مدرن — مقاوم به CSS تم‌ها ============ */
            .cap-field[data-captcha]{
                display: flex !important;
                flex-direction: column !important;
                width: 100% !important;
                margin: 0 0 20px !important;
            }
            .cap-field[data-captcha] .cap-label {
                display: block !important;
                float: none !important;
                width: 100% !important;
                font-size: 12.5px !important;
                font-weight: 700 !important;
                color: #475569 !important;
                margin: 0 0 8px !important;
                padding: 0 !important;
                text-align: start !important;
                line-height: 1.6;
            }
            .cap-field[data-captcha] .cap-req { color: #ef4444 !important; }

            .cap-field[data-captcha] .cap-row {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 10px !important;
                align-items: stretch !important;
                float: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* ---- فیلد پاسخ ---- */
            .cap-field[data-captcha] .cap-input {
                position: relative !important;
                flex: 1 1 150px !important;
                min-width: 140px !important;
                float: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .cap-field[data-captcha] .cap-input > i {
                position: absolute !important;
                inset-inline-start: 15px !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
                color: #94a3b8 !important;
                font-size: 19px !important;
                pointer-events: none !important;
                transition: color .2s ease !important;
            }
            .cap-field[data-captcha] .cap-input__field {
                float: none !important;
                width: 100% !important;
                height: 52px !important;
                margin: 0 !important;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 14px !important;
                background: #fff !important;
                padding-inline: 42px !important;
                font-size: 17px !important;
                font-weight: 800 !important;
                letter-spacing: 4px !important;
                text-transform: uppercase !important;
                text-align: center !important;
                color: #1e293b !important;
                outline: none !important;
                direction: ltr !important;
                box-shadow: 0 1px 2px rgba(15, 23, 42, .05) !important;
                transition: border-color .2s ease, box-shadow .2s ease !important;
                font-family: inherit !important;
                line-height: normal !important;
            }
            /* حالت ریاضی — بدون حروف بزرگ/فاصلهٔ حروف؛ پاسخ فقط عدد است */
            .cap-field[data-captcha] .cap-input__field--math {
                letter-spacing: 2px !important;
                text-transform: none !important;
                font-size: 20px !important;
            }
            .cap-field[data-captcha] .cap-input__field::placeholder {
                text-align: center !important;
                text-indent: 0 !important;
                color: #cbd5e1 !important;
                letter-spacing: 4px;
                opacity: 1 !important;
            }
            .cap-field[data-captcha] .cap-input__field:focus,
            .cap-field[data-captcha] .cap-row:focus-within .cap-input__field {
                border-color: #7c3aed !important;
                box-shadow: 0 0 0 4px rgba(124, 58, 237, .13) !important;
            }
            .cap-field[data-captcha] .cap-row:focus-within .cap-input > i { color: #7c3aed !important; }

            /* ---- تصویر چالش ----
               فاز ۲۰۱ — عرض تصویر از ۱۵۰ به ۱۹۰px (ریاضی ۲۱۵px) بزرگ شد تا
               آخرین کاراکتر کد هم خوانا بماند؛ دکمهٔ گردش تازه از روی تصویر
               بیرون آمده و به‌صورت المان مستقل کنار تصویر قرار گرفته است. */
            .cap-field[data-captcha] .cap-image {
                position: relative !important;
                flex: 0 0 auto !important;
                width: 190px !important;
                height: 52px !important;
                border-radius: 14px !important;
                overflow: hidden !important;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
                border: 1.5px solid #1e293b !important;
                box-shadow: inset 0 2px 10px rgba(0, 0, 0, .4), 0 1px 2px rgba(15, 23, 42, .08) !important;
                float: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            /* حالت ریاضی — تصویر پهن‌تر تا کل عبارت خوانا بماند */
            .cap-field[data-captcha] .cap-image--math { width: 215px !important; }
            .cap-field[data-captcha] .cap-image__img {
                display: block !important;
                width: 100% !important;
                height: 100% !important;
                object-fit: contain !important;
                float: none !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
            }

            /* ---- دکمهٔ تولید جدید — المان مستقلِ کنار تصویر (فاز ۲۰۱)
               دیگر روی تصویر قرار نمی‌گیرد تا هیچ کاراکتری از کد پوشیده نشود ---- */
            .cap-field[data-captcha] .cap-refresh {
                position: relative !important;
                flex: 0 0 auto !important;
                width: 52px !important;
                height: 52px !important;
                border: 1.5px solid #e2e8f0 !important;
                border-radius: 14px !important;
                background: #fff !important;
                color: #475569 !important;
                cursor: pointer !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 !important;
                padding: 0 !important;
                transition: background .2s ease, color .2s ease, border-color .2s ease, transform .25s ease !important;
            }
            .cap-field[data-captcha] .cap-refresh i { font-size: 20px !important; line-height: 1 !important; }
            .cap-field[data-captcha] .cap-refresh:hover {
                background: #f8fafc !important;
                color: #7c3aed !important;
                border-color: #7c3aed !important;
                transform: rotate(90deg) !important;
            }
            .cap-field[data-captcha] .cap-refresh:focus-visible {
                outline: 2px solid #7c3aed !important;
                outline-offset: 1px !important;
            }

            /* ---- لودینگ ---- */
            .cap-field[data-captcha] .cap-image__loading {
                position: absolute !important;
                inset: 0 !important;
                display: grid !important;
                place-items: center !important;
                background: rgba(15, 23, 42, .78) !important;
                color: #fff !important;
                font-size: 20px !important;
                opacity: 0 !important;
                visibility: hidden !important;
                transition: .2s ease !important;
                margin: 0 !important;
                float: none !important;
            }
            .cap-field[data-captcha] .cap-image__loading.is-visible { opacity: 1 !important; visibility: visible !important; }
            @keyframes cap-spin { to { transform: rotate(360deg); } }
            .cap-field[data-captcha] .cap-image__loading i { animation: cap-spin 1s linear infinite !important; }

            /* ---- خطا ---- */
            .cap-field[data-captcha] .cap-row--error .cap-input__field {
                border-color: #ef4444 !important;
                box-shadow: 0 0 0 4px rgba(239, 68, 68, .1) !important;
            }
            .cap-field[data-captcha] .cap-error {
                display: flex !important;
                align-items: center !important;
                gap: 5px !important;
                color: #ef4444 !important;
                font-size: 12px !important;
                font-weight: 600;
                margin: 8px 2px 0 !important;
                padding: 0 !important;
                float: none !important;
            }

            /* ---- موبایل: تصویر زیر فیلد، تمام‌عرض ---- */
            @media (max-width: 480px) {
                .cap-field[data-captcha] .cap-input { flex: 1 1 100% !important; min-width: 100% !important; }
                .cap-field[data-captcha] .cap-image,
                .cap-field[data-captcha] .cap-image--math { flex: 1 1 auto !important; width: auto !important; }
                .cap-field[data-captcha] .cap-refresh { width: 48px !important; }
            }

            /* احترام به کاهش انیمیشن */
            @media (prefers-reduced-motion: reduce) {
                .cap-field[data-captcha] .cap-refresh,
                .cap-field[data-captcha] .cap-input__field,
                .cap-field[data-captcha] .cap-image__loading,
                .cap-field[data-captcha] .cap-image__loading i { transition: none !important; animation: none !important; }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.querySelectorAll('[data-captcha]').forEach(function (field) {
                var img      = field.querySelector('.cap-image__img');
                var btn      = field.querySelector('.cap-refresh');
                var loading  = field.querySelector('.cap-image__loading');
                var input    = field.querySelector('.cap-input__field');
                var refreshed = false;

                function refresh() {
                    refreshed = true;
                    loading.classList.add('is-visible');
                    // cache-bust با پارامتر تصادفی
                    img.src = img.src.split('?')[0] + '?_=' + Date.now() + Math.random().toString(36).slice(2, 6);
                }

                img.addEventListener('load', function () {
                    loading.classList.remove('is-visible');
                    if (refreshed) {           // فقط بعد از رفرش دستی/خودکار
                        input.value = '';
                        input.focus();
                    }
                });
                img.addEventListener('error', function () {
                    loading.classList.remove('is-visible');
                });

                btn.addEventListener('click', refresh);

                // اگر فرم قبلاً خطا داده (کد مصرف/اشتباه شده)، خودکار کد تازه بیاور
                if (field.querySelector('.cap-row--error')) {
                    window.addEventListener('load', refresh);
                }
            });

            // سراسری — بعد از خطای AJAX فرم‌ها همهٔ ویجت‌های کپچا تازه شوند
            // (تصویری/ریاضی: تصویر جدید + خالی کردن فیلد | ابری: spCaptchaReset)
            window.spCaptchaRefreshAll = window.spCaptchaRefreshAll || function () {
                document.querySelectorAll('[data-captcha]').forEach(function (field) {
                    var img   = field.querySelector('.cap-image__img');
                    var input = field.querySelector('.cap-input__field');
                    if (img && img.src) { img.src = img.src.split('?')[0] + '?_=' + Date.now(); }
                    if (input) { input.value = ''; }
                });
                if (window.spCaptchaReset) { window.spCaptchaReset(); }
            };
        </script>
    @endpush
@endonce
