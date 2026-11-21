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
 *  • فاز ۱۹۵ — دو رفع نهایی:
 *    ۱) سوایپ‌های زیر آستانهٔ touch-slop مرورگر (touchmove dispatch نمی‌شود):
 *       دلتای نهایی در finishDrag «قبل از» خاموش‌شدن tracking شمرده می‌شود +
 *       فالبک pointermove برای ژست لمسی. (تأییدشده روی فروشگاه live)
 *    ۲) «خروج خودکار از پنل مشتری»: لینک GET /logout هرگز x-navigate نمی‌گیرد؛
 *       وگرنه فقط هاور روی آیتم «خروج» در منو prefetch را اجرا می‌کرد و کاربر
 *       بدون هیچ کلیکی logout می‌شد.
 *  • فاز ۱۹۶ — ریشهٔ باقی‌ماندهٔ «سوایپ ← پرش به تک‌صفحه» + تماس/فروشنده:
 *    ۱) مهار مبتنی بر خودِ Swiper: هر slideChange که تا ۹۰۰ms پیش از آن
 *       touchStart خورده باشد یعنی سوایپِ واقعی است ⇒ window.
 *       saniarSuppressAfterSwipe ⇒ هر کلیک/mouseup تا ۷۰۰ms بلعیده می‌شود.
 *       دیگر هیچ سوایپی — حتی چند پیکسلی — از دست گاردها نمی‌زند، بدون
 *       آنکه تپ‌های سالم (بدون تغییر اسلاید) قربانی شوند.
 *    ۲) pointerdown/pointerup در فاز capture: رخدادهای pointer قبل از
 *       touchend می‌آیند و مکانیزم mousedown→mouseup لایووایر ممکن است
 *       از همین مسیر مسلح/شلیک شود؛ اکنون در همان مرحله مهار می‌شوند.
 *    ۳) [data-bs-toggle="tooltip"] دیگر لینک را از SPA مستثنی نمی‌کند
 *       (tooltip با هاور باز می‌شود نه کلیک) — لینک‌های محصولِ اسلایدر
 *       حالا SPA می‌گیرند و سوایپ روی‌شان هم مهار می‌شود؛ فقط toggleهای
 *       کلیک‌محور (dropdown/collapse/modal/tab/pill/button/popover)
 *       مستثنی می‌مانند.
 *    ۴) صفحهٔ «تماس با ما» حالا SPA است (حذف contact از مستثنی‌ها).
 *    ۵) مسیرهای «seller» مستثنی شدند: لایوت پنل فروشندگان CSS خودش را با
 *       data-navigate-track="reload" لود می‌کند و mergeNewHead لایووایر
 *       لینک‌های CSS قدیمی تم را حذف نمی‌کند ⇒ بعد از ناوبری نرم، CSS تم
 *       و ادمین همزمان اعمال و صفحه خراب می‌شد؛ حالا مثل /admin لود کامل —
 *       استایل‌ها همیشه درست، بدون رفرش دستی.
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
       فاز ۱۹۴ — «login» و «register» حذف شدند: صفحهٔ ورود/ثبت‌نام هم حالا SPA است
       (رفع «صفحهٔ لاگین رفرش می‌شود»). صفحهٔ احراز هویت سند کامل خودش را دارد و
       mergeNewHead لایووایر دارایی‌های آن را مدیریت می‌کند؛ نسخه‌های اسکریپت‌های
       مشترک در همهٔ لایوت‌ها یکسان شده است تا اجرای مجدد/خطای redeclaration رخ ندهد.
       فاز ۱۹۵ — «logout» اضافه شد: روت GET /logout یک اکشن مخربِ نشست است؛ اگر
       لینک خروجِ منو x-navigate.hover بگیرد، فقط ۶۰ms هاور روی آیتم «خروج» (بدون
       هیچ کلیکی) prefetch را اجرا می‌کرد و کاربرِ پروفایل بی‌درنگ logout و به
       صفحهٔ ورود پرت می‌شد — دقیقاً همان «میفتم بیرون وقتی بین منوها می‌گردم».
       فاز ۱۹۶ — «contact» حذف شد (صفحهٔ تماس با ما حالا SPA است؛ اسکریپت‌های
       پوش‌شدهٔ آن بعد از هر ناوبری توسط navigate لایووایر دوباره اجرا می‌شوند و
       نقشه/فرم داخل $(document).ready با jQuery آماده بلافاصله مقداردهی می‌شوند).
       فاز ۱۹۶ — «seller» اضافه شد: لایوت پنل فروشندگان (مهمان + پنل) CSS خودش
       (Vite + data-navigate-track="reload") را دارد و mergeNewHead لایووایر
       CSSهای قدیمی تم را حذف نمی‌کند؛ ناوبری نرم به /seller صفحهٔ خراب می‌داد
       («استایل‌ها لود نمی‌شه، باید رفرش کنم»). لود کامل ⇒ همیشه درست. */
    var SPA_EXCLUDE_SEGMENTS = [
        'verify', 'welcome', 'change-username', 'password', 'logout',
        'form', 'newsletter',
        'admin', 'seller', 'install', 'setup'
    ];

    /* فاز ۱۹۵ — لینک‌هایی که هرگز نباید علامت x-navigate بگیرند حتی اگر
       مسیرشان در لیست بالا نبود (دفاع دوم؛ مثلاً با تغییر مسیر خروج). */
    var NEVER_NAVIGATE_HREF = /\/logout(\/|\?|#|$)/i;

    /* فاز ۱۸۸ — اتریبیوت واقعیِ دایرکتیو Alpine.
       در این هسته wire:navigate فقط «هنگام init المان» به x-navigate ترجمه
       می‌شود؛ اما Alpine بعد از boot فقط «تغییر اتریبیوت» المان‌های موجود
       را پردازش می‌کند (onAttributesAdded). پس مستقیماً x-navigate.hover
       می‌گذاریم تا hover-prefetch (۶۰ms) + press-prefetch + کلیکِ نتیجه از
       خودِ Alpine فعال شود — ناوبری از cache: ~۱۸ms به‌جای ~۵۵۰ms. */
    var NAV_ATTR = 'x-navigate.hover';

    /* لینک‌هایی که رفتار JS خاصی رویشان سوار است — هرگز wire:navigate نگیرند.
       فاز ۱۹۶ — [data-bs-toggle] کلی به تفکیک مقدار شکسته شد: tooltip و
       popoverِ هاوری با کلیک کاری ندارند؛ مستثنی‌کردنشان باعث می‌شد لینک‌های
       محصول داخل اسلایدرها (که data-bs-toggle="tooltip" دارند) علامت SPA
       نگیرند و با سوایپ/کلیک، ناوبری بومیِ کامل رخ می‌داد. فقط مقادیرِ
       کلیک‌محور بوت‌استرپ مستثنی می‌مانند. */
    var JS_HANDLED_SELECTOR = [
        '.showSubMenu',
        '[data-no-navigate]',
        '[data-bs-toggle="dropdown"]',
        '[data-bs-toggle="collapse"]',
        '[data-bs-toggle="modal"]',
        '[data-bs-toggle="tab"]',
        '[data-bs-toggle="pill"]',
        '[data-bs-toggle="button"]',
        '[data-bs-toggle="popover"]',
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

        /* فاز ۱۹۵ — لینک خروج و هر اکشن مخربِ نشست: هرگز علامت نمی‌خورد */
        if (NEVER_NAVIGATE_HREF.test(href)) { return false; }

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

        /* فاز ۱۹۵ — دلتای نهایی «قبل از» خاموش‌کردن tracking شمرده شود:
           ====================================================================
           ترتیب قبلی (tracking=false سپس trackMove) باعث می‌شد trackMove به‌خاطر
           گاردِ !tracking زود برگردد و جابه‌جایی بین آخرین touchmove و touchend
           هرگز حساب نمی‌شد. در ژست‌های سریع (flick) یا کوتاه که touchmove کمی
           دارند — و مهم‌تر: در حرکت‌های زیر آستانهٔ touch-slop مرورگر که touchmove
           اصلاً dispatch نمی‌شود — travelX صفر می‌ماند و سوایپ مثل «تپ» پردازش
           می‌شد ⇒ کلیکِ پایانی ناوبری به تک‌صفحهٔ محصول می‌کرد. */
        if (typeof x === 'number') { trackMove(x, y); }
        dragState.tracking = false;

        dragState.lastGestureEnd = Date.now();

        /* سوایپِ واقعی = جابه‌جایی افقی آشکار — آستانهٔ ۶px و غلبهٔ افقی بر عمودی */
        var dy = Math.abs(dragState.lastY - dragState.y);

        if (dragState.travelX >= 6 && dragState.travelX >= dy) {
            dragState.suppressUntil = Date.now() + 500;
        }
    }

    /* فاز ۱۹۴ — آیا این gesture باید بلعیده شود؟ (mouseup/click پس از سوایپ) */
    function shouldSuppressGesture() {
        if (Date.now() < dragState.suppressUntil) { return true; }

        /* فالبک: click/mouseupِ تاچی که خیلی زود بعد از ژستِ دارای حرکت افقی dispatch
           شده ولی پنجرهٔ suppress فعال نیست (تأخیر dispatch در iOS/برخی اندرویدها) */
        if (dragState.isTouch
            && dragState.lastGestureEnd
            && Date.now() - dragState.lastGestureEnd < 700
            && dragState.travelX >= 6) {
            return true;
        }

        return false;
    }

    function suppressDragClick() {
        return Date.now() < dragState.suppressUntil;
    }

    /* فاز ۱۹۶ — مهارِ مبتنی بر خودِ Swiper (لایهٔ قطعی ضد «سوایپ ← پرش به تک‌صفحه»):
       ====================================================================
       حساب‌وکتاب ژست (travelX/آستانه) هیچ‌وقت صددرصد نمی‌شود: سوایپ‌های چند
       پیکسلی که Swiper با shortSwipes می‌فهمد ولی زیر آستانهٔ ما هستند و
       رخدادهای pointer که قبل از touchend می‌آیند، از فیلترهای قبلی می‌گذشتند
       و کاربر باز «با اسلاید کردن به تک‌صفحه می‌رفت».
       راه‌حل ریشه‌ای: خودِ Swiper بهترین داور است — اگر slideChange تا ۹۰۰ms
       بعد از touchStart رخ داده باشد، این ژست قطعاً «سوایپِ تغییردهندهٔ
       اسلاید» بوده است ⇒ پنجرهٔ مهار ۷۰۰ms باز می‌شود و هر mouseup/clickِ
       بعدی (مسیر ناوبری x-navigate و ناوبری بومی) بلعیده می‌شود.
       در مقابل، تپ سالم هیچ slideChangeای نمی‌دهد ⇒ هرگز مهار نمی‌شود ⇒
       تپ روی محصولات اسلایدر کاملاً سالم می‌ماند (بدون تنشِ آستانه).
       app.js این تابع را از هندلرهای touchStart/slideChange همهٔ اسلایدرها
       (saniarSwiper) صدا می‌زند. */
    window.saniarSuppressAfterSwipe = function () {
        dragState.suppressUntil = Date.now() + 700;
        dragState.lastGestureEnd = Date.now();
    };

    function interceptDragEvents() {
        if (window.__saniarSpaDragBound) { return; }
        window.__saniarSpaDragBound = true;

        /* فاز ۱۹۶ — pointerdown (فاز capture): رخدادهای pointer «قبل از» touch
           می‌آیند. با ثبتِ ژست لمسی در همین مرحله، هر مسیری که از pointer
           تغذیه شود (از جمله مکانیزم mousedown→mouseup لایووایر در مرورگرهایی
           که رخدادهای ترکیبی را زودتر می‌فرستند) از لحظهٔ صفر ردیابی می‌شود. */
        document.addEventListener('pointerdown', function (e) {
            if (e.pointerType === 'touch') {
                dragState.isTouch = true;
                registerDrag(e.clientX, e.clientY);
            }
        }, true);

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

        /* فاز ۱۹۵ — فالبک pointermove برای ژست‌های لمسی:
           ====================================================================
           بعضی محیط‌ها در حرکت‌های زیر آستانهٔ touch-slop فقط pointermove
           می‌دهند و touchmove اصلاً dispatch نمی‌شود (تأییدشده روی فروشگاه live)؛
           بدون این فالبک، مسافتِ سوایپ‌های کوتاه صفر می‌ماند و کلیکِ پایانیِ
           ژست ناوبری می‌کرد. برای pointerType='touch' انباشته می‌شود؛ ماوس از
           mousemove موجود تغذیه می‌شود تا دوبار شمرده نشود. */
        document.addEventListener('pointermove', function (e) {
            if (dragState.tracking && e.pointerType === 'touch') {
                trackMove(e.clientX, e.clientY);
            }
        }, { capture: true, passive: true });

        /* فاز ۱۹۴ — preventDefault روی touchendِ سوایپ:
           ====================================================================
           ریشهٔ واقعیِ «با سوایپ به تک‌صفحهٔ محصول می‌رود» این بود که دایرکتیو
           x-navigate لایووایر ناوبری را با جفت «mousedown → mouseup» انجام می‌دهد
           (whenThisLinkIsPressed: در mousedown هندلر mouseup مسلح می‌شود و در
           mouseup ناوبری start می‌شود) — نه با کلیک!
           در سوایپِ لمسی، مرورگر mouseup ترکیبی را «روی خودِ لینکِ محصول» دیسپچ
           می‌کند (کارت‌های اسلایدر بزرگ‌اند و انگشت معمولاً روی همان کارت بلند
           می‌شود) ⇒ هندلر مسلح‌شده ناوبری را شروع می‌کرد در حالی که
           suppressorِ کلیکِ فاز ۱۹۱/۱۹۲ هرگز آن را نمی‌دید.
           preventDefault روی touchend از تولیدِ همهٔ رخدادهای ماوسِ ترکیبی
           (mousedown/mouseup/click) جلوگیری می‌کند — رفع در منبع.
           listener عمداً غیر-passive است. */
        document.addEventListener('touchend', function (e) {
            var t = e.changedTouches && e.changedTouches[0];
            finishDrag(t ? t.clientX : undefined, t ? t.clientY : undefined);

            if (dragState.suppressUntil && Date.now() < dragState.suppressUntil) {
                try { e.preventDefault(); } catch (err) { /* بی‌اثر */ }
            }
        }, true);

        /* فاز ۱۹۶ — pointerup (فاز capture): در دستگاه‌های لمسی، pointerup «قبل
           از» touchend می‌آید؛ اگر مکانیزم ناوبری از رخدادهای pointer تغذیه
           شود، مهارِ ما در touchend دیر است. اینجا در همان لحظهٔ pointerup،
           اگر ژستِ در جریانِ لمسی جابه‌جایی افقی آشکار داشته باشد، مهار قطعی
           می‌شود: preventDefault (لغو رخدادهای ماوسِ ترکیبی) +
           stopImmediatePropagation (خفن‌کردن هر هندلر مسلح‌شده روی المان). */
        document.addEventListener('pointerup', function (e) {
            if (e.pointerType !== 'touch') { return; }

            finishDrag(e.clientX, e.clientY);

            if (shouldSuppressGesture()) {
                try { e.preventDefault(); } catch (err) { /* بی‌اثر */ }
                e.stopImmediatePropagation();
            }
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

        /* فاز ۱۹۴ — mouseup در فاز capture زودتر از هر هندلر دیگری دیده می‌شود.
           دراگ ماوسی روی اسلایدرها (یا mouseup ترکیبیِ تاچ که با avoid بالا از
           تولیدش جلوگیری نشده) هرگز نباید به هندلر مسلح‌شدهٔ x-navigate برسد؛
           وگرنه همان پرش به تک‌صفحه اتفاق می‌افتد. روی ماوس فقط داخل اسلایدرها
           (تا انتخاب متن عادی بیرون اسلایدر آسیب نبیند). */
        document.addEventListener('mouseup', function (e) {
            finishDrag(e.clientX, e.clientY);

            if (shouldSuppressGesture()) {
                if (!dragState.isTouch) {
                    var el = e.target && e.target.closest ? e.target.closest('.swiper, .owl-carousel, .amazing-slider, .product-slider, .swiper-container, #mainCarousel, #carouselExampleIndicators') : null;
                    if (!el) { return; }
                }
                try { e.preventDefault(); } catch (err) { /* بی‌اثر */ }
                e.stopImmediatePropagation();
            }
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
            /* فاز ۱۹۴ — suppress یکپارچهٔ gesture (پنجرهٔ ۵۰۰ms + فالبک ۷۰۰ms تاچ)؛
               روی تاچ همه‌جا فعال؛ روی ماوس فقط داخل اسلایدرها (انتخاب متن بیرون
               اسلایدر آسیب نبیند). */
            /* فاز ۱۹۶ — شرط ماوس از «!e.pointerType» به «e.pointerType !== 'touch'»
               تغییر کرد: در کرومِ مدرن click خودش PointerEvent با pointerType
               'mouse' است؛ شرط قبلی این حالت را از چکِ اسلایدر معاف می‌کرد و
               کلیکِ بعد از درگ/انتخابِ متنِ بیرون اسلایدر هم بلعیده می‌شد.
               حالا همهٔ کلیک‌های غیرلمسی فقط داخل اسلایدرها مهار می‌شوند. */
            if (shouldSuppressGesture()) {
                if (!dragState.isTouch && e.pointerType !== 'touch') {
                    var sliderBox = a.closest('.swiper, .owl-carousel, .amazing-slider, .product-slider, .swiper-container, #mainCarousel, #carouselExampleIndicators');
                    if (!sliderBox) { return; }
                }
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

    /* فاز ۱۹۳ — نوسازی هندلرهای delegated جی‌کوئریِ document بعد از هر ناوبری
       ====================================================================
       علامت اصلی: بعد از یک ناوبری SPA، هندلرهای delegated که در لود اول روی
       document بایند شده‌اند (.mask-handler «مشاهده بیشتر»، .add-to-cart و …)
       در کشِ jQuery دیده می‌شوند ولی با کلیک واقعی دیگر اجرا نمی‌شوند — حتی
       هندلر تازه‌ای که بعد از ناوبری بایند شود کار می‌کند.
       راه‌حل: همهٔ هندلرهای document را با off+on (همان تابع، همان سلکتور،
       همان namespace) یک دور برداشت/نصب دوباره می‌کنیم تا شنوندهٔ ریشهٔ
       dispatch جی‌کوئری مطمئناً به document فعلی وصل باشد. همین الگوی
       off+on که اسکریپت‌های صفحه (show.js و …) دارند، این‌جا به‌صورت سراسری
       و idempotent اجرا می‌شود؛ هیچ handler جدیدی اضافه نمی‌شود و ترتیب
       منطقی بایندها حفظ می‌ماند. */
    function refreshJqueryDocumentHandlers() {
        if (!window.jQuery || !window.jQuery._data) { return; }

        var all;
        try { all = window.jQuery._data(document, 'events'); } catch (e) { return; }
        if (!all) { return; }

        Object.keys(all).forEach(function (type) {
            var list = all[type];
            if (!list || !list.length) { return; }

            list.slice().forEach(function (h) {
                if (!h || typeof h.handler !== 'function') { return; }

                var types = h.type || type;
                if (h.namespace) { types += '.' + h.namespace; }
                var selector = h.selector || null;

                try {
                    if (selector) {
                        window.jQuery(document).off(types, selector, h.handler);
                        window.jQuery(document).on(types, selector, h.data, h.handler);
                    } else {
                        window.jQuery(document).off(types, null, h.handler);
                        window.jQuery(document).on(types, null, h.data, h.handler);
                    }
                } catch (e) { /* هیچ نوسازی نباید ناوبری را بشکند */ }
            });
        });
    }

    function onNavigated() {
        markLinks(document);   /* لینک‌های تازهٔ صفحهٔ جدید */
        normalizeHead();       /* ضدتکرار متا/کنonical برای SEO */
        runReinits();          /* initهای تم روی DOM تازه */
        refreshJqueryDocumentHandlers(); /* فاز ۱۹۳ — احیای هندلرهای document */
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
