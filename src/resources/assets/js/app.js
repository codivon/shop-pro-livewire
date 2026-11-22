/* =========================================================================
   app.js — مقداردهی اسلایدرهای Swiper تمپلیت سانیار
   همهٔ مقداردهی‌ها «اسکوپ‌شده روی خود نمونه» شدند:
   قبلاً همهٔ اسلایدرها با سلکتور سراسری (.swiper-button-next / .swiper-pagination)
   مقداردهی می‌شدند؛ در نتیجه هر دکمهٔ ناوبری، «همهٔ» اسلایدرهای صفحه را
   جلو می‌برد و با وجود چند اسلایدر در صفحهٔ اصلی، رفتارها قاطی می‌شد.
   حالا هر نمونه فقط گره‌های داخل خودش را کنترل می‌کند.

   فاز ۱۸۵ (SPA): همهٔ مقداردهی‌ها داخل saniarInitSwipers() قرار گرفتند تا
   بعد از هر ناوبری wire:navigate (رویداد livewire:navigated) هم روی گره‌های
   تازه اجرا شوند؛ گارد data-swiper-bound مانع مقداردهی دوبارهٔ همان نمونه است.
   ========================================================================= */

/* فاز ۱۹۱ — بازگشت مرورگر (back/forward) اسلایدرها را «بی‌پاسخ» می‌کند:
   ====================================================================
   Livewire روی back/forward صفحهٔ قبلی را بازسازی می‌کند؛ در این حالت نمونهٔ
   Swiper ممکن است روی همان گره‌ها زنده بماند ولی وضعیت داخلی‌اش (progress,
   realIndex, چرخهٔ loop) بعد از restore خراب می‌ماند — اسلایدر «هست» ولی
   فلش‌ها/سوایپ/اتوپلی هیچ اثری ندارند (تأییدشده روی فروشگاه live).

   راه‌حل نهایی: شمارندهٔ «چرخهٔ ناوبری» — هر اسلایدر فقط یک‌بار در هر چرخهٔ
   ناوبری مقداردهی می‌شود؛ با بازگشت به صفحهٔ قبلی، token اسلایدر قدیمی است
   ⇒ پاک‌سازی کامل markup و مقداردهی تازه انجام می‌شود. */
var saniarNavSeq = 0;
document.addEventListener('livewire:navigated', function () { saniarNavSeq++; });

function saniarCleanupSwiperMarkup(el) {
    try { if (el.swiper) { el.swiper.destroy(true, true); } } catch (e) { /* */ }

    el.querySelectorAll('.swiper-slide-duplicate').forEach(function (n) { n.remove(); });

    var wrapper = el.querySelector('.swiper-wrapper');
    if (wrapper) { wrapper.removeAttribute('style'); }

    el.querySelectorAll('.swiper-slide').forEach(function (s) {
        s.removeAttribute('style');
        s.classList.remove(
            'swiper-slide-active', 'swiper-slide-next', 'swiper-slide-prev',
            'swiper-slide-duplicate-active', 'swiper-slide-duplicate-next',
            'swiper-slide-duplicate-prev', 'swiper-slide-visible', 'swiper-slide-fully-visible'
        );
    });

    var pg = el.querySelector('.swiper-pagination');
    if (pg) { pg.innerHTML = ''; }

    el.querySelectorAll('.swiper-notification').forEach(function (n) { n.remove(); });
}

/* true = این اسلایدر در همین چرخهٔ ناوبری قبلاً مقداردهی شده و سالم است */
function saniarSliderIsLive(el) {
    if (el.dataset.swiperNavToken === String(saniarNavSeq)
        && el.swiper && el.swiper.el === el && el.isConnected) {
        return true; /* دوبار init در همان چرخه جلوگیری می‌شود */
    }

    /* بازگشت به صفحهٔ قبلی یا markup قدیمی — ردپاهای ساختاری پاک شود */
    saniarCleanupSwiperMarkup(el);
    delete el.dataset.swiperBound;

    return false;
}

/* مقداردهی هر اسلایدر: token چرخهٔ ناوبری ذخیره می‌شود */
function saniarMarkSliderBound(el) {
    el.dataset.swiperBound = '1';
    el.dataset.swiperNavToken = String(saniarNavSeq);
}

/* ساخت نمونهٔ Swiper با گره‌های اسکوپ‌شدهٔ خودِ المنت */
function saniarSwiper(el, config) {
    if (!el || !window.Swiper) { return null; }
    config = config || {};
    config.pagination = config.pagination || {};
    config.navigation = config.navigation || {};
    if (config.pagination.el === undefined) { config.pagination.el = el.querySelector('.swiper-pagination'); }
    if (config.navigation.nextEl === undefined) { config.navigation.nextEl = el.querySelector('.swiper-button-next'); }
    if (config.navigation.prevEl === undefined) { config.navigation.prevEl = el.querySelector('.swiper-button-prev'); }
    if (config.pagination.el) { config.pagination.clickable = true; }

    /* فاز ۱۹۶ — مهار مبتنی بر خودِ Swiper (ضد «سوایپ ← پرش به تک‌صفحه»):
       ====================================================================
       حساب‌وکتاب ژست در saniar-spa.js هیچ‌وقت صددرصد نمی‌شود؛ اما خودِ Swiper
       دقیقاً می‌داند کی ژست «سوایپِ تغییردهندهٔ اسلاید» بوده است. با این دو
       هندلر: هر slideChange که تا ۹۰۰ms پیش از آن touchStart خورده باشد ⇒
       window.saniarSuppressAfterSwipe (در saniar-spa.js) پنجرهٔ ۷۰۰ms مهارِ
       mouseup/click را باز می‌کند. تپ سالم هرگز slideChange نمی‌دهد ⇒
       هرگز مهار نمی‌شود. هندلرهای شخصی‌سازی‌شدهٔ هر اسلایدر هم حفظ می‌شوند. */
    var userOn = config.on || {};
    var userTouchStart = userOn.touchStart;
    var userSlideChange = userOn.slideChange;

    config.on = userOn;
    config.on.touchStart = function () {
        try { this.__snrTouchAt = Date.now(); } catch (e) { /* */ }
        if (typeof userTouchStart === 'function') { return userTouchStart.apply(this, arguments); }
    };
    config.on.slideChange = function () {
        try {
            if (this.__snrTouchAt
                && (Date.now() - this.__snrTouchAt) < 900
                && typeof window.saniarSuppressAfterSwipe === 'function') {
                window.saniarSuppressAfterSwipe();
            }
        } catch (e) { /* هیچ مهاری نباید اسلایدر را بشکند */ }

        if (typeof userSlideChange === 'function') { return userSlideChange.apply(this, arguments); }
    };

    return new Swiper(el, config);
}

window.saniarInitSwipers = function () {
    if (!window.Swiper) { return; }

/* ========= اسلایدر اصلی صفحهٔ اصلی (js-home-slider کلاس شد تا چند
   نمونهٔ main-slider با id یکتا روی یک صفحه مقداردهی شوند) ========= */
document.querySelectorAll('.js-home-slider').forEach(function (el) {
    if (saniarSliderIsLive(el)) { return; }
    saniarMarkSliderBound(el);
    /* فاز ۲۰۲ — ریشهٔ «اسلایدر در گوشی اسلاید نمی‌خورد»: افکت fade است؛
       در fade هیچ حرکت افقی دیده نمی‌شود و ژست سوایپ لمسی عملاً بی‌اثر حس می‌شود.
       در موبایل/تبلت افکت slide (حرکت واقعی) و در دسکتاپ fade طبق تمپلیت. */
    var isTouchLayout = window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches;
    saniarSwiper(el, {
        spaceBetween: 30,
        centeredSlides: true,
        loop: el.querySelectorAll('.swiper-slide').length > 1,
        loopAdditionalSlides: 6, /* فاز ۱۹۶ — به‌درخواست کاربر */
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        effect: isTouchLayout ? "slide" : "fade",
        grabCursor: true,
    });
});

/* ============== پیشنهاد لحظه‌ای ============== */
document.querySelectorAll('.js-moment-slider').forEach(function (el) {
    if (saniarSliderIsLive(el)) { return; }
    saniarMarkSliderBound(el);
    var progressBar = el.querySelector('.swiper-progress-bar');
    saniarSwiper(el, {
        slidesPerView: 1,
        spaceBetween: 30,
        loop: el.querySelectorAll('.swiper-slide').length > 1,
        loopAdditionalSlides: 6, /* فاز ۱۹۶ — به‌درخواست کاربر */
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        speed: 500,
        on: {
            init: function () {
                if (!progressBar) { return; }
                progressBar.classList.remove('animate', 'active');
                progressBar.classList.add('animate', 'active');
            },
            slideChangeTransitionStart: function () {
                if (!progressBar) { return; }
                progressBar.classList.remove('animate', 'active');
                progressBar.classList.add('active');
            },
            slideChangeTransitionEnd: function () {
                if (progressBar) { progressBar.classList.add('animate'); }
            }
        }
    });
});

/* ============== اسلایدر شگفت انگیز (امازینگ) ============== */
/* جفت اصلی/فهرست کنار هم در یک ریشهٔ .amazing-slider مقداردهی می‌شوند */
document.querySelectorAll('.amazing-slider').forEach(function (root) {
    if (saniarSliderIsLive(root)) { return; }
    saniarMarkSliderBound(root);

    var linkEl = root.querySelector('.amazing-slider-link');
    var sliderEl = root.querySelector('.amazing-slider-slider');
    if (!linkEl || !sliderEl) { return; }

    var thumbs = saniarSwiper(linkEl, {
        spaceBetween: 10,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesProgress: true,
        allowTouchMove: false,
    });

    var mainConfig = {
        effect: "fade",
        speed: 1000,
        loop: sliderEl.querySelectorAll('.swiper-slide').length > 1,
        loopAdditionalSlides: 6, /* فاز ۱۹۶ — به‌درخواست کاربر */
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        spaceBetween: 10,
    };

    /* اتصال thumbs — فقط از طریق کانفیگ (API درست Swiper) */
    if (thumbs) { mainConfig.thumbs = { swiper: thumbs }; }

    saniarSwiper(sliderEl, mainConfig);
});

/* ========= اسلایدر ردیف محصولات (چند نمونه در صفحه) ========= */
document.querySelectorAll('.product-slider-swiper').forEach(function (el) {
    if (saniarSliderIsLive(el)) { return; }
    saniarMarkSliderBound(el);

    /* فاز ۱۹۶ — loop + loopAdditionalSlides به‌درخواست کاربر:
       با loop، سوایپِ عبور از انتهای اسلایدر به‌نرمی می‌پیچد (به‌جای گیر
       و لرزش روی مرز) و با slidesPerView=۵ فقط وقتی اسلاید کافی باشد فعال
       می‌شود تا Swiper 11 برای چرخش به اسلاید خالی نیاز پیدا نکند. */
    var productSlides = el.querySelectorAll('.swiper-slide').length;

    saniarSwiper(el, {
        slidesPerView: 5,
        spaceBetween: 10,
        loop: productSlides > 5,
        loopAdditionalSlides: 6, /* عدد ۶ طبق درخواست کاربر */
        breakpoints: {
            100:  { slidesPerView: 1, spaceBetween: 20 },
            576:  { slidesPerView: 2, spaceBetween: 20 },
            768:  { slidesPerView: 2, spaceBetween: 20 },
            1024: { slidesPerView: 3, spaceBetween: 20 },
            1200: { slidesPerView: 4, spaceBetween: 20 },
            1400: { slidesPerView: 5, spaceBetween: 20 },
        },
    });
});

/* ========= اسلایدر برندهای صفحهٔ اصلی (فاز ۱۹۱ — ویجت بازطراحی‌شده) ========= */
/* پخش خودکار + سرعت از تنظیمات خود ابزارک می‌آید (data-autoplay / data-autoplay-speed) */
document.querySelectorAll('.saniar-brands__swiper').forEach(function (el) {
    if (saniarSliderIsLive(el)) { return; }
    saniarMarkSliderBound(el);

    var autoplayEnabled = el.dataset.autoplay === '1';
    var autoplaySpeed = parseInt(el.dataset.autoplaySpeed || '3000', 10);
    if (!isFinite(autoplaySpeed) || autoplaySpeed < 800) { autoplaySpeed = 3000; }

    saniarSwiper(el, {
        rtl: true,
        slidesPerView: 6,
        spaceBetween: 12,
        loop: el.querySelectorAll('.swiper-slide').length > 6,
        loopAdditionalSlides: 6, /* فاز ۱۹۶ — به‌درخواست کاربر */
        autoplay: autoplayEnabled
            ? { delay: autoplaySpeed, disableOnInteraction: false, pauseOnMouseEnter: true }
            : false,
        breakpoints: {
            100:  { slidesPerView: 2, spaceBetween: 10 },
            576:  { slidesPerView: 3, spaceBetween: 10 },
            768:  { slidesPerView: 4, spaceBetween: 12 },
            992:  { slidesPerView: 5, spaceBetween: 12 },
            1200: { slidesPerView: 6, spaceBetween: 12 },
        },
    });
});

/* ============ گالری تصاویر صفحهٔ محصول ============ */
document.querySelectorAll('.product-gallery-thumb').forEach(function (el) {
    if (saniarSliderIsLive(el)) { return; }
    saniarMarkSliderBound(el);
    saniarSwiper(el, {
        spaceBetween: 10,
        slidesPerView: 4,
        freeMode: true,
        watchSlidesProgress: true,
        breakpoints: {
            320: { slidesPerView: 3, spaceBetween: 10 },
            400: { slidesPerView: 4, spaceBetween: 10 },
        },
    });
});

document.querySelectorAll('.product-gallery').forEach(function (el) {
    if (saniarSliderIsLive(el)) { return; }
    saniarMarkSliderBound(el);

    var thumbEl = document.querySelector('.product-gallery-thumb');
    var thumbsInstance = thumbEl && thumbEl.swiper ? thumbEl.swiper : null;

    var galleryConfig = {
        spaceBetween: 10,
        zoom: {
            maxRatio: 3,
            minRation: 1
        },
    };

    if (thumbsInstance) { galleryConfig.thumbs = { swiper: thumbsInstance }; }

    saniarSwiper(el, galleryConfig);
});

/* ========= اسلایدر افقی دسته‌بندی‌ها (free-mode) ========= */
document.querySelectorAll('.free-mode').forEach(function (el) {
    if (saniarSliderIsLive(el)) { return; }
    saniarMarkSliderBound(el);
    saniarSwiper(el, {
        slidesPerView: "auto",
        spaceBetween: 10,
        freeMode: true,
    });
});
};

/* اجرای اولیه + ثبت برای ناوبری SPA */
window.saniarInitSwipers();
(window.saniarReinit = window.saniarReinit || []).push(function () { window.saniarInitSwipers(); });
