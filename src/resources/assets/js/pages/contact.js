/* فاز ۱۹۶ — صفحهٔ تماس با ما حالا SPA است:
   بعد از هر ناوبری نرم، navigate لایووایر اسکریپت‌های پوش‌شدهٔ صفحه (این فایل
   و contact-page-vars.js و کتابخانهٔ نقشه) را دوباره اجرا می‌کند؛ $(document).ready
   وقتی سند از قبل آماده باشد بلافاصله اجرا می‌شود، پس مقداردهی نقشه/فرم در
   ناوبری SPA هم رخ می‌دهد. فقط مسیر google (اسکریپت defer) ممکن است هنگام
   اجرای این فایل هنوز نیامده باشد — با polling کوتاه تا ۱۰ ثانیه صبر می‌شود. */
$(document).ready(function() {
    "use strict";

    /* متغیرهای صفحه (contact-page-vars.js) به هر حال قبل از این فایل لود شده‌اند؛
       ولی برای مقاومت در برابر هر ترتیب لود، گارد undefined گذاشته می‌شود */
    if (typeof info_map_type === 'undefined') { return; }

    /* ---------- نقشه ---------- */
    if (info_map_type === 'google') {
        var contactGoogleAttempts = 0;

        var initGoogleContactMap = function () {
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                /* بعد از ناوبری SPA اسکریپت گوگل‌مپ (defer) ممکن است هنوز نرسیده باشد */
                if (contactGoogleAttempts++ < 40) {
                    window.setTimeout(initGoogleContactMap, 250);
                }
                return;
            }

            if ($('#map').length === 0) { return; }

            var text = info_site_title;
            var myLatlng = new google.maps.LatLng(info_latitude, info_Longitude);

            var map = new google.maps.Map(document.getElementById("map"), {
                center: myLatlng,
                zoom: 16,
                scrollwheel: false,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
            });

            var marker = new google.maps.Marker({
                position: myLatlng,
            });

            var infowindow = new google.maps.InfoWindow({
                content: text,
            });

            infowindow.open(map, marker);
            marker.setMap(map);
        };

        initGoogleContactMap();
    } else if (typeof Mapp !== 'undefined' && $('#map').length > 0) {
        var app = new Mapp({
            element: '#map',
            presets: {
                latlng: {
                    lat: info_latitude,
                    lng: info_Longitude,
                },
                zoom: 16
            },
            apiKey: mapIrApiKey
        });
        app.addLayers();

        var marker = app.addMarker({
            name: 'marker',
            latlng: {
                lat: info_latitude,
                lng: info_Longitude,
            },
            icon: app.icons.red,
            popup: false,
            pan: true,
        });

        var popup = app.generatePopupHtml({
            title: {
                i18n: info_site_title,
            },
            class: 'custom-css-class',
        });

        marker.bindPopup(popup);
    }
});

/* ---------- اعتبارسنجی و ارسال فرم (بعد از ناوبری SPA هم دوباره بایند می‌شود) ---------- */
(function () {
    var bindContactForm = function () {
        var $form = jQuery('#contact-form');
        if ($form.length === 0 || typeof jQuery.fn.validate !== 'function') { return; }

        if (jQuery.data($form[0], 'validator')) { return; } /* دوبار مقداردهی نشود */

        $form.validate({
            rules: {
                'name':    { required: true },
                'email':   { required: true },
                'subject': { required: true },
                'message': { required: true },
            },
        });

        $form.off('submit.saniarContact').on('submit.saniarContact', function (e) {
            e.preventDefault();
            if (!$(this).valid()) { return; }

            block('#contact-form');

            var formData = new FormData(this);

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                success: function () {
                    Swal.fire({
                        text: 'پیام شما با موفقیت ارسال شد. با تشکر',
                        type: 'success',
                        showCancelButton: false,
                        confirmButtonText: 'باشه',
                    });

                    if (typeof reloadCaptcha === 'function') { reloadCaptcha(); }

                    $('#contact-form').trigger('reset');
                    unblock('#contact-form');
                    unblock('.form-ui');
                },

                beforeSend: function (xhr) {
                    block('.form-ui');
                    xhr.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content'));
                },
                complete: function () {
                    unblock('.form-ui');
                    unblock('#contact-form');
                },

                cache: false,
                contentType: false,
                processData: false
            });
        });
    };

    /* اجرای اولیه + بعد از هر ناوبری SPA (فرم تازه صفحهٔ جدید بایند می‌شود) */
    jQuery(document).ready(bindContactForm);
    (window.saniarReinit = window.saniarReinit || []).push(bindContactForm);
})();
