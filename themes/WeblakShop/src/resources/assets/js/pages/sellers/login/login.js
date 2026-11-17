// validate form with jquery validation plugin
jQuery('#seller-register-level1-form').validate({
    rules: {
        username: {
            required: true,
        },
        password: {
            required: true
        },
    },
    messages: {
        username: {
            required: "فیلد ایمیل یا شماره موبایل نمی تواند خالی باشد",
        },
        password: "رمز عبور را وارد کنید",
    }
});


$('#seller-register-level1-form').submit(function (e) {
    e.preventDefault();
    if ($(this).valid() && !$(this).data('disabled')) {
        var formData = new FormData(this);


        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function (data) {
                if (data.status=='error'){
                    showCustomToast(data.message,'error');
                    // کپچاهای چندگانه — بعد از خطا کپچا تازه شود (captcha_refresh از سرور)
                    if (data.captcha_refresh && window.spCaptchaRefreshAll) { window.spCaptchaRefreshAll(); }
                }else if(data.status=="success"){
                    showCustomToast(data.message,'success')
                    setTimeout(function(){
                        window.location.href = data.redirect;
                    }, 800);

                }

            },
            error: function (xhr) {
                // خطای اعتبارسنجی (۴۲۲) — اولین پیام + تازه‌سازی کپچا
                var errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
                var msg = '';
                $.each(errors, function (key, val) {
                    if (!msg && val && val.length) { msg = val[0]; }
                });
                showCustomToast(msg || 'خطا در ورود؛ لطفاً دوباره تلاش کنید.', 'error');
                if (window.spCaptchaRefreshAll) { window.spCaptchaRefreshAll(); }
            },
            beforeSend: function (xhr) {
                block('.content-account');
                xhr.setRequestHeader(
                    'X-CSRF-TOKEN',
                    $('meta[name="csrf-token"]').attr('content')
                );
            },
            complete: function () {
                unblock('.content-account');
            },
            cache: false,
            contentType: false,
            processData: false
        });
    }
});
