/**
 * SaniarTheme — منطق‌های سراسری تکمیلی تمپلیت
 * (پاپ‌آپ صفحه اصلی + حالت پیش‌نمایش ویجت‌ساز پنل)
 * بدون هیچ کد JS داخل فایل‌های بلید.
 */
(function () {
    'use strict';

    /* فاز ۱۹۳ — فالبک تصویر برند: وقتی لوگوی برند ۴۰۴ می‌دهد (فایل روی سرور
       موجود نیست)، به‌جای آیکون تصویر شکسته، نشان حرف اول نام برند نمایش
       داده می‌شود (span.saniar-brand-card__fallback داخل همان کارت از قبل
       با hidden کنار img گذاشته شده است). */
    window.snrBrandImgFallback = function (img) {
        try {
            var card = img.closest('.saniar-brand-card') || img.parentElement;
            if (!card) { return; }
            img.hidden = true;
            var fb = card.querySelector('.saniar-brand-card__fallback');
            if (fb) { fb.hidden = false; }
        } catch (e) { /* هرگز بقیهٔ صفحه را نشکند */ }
    };

    /* فاز ۱۸۵ (SPA) — مقداردهی‌ها تابع قابل-فراخوانی شدند تا بعد از هر
       ناوبری wire:navigate هم روی گره‌های تازه اجرا شوند */
    window.saniarTemplateInit = function () {

        // ---------- اسلایدر «اسلایدر و دو بنر کناری» ----------
        // مقداردهی مستقل برای هر نمونه با گره‌های خودِ همان اسلایدر تا اگر چند
        // اسلایدر در صفحه باشند، Pagination/Navigation ها با هم قاطی نشوند.
        if (window.Swiper) {
            document.querySelectorAll('.saniar-side-slider').forEach(function (el) {
                if (el.dataset.saniarSlider === '1') { return; }
                el.dataset.saniarSlider = '1';

                try {
                    new Swiper(el, {
                        spaceBetween: 30,
                        centeredSlides: true,
                        loop: el.querySelectorAll('.swiper-slide').length > 1,
                        autoplay: {
                            delay: 4500,
                            disableOnInteraction: false,
                        },
                        effect: 'fade',
                        fadeEffect: { crossFade: true },
                        pagination: {
                            el: el.querySelector('.swiper-pagination'),
                            clickable: true,
                        },
                        navigation: {
                            nextEl: el.querySelector('.swiper-button-next'),
                            prevEl: el.querySelector('.swiper-button-prev'),
                        },
                    });
                } catch (e) { /* اسلایدر نباید بقیهٔ صفحه را بشکند */ }
            });
        }

        // ---------- پاپ‌آپ صفحه اصلی ----------
        var imagePopup = document.getElementById('image-popup-modal');
        var textPopup = document.getElementById('text-popup-modal');

        if (imagePopup && window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(imagePopup).show();
        }
        if (textPopup && window.bootstrap && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(textPopup).show();
        }

        // ---------- حالت پیش‌نمایش ویجت‌ساز پنل (?ap-preview=1) ----------
        if (window.location.search.indexOf('ap-preview') === -1) {
            return;
        }

        if (!document.getElementById('saniar-ap-preview-style')) {
            var style = document.createElement('style');
            style.id = 'saniar-ap-preview-style';
            style.textContent =
                '[data-ap-outline]{animation:apWidgetPulse 1.6s ease-in-out infinite}' +
                '@keyframes apWidgetPulse{0%,100%{outline-color:rgba(245,158,11,.95);outline-offset:4px}' +
                '50%{outline-color:rgba(245,158,11,.35);outline-offset:9px}}' +
                '.ap-widget-guide{outline:1px dashed rgba(148,163,184,.8)!important;outline-offset:2px}';
            document.head.appendChild(style);
        }

        function clearHighlight() {
            document.querySelectorAll('[data-ap-outline]').forEach(function (node) {
                node.style.outline = '';
                node.removeAttribute('data-ap-outline');
            });
        }

        function highlightWidget(id) {
            var el = document.getElementById('widget-' + id);
            if (!el) { return; }
            clearHighlight();
            el.setAttribute('data-ap-outline', '1');
            el.style.outline = '2px dashed rgba(245, 158, 11, .95)';
            el.style.outlineOffset = '4px';
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function setGuides(on) {
            document.querySelectorAll('[id^="widget-"]').forEach(function (node) {
                node.classList.toggle('ap-widget-guide', !!on);
            });
        }

        /* شنوندهٔ پیام فقط یک‌بار ثبت می‌شود (تابع با هر ناوبری اجرا می‌شود) */
        if (!window.__saniarApPreviewBound) {
            window.__saniarApPreviewBound = true;
            window.addEventListener('message', function (event) {
                var data = event.data || {};
                if (data.apWidgetId) { highlightWidget(data.apWidgetId); }
                if (typeof data.apShowGuides !== 'undefined') { setGuides(data.apShowGuides); }
            });
        }
    };

    /* اجرای اولیه + ثبت برای ناوبری SPA */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { window.saniarTemplateInit(); });
    } else {
        window.saniarTemplateInit();
    }
    (window.saniarReinit = window.saniarReinit || []).push(function () { window.saniarTemplateInit(); });

    /* ================================================================
       فاز ۱۹۴ — زنده‌نگه‌داشتن نشست (ضد «لاگ‌اوت خودکار بعد از چند دقیقه»)
       ================================================================
       کاربر واردشده که روی صفحه‌ای بی‌اکشن مانده (مثلاً صفحات پروفایل) بعد از
       انقضای SESSION_LIFETIME خودکار logout می‌شد و درخواست بعدی هم با ۴۱۹
       (CSRF منقضی) متوقف می‌شد.
       رفع: هر ۱۲۰ ثانیه — فقط وقتی تب مرئی است — یک GET به /session/ping:
         • هر درخواستِ دارای سشن، last activity را تازه می‌کند ⇒ نشست منقضی نمی‌شود.
         • توکن CSRF تازه در پاسخ meta را به‌روز می‌کند.
         • endpoint بدون نیاز به ورود است؛ برای مهمان‌ها هم بی‌ضرر است ولی برای
           پرهیز از ترافیک بیهوده فقط برای کاربر واردشده پینگ می‌کنیم. */
    (function startSessionKeepAlive() {
        if (window.__saniarKeepAliveBound) { return; }
        window.__saniarKeepAliveBound = true;

        var PING_URL = '/session/ping';
        var INTERVAL_MS = 120 * 1000;
        var authMeta = document.querySelector('meta[name="Auth"]');

        function isGuest() {
            var m = document.querySelector('meta[name="Auth"]');
            return !m || (m.getAttribute('content') || '').indexOf('Yes') === -1;
        }

        function ping() {
            if (document.visibilityState !== 'visible') { return; } /* تب پشت‌زمینه — پینگ نکن */
            if (isGuest()) { return; }

            try {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', PING_URL, true);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onload = function () {
                    try {
                        var data = JSON.parse(xhr.responseText || '{}');
                        if (data && data.csrf) {
                            var meta = document.querySelector('meta[name="csrf-token"]');
                            if (meta && meta.getAttribute('content') !== data.csrf) {
                                meta.setAttribute('content', data.csrf);
                            }
                        }
                    } catch (e) { /* پاسخ نامعتبر — بی‌اثر */ }
                };
                xhr.send();
            } catch (e) { /* شبکه در دسترس نیست — دورهٔ بعد دوباره */ }
        }

        setInterval(ping, INTERVAL_MS);

        /* اولین پینگ کمی بعد از لود تا نشست تازه‌تر از lifetime بماند */
        setTimeout(ping, 15 * 1000);

        /* بعد از هر ناوبری SPA هم مطمئن شویم meta تازه است */
        document.addEventListener('livewire:navigated', function () {
            authMeta = document.querySelector('meta[name="Auth"]');
        });
    })();
})();
