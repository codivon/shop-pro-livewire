/**
 * SaniarTheme — floating-widget.js
 * =========================================================================
 * ویجت شناور تماس (fab/panel/tabs)
 *
 * فاز ۱۹۲ — رفع «ویجت شناور پشتیبانی وقتی وارد یک صفحه می‌شویم باز و بسته
 * می‌شود»:
 *   ریشه: ناوبری SPA (Livewire.navigate) بدنهٔ صفحه را morph می‌کند و
 *   کلاس‌های قبلی همان گره‌ها حفظ می‌شوند — اگر پنل در صفحهٔ قبلی باز بود،
 *   در صفحهٔ جدید هم باز می‌ماند («خودش باز شده») و با اولین کلیک بیرونی
 *   بسته می‌شود («خودش بسته شد»). این نسخه پس از هر ناوبری وضعیت را به
 *   «بسته» برمی‌گرداند و رنگ اصلی را دوباره اعمال می‌کند.
 *
 * همچنین:
 *   • بایند هندلرها یک‌بار است (گارد dataset) تا با هر ناوبری شنوندهٔ
 *     اضافه روی document انبار نشود؛
 *   • گره‌ها با هر اجرا دوباره از DOM خوانده می‌شوند (بعد از morph درست است)؛
 *   • حلقهٔ پالس (پرد-lite) در CSS فقط سه تکرار شد تا «باز و بسته شدن
 *     خودکار» حس نشود.
 * =========================================================================
 */
(function () {
    'use strict';

    function applyMainColor() {
        var wrapper = document.querySelector('.fw-wrapper');
        if (wrapper && wrapper.dataset.mainColor) {
            var root = document.documentElement;
            root.style.setProperty('--fw-primary', wrapper.dataset.mainColor);
            root.style.setProperty('--fw-primary-dark',
                'color-mix(in srgb, ' + wrapper.dataset.mainColor + ' 80%, #000)');
        }
    }

    function initFloatingWidget() {
        applyMainColor();

        var wrapper = document.querySelector('.fw-wrapper');
        if (!wrapper) { return; }

        /* فاز ۱۹۲ — وضعیت همیشه «بسته» شروع شود (بعد از هر ناوبری هم اجرا می‌شود)؛
           کلاس‌های بازِ به‌جامانده از صفحهٔ قبلی توسط morph پاک می‌شوند این‌جا */
        wrapper.classList.remove('fw-open');
        var fab = document.getElementById('fw-fab');
        var panel = document.getElementById('fw-panel');
        if (fab) { fab.classList.remove('fw-open'); }
        if (panel) { panel.classList.remove('fw-panel-open'); }

        /* بایند یک‌بار — گره‌های fab/panel با morph همین‌ها هستند */
        if (wrapper.dataset.fwBound === '1') { return; }
        wrapper.dataset.fwBound = '1';

        var getFab = function () { return document.getElementById('fw-fab'); };
        var getPanel = function () { return document.getElementById('fw-panel'); };

        if (fab) {
            fab.addEventListener('click', function () {
                getFab().classList.toggle('fw-open');
                getPanel().classList.toggle('fw-panel-open');
            });
        }

        /* کلیک بیرون ویجت → بستن؛ کلیک داخل پنل → باز بماند */
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.fw-wrapper')) {
                var f = getFab();
                var p = getPanel();
                if (f) { f.classList.remove('fw-open'); }
                if (p) { p.classList.remove('fw-panel-open'); }
            }
        });

        /* تب‌ها — delegated روی خود wrapper تا با تعویض DOM تب‌ها هم زنده بماند */
        wrapper.addEventListener('click', function (e) {
            var btn = e.target.closest('.fw-tab-btn');
            if (!btn || !wrapper.contains(btn)) { return; }

            wrapper.querySelectorAll('.fw-tab-btn').forEach(function (b) {
                b.classList.remove('fw-tab-active');
            });
            wrapper.querySelectorAll('.fw-tab-pane').forEach(function (p) {
                p.classList.remove('fw-active');
            });
            btn.classList.add('fw-tab-active');

            var target = document.getElementById(btn.getAttribute('data-tab'));
            if (target) { target.classList.add('fw-active'); }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            applyMainColor();
            initFloatingWidget();
        });
    } else {
        initFloatingWidget();
    }

    /* فاز ۱۹۲ — پس از هر ناوبری SPA: وضعیت بسته + رنگ اصلی + بایند گره‌های تازه */
    document.addEventListener('livewire:navigated', function () {
        initFloatingWidget();
    });
    (window.saniarReinit = window.saniarReinit || []).push(initFloatingWidget);
})();
