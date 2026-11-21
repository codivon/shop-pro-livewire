$(function () {

    ///نمایش زیر منو
    /* فاز ۱۹۷ — بایند delegated به‌جای مستقیم: بایندِ مستقیم بعد از هر
       ناوبری SPA (تعویض کامل body توسط livewire:navigate) می‌مُرد و
       شِورون‌های زیرمنو در منوی ریسپانسیو بی‌پاسخ می‌شدند. */
    $(document).on('click', '.showSubMenu', function () {
        $(this).nextAll("ul").toggleClass("show");
        $(this).toggleClass('open');
    })
    
    ///نمایش مگامنو آپدیدت جدید :
    ///۱) همهٔ سلکتورها به .top-menu-menu محدود شدند تا نوار مدیریت بالای سایت
    ///   (.admin-loggedin-navbar که کلاس قدیمی main-menu را داشت) دیگر تحت
    ///   تأثیر این کدها قرار نگیرد — علت منوهای بازماندهٔ نوار ادمین همین بود.
    ///۲) mouseleave با event delegation متصل می‌شود؛ قبلاً هنگام لود صفحه هیچ
    ///   عنصری کلاس main-menu-sub-active را نداشت، پس هندلر هرگز متصل نمی‌شد
    ///   و منوها بعد از خروج موس باز می‌ماندند.
    /* ستون فرعی مگامنو = «یک پنل ثابت واحد» (نیاز محصول):
       قبلاً به‌ازای هر ردیف، ul فرعیِ خودِ ردیف با position:fixed جابه‌جا و با
       transition «all» نمایش داده می‌شد؛ با هر حرکت موس بین دسته‌ها، پنل از بالا
       دوباره انیمیت می‌شد (پرش صفحه). حالا یک پنل fixed با مختصات ثابت ساخته
       می‌شود و فقط محتوایش (کپی ستون فرعی ردیف فعال) عوض می‌شود؛ اگر ردیف
       تصویر پس‌زمینه داشته باشد (data-cat-image) همان تصویر پایین-چپ پنل
       نمایش داده می‌شود. هیچ transition موقعیتی وجود ندارد. */

    function ensureMegaFlyout(panel) {
        if (!panel) { return null; }
        if (panel.__snrFlyout) { return panel.__snrFlyout; }

        var flyout = document.createElement('ul');
        flyout.className = 'main-menu-sub snr-mega-flyout';
        flyout.setAttribute('aria-label', 'زیرمنوی دسته‌بندی‌ها');
        panel.appendChild(flyout);
        panel.__snrFlyout = flyout;

        return flyout;
    }

    function placeMegaFlyout(panel) {
        var flyout = panel.__snrFlyout;
        if (!flyout) { return; }

        var rect = panel.getBoundingClientRect();
        if (!rect.width) { return; }

        /* ستون ردیف‌ها ۲۰۰px از لبهٔ راست پنل است؛ پنل فرعی دقیقاً از همان‌جا
           تا لبهٔ چپ خود پنل باز می‌شود.
           فاز ۱۹۸ — «height» دیگر ست نمی‌شود: ارتفاع پنل فرعی باید «قدِ محتوای
           خودش» باشد (به‌درخواست کاربر: اگر دسته زیرمنوی کوتاه یا بدون زیرمنو
           دارد، پنل بچسبد و جای خالی نماند). قبلاً ارتفاع پنلِ ردیف‌ها کپی
           می‌شد؛ با محتوای کوتاه، پایین پنل فرعی ۲۰۰-۳۰۰px خالی می‌ماند. */
        flyout.style.top = Math.round(rect.top) + 'px';
        flyout.style.right = Math.round(window.innerWidth - rect.right + 200) + 'px';
        flyout.style.left = Math.round(rect.left) + 'px';
    }

    function showMegaFlyoutRow(panel, row) {
        var flyout = ensureMegaFlyout(panel);
        if (!flyout) { return; }

        var sub = row.querySelector(':scope > ul.main-menu-sub');
        flyout.innerHTML = sub ? sub.innerHTML : '';

        var img = row.getAttribute('data-cat-image');
        flyout.style.backgroundImage = img ? 'url("' + img + '")' : '';
        /* فاز ۱۹۸ — فقط وقتی تصویر دسته وجود دارد، فضای پایین-چپ پنل برای
           نمایش تصویر رزرو می‌شود؛ دسته‌های بدون تصویر پنل فشرده می‌گیرند */
        flyout.classList.toggle('has-cat-image', !!img);

        placeMegaFlyout(panel);
        flyout.classList.add('main-menu-sub-active');
    }

    /* جابه‌جایی پنل‌های باز هنگام اسکرول/ریسایز (بدون انیمیشن) */
    var repositionOpenFlyouts = function () {
        document.querySelectorAll('.top-menu-menu ul.main-menu.active').forEach(function (panel) {
            if (panel.__snrFlyout) { placeMegaFlyout(panel); }
        });
    };
    window.addEventListener('scroll', repositionOpenFlyouts, { passive: true });
    window.addEventListener('resize', repositionOpenFlyouts);

    /* باز/بسته شدن کل پنل مگا منو با ورود و خروج موس از دکمهٔ «دسته‌بندی کالاها»
       =====================================================================
       فاز ۱۹۷ — ریشه‌یابی «لینک‌های زیرمنو/فرزندان کار نمی‌کنند»:
       ۱) گپ مردهٔ ~۴px بین لبهٔ پایین دکمه و لبهٔ بالای پنل/پنل فرعی؛ عبور
          پوینتر از این نوار، mouseleave سراسری می‌داد و کل مگا منو «وسط راه»
          بسته می‌شد — کاربر هرگز به زیرمنو نمی‌رسید (کلیک روی فرزندان غیرممکن).
          ⇒ بستن با «تأخیر ۳۰۰ms» + پل CSS (‎::after‎ در style.css) هر دو اعمال شد.
       ۲) هر ورود مجدد پوینتر به زیردرخت head، محتوای پنل فرعی را از نو
          innerHTML می‌کرد؛ المان زیر پوینتر بین mousedown و mouseup از DOM
          جدا می‌شد و کلیک ناوبری می‌مُرد (۶۱ بار detach پیاپی در تست).
          ⇒ اگر پنل از قبل باز و پنل فرعی از قبل نمایش است، رندر تکرار نمی‌شود. */
    var megaCloseTimer = null;
    $(document).on('mouseenter', '.top-menu-menu .main-menu-head', function () {
        if (megaCloseTimer) { clearTimeout(megaCloseTimer); megaCloseTimer = null; }

        var wasActive = $(this).children('ul.main-menu').hasClass('active');
        $(this).children().addClass('active');

        var panel = $(this).children('ul.main-menu').get(0);
        if (wasActive && panel && panel.__snrFlyout &&
            panel.__snrFlyout.classList.contains('main-menu-sub-active')) {
            /* پنل باز و پنل فرعی پر است — بازنویسی innerHTML = detach المانِ
               زیر پوینتر و مرگ کلیک؛ فقط ردیف فعال را دست نمی‌زنیم */
            return;
        }

        var firstRow = panel ? panel.querySelector(':scope > li') : null;
        if (firstRow) {
            /* فاز ۱۸۵ — ردیف اول هم «هایلایت» می‌شود تا با محتوای پنل فرعی
               (که همان ردیف اول است) هم‌خوان باشد؛ قبلاً پنل باز می‌شد ولی
               هیچ ردیفی هایلایت نداشت */
            $(panel).children('li').removeClass('main-menu-sub-active-li');
            $(firstRow).addClass('main-menu-sub-active-li');
            showMegaFlyoutRow(panel, firstRow);
        }
    })
    $(document).on('mouseleave', '.top-menu-menu .main-menu-head', function () {
        var self = this;
        if (megaCloseTimer) { clearTimeout(megaCloseTimer); }
        megaCloseTimer = setTimeout(function () {
            megaCloseTimer = null;
            $(self).children().removeClass('active');

            var panel = $(self).children('ul.main-menu').get(0);
            if (panel && panel.__snrFlyout) {
                $(panel.__snrFlyout).removeClass('main-menu-sub-active');
                $(panel).find('> li').removeClass('main-menu-sub-active-li');
            }
        }, 300);
    })

    /* هاور روی ردیف‌های مگا منو → فقط محتوای پنل ثابت فرعی عوض می‌شود.
       با delegation روی خود پنل، ورود موس به پنل فرعی هم ردیف فعال و پنل را
       باز نگه می‌دارد (target داخل flyout به هیچ ردیفی resolve نمی‌شود). */
    $(document).on('mouseover', '.top-menu-menu ul.main-menu', function (event) {
        var row = event.target.closest('.top-menu-menu ul.main-menu > li');
        if (!row || !this.contains(row)) { return; }

        var $row = $(row);
        if ($row.hasClass('main-menu-sub-active-li') &&
            this.__snrFlyout &&
            this.__snrFlyout.classList.contains('main-menu-sub-active')) { return; }

        $row.parent().children('li').removeClass('main-menu-sub-active-li');
        $row.addClass('main-menu-sub-active-li');

        showMegaFlyoutRow(this, row);
    });

    /* خروج موس از ستون ردیف‌های پنل مگا منو
       فاز ۱۹۷ — فقط هایلایتِ ردیف‌ها پاک می‌شود؛ پنهان‌سازی پنل فرعی و بستنِ
       پنل را «mouseleave تأخیردارِ head» انجام می‌دهد. قبلاً همین‌جا پنل فرعی
       فوراً مخفی می‌شد: عبور پوینتر از گپِ بین دکمه و پنل (مسیر مورب کاربر)
       = خروج از پنل ⇒ پنل فرعی خاموش ⇒ ورود دوباره به ناحیهٔ خالی ⇒ رندر
       مجدد و پرش — دقیقاً «زیرمنو باز نمی‌ماند و کلیک روی فرزندان نمی‌گیرد». */
    $(document).on('mouseleave', '.top-menu-menu ul.main-menu', function () {
        $(this).find('> li').removeClass('main-menu-sub-active-li');
    })

    ///شمارنده محصول برای اضافه کردن به سبد خرید
    /* ورودی‌های سبد کشویی هدر (.js-mini-qty) نباید TouchSpin شوند:
       TouchSpin با markup خودش input-group تزریق می‌کند و ظاهر سایدبار
       پس از رفرش صفحه به‌هم می‌ریزد و دکمه‌های +/− ای‌جکسی آن از کار
       می‌افتند. شمارندهٔ سبد کشویی منطق اختصاصی خودش را دارد (main.js). */
    /* فاز ۱۸۵ — TouchSpin باید بعد از هر ناوبری SPA هم روی inputهای تازه
       اجرا شود؛ به‌صورت تابع قابل-فراخوانی + گارد per-element.
       (saniar-spa.js بعد از هر livewire:navigated آن را صدا می‌زند) */
    window.saniarTouchSpinInit = function () {
        if (!$.fn.TouchSpin) { return; }
        $("input[name='count']").not('.js-mini-qty').not('.cart-canvas input').not('[data-touchspin-bound]').each(function () {
            this.setAttribute('data-touchspin-bound', '1');
            $(this).TouchSpin({
                min: 1,
                max: '1000000000000000',
                buttondown_class: "btn-counter waves-effect waves-light",
                buttonup_class: "btn-counter waves-effect waves-light"
            });
        });
    };
    window.saniarTouchSpinInit();
 
    ///انتخاب گر رنگ
    $(".category-sort .form-checks .form-check").click(function () {
        $(".category-sort .form-checks .form-check").removeClass("active");
        $(this).addClass('active');
        $(".category-sort .form-checks .form-check").children("input[type=radio]").attr('checked', false);
        $(this).children("input[type=radio]").attr('checked', true);
    })


    ///انتخاب زمان ارسال
    $(".send-item").click(function () {
        $(".send-item").removeClass("active");
        $(this).addClass('active');
    })

    ///انتخاب روش پستی
    $(".shipping-item").click(function () {
        $(".shipping-item").removeClass("active");
        $(this).addClass('active');
    })

    ///انتخاب روش پرداخت
    $(".bank-item").click(function () {
        $(".bank-item").removeClass("active");
        $(this).addClass('active');
    })
    


    jQuery('[data-bs-toggle="tooltip"]').tooltip();
    jQuery('[data-bs-toggle="modal"][title]').tooltip();
    
});



// When the user clicks on the button, scroll to the top of the document
function topFunction() {
    document.body.scrollTop = 0; // For Safari
    document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
  }


////video play button

  $(document).ready(function () {
    var ctrlVideo = document.getElementById("aboutVideo");

    $('.play-btn').click(function () {
        if ($('.play-btn').hasClass("active")) {
            
            $('.play-btn').toggleClass("play-opacity");

            $("#aboutVideo").toggleClass('vd-style');

            ctrlVideo.play();

            $('#play-icon').removeClass("bi bi-play-fill");
            $('#play-icon').addClass("bi bi-pause-fill");

            $('.play-btn').toggleClass("active");
        } else {

            $('.play-btn').toggleClass("play-opacity");

            $("#aboutVideo").toggleClass('vd-style');
            ctrlVideo.pause();

            $('#play-icon').removeClass("bi bi-pause-fill");
            $('#play-icon').addClass("bi bi-play-fill");

            $('.play-btn').toggleClass("active");
        }
    });
});



/*
* فرم چند مرحله ای ورود / ثبت نام
*/

$(document).ready(function (){
    ///disable fild password in load form
    $(".step-passwd").hide();
    ///disable button submit in step one
    $(".step-two").hide();

    ///show filed password in step two form
    $(".step-one").click(function (){

        ///check empty fild username
        if($(".step-username #username").val() != ""){
            ///hide username filed
            $(".step-username").hide();
            ///show password filed
            $(".step-passwd").show();
            ///hide button step one
            $(this).hide();
            ///show button submit
            $(".step-two").show();
        }else{
            $(".step-username #username").addClass("border-danger border-2");
        }


        ///check empty fild password

        $(".btnForm").click(function(){
            if($(".step-passwd #passwd").val() !=""){
                $("#form-auth").submit();
            }else{
                $(".step-passwd #passwd").addClass("border-danger border-2");
            }
        })


    })

})
