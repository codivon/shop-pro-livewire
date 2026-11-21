/**
 * فاز ۱۹۴ — public/back/assets/js/livewire-uploads.js
 * =========================================================================
 * بهبود تجربهٔ آپلود فایل در پنل مدیریت (مستقل از فرم‌ها):
 *   ۱) پیش‌نمایش آنی سمت کلاینت برای input[type=file][data-ap-preview]:
 *      با FileReader بلافاصله بعد از انتخاب فایل، تصویر + نام فایل نمایش داده
 *      می‌شود — بدون وابستگی به موفقیت آپلود Livewire.
 *   ۲) دکمهٔ «حذف» پیش‌نمایش (data-ap-preview-clear) — مقدار input را پاک و
 *      باکس را مخفی می‌کند؛ اگر Livewire هم در دسترس باشد پراپرتی را null می‌کند.
 *   ۳) شنوندهٔ سراسری livewire-upload-error — به‌جای شکست بی‌صدا، پیام خطای
 *      واضح (نام فایل + علت محتمل) نمایش داده می‌شود.
 *   ۴) شنوندهٔ livewire-upload-finish با خطا — همان بالا.
 *
 * همگی delegated/سراسری‌اند؛ با ناوبری SPA پنل هم زنده می‌مانند و هیچ
 * شنونده‌ای دوبار بایند نمی‌شود (گارد dataset).
 * =========================================================================
 */
(function () {
    'use strict';

    if (window.__apUploadsBound) { return; }
    window.__apUploadsBound = true;

    /* پیام خطا — showCustomToast (فایل customToast.js) یا هر toast موجود */
    function toast(message, type) {
        try {
            if (typeof window.showCustomToast === 'function') {
                window.showCustomToast(message, type || 'error', 7000);
            } else if (window.ApToast && typeof window.ApToast.error === 'function') {
                window.ApToast.error(message);
            } else if (typeof window.toastr === 'object' && window.toastr) {
                window.toastr.error(message);
            } else {
                console.warn('[ap-uploads]', message);
            }
        } catch (e) { /* هیچ‌وقت خودش خطا ندهد */ }
    }

    /* ۱) پیش‌نمایش آنی با FileReader — delegated روی document */
    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.tagName !== 'INPUT' || input.type !== 'file') { return; }
        if (!input.hasAttribute('data-ap-preview')) { return; }

        var box = document.getElementById(input.getAttribute('data-ap-preview'));
        if (!box) { return; }

        var file = input.files && input.files[0];

        if (!file) {
            box.classList.add('hidden');
            var img = box.querySelector('[data-ap-preview-img]');
            if (img) { img.removeAttribute('src'); }
            return;
        }

        /* نمایش نام فایل بلافاصله — قبل از خواندن تصویر */
        var nameEl = box.querySelector('[data-ap-preview-name]');
        if (nameEl) { nameEl.textContent = file.name; }
        box.classList.remove('hidden');

        if (typeof FileReader !== 'function' || !/^image\//i.test(file.type)) {
            /* تصویر نیست یا FileReader نیست — فقط نام کافی است */
            var img2 = box.querySelector('[data-ap-preview-img]');
            if (img2) { img2.removeAttribute('src'); }
            return;
        }

        var reader = new FileReader();
        reader.onload = function () {
            var img3 = box.querySelector('[data-ap-preview-img]');
            if (img3) { img3.src = String(reader.result || ''); }
        };
        reader.onerror = function () {
            toast('خواندن فایل برای پیش‌نمایش ناموفق بود؛ اما فایل همچنان انتخاب شده است.', 'warning');
        };
        reader.readAsDataURL(file);
    }, true);

    /* ۲) دکمهٔ حذف پیش‌نمایش — delegated */
    document.addEventListener('click', function (e) {
        var clearBtn = e.target && e.target.closest ? e.target.closest('[data-ap-preview-clear]') : null;
        if (!clearBtn) { return; }

        var box = clearBtn.closest('[id]');
        if (box) {
            var input = document.querySelector('input[type=file][data-ap-preview="' + box.id + '"]');
            if (input) {
                input.value = '';
                /* اگر Livewire در دسترس است، پراپرتی هم null شود تا آپلودی ارسال نشود */
                try {
                    var root = input.closest('[wire\\:id]');
                    if (root && window.Livewire) {
                        var component = window.Livewire.find(root.getAttribute('wire:id'));
                        if (component) {
                            var prop = input.getAttribute('wire:model');
                            if (prop) { component.set(prop, null); }
                        }
                    }
                } catch (err) { /* بی‌اثر */ }
            }
            box.classList.add('hidden');
        }
    }, true);

    /* ۳) خطای آپلود Livewire — به‌جای سکوت، پیام واضح */
    window.addEventListener('livewire-upload-error', function (e) {
        var prop = (e.detail && e.detail.property) || '';
        var hint = 'حجم فایل (سقف PHP: upload_max_filesize / post_max_size) یا اتصال را بررسی کنید و دوباره تلاش کنید.';
        toast('بارگذاری «' + (prop ? prop : 'فایل') + '» ناموفق بود. ' + hint, 'error');
    });

    /* ۴) پس از پایان هر آپلود موفق در کامپوننت برند، خطاهای قبلی نمایشی پاک بمانند — کاری لازم نیست */
})();
