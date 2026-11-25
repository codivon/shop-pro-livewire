/* ============================================================
   cart.js (SaniarTheme)
   صفحهٔ سبد خرید: همهٔ تغییرات با AJAX و بدون رفرش صفحه

   - تغییر تعداد (TouchSpin / ورود دستی) → ذخیرهٔ خودکار با تأخیر
   - حذف محصول (مودال تأیید) → AJAX DELETE
   - پس از هر تغییر: ناحیهٔ سبد + سایدبار + سبد کشویی هدر + شمارنده
     همه با پاسخ سرور جایگزین می‌شوند (بدون window.location)
   ============================================================ */

(function ($) {
    'use strict';

    var syncTimer = null;

    /* ------------------ شمارشگر تعداد (TouchSpin) ------------------
       به اسکوپ بیرونی منتقل شد تا بعد از «هر» رندر مجدد ناحیهٔ سبد
       (حتی وقتی پاسخ سرور خطای موجودی است) دوباره روی اینپوت‌های تازه اجرا شود.
       قبلاً فقط $(document).ajaxSuccess این کار را می‌کرد که در درخواست‌های
       خطادار اجرا نمی‌شود و به همین دلیل بعد از خطای موجودی، استایل کادر تعداد
       به‌کلی می‌ریخت (اینپوت خام بدون دکمهٔ +/−). */

    /* فاز ۱۹۱ — بازخورد مرز min/max:
       تاچ‌اسپین در سقف/کف موجودی «بی‌صدا» هیچ کار نمی‌کند و کاربر فکر می‌کند
       دکمهٔ +/− خراب است (دقیقاً گزارش «دکمهٔ زیاد/کم کردن تعداد کار نمی‌کند»).
       حالا:
       ۱) دکمهٔ مربوطه در مرز، ظاهرِ غیرفعال می‌گیرد (کلاس + aria-disabled)
       ۲) پیغام شفاف «بیشترین موجودی …» / «حداقل تعداد …» نمایش داده می‌شود */

    function applyTouchSpinBounds($input) {
        if (!$input || !$input.length) { return; }

        var $wrap = $input.closest('.bootstrap-touchspin');
        if (!$wrap.length) { return; }

        var val = parseFloat(String($input.val()).replace(/[^0-9.\-]/g, ''));
        var min = $input.attr('min');
        var max = $input.attr('max');

        min = (min === '' || min === null || isNaN(parseFloat(min))) ? null : parseFloat(min);
        max = (max === '' || max === null || isNaN(parseFloat(max))) ? null : parseFloat(max);

        var $up = $wrap.find('.bootstrap-touchspin-up');
        var $down = $wrap.find('.bootstrap-touchspin-down');

        var atMax = max !== null && !isNaN(val) && val >= max;
        var atMin = min !== null && !isNaN(val) && val <= min;

        $up.toggleClass('saniar-qty-capped', atMax).attr('aria-disabled', atMax ? 'true' : 'false');
        $down.toggleClass('saniar-qty-capped', atMin).attr('aria-disabled', atMin ? 'true' : 'false');
    }

    function qtyBoundMessage($input, which) {
        var max = $input.attr('max');
        var min = $input.attr('min');

        if (which === 'max') {
            showCustomToast(max ? ('بیشترین تعداد قابل سفارش این محصول ' + max + ' عدد است.') : 'به سقف تعداد مجاز رسیدید.', 'info');
        } else {
            showCustomToast(min ? ('حداقل تعداد این محصول ' + min + ' عدد است.') : 'حداقل تعداد مجاز ۱ عدد است.', 'info');
        }
    }

    function initTouchspin() {
        $('#cart-update-form input.cart_quantity').each(function () {
            var $input = $(this);
            if ($input.attr('data-touchspin-initialized')) {
                applyTouchSpinBounds($input);
                return;
            }

            $input.TouchSpin({
                min: parseFloat($input.attr('min')) || 1,
                max: parseFloat($input.attr('max')) || 100000000,
                step: parseFloat($input.attr('step')) || 1,
                initval: $input.val(),
                decimals: 0,
                boostat: 5,
                maxboostedstep: 10,
                buttondown_class: 'btn-counter waves-effect waves-light',
                buttonup_class: 'btn-counter waves-effect waves-light'
            });

            $input.attr('data-touchspin-initialized', '1');
            applyTouchSpinBounds($input);
        });
    }

    function initTooltips() {
        if (window.bootstrap && window.bootstrap.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                window.bootstrap.Tooltip.getOrCreateInstance(el);
            });
        }
    }

    /* ------------------ اعمال پاسخ سرور ------------------ */

    function applyCartPayload(res) {
        if (!res) return;

        /* ناحیهٔ اصلی سبد (آیتم‌ها یا حالت خالی) */
        if (res.area && $('#cart-main-area').length) {
            $('#cart-main-area').replaceWith(res.area);
        }

        /* سایدبار «سفارش شما» */
        if (typeof res.sidebar === 'string') {
            if (res.sidebar && $('#checkout-sidebar').length) {
                $('#checkout-sidebar').replaceWith(res.sidebar);
            } else if (!res.sidebar && $('.checkout-summary').length) {
                /* سبد خالی شد → سایدبار هم حذف شود */
                $('#checkout-sidebar').replaceWith('<span id="checkout-sidebar"></span>');
            }
        }

        /* سبد کشویی هدر + شمارنده — فاز ۲۱۰: شمارندهٔ صریح سرور پاس داده می‌شود */
        if (res.cart) {
            /* بروزرسانی درجای سایدبار (بدون بازسازی گره — رفع بسته/بازشدن و قفل اسکرول) */
            if (window.SaniarThemeCartSync) {
                window.SaniarThemeCartSync(
                    res.cart,
                    typeof res.cart_count !== 'undefined' && res.cart_count !== null ? parseInt(res.cart_count, 10) : null
                );
            }
        }

        /* صفحهٔ پرداخت (اگر سایدبار مشترک باشد): محاسبهٔ مجدد هزینهٔ ارسال */
        if (typeof getFinalPrice === 'function' && $('.consignment-container .consignments').length) {
            try { getFinalPrice(true); } catch (e) {}
        }

        /* بعد از رندر مجدد (موفق یا خطای موجودی) شمارشگرها و تولتیپ‌های
           تازه‌رندرشده دوباره فعال شوند */
        initTouchspin();
        initTooltips();
    }

    /* ------------------ ذخیرهٔ تغییرات تعداد ------------------ */

    function syncCart() {
        var $form = $('#cart-update-form');
        if (!$form.length) return;

        block('.cart-product');
        if ($('.checkout-summary').length) { block('.checkout-summary'); }

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            success: function (res) {
                applyCartPayload(res);
                showCustomToast('سبد خرید بروزرسانی شد', 'success');
            },
            error: function (request) {
                var res = request.responseJSON;
                var errors = res && res.errors;
                if (errors && errors.length) {
                    errors.forEach(function (error) {
                        showCustomToast(error, 'error');
                    });
                } else {
                    showCustomToast('خطایی رخ داده است.', 'error');
                }
                /* سرور در پاسخ خطا وضعیت واقعی سبد را هم می‌فرستد؛
                   شمارشگر/جمع کل به مقدار مجاز برمی‌گردد (عدد غلط نمی‌ماند) */
                if (res && res.area) {
                    applyCartPayload(res);
                }
            },
            complete: function () {
                unblock('.cart-product');
                if ($('.checkout-summary').length) { unblock('.checkout-summary'); }
            },
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
            }
        });
    }

    function scheduleCartSync(delay) {
        clearTimeout(syncTimer);
        syncTimer = setTimeout(syncCart, delay || 600);
    }

    /* ------------------ رویدادها ------------------ */

    $(function () {

        /* شمارشگر تعداد (TouchSpin — مطابق قالب سانیار) */
        initTouchspin();

        /* بعد از رندر مجدد ناحیهٔ سبد، شمارشگرها دوباره فعال شوند (حالت موفق) */
        /* فاز ۱۸۸ — با هر بازدید SPA از صفحهٔ سبد، این اسکریپت دوباره اجرا می‌شود؛
           ajaxSuccess فقط یک‌بار ثبت شود (وگرنه N بار اجرا می‌شد) */
        if (!window.__saniarCartAjaxSuccessBound) {
            window.__saniarCartAjaxSuccessBound = true;
            $(document).ajaxSuccess(function () {
                initTouchspin();
            });
        }

        /* تولتیپ‌های سانیار (Bootstrap 5) */
        initTooltips();

        /* تغییر تعداد → ذخیرهٔ خودکار (بدون دکمهٔ بروزرسانی) */
        /* فاز ۱۸۸ — namespace guard: با بازدید مجدد SPA، هندلر قبلی جایگزین می‌شود نه تکرار */
        $(document).off('change.saniarCart').on('change.saniarCart', '#cart-update-form input.cart_quantity', function () {
            scheduleCartSync();
        });

        /* فاز ۱۹۱ — مرز min/max شمارشگر: پیغام شفاف + وضعیت بصری دکمه‌ها
           (قبلاً در سقف موجودی، کلیک بی‌صدا هیچ کاری نمی‌کرد و کاربر دکمه را
           «خراب» گزارش می‌کرد) */
        $(document).off('touchspin.on.max.saniarQty touchspin.on.min.saniarQty')
            .on('touchspin.on.max.saniarQty', '#cart-update-form input.cart_quantity', function () {
                applyTouchSpinBounds($(this));
                qtyBoundMessage($(this), 'max');
            })
            .on('touchspin.on.min.saniarQty', '#cart-update-form input.cart_quantity', function () {
                applyTouchSpinBounds($(this));
                qtyBoundMessage($(this), 'min');
            });

        $(document).off('change.saniarQtyState input.saniarQtyState')
            .on('change.saniarQtyState input.saniarQtyState', '#cart-update-form input.cart_quantity', function () {
                applyTouchSpinBounds($(this));
            });

        /* دکمهٔ «بروزرسانی سبد خرید» هم بدون رفرش صفحه همان کار را می‌کند */
        $(document).off('click.saniarCartUpdate').on('click.saniarCartUpdate', '#update-cart', function (e) {
            e.preventDefault();
            if (!$('#cart-update-form').length) return;
            clearTimeout(syncTimer);
            syncCart();
        });

        /* دکمهٔ «تسویه حساب» (سبد → مرحلهٔ بعد):
           اول تغییرات سبد ذخیره می‌شود (موجودی دوباره کنترل می‌شود)،
           سپس به صفحهٔ پرداخت هدایت می‌شود.
           قبلاً این دکمه در صفحهٔ سبد هیچ کاری نمی‌کرد، چون هندلرهای عمومی فقط
           فرم #checkout-form صفحهٔ پرداخت را تریگر می‌کردند که در صفحهٔ سبد
           وجود ندارد (رفع باگ «وارد مرحله بعد نمیشه»). */
        $(document).off('click.saniarCartCheckout').on('click.saniarCartCheckout', '#checkout-link', function (e) {
            if (!$('#cart-update-form').length) {
                return; /* صفحهٔ پرداخت — هندلر خودش در checkout.js است */
            }

            e.preventDefault();

            var btn = this;
            var redirectUrl = $(btn).data('redirect');
            if (!redirectUrl) return;

            clearTimeout(syncTimer);
            block(btn);
            if ($('.checkout-summary').length) { block('.checkout-summary'); }

            $.ajax({
                url: $('#cart-update-form').attr('action'),
                type: 'POST',
                data: $('#cart-update-form').serialize(),
                success: function (res) {
                    applyCartPayload(res);
                    /* فاز ۱۸۸ — رفتن به صفحهٔ پرداخت بدون رفرش (SPA) */
                    if (window.saniarSoftNavigate) {
                        window.saniarSoftNavigate(redirectUrl);
                    } else {
                        window.location.href = redirectUrl;
                    }
                },
                error: function (request) {
                    var res = request.responseJSON;
                    var errors = res && res.errors;
                    if (errors && errors.length) {
                        errors.forEach(function (error) {
                            showCustomToast(error, 'error');
                        });
                    } else {
                        showCustomToast('خطایی رخ داده است.', 'error');
                    }
                    /* وضعیت واقعی سبد برمی‌گردد تا تعداد/جمع کل درست شود */
                    if (res && res.area) {
                        applyCartPayload(res);
                    }
                },
                complete: function () {
                    unblock(btn);
                    if ($('.checkout-summary').length) { unblock('.checkout-summary'); }
                },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
                }
            });
        });

        /* ------------------ حذف محصول ------------------ */

        $(document).off('click.saniarCartRemove').on('click.saniarCartRemove', '.remove-card', function (e) {
            e.preventDefault();
            $('#delete-form').attr('action', $(this).data('action'));
            if (window.jQuery.fn.modal) {
                jQuery('#delete-modal').modal('show');
            } else if (window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('delete-modal')).show();
            }
        });

        /* تأیید حذف در مودال → AJAX DELETE بدون رفرش */
        $(document).off('submit.saniarCartDelete').on('submit.saniarCartDelete', '#delete-form', function (e) {
            e.preventDefault();

            var $form = $(this);
            var action = $form.attr('action');
            if (!action) return;

            block('.cart-product');

            $.ajax({
                url: action,
                type: 'POST',
                data: $form.serialize(),
                success: function (res) {
                    applyCartPayload(res);
                    showCustomToast('محصول از سبد خرید حذف شد', 'success');
                },
                error: function () {
                    showCustomToast('خطایی رخ داده است.', 'error');
                },
                complete: function () {
                    unblock('.cart-product');
                    /* بستن مودال */
                    var modalEl = document.getElementById('delete-modal');
                    if (modalEl && window.bootstrap) {
                        window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                    }
                },
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
                }
            });
        });
    });
})(jQuery);
