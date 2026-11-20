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
 *  • بعد از هر ناوبری: لینک‌های تازه علامت می‌خورند، initهای تم
 *    (window.saniarReinit) اجرا می‌شوند و head نرمال‌سازی می‌شود.
 * =========================================================================
 */
(function () {
    'use strict';

    /* اولین سگمنت مسیرهایی که باید «لود کامل» بمانند */
    var SPA_EXCLUDE_SEGMENTS = [
        'cart', 'checkout', 'orderResultInfo',
        'login', 'register', 'verify', 'change-username', 'welcome', 'password',
        'profile', 'wallet', 'reviews', 'compare', 'form', 'contact',
        'admin', 'install', 'setup', 'newsletter', 'seller'
    ];

    var NAV_ATTR = 'wire:navigate';

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
        /* لینک‌های دانلود، مقایسه و نقشهٔ آدرس‌ها */
        if (/\/download(\/|$)/i.test(pathname || '')) { return true; }
        if (/\/compare(\/|$)/i.test(pathname || '')) { return true; }
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

        var href = a.getAttribute('href') || '';
        if (!href) { return false; }
        if (href.charAt(0) === '#') { return false; }
        if (/^(javascript|mailto|tel|sms|data|blob):/i.test(href)) { return false; }

        if (a.hasAttribute('download')) { return false; }
        if (a.target === '_blank') { return false; }
        if ((a.getAttribute('rel') || '').indexOf('external') !== -1) { return false; }

        if (a.hasAttribute('data-no-navigate')) { return false; }
        if (a.closest('[data-no-navigate]')) { return false; }
        if (a.closest('.admin-loggedin-navbar')) { return false; }

        var url;
        try { url = new URL(a.href, window.location.origin); } catch (e) { return false; }
        if (url.origin !== window.location.origin) { return false; }
        if (isExcludedPath(url.pathname)) { return false; }
        /* فایل استاتیک */
        if (/\.(png|jpe?g|gif|svg|pdf|zip|rar|css|js|ico|webp|mp4|mp3|xlsx|docx?)$/i.test(url.pathname)) { return false; }

        if (a.matches(JS_HANDLED_SELECTOR)) { return false; }
        if (elementHasDirectClickHandler(a)) { return false; }
        if (matchesDelegatedClick(a)) { return false; }

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

    function enhance() {
        if (!window.Livewire) { return; } /* بدون لایووایر → همه‌چیز مثل قبل (لود کامل) */
        markLinks(document);
    }

    function onNavigated() {
        markLinks(document);   /* لینک‌های تازهٔ صفحهٔ جدید */
        normalizeHead();       /* ضدتکرار متا/کنonical برای SEO */
        runReinits();          /* initهای تم روی DOM تازه */
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enhance);
    } else {
        enhance();
    }

    document.addEventListener('livewire:navigated', onNavigated);
})();
