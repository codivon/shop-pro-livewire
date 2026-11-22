/**
 * SaniarTheme — theme-header.js
 * منطق هدر: جستجوی زنده (پنل نتایج) + دکمه جستجو
 * بدون هیچ کد JS داخل فایل‌های بلید.
 */
(function () {
    'use strict';

    function initHeaderSearch() {
        /* =========================================================
           جستجوی لحظه‌ای پیشرفتهٔ سانیار
           - فوکوس روی جستجو → پنل «جستجوهای اخیر + پرطرفدار + بنر»
           - تایپ (۲ کاراکتر به بعد) → نتایج زنده: دسته‌بندی/برند/محصول
           - همه از GET /search/suggest (JSON + کش سمت سرور)
           - جستجوهای اخیر در localStorage مرورگر کاربر
           ========================================================= */
        var suggestUrl = (document.querySelector('meta[name="saniar-suggest"]') || {}).content || '/search/suggest';

        /* ---------- ابزارها ---------- */
        function escapeHtml(text) {
            return String(text == null ? '' : text)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        function getRecent() {
            try {
                var list = JSON.parse(localStorage.getItem('saniarRecentSearches') || '[]');
                return Array.isArray(list) ? list : [];
            } catch (e) {
                return [];
            }
        }

        function pushRecent(term) {
            term = (term || '').trim();
            if (!term) { return; }
            var list = getRecent().filter(function (t) { return t !== term; });
            list.unshift(term);
            try { localStorage.setItem('saniarRecentSearches', JSON.stringify(list.slice(0, 6))); } catch (e) { /* */ }
        }

        /* ---------- رندر ---------- */
        function chip(term, withIcon) {
            return '<button type="button" class="sr2-chip js-sr2-term" data-term="' + escapeHtml(term) + '">' +
                (withIcon ? '<i class="bi bi-arrow-left-right"></i>' : '') +
                '<span>' + escapeHtml(term) + '</span>' +
                '</button>';
        }

        function resultRow(item, icon, withThumb, contextPrefix) {
            var thumb = withThumb
                ? (item.image
                    ? '<span class="sr2-thumb"><img src="' + escapeHtml(item.image) + '" loading="lazy" alt=""></span>'
                    : '<span class="sr2-thumb sr2-thumb-empty"><i class="bi bi-box"></i></span>')
                : '<span class="sr2-row-icon"><i class="bi ' + icon + '"></i></span>';

            return '<a href="' + escapeHtml(item.url) + '" class="sr2-row">' +
                '<span class="sr2-row-main">' + thumb +
                '<span class="sr2-row-title">' + escapeHtml(item.title) + '</span></span>' +
                (item.context ? '<span class="sr2-row-context">' + escapeHtml((contextPrefix || '') + item.context) + '</span>' : '') +
                '</a>';
        }

        /* فاز ۲۰۲ پیوست — اسلایدر جستجو (گروه «اسلایدر جستجو»):
           قالب #saniarSearchPromo در هدر رندر شده؛ اینجا محتوای آن داخل
           حالت خالیِ پنل جستجو به‌صورت ستون تبلیغاتی چرخان کلون می‌شود. */
        var promoTimer = null;
        function promoSource() {
            return document.getElementById('saniarSearchPromo');
        }
        function promoHtml() {
            var src = promoSource();
            if (!src || !src.children.length) { return ''; }
            return '<div class="sr2-promo" id="saniarSearchPromoPanel">' +
                '<div class="sr2-promo-track">' + src.innerHTML + '</div>' +
                '</div>';
        }
        function startPromoRotation(panel) {
            if (promoTimer) { window.clearInterval(promoTimer); promoTimer = null; }
            var track = panel.querySelector('.sr2-promo-track');
            if (!track || track.children.length < 2) { return; }
            promoTimer = window.setInterval(function () {
                var active = track.querySelector('.snr-search-promo-item.is-active');
                if (!active) {
                    active = track.children[0];
                    if (active) { active.classList.add('is-active'); }
                    return;
                }
                active.classList.remove('is-active');
                var next = active.nextElementSibling || track.children[0];
                if (next) { next.classList.add('is-active'); }
            }, 3800);
        }
        function stopPromoRotation() {
            if (promoTimer) { window.clearInterval(promoTimer); promoTimer = null; }
        }
        /* خروجی: اگر بنر جستجو موجود باشد، محتوای اصلی در ستون کنار ستون تبلیغاتی می‌نشیند */
        function withPromoLayout(innerHtml) {
            var promo = promoHtml();
            if (!promo) { return innerHtml; }
            return '<div class="sr2-with-promo">' +
                '<div class="sr2-main-col">' + innerHtml + '</div>' +
                promo +
                '</div>';
        }

        function renderEmptyState(panel, data) {
            var recent = getRecent();
            var popular = (data && data.popular) || [];
            var banner = data && data.banner;
            var html = '';
            var hasSection = false;

            if (recent.length) {
                hasSection = true;
                html += '<div class="sr2-section"><div class="sr2-heading">جستجوهای اخیر</div><div class="sr2-chips">';
                recent.forEach(function (t) { html += chip(t, false); });
                html += '</div></div>';
            }

            if (popular.length) {
                hasSection = true;
                html += '<div class="sr2-section"><div class="sr2-heading">جستجوهای پرطرفدار</div><div class="sr2-chips">';
                popular.forEach(function (t) { html += chip(t, true); });
                html += '</div></div>';
            }

            if (banner) {
                hasSection = true;
                /* فاز ۱۷۷ — بنر با تصویر دلخواه مدیر (اگر تصویر تنظیم شده باشد)
                   یا بنر متنی؛ فقط وقتی مدیر در تنظیمات مقدار داده باشد می‌آید */
                var bannerImage = banner.image || '';
                if (bannerImage) {
                    html += '<a href="' + escapeHtml(banner.link || '#') + '" class="sr2-banner sr2-banner-image">' +
                        '<img src="' + escapeHtml(bannerImage) + '" alt="' + escapeHtml(banner.title || '') + '" loading="lazy">' +
                        '</a>';
                } else {
                    html += '<a href="' + escapeHtml(banner.link || '#') + '" class="sr2-banner">' +
                        '<span class="sr2-banner-text">' +
                        '<span class="sr2-banner-title">' + escapeHtml(banner.title || '') + '</span>' +
                        '<span class="sr2-banner-sub">' + escapeHtml(banner.subtitle || '') + '</span>' +
                        '</span>' +
                        '<i class="bi bi-arrow-left"></i>' +
                        '</a>';
                }
            }

            /* فاز ۲۰۲ پیوست — اسلایدر جستجو موجود باشد حتی بدون بخش‌ها هم
               نمایش داده می‌شود (تنها محتوای پنلِ خالی همان ستون تبلیغاتی است) */
            var promo = promoHtml();
            var inner = hasSection ? html : '<div class="sr2-empty">چیزی برای نمایش نیست</div>';
            panel.innerHTML = promo ? withPromoLayout(inner) : inner;
            panel.classList.add('open');
            if (promo) { startPromoRotation(panel); } else { stopPromoRotation(); }
        }

        function renderResults(panel, data, q) {
            stopPromoRotation(); /* فاز ۲۰۲ پیوست — در حالت نتایج، چرخش تبلیغ متوقف شود */
            var html = '';

            if (data.categories && data.categories.length) {
                html += '<div class="sr2-section"><div class="sr2-heading">دسته‌بندی‌ها</div>';
                data.categories.forEach(function (item) { html += resultRow(item, 'bi-grid', false, 'در فروشی '); });
                html += '</div>';
            }

            if (data.brands && data.brands.length) {
                html += '<div class="sr2-section"><div class="sr2-heading">برندها</div>';
                data.brands.forEach(function (item) {
                    html += resultRow({ title: item.title, url: item.url, context: 'همه کالاهای برند ' + item.title }, 'bi-award', false, '');
                });
                html += '</div>';
            }

            if (data.products && data.products.length) {
                html += '<div class="sr2-section"><div class="sr2-heading">محصولات</div>';
                data.products.forEach(function (item) { html += resultRow(item, 'bi-box', true, 'در فروشی '); });
                html += '</div>';
            }

            if (data.all) {
                html += '<a href="' + escapeHtml(data.all) + '" class="sr2-row sr2-all">' +
                    '<span class="sr2-row-main"><span class="sr2-row-icon"><i class="bi bi-search"></i></span>' +
                    '<span class="sr2-row-title">مشاهده همهٔ نتایج «' + escapeHtml(q) + '»</span></span>' +
                    '<span class="sr2-row-context"><i class="bi bi-arrow-left"></i></span>' +
                    '</a>';
            }

            panel.innerHTML = html || '<div class="sr2-empty">نتیجه‌ای برای «' + escapeHtml(q) + '» یافت نشد</div>';
            panel.classList.add('open');
        }

        /* ---------- اتصال به هر فرم جستجو (دسکتاپ + موبایل) ---------- */
        document.querySelectorAll('.search-form').forEach(function (form) {
            var input = form.querySelector('input[name="q"]');
            if (!input || input.dataset.sr2Bound === '1') { return; }
            input.dataset.sr2Bound = '1';

            var panel = document.createElement('div');
            panel.className = 'saniar-search-panel';
            form.appendChild(panel);

            var hidePanel = function () {
                stopPromoRotation();
                panel.classList.remove('open');
                panel.innerHTML = '';
            };

            var timer = null;
            var controller = null;

            /* فاز ۱۸۵ — در صورت خطای شبکه/۵۰۲، یک تلاش مجدد بی‌صدا انجام می‌شود */
            var fetchSuggest = function (term, done, isRetry) {
                if (controller && controller.abort) { controller.abort(); }
                if (window.AbortController) { controller = new AbortController(); }

                fetch(suggestUrl + '?q=' + encodeURIComponent(term), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller ? controller.signal : undefined
                })
                    .then(function (res) { return res.ok ? res.json() : Promise.reject(res); })
                    .then(done)
                    .catch(function () {
                        /* ۵۰۲/خطای شبکه → یک تلاش مجدد (فقط یک‌بار) */
                        if (!isRetry) {
                            window.setTimeout(function () { fetchSuggest(term, done, true); }, 600);
                        }
                    });
            };

            /* فوکوس / کلیک با ورودی خالی → پنل اخیر + پرطرفدار */
            var showIdlePanel = function () {
                if ((input.value || '').trim().length >= 2) { return; }
                fetchSuggest('', function (data) {
                    if ((input.value || '').trim().length >= 2) { return; }
                    renderEmptyState(panel, data);
                });
            };

            input.addEventListener('focus', showIdlePanel);
            input.addEventListener('click', showIdlePanel);

            /* تایپ → نتایج زنده
               دیبانس ۳۰۰ میلی‌ثانیه‌ای:
               تا وقتی کاربر در حال تایپ است هیچ درخواستی زده نمی‌شود؛
               جستجو فقط ۳۰۰ms بعد از آخرین کاراکتر اجرا می‌شود و
               پیام «در حال جستجو…» هم فقط پس از همین مکث نمایش داده می‌شود
               (قبلاً با هر بار فشار دادن کلید فوراً نمایش داده می‌شد). */
            var SEARCH_DEBOUNCE = 300;

            input.addEventListener('input', function () {
                window.clearTimeout(timer);
                var q = (input.value || '').trim();

                if (q.length < 2) {
                    timer = window.setTimeout(showIdlePanel, SEARCH_DEBOUNCE);
                    return;
                }

                timer = window.setTimeout(function () {
                    if ((input.value || '').trim() !== q) { return; }

                    panel.innerHTML = '<div class="sr2-empty"><i class="bi bi-arrow-repeat"></i> در حال جستجو…</div>';
                    panel.classList.add('open');

                    fetchSuggest(q, function (data) {
                        if ((input.value || '').trim() !== q) { return; }
                        renderResults(panel, data, q);
                    });
                }, SEARCH_DEBOUNCE);
            });

            /* کلیک روی چیپ‌ها → جستجوی همان عبارت
               فاز ۱۸۵ — رفع ۴۰۴ چیپ‌های «جستجوهای پرطرفدار/اخیر»:
               مقصد فقط از pathname ساخته می‌شود (نه URL مطلقِ اتریبیوت action).
               اگر میزبان/proxy «action» را با هاست داخلی تولید کرده باشد،
               ناوبری با هاست داخلی به ۴۰۴ می‌رسید؛ اکنون همیشه روی همان
               هاست فعلی به /search می‌رود. */
            var searchPath = (function () {
                try {
                    var a = document.createElement('a');
                    a.href = form.getAttribute('action') || '/search';
                    return a.pathname || '/search';
                } catch (e) { return '/search'; }
            })();

            panel.addEventListener('click', function (e) {
                var chipEl = e.target.closest('.js-sr2-term');
                if (!chipEl) { return; }
                e.preventDefault();
                var term = chipEl.getAttribute('data-term') || '';
                input.value = term;
                pushRecent(term);
                /* فاز ۱۸۸ — جستجو هم بدون رفرش (SPA)؛ مسیرهای مستثنی مثل قبل لود کامل */
                var target = searchPath + '?q=' + encodeURIComponent(term);
                if (window.saniarSoftNavigate) {
                    window.saniarSoftNavigate(target);
                } else {
                    window.location.href = target;
                }
            });

            /* ذخیرهٔ عبارت هنگام جستجو */
            form.addEventListener('submit', function () {
                pushRecent(input.value);
            });

            /* بستن با کلیک بیرون */
            document.addEventListener('click', function (e) {
                if (!form.contains(e.target)) { hidePanel(); }
            });

            /* بستن با Escape */
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { hidePanel(); }
            });
        });

        /* ---------- سازگاری با بلوک قدیمی (panel#search-result) ---------- */
        var legacyPanel = document.getElementById('search-result');
        if (legacyPanel) { legacyPanel.remove(); }

        // ---------- دکمه جستجو (اسکریپت قدیمی event را preventDefault می‌کند) ----------
        var desktopForm = document.getElementById('search-form');
        var desktopInput = document.getElementById('search-input');
        var searchBtn = document.getElementById('search-header-btn-search');

        if (searchBtn && desktopForm) {
            searchBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (desktopInput && (desktopInput.value || '').trim().length > 0) {
                    /* فاز ۱۷۷ — form.submit() رخداد submit را آتش نمی‌زند؛
                       ذخیرهٔ جستجوی اخیر را اینجا دستی انجام می‌دهیم */
                    pushRecent(desktopInput.value);
                    desktopForm.submit();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderSearch);
    } else {
        initHeaderSearch();
    }

    /* فاز ۱۸۵ (SPA) — بعد از هر ناوبری wire:navigate، هدر تازه است؛
       اتصال‌های هدر (جستجو/کلیدها/نوار ادمین) دوباره برقرار می‌شوند */
    (window.saniarReinit = window.saniarReinit || []).push(function () { initHeaderSearch(); });

    /* =========================================================
       همگام‌سازی سبد خرید پس از افزودن ای‌جکسی
       بازنویسی: «بروزرسانی درجا»
         مشکل قبلی: جایگزینی کامل گرهٔ offcanvas در حالی که سایدبار باز بود
         ⇒ ماشین‌حالت بوت‌استرپ خراب می‌شد: بک‌دراپ یتیم، قفل‌ماندنِ
         overflow بدنه (مرگ اسکرول در همین صفحه یا صفحهٔ بعد از back/forward)
         و چشمکِ بسته/بازشدن سایدبار.
         الان: خودِ گرهٔ offcanvas زنده می‌ماند و فقط محتوای داخلی‌اش
         عوض می‌شود ⇒ وضعیت باز بودن حفظ می‌شود و هیچ پاک‌سازی دستی
         لازم نیست.
       ========================================================= */
    window.SaniarThemeCartSync = function (cartHtml) {
        if (!cartHtml) { return; }

        var count = 0;
        var parsedCanvas = null;

        if (typeof cartHtml === 'string') {
            var match = cartHtml.match(/data-cart-count="(\d+)"/);
            if (match) { count = parseInt(match[1], 10) || 0; }

            var tpl = document.createElement('template');
            tpl.innerHTML = cartHtml;
            parsedCanvas = tpl.content.querySelector('.cart-canvas') || tpl.content.firstElementChild;
        } else if (cartHtml.getAttribute) {
            count = parseInt(cartHtml.getAttribute('data-cart-count') || '0', 10) || 0;
            parsedCanvas = cartHtml;
        }

        /* جایگزین: شمارش آیتم‌های محصول در DOM تازه (آیتم‌های دارای تصویر —
           ردیف فاکتور و باکس «سبد خالی» تصویر ندارند) */
        var fresh = parsedCanvas && parsedCanvas.querySelector ? parsedCanvas.querySelector('.cart-canvases') : null;
        if (!count && fresh) {
            count = fresh.querySelectorAll(':scope > .item .image').length;
        }
        if (count < 0) { count = 0; }

        document.querySelectorAll('.header-cart-counter').forEach(function (el) {
            el.textContent = String(count);
        });

        var live = document.getElementById('offcanvasCart');

        if (live && parsedCanvas) {
            /* مسیر اصلی — بروزرسانی درجا: گرهٔ زنده عوض نمی‌شود،
               instance بوت‌استرپ و وضعیت باز بودن دست‌نخورده می‌ماند */
            live.setAttribute('data-cart-count', String(count));
            if (parsedCanvas.innerHTML !== undefined) {
                live.innerHTML = parsedCanvas.innerHTML;
            }
        } else if (parsedCanvas) {
            /* مسیر جایگزین — گرهٔ زنده از بین رفته بود (نباید معمولاً رخ دهد):
               نسل قدیمیِ بازسازی + بازگشایی */
            var old = document.querySelector('.cart-canvas');
            if (old) {
                var inst = window.bootstrap && window.bootstrap.Offcanvas && window.bootstrap.Offcanvas.getInstance(old);
                if (inst) { inst.dispose(); }
                old.replaceWith(parsedCanvas);
            } else {
                document.body.appendChild(parsedCanvas);
            }
            if (window.bootstrap && window.bootstrap.Offcanvas) {
                window.bootstrap.Offcanvas.getOrCreateInstance(parsedCanvas).show();
            }
        }

        /* گارد دفاعی اسکرول — اگر هیچ offcanvas/مودالی باز نیست ولی بدنه
           قفل مانده (overflow:hidden)، قفل برداشته شود */
        var anyOpen = document.querySelector('.offcanvas.show, .modal.show');
        if (!anyOpen && !document.body.classList.contains('modal-open')) {
            document.querySelectorAll('.offcanvas-backdrop').forEach(function (node) {
                node.remove();
            });
            if (document.body.style.overflow === 'hidden') {
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        }
    };

    /* =========================================================
       ۲) دکمهٔ «برگشت به بالا» — مستطیلی، وسط صفحه
       جایگزین پلاگین go-to-top (دایرهٔ گوشهٔ پایین)
       ========================================================= */
    function initBackToTop() {
        var btn = document.getElementById('back-to-top');
        if (!btn || btn.dataset.spaBound === '1') { return; }
        btn.dataset.spaBound = '1';

        var toggle = function () {
            if (window.pageYOffset > 350) {
                btn.classList.add('is-visible');
            } else {
                btn.classList.remove('is-visible');
            }
        };

        window.addEventListener('scroll', toggle, { passive: true });
        toggle();

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* =========================================================
       ۳) کلید روز / شب (دارک مود)
       حالت در localStorage ذخیره می‌شود؛ مقداردهی اولیهٔ
       html[data-theme] در theme-init.js (سربین) انجام می‌شود
       تا صفحه هنگام لود چشمک نزند.
       ========================================================= */
    function initThemeToggle() {
        /* همگام‌سازی وضعیت اولیهٔ aria با تم اعمال‌شده در theme-init.js */
        var initialDark = document.documentElement.getAttribute('data-theme') === 'dark';

        /* همهٔ کلیدها (دسکتاپ + موبایل) با کلاس بایند می‌شوند — نه id یکتا */
        document.querySelectorAll('.theme-toggle-btn').forEach(function (toggle) {
            toggle.setAttribute('aria-pressed', initialDark ? 'true' : 'false');

            if (toggle.dataset.bound === '1') { return; }
            toggle.dataset.bound = '1';
            toggle.addEventListener('click', function () {
                var root = document.documentElement;
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

                root.setAttribute('data-theme', next);
                try { localStorage.setItem('saniar-theme', next); } catch (e) { /* حافظه در دسترس نیست */ }

                /* فاز ۱۹۲ — کوکی همگام: بعد از ناوبری SPA اتریبیوت <html> از
                   HTML سرور (که با همین کوکی رندر می‌شود) درست می‌ماند */
                try {
                    document.cookie = 'saniar-theme=' + next +
                        ';path=/;max-age=31536000;SameSite=Lax';
                } catch (e) { /* کوکی در دسترس نیست — localStorage کافی است */ }

                /* همگام‌سازی همهٔ کلیدهای موجود در صفحه */
                document.querySelectorAll('.theme-toggle-btn').forEach(function (btn) {
                    btn.setAttribute('aria-pressed', next === 'dark' ? 'true' : 'false');
                });
            });
        });
    }

    /* =========================================================
       نوار مدیریت بالای سایت (دسترسی سریع ادمین):
       ۱) رفع باگ «منو باز می‌ماند» در تاچ: در صفحه‌های لمسی،
          :hover پس از ضربه باقی می‌ماند. با تاچ روی عنوان، پنل با
          کلاس .touch-open باز/بسته می‌شود و با لمس بیرون بسته می‌شود.
       ۲) blur هنگام خروج موس — برای حالت کلیک/فوکوس.
       ========================================================= */
    function initAdminNavbarFocusFix() {
        var navbar = document.querySelector('.admin-loggedin-navbar');
        if (!navbar || navbar.dataset.focusFixBound === '1') { return; }
        navbar.dataset.focusFixBound = '1';

        var isTouch = window.matchMedia && window.matchMedia('(hover: none)').matches;

        var closeAll = function () {
            navbar.querySelectorAll('.list-item-has-children').forEach(function (li) {
                li.classList.remove('touch-open');
                var link = li.querySelector(':scope > .nav-link');
                if (link && typeof link.blur === 'function') { link.blur(); }
            });
        };

        navbar.querySelectorAll('.list-item-has-children').forEach(function (li) {
            var link = li.querySelector(':scope > .nav-link');

            /* خروج موس از آیتم منودار → فوکوس آزاد شود (دسکتاپ) */
            li.addEventListener('mouseleave', function () {
                if (link && typeof link.blur === 'function') { link.blur(); }
            });

            /* تاچ: ضربهٔ اول باز می‌کند، ضربهٔ دوم می‌بندد */
            if (link && isTouch) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var wasOpen = li.classList.contains('touch-open');
                    closeAll();
                    if (!wasOpen) { li.classList.add('touch-open'); }
                });
            }
        });

        /* کلیک / لمس بیرون نوار → همهٔ پنل‌ها بسته شوند */
        document.addEventListener('click', function (e) {
            if (!navbar.contains(e.target)) { closeAll(); }
        });
        document.addEventListener('touchstart', function (e) {
            if (!navbar.contains(e.target)) { closeAll(); }
        }, { passive: true });

        /* کلید Escape هم پنل‌ها را می‌بندد */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeAll(); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initBackToTop();
            initThemeToggle();
            initAdminNavbarFocusFix();
        });
    } else {
        initBackToTop();
        initThemeToggle();
        initAdminNavbarFocusFix();
    }

    /* فاز ۱۸۵ (SPA) — المان‌های این سه سازنده با هر ناوبری عوض می‌شوند */
    (window.saniarReinit = window.saniarReinit || []).push(function () {
        initBackToTop();
        initThemeToggle();
        initAdminNavbarFocusFix();
    });
})();
