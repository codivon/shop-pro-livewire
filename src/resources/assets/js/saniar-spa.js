/**
 * SaniarTheme — saniar-spa.js
 * =========================================================================
 * فاز ۱۸۵ — پیمایش SPA با Livewire 4 (wire:navigate)
 *
 * ایده:
 *  • همهٔ لینک‌های داخلی «امن» با اتریبیوت wire:navigate علامت‌گذاری می‌شوند؛
 *    موتور navigate لایووایر همان‌جا prefetch (هنگام press) + تعویض صفحهٔ
 *    بدون رفرش + به‌روزرسانی title/meta را انجام می‌دهد.
 *  • SEO: صفحات همچنان HTML کامل سرور-رندر دارند؛ wire:navigate فقط
 *    بهبودِ مرورگرِ دارای JS است، پس خزنده‌ها و پکیج SEOAudit هیچ تفاوتی
 *    نمی‌بینند (هر URL، HTML کامل خودش را دارد).
 *  • «امن» یعنی: هم‌مبدأ، بدون هندلر JS تم (مستقیم یا delegated)، خارج از
 *    مسیرهای مستثنی (سبد/پرداخت/احراز هویت/پنل کاربری/مقایسه/فرم‌ها/ادمین).
 *  • مسیرهای مستثنی با لود کامل باز می‌شوند — بدون هیچ ریسکی برای
 *    فرایندهای حساس (پرداخت، ورود، نقشهٔ آدرس و …).
 *  • فاز ۱۸۷: intercept کلیک در فاز capture + فراخوانی مستقیم
 *    Livewire.navigate — چون Alpine دایرکتیوهای wire:navigate افزوده‌شده
 *    بعد از بوت را init نمی‌کند، این interceptor مسیر قطعیِ SPA است.
 *  • فاز ۱۸۸: علامت‌گذاری با x-navigate.hover (دایرکتیو واقعی Alpine که
 *    بعد از boot هم روی تغییر اتریبیوت init می‌شود) ⇒ hover/press-prefetch
 *    خودکار ⇒ ناوبری ~۱۸ms از cache. پوشش SPA گسترش یافت: صفحات پرداخت
 *    (سبد/چک‌اوت/نتیجهٔ پرداخت)، کیف پول، مقایسه، پروفایل، فروشنده و نظرات.
 *  • بعد از هر ناوبری: لینک‌های تازه علامت می‌خورند، initهای تم
 *    (window.saniarReinit) اجرا می‌شوند و head نرمال‌سازی می‌شود.
 *  • فاز ۱۹۱: تشخیص سوایپ/درگ — کلیکی که در پایانِ کشیدنِ انگشت/ماوس روی
 *    اسلایدرها (Swiper/Owl) تولید می‌شود هرگز نباید ناوبری کند؛ وگرنه با هر
 *    سوایپ، کلیکِ روی تصویر/عنوان محصول به «تک‌صفحهٔ محصول» می‌رسید.
 * =========================================================================
 */
(function () {
    'use strict';

    /* فاز ۱۸۶ — تشخیص نبود Livewire: SPA فعال نمی‌شود و راهنمای دقیق در کنسول
       نمایش داده می‌شود تا ادمین بداند باید پیش‌نیازهای قالب را اجرا کند. */
    if (!window.Livewire) {
        console.warn('[SaniarTheme] Livewire در دسترس نیست؛ حالت SPA خاموش است. ' +
            'از پنل مدیریت (قالب‌ها ← اجرای پیش‌نیازها) پیش‌نیازهای قالب را اجرا کنید ' +
            'یا روی سرور اجرا کنید: composer require livewire/livewire');
    }

    /* اولین سگمنت مسیرهایی که باید «لود کامل» بمانند.
       فاز ۱۸۸ — فقط مسیرهای واقعاً حساس مستثنی ماندند؛ صفحات پرداخت
       (سبد/چک‌اوت/نتیجهٔ پرداخت)، کیف پول، نظرات، مقایسه، پروفایل و
       فروشنده حالا SPA هستند. ورود/ثبت‌نام/کپچا/ادمین لود کامل. */
    var SPA_EXCLUDE_SEGMENTS = [
        'login', 'register', 'verify', 'change-username', 'welcome', 'password',
        'form', 'contact', 'newsletter',
        'admin', 'install', 'setup'
    ];

    /* فاز ۱۸۸ — اتریبیوت واقعیِ دایرکتیو Alpine.
       در این هسته wire:navigate فقط «هنگام init المان» به x-navigate ترجمه
       می‌شود؛ اما Alpine بعد از boot فقط «تغییر اتریبیوت» المان‌های موجود
       را پردازش می‌کند (onAttributesAdded). پس مستقیماً x-navigate.hover
       می‌گذاریم تا hover-prefetch (۶۰ms) + press-prefetch + کلیکِ نتیجه از
       خودِ Alpine فعال شود — ناوبری از cache: ~۱۸ms به‌جای ~۵۵۰ms. */
    var NAV_ATTR = 'x-navigate.hover';

    /* لینک‌هایی که رفتار JS خاصی رویشان سوار است — هرگز wire:navigate نگیرند */
    var JS_HANDLED_SELECTOR = [
        '.showSubMenu',
        '[data-no-navigate]',
        '[data-bs-toggle]',
        '[data-action]',
        '[data-fancybox]',
        '[data-magnify]',
        '.apm-toast-link'
    ].join(',');

    function firstSegment(pathname) {
        var parts = (pathname || '/').split('/').filter(Boolean);
        return (parts[0] || '').toLowerCase();
    }

    function isExcludedPath(pathname) {
        if (SPA_EXCLUDE_SEGMENTS.indexOf(firstSegment(pathname)) !== -1) {
            return true;
        }
        /* لینک‌های دانلود (فایل واقعی — هرگز SPA)؛ فاز ۱۸۸: مقایسه SPA شد */
        if (/\/download(\/|$)/i.test(pathname || '')) { return true; }
        return false;
    }

    /* هندلر مستقیم jQuery روی خود عنصر؟ */
    function elementHasDirectClickHandler(el) {
        if (!window.jQuery || !window.jQuery._data) { return false; }
        var evs = window.jQuery._data(el, 'events');
        return !!(evs && evs.click && evs.click.length);
    }

    /* هندلر delegated ثبت‌شده روی document/body/window که این لینک را می‌گیرد؟ */
    function matchesDelegatedClick(el) {
        if (!window.jQuery || !window.jQuery._data) { return false; }
        var owners = [document, document.body, document.documentElement, window];
        for (var i = 0; i < owners.length; i++) {
            var evs = owners[i] ? window.jQuery._data(owners[i], 'events') : null;
            if (!evs || !evs.click) { continue; }
            for (var j = 0; j < evs.click.length; j++) {
                var sel = evs.click[j] && evs.click[j].selector;
                if (!sel) { continue; }
                try {
                    if (window.jQuery(el).is(sel)) { return true; }
                } catch (e) { /* سلکتور نامعتبر — بی‌اثر */ }
            }
        }
        return false;
    }

    function isEligible(a) {
        if (!a || a.tagName !== 'A') { return false; }
        if (a.hasAttribute(NAV_ATTR)) { return false; }
        if (a.dataset.spaChecked === '1') { return false; }
        a.dataset.spaChecked = '1';

        return isClickableEligible(a);
    }

    /* فاز ۱۸۷ — eligibility بدون کشِ dataset؛ برای interceptorِ کلیک که باید
       هر بار در لحظهٔ کلیک تصمیم بگیرد (نه بر اساس علامت‌گذاری اولیه) */
    function isClickableEligible(a) {
        if (!a || a.tagName !== 'A') { return false; }

        var href = a.getAttribute('href') || '';
        if (!href) { return false; }
        if (href.charAt(0) === '#') { return false; }
        if (/^(javascript|mailto|tel|sms|data|blob):/i.test(href)) { return false; }

        if (a.hasAttribute('download')) { return false; }
        /* هر targetی جز _self (blank/_top/iframe) → مداخله نکن */
        if (a.target && a.target !== '_self') { return false; }
        if ((a.getAttribute('rel') || '').indexOf('external') !== -1) { return false; }

        if (a.hasAttribute('data-no-navigate')) { return false; }
        if (a.closest('[data-no-navigate]')) { return false; }
        if (a.closest('.admin-loggedin-navbar')) { return false; }

        /* اکشن‌های خود لایووایر داخل کامپوننت‌ها — هندلر خودش را دارد */
        if (a.hasAttribute('wire:click') || a.closest('[wire\\:click]')) { return false; }

        if (a.matches(JS_HANDLED_SELECTOR)) { return false; }
        if (elementHasDirectClickHandler(a)) { return false; }
        if (matchesDelegatedClick(a)) { return false; }

        var url;
        try { url = new URL(a.href, window.location.origin); } catch (e) { return false; }
        if (url.origin !== window.location.origin) { return false; }
        if (isExcludedPath(url.pathname)) { return false; }
        /* فایل استاتیک */
        if (/\.(png|jpe?g|gif|svg|pdf|zip|rar|css|js|ico|webp|mp4|mp3|xlsx|docx?)$/i.test(url.pathname)) { return false; }

        /* همان صفحه با همان query → پرش hash بومی مرورگر بهتر از رفرشِ نرم است */
        if (url.pathname === window.location.pathname && url.search === window.location.search) { return false; }

        return true;
    }

    function markLinks(root) {
        var links = (root || document).querySelectorAll('a[href]');
        for (var i = 0; i < links.length; i++) {
            var a = links[i];
            if (isEligible(a)) { a.setAttribute(NAV_ATTR, ''); }
        }
    }

    /* head نرمال‌سازی: بعد از navigate ممکن است متا/کنonical تکراری بمانند؛
       از هر کدام «آخرین» (مربوط به صفحهٔ فعلی) نگه داشته می‌شود */
    function normalizeHead() {
        var keepLastSelectors = [
            'link[rel="canonical"]',
            'meta[name="description"]',
            'meta[name="robots"]',
            'meta[property^="og:"]',
            'meta[name^="twitter:"]'
        ];
        keepLastSelectors.forEach(function (sel) {
            var nodes = document.head.querySelectorAll(sel);
            for (var i = 0; i < nodes.length - 1; i++) {
                nodes[i].remove();
            }
        });
    }

    function runReinits() {
        (window.saniarReinit || []).forEach(function (fn) {
            try { fn(); } catch (e) { /* هیچ init نباید ناوبری را بشکند */ }
        });

        /* تصاویر lazy تازهٔ صفحهٔ جدید */
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.lazyLoadXT) {
            try { window.jQuery(window).lazyLoadXT(); } catch (e) { /* */ }
        }
    }

    /* فاز ۱۸۷ — رفع اصلی «SPA کار نمی‌کند؛ صفحه‌ها رفرش کامل می‌شوند»:
       ====================================================================
       در این هسته، livewire.js اتریبیوت wire:navigate را به دایرکتیو Alpine
       یعنی x-navigate ترجمه می‌کند ("wire:navigate": "x-navigate"). دایرکتیو
       Alpine فقط روی المان‌هایی initialize می‌شود که «هنگام بوت Alpine یا
       هنگام morph» موجودند؛ اما markLinks بعد از بوت Alpine اتریبیوت را روی
       لینک‌های DOM فعلی می‌گذارد ⇒ Alpine هرگز آن را پردازش نمی‌کند ⇒
       هیچ هندلر navigateای روی کلیک‌ها فعال نیست ⇒ کلیک = لود کامل.

       راه‌حل (تأییدشده روی فروشگاه live): interceptorِ کلیک در «فاز capture»
       روی document — قبل از همهٔ هندلرهای jQuery/قدیمی اجرا می‌شود، لینک
       واجد شرایط را مستقیماً با window.Livewire.navigate(url) باز می‌کند و
       stopImmediatePropagation جلوی هر هندلر دیگر (از جمله هر مداخله‌گر
       رفرشِ دستی) را می‌گیرد. اگر navigate خطا بدهد → لود عادی مثل قبل. */
    /* فاز ۱۹۱ — تشخیص درگ/سوایپ افقی: با شروع و پایان لمس/درگ ماوس،
       جابه‌جایی افقی ثبت می‌شود؛ اگر بیش از آستانه بود، برای مدت کوتاه هر کلیکی
       که روی لینک درون اسلایدرها برسد «بلعیده» می‌شود (هرگز ناوبری نه،
       نه SPA و نه لود کامل). این همان ریشهٔ «با اسلاید کردن به تک‌صفحهٔ
       محصول می‌رود» بود: پیش‌تر interceptor در capture-document زودتر از
       preventClicks خودِ Swiper اجرا می‌شد و ناوبری را شروع می‌کرد.

    فاز ۱۹۲ — تقویت نهایی (رفع باقی‌ماندهٔ پرش به تک‌صفحه هنگام سوایپ):
       ====================================================================
       نسخهٔ ۱۹۱ فقط «اختلاف بین مختصات touchstart و touchend» را می‌سنجید و
       سه شکاف داشت:
       ۱) سوایپ‌های کوتاه (۷-۹ پیکسل) از آستانهٔ ۱۰px رد می‌شدند ولی Swiper
          آن‌ها را «تغییر اسلاید» حساب می‌کرد و کلیکِ انتهای لمس ناوبری را
          شروع می‌کرد؛
       ۲) پنجرهٔ ۳۵۰ms در iOS/برخی اندرویدها قبل از dispatch شدن click
          تمام می‌شد؛
       ۳) حرکتِ وسطِ ژست (touchmove) دیده نمی‌شد — فقط نقاط شروع/پایان.
       اکنون: مسافت کلِ افقیِ طی‌شده در طول ژست (touchmove) انباشته می‌شود،
       آستانه به ۶px و پنجرهٔ چشم‌پوشی به ۵۰۰ms رسیده و اگر click در نقطه‌ای
       دورتر از آخرین مختصات لمس رخ دهد هم سوایپ حساب می‌شود. */
    var dragState = {
        x: 0, y: 0,               /* نقطهٔ شروع ژست */
        lastX: 0, lastY: 0,       /* آخرین مختصات دیده‌شده */
        travelX: 0,               /* مسافت کل افقی طی‌شده در ژست */
        tracking: false,
        suppressUntil: 0,
        lastGestureEnd: 0,        /* زمان پایان آخرین ژست لمسی */
        isTouch: false            /* فاز ۱۹۲ — آخرین ژست لمسی بوده یا ماوس؟ */
    };

    function registerDrag(x, y) {
        dragState.x = x;
        dragState.y = y;
        dragState.lastX = x;
        dragState.lastY = y;
        dragState.travelX = 0;
        dragState.tracking = true;
    }

    /* فاز ۱۹۲ — مسافت افقیِ طی‌شده در طول ژست انباشته می‌شود
       (نه فقط اختلاف ابتدا و انتها) */
    function trackMove(x, y) {
        if (!dragState.tracking) { return; }
        dragState.travelX += Math.abs(x - dragState.lastX);
        dragState.lastX = x;
        dragState.lastY = y;
    }

    function finishDrag(x, y) {
        if (!dragState.tracking) { return; }
        dragState.tracking = false;

        if (typeof x === 'number') { trackMove(x, y); }

        dragState.lastGestureEnd = Date.now();

        /* سوایپِ واقعی = جابه‌جایی افقی آشکار — آستانهٔ ۶px و غلبهٔ افقی بر عمودی */
        var dy = Math.abs(dragState.lastY - dragState.y);

        if (dragState.travelX >= 6 && dragState.travelX >= dy) {
            dragState.suppressUntil = Date.now() + 500;
        }
    }

    function suppressDragClick() {
        return Date.now() < dragState.suppressUntil;
    }

    function interceptDragEvents() {
        if (window.__saniarSpaDragBound) { return; }
        window.__saniarSpaDragBound = true;

        document.addEventListener('touchstart', function (e) {
            var t = e.touches && e.touches[0];
            if (!t) { return; }
            dragState.isTouch = true;
            registerDrag(t.clientX, t.clientY);
        }, true);

        /* فاز ۱۹۲ — حرکت وسط ژست هم شمرده می‌شود (سوایپ کوتاه از قلم نیفتد) */
        document.addEventListener('touchmove', function (e) {
            var t = e.touches && e.touches[0];
            if (!t) { return; }
            trackMove(t.clientX, t.clientY);
        }, { capture: true, passive: true });

        document.addEventListener('touchend', function (e) {
            var t = e.changedTouches && e.changedTouches[0];
            finishDrag(t ? t.clientX : undefined, t ? t.clientY : undefined);
        }, true);

        /* touchcancel — ژست توسط مرورگر گرفته شد؛ click نخواهد آمد ولی
           شمارنده‌ها باید ریست شوند تا ژست بعدی درست سنجیده شود */
        document.addEventListener('touchcancel', function () {
            dragState.tracking = false;
            dragState.travelX = 0;
            dragState.lastGestureEnd = Date.now();
        }, true);

        document.addEventListener('mousedown', function (e) {
            dragState.isTouch = false;
            registerDrag(e.clientX, e.clientY);
        }, true);

        document.addEventListener('mousemove', function (e) {
            if (dragState.tracking) { trackMove(e.clientX, e.clientY); }
        }, { capture: true, passive: true });

        document.addEventListener('mouseup', function (e) {
            finishDrag(e.clientX, e.clientY);
        }, true);
    }

    function interceptClicks() {
        if (window.__saniarSpaClickBound) { return; }
        window.__saniarSpaClickBound = true;

        interceptDragEvents();

        document.addEventListener('click', function (e) {
            /* فقط کلیک چپ بدون کلید اصلاحی (ctrl/cmd = tab جدید، shift = پنجرهٔ جدید) */
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) { return; }

            /* هندلر دیگری قبلاً default را گرفته — بی‌مداخله */
            if (e.defaultPrevented) { return; }

            var target = e.target;
            if (!target || !target.closest) { return; }

            var a = target.closest('a[href]');
            if (!a) { return; }

            /* فاز ۱۹۱ — کلیکِ پس از درگ/سوایپ اسلایدر: ببلع و تمام.
               روی تاچ همه‌جا فعال است؛ روی ماوس فقط داخل اسلایدرها
               (تا انتخابِ متن عادی بیرون اسلایدر آسیب نبیند). */
            if (suppressDragClick()) {
                if (!e.pointerType && !('ontouchstart' in window)) {
                    var sliderBox = a.closest('.swiper, .owl-carousel, .amazing-slider, .product-slider, .swiper-container, #mainCarousel, #carouselExampleIndicators');
                    if (!sliderBox) { return; }
                }
                e.preventDefault();
                e.stopImmediatePropagation();
                return;
            }

            /* فاز ۱۹۲ — فالبک دوم: کلیکِ تاچی که خیلی زود بعد از ژستِ دارای
               حرکت افقی dispatch شده ولی پنجرهٔ suppress فعال نیست
               (تأخیر dispatch شدن click در iOS/برخی اندرویدها > ۵۰۰ms). */
            if (dragState.isTouch
                && dragState.lastGestureEnd
                && Date.now() - dragState.lastGestureEnd < 700
                && dragState.travelX >= 6) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return;
            }

            if (!isClickableEligible(a)) { return; }

            if (!window.Livewire || typeof window.Livewire.navigate !== 'function') { return; }

            var url;
            try { url = new URL(a.href, window.location.origin); } catch (err) { return; }

            /* فاز capture = قبل از همه؛ زنجیرهٔ هندلرهای دیگر را قطع می‌کنیم */
            e.preventDefault();
            e.stopImmediatePropagation();

            try {
                window.Livewire.navigate(url.href);
            } catch (err) {
                /* ناوبری نرم ممکن نشد → رفتار عادی مثل قبل (لود کامل) */
                window.location.href = url.href;
            }
        }, true);
    }

    function enhance() {
        if (!window.Livewire) { return; } /* بدون لایووایر → همه‌چیز مثل قبل (لود کامل) */
        markLinks(document);
        interceptClicks();
    }

    function onNavigated() {
        markLinks(document);   /* لینک‌های تازهٔ صفحهٔ جدید */
        normalizeHead();       /* ضدتکرار متا/کنonical برای SEO */
        runReinits();          /* initهای تم روی DOM تازه */
    }

    /* فاز ۱۸۸ — ناوبری نرم عمومی برای ریدایرکت‌های JS تم
       (مثل data.redirect در جریان سبد/چک‌اوت یا جستجو).
       اگر مسیر SPA باشد → Livewire.navigate (از prefetch cache هم استفاده می‌کند)؛
       وگرنه (خارج/مستثنی/نبود Livewire) → لود عادی مثل قبل. */
    window.saniarSoftNavigate = function (url) {
        try {
            var u = new URL(url, window.location.origin);
            if (u.origin === window.location.origin
                && !isExcludedPath(u.pathname)
                && window.Livewire
                && typeof window.Livewire.navigate === 'function') {
                window.Livewire.navigate(u.href);
                return true;
            }
        } catch (e) { /* url نامعتبر → لود عادی */ }
        window.location.href = url;
        return false;
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enhance);
    } else {
        enhance();
    }

    document.addEventListener('livewire:navigated', onNavigated);
})();
