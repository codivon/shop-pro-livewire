{{-- هلپر fetch پنل فروشنده: ارسال فرم به اندپوینت‌های JSON قدیمی
     (همان کنترلرهای منتقل‌شدهٔ App\Http\Controllers\Seller) با هندل خطا/ری‌دایرکت یکسان --}}
<script>
    window.spPost = async function (url, formData) {
        formData = formData || new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        var res = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        var ct = res.headers.get('content-type') || '';
        var data = ct.includes('json') ? await res.json() : { status: res.ok ? 'success' : 'error', message: 'پاسخ نامعتبر از سرور' };

        // اعتبارسنجی Laravel (بازگشت 422 با errors)
        if (res.status === 422 && data.errors) {
            var first = Object.values(data.errors)[0];
            return { status: 'error', message: Array.isArray(first) ? first[0] : String(first), errors: data.errors };
        }

        return data;
    };

    {{-- رویداد روی document dispatch می‌شود (شنوندهٔ ‎.document آلپاین
         رویدادهای dispatch‌شده روی window را نمی‌شنود — علت واقعی دیده‌نشدن پیام‌ها) --}}
    window.spToast = function (message, type) {
        document.dispatchEvent(new CustomEvent('sp-auth-toast', { detail: { message: message, type: type || 'error' } }));
    };

    {{-- بررسی سمت کلاینتِ فیلدهای required با پیام فارسی یکدست.
         فرم‌ها novalidate هستند (حبابهٔ انگلیسی مرورگر نداریم)؛ خالی‌ها با کلیدِ
         name فیلد در errors (همان آبجکت آلپاین) می‌نشینند و زیر فیلد رندر می‌شوند.
         customMessages: map اختیاری name → پیام فارسی (مثل تیک‌باکس‌های تأیید). --}}
    window.spRequire = function (form, errors, customMessages) {
        customMessages = customMessages || {};
        var missing = false;

        form.querySelectorAll('[required]').forEach(function (el) {
            var filled = el.type === 'checkbox' ? el.checked : String(el.value || '').trim() !== '';

            if (! filled) {
                var name = el.name || el.id || '_';
                if (! errors[name] || ! errors[name].length) {
                    errors[name] = [customMessages[name] || 'این فیلد الزامی است'];
                }
                missing = true;
            }
        });

        return missing;
    };

    {{-- تبدیل ارقام فارسی/عربی به انگلیسی + اعتبارسنجی کد ملی ایرانی
         (الگوریتم رسمی کنترل رقم) سمت کلاینت — قبل از ارسال به سرور --}}
    window.spEnDigits = function (value) {
        var map = { '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
                    '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4', '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9' };
        return String(value || '').replace(/[۰-۹٠-٩]/g, function (d) { return map[d]; });
    };

    window.spValidNationalCode = function (code) {
        code = spEnDigits(code).replace(/\D/g, '');
        if (!/^\d{10}$/.test(code)) return false;
        if (/^(\d)\1{9}$/.test(code)) return false; // ارقام یکسان معتبر نیستند
        var sum = 0;
        for (var i = 0; i < 9; i++) { sum += parseInt(code[i], 10) * (10 - i); }
        var r = sum % 11;
        return parseInt(code[9], 10) === (r < 2 ? r : 11 - r);
    };

    {{-- فاز ۲۱۷ — نرمال‌سازی زندهٔ فیلدهای عددی حین تایپ.
         ریشهٔ باگ «کد ملی در گوشی اشتباه وارد می‌شود و رد می‌شود ولی در دسکتاپ درست است»:
         کیبورد موبایل (چیدمان فارسی/پیشنهاد واژه) ارقام فارسی، فاصله یا نویسهٔ
         نامرئی (نیم‌فاصله U+200C و…) داخل مقدار می‌گذارد؛ ولیدیشن کلاینت با
         پاک‌سازی موقت رد می‌شد ولی همین مقدار خام به سرور می‌رفت و آنجا رد می‌شد.
         حالا مقدار خودِ فیلد در لحظه پاک‌سازی می‌شود (تبدیل ارقام فارسی/عربی +
         حذف هر نویسهٔ غیررقمی) تا مقدار ارسالی به سرور همیشه دقیقاً همان چیزی
         باشد که کلاینت دیده — رفتار موبایل و دسکتاپ یکسان می‌شود. --}}
    window.spNormalizeDigitsInput = function (event) {
        var el = event.target;
        var max = parseInt(el.getAttribute('maxlength') || '0', 10);
        var clean = spEnDigits(el.value).replace(/[^0-9]/g, '');
        if (max > 0 && clean.length > max) { clean = clean.slice(0, max); }
        if (el.value !== clean) { el.value = clean; }
    };

    {{-- فاز ۲۱۷ — نرمال‌سازی زندهٔ شبا حین تایپ: بزرگ‌کردن، حذف فاصله/خط تیره/
         نویسه‌های اضافه و افزودن خودکار پیشوند IR برای شبای ۲۴ رقمی --}}
    window.spNormalizeShebaInput = function (event) {
        var el = event.target;
        var clean = spEnDigits(el.value).toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (/^[0-9]{24}$/.test(clean)) { clean = 'IR' + clean; }
        if (clean.length > 26) { clean = clean.slice(0, 26); }
        if (el.value !== clean) { el.value = clean; }
    };

    {{-- فاز ۲۱۷ — اعتبارسنجی کلاینتی شماره کارت بانکی ۱۶ رقمی (الگوریتم Luhn) --}}
    window.spValidCardNumber = function (card) {
        card = spEnDigits(card).replace(/\D/g, '');
        if (!/^[2569]\d{15}$/.test(card)) return false;
        var sum = 0, alt = false;
        for (var i = card.length - 1; i >= 0; i--) {
            var d = parseInt(card[i], 10);
            if (alt) { d *= 2; if (d > 9) { d -= 9; } }
            sum += d;
            alt = !alt;
        }
        return sum % 10 === 0;
    };

    {{-- فاز ۲۱۷ — اعتبارسنجی کلاینتی شبا ایرانی (IR + ۲۴ رقم، الگوریتم MOD-97) --}}
    window.spValidSheba = function (code) {
        code = spEnDigits(code).toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (/^[0-9]{24}$/.test(code)) { code = 'IR' + code; }
        if (!/^IR[0-9]{24}$/.test(code)) return false;
        // الگوریتم IBAN: چهار نویسهٔ اول به انتها می‌رود؛ IR معادل 1827 است
        var s = code.substring(4) + '1827' + code.substring(2, 4);
        var remainder = 0;
        for (var i = 0; i < s.length; i++) {
            remainder = (remainder * 10 + parseInt(s[i], 10)) % 97;
        }
        return remainder === 1;
    };

    {{-- فاز ۲۱۷ — ولیدیشن زندهٔ کد ملی: وقتی ۱۰ رقم کامل شد همان‌جا پیام فارسی
         می‌نشیند (بدون رفت‌وبرگشت سرور) و با ویرایش مقدار پاک می‌شود --}}
    window.spLiveNationalCodeCheck = function (form, errors) {
        var el = form.querySelector('[name=national_code]');
        if (! el) { return; }
        var value = String(el.value || '');
        if (value.length === 10 && ! spValidNationalCode(value)) {
            errors.national_code = ['کد ملی وارد شده معتبر نیست؛ کد ملی ۱۰ رقمی را مطابق کارت ملی بررسی کنید'];
        } else if (errors.national_code && errors.national_code[0] === 'کد ملی وارد شده معتبر نیست؛ کد ملی ۱۰ رقمی را مطابق کارت ملی بررسی کنید') {
            delete errors.national_code;
        }
    };

    {{-- پاک‌کردن پیام «الزامی» کلاینت وقتی کاربر شروع به پرکردن کرد.
         scope = خود کامپوننت آلپاین (this در init) تا همیشه errors فعلی خوانده شود --}}
    window.spClearErrorOnInput = function (form, scope) {
        form.addEventListener('input', function (e) {
            var errs = scope.errors || {};
            if (e.target && e.target.name && errs[e.target.name] && errs[e.target.name][0] === 'این فیلد الزامی است') {
                delete errs[e.target.name];
            }
        });
        form.addEventListener('change', function (e) {
            var errs = scope.errors || {};
            if (e.target && e.target.type === 'checkbox' && e.target.name && errs[e.target.name]) {
                delete errs[e.target.name];
            }
        });
    };
</script>
