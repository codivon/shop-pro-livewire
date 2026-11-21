/* ============================================================
   SaniarTheme — theme-init.js
   ابتدایی‌ساز حالت روز/شب — در <head> و پیش از رندر لود می‌شود
   تا اگر کاربر حالت شب را انتخاب کرده بود، صفحه از همان قاب
   اول تیره رندر شود (بدون چشمک/فلش پس‌زمینهٔ روشن).
   هیچ وابستگی به DOM ندارد؛ فقط attribute روی <html> می‌گذارد.

   فاز ۱۹۲ — رفع «تم روشن بعد از ناوبری تا رفرش»:
   • منبع اولیه: کوکی saniar-theme (سمت سرور روی <html> رندر شده)؛
     اگر کوکی نبود، localStorage مرجع است.
   • بعد از هر ناوبری SPA مقدار هم دوباره اعمال می‌شود (حتی اگر
     morph اتریبیوت <html> را به مقدار پیش‌فرض برگرداند).
   ============================================================ */
(function () {
    'use strict';

    function currentTheme() {
        var theme = 'light';

        try {
            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                theme = 'dark';
            }
            theme = localStorage.getItem('saniar-theme') === 'dark' ? 'dark'
                : (localStorage.getItem('saniar-theme') === 'light' ? 'light' : theme);
        } catch (e) { /* localStorage در دسترس نیست — مقدار سرور/پیش‌فرض */ }

        return theme;
    }

    function applyTheme() {
        document.documentElement.setAttribute('data-theme', currentTheme());
    }

    /* اجرای اولیه (قبل از رندر بادی) */
    applyTheme();

    /* فاز ۱۹۲ — همگرایی: اگر کوکی با localStorage فرق دارد یا نبود،
       کوکی همگام شود تا رندر سمت سرور ناوبری‌های بعدی هم درست باشد */
    try {
        if (document.cookie.indexOf('saniar-theme=' + currentTheme()) === -1) {
            document.cookie = 'saniar-theme=' + currentTheme() +
                ';path=/;max-age=31536000;SameSite=Lax';
        }
    } catch (e) { /* کوکی در دسترس نیست */ }

    /* فاز ۱۹۲ — پس از هر ناوبری SPA هم اعمال شود (بelt & suspenders
       برای وقتی کوکی/سرور مقدار درست را رندر نکرده باشد) */
    if (window.Livewire) {
        document.addEventListener('livewire:navigated', applyTheme);
    }
})();
