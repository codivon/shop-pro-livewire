@extends('sellers-old.panel.layouts.master')

@section('content')
    {{-- فاز ۲۱۶ — حساب و امنیت فروشنده (پنل قدیمی) — لوگو + گذرواژه --}}
    {{-- لوگو و تغییر رمز از پروفایل به اینجا منتقل شدند چون این دو باید بدون
         تایید کارشناس و بلافاصله اعمال شوند (برخلاف سایر فیلدهای پروفایل). --}}
    <div class="c-content-page c-content-page--plain c-grid__row w-100 mb-2">
        <div class="c-grid__col">
            <div class="c-content-page__header">
                <span class="c-content-page__header-action">حساب و امنیت </span>
                <span class="c-content-page__header-desc">تغییر لوگوی فروشگاه و رمز ورود — این تغییرات بلافاصله اعمال می‌شوند و نیازی به تایید کارشناس ندارند.</span>
            </div>
        </div>
    </div>
    @include('sellers-old.panel.partials.sidebar')

    <div class="col-lg-9 col-md-8 col-xs-12 pull-right pr-0">
        <div class="row dashboard-profile">
            <div class="col-12 dashboard-profile-item">

                {{-- ---------- لوگوی فروشنده ---------- --}}
                <div class="c-card">
                    <div class="c-card__header">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link menu-title active" data-toggle="tab" href="#seller-logo">لوگوی فروشنده</a>
                            </li>
                        </ul>
                    </div>
                    <div class="c-card__body">
                        <div class="tab-content">
                            <div class="tab-pane container active" id="seller-logo">
                                <div class="form-body col-md-12" id="seller-logo-form-body">
                                    <div class="row w-100">
                                        <div class="col-md-3">
                                            <label>لوگوی فعلی</label>
                                            <div class="users-view-image">
                                                <img id="seller-logo-preview" src="{{ $seller_info->logo ? asset($seller_info->logo) : asset('/empty.svg') }}" class="users-avatar-shadow w-100 rounded mb-2 ml-1" alt="لوگوی فروشنده">
                                            </div>
                                        </div>

                                        <div class="col-md-9">
                                            <div class="form-group">
                                                <label>تغییر لوگو</label>
                                                <div class="custom-file">
                                                    <input type="file" accept="image/*" id="seller-logo-input" class="custom-file-input valid" aria-invalid="false">
                                                    <label class="custom-file-label" for="seller-logo-input"></label>
                                                    <p><small>بهترین اندازه <span class="text-danger">600 * 600</span> پیکسل میباشد. فرمت‌های مجاز: JPG، PNG، WEBP — حداکثر ۳ مگابایت.</small></p>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <button type="button" id="seller-logo-submit" class="btn btn-primary">
                                                    بارگذاری لوگو
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ---------- تغییر گذرواژه ---------- --}}
                <div class="c-card">
                    <div class="c-card__header">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link menu-title active" data-toggle="tab" href="#seller-password">تغییر گذرواژه</a>
                            </li>
                        </ul>
                    </div>
                    <div class="c-card__body">
                        <div class="tab-content">
                            <div class="tab-pane container active" id="seller-password">
                                <div class="form-body col-md-12" id="seller-password-form-body">
                                    <div class="row">
                                        <div class="col-md-6 col-sm-12 m-auto">
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label for="old-password">گذرواژه قبلی</label>
                                                    <input type="password" id="old-password" class="form-control ltr" name="prev_password">
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>گذرواژه جدید</label>
                                                    <input type="password" id="password" class="form-control ltr" name="password">
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-group">
                                                    <label>تکرار گذرواژه جدید</label>
                                                    <input type="password" id="password-confirmation" class="form-control ltr" name="password_confirmation">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="alert alert-info mt-1 alert-validation-msg" role="alert">
                                                <i class="feather icon-info ml-1 align-middle"></i>
                                                <span>پس از ذخیره، گذرواژهٔ جدید بلافاصله فعال می‌شود؛ دفعهٔ بعد با گذرواژهٔ جدید وارد شوید.</span>
                                            </div>
                                            <button type="button" id="seller-password-submit" class="btn btn-primary">
                                                ذخیرهٔ گذرواژه
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                'use strict';

                // ارسال فرم با fetch — هم‌الگوی index.js قدیمی پنل (کنترلر response('success') می‌دهد)
                function toast(message) {
                    if (typeof showCustomToast === 'function') { showCustomToast(message, 'info'); return; }
                    alert(message);
                }

                function firstError(errors) {
                    try {
                        return Object.values(errors)[0][0];
                    } catch (e) {
                        return 'خطا در ثبت اطلاعات، دوباره تلاش کنید.';
                    }
                }

                function getCsrfToken() {
                    var meta = document.querySelector('meta[name=csrf-token]');
                    return meta ? meta.content : (window.Laravel && window.Laravel.csrfToken ? window.Laravel.csrfToken : '');
                }

                // ---------- لوگو ----------
                var logoInput = document.getElementById('seller-logo-input');
                var logoPreview = document.getElementById('seller-logo-preview');
                var logoSubmit = document.getElementById('seller-logo-submit');

                if (logoInput && logoPreview) {
                    logoInput.addEventListener('change', function () {
                        if (this.files && this.files[0]) {
                            logoPreview.src = URL.createObjectURL(this.files[0]);
                        }
                    });
                }

                if (logoSubmit) {
                    logoSubmit.addEventListener('click', function () {
                        if (! logoInput || ! logoInput.files.length) {
                            toast('ابتدا تصویر لوگو را انتخاب کنید');
                            return;
                        }

                        var body = new FormData();
                        body.append('image', logoInput.files[0]);
                        body.append('_token', getCsrfToken());

                        logoSubmit.disabled = true;
                        fetch('{{ $logoUrl }}', {
                            method: 'POST',
                            body: body,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        }).then(function (res) {
                            if (res.ok) {
                                toast('لوگوی فروشنده با موفقیت به‌روزرسانی شد.');
                                setTimeout(function () { window.location.reload(); }, 900);
                                return;
                            }
                            return res.json().then(function (data) {
                                toast(firstError(data.errors || {}));
                                logoSubmit.disabled = false;
                            }).catch(function () {
                                toast('خطا در بارگذاری لوگو، دوباره تلاش کنید.');
                                logoSubmit.disabled = false;
                            });
                        }).catch(function () {
                            toast('ارتباط با سرور برقرار نشد.');
                            logoSubmit.disabled = false;
                        });
                    });
                }

                // ---------- گذرواژه ----------
                var passSubmit = document.getElementById('seller-password-submit');

                if (passSubmit) {
                    passSubmit.addEventListener('click', function () {
                        var prev = document.getElementById('old-password').value;
                        var pass = document.getElementById('password').value;
                        var confirmation = document.getElementById('password-confirmation').value;

                        if (! prev) { toast('وارد کردن گذرواژه قبلی الزامی است.'); return; }
                        if (! pass || pass.length < 6) { toast('گذرواژه جدید باید حداقل ۶ کاراکتر باشد.'); return; }
                        if (pass !== confirmation) { toast('تکرار گذرواژه جدید با گذرواژه جدید یکسان نیست.'); return; }

                        var body = new FormData();
                        body.append('prev_password', prev);
                        body.append('password', pass);
                        body.append('password_confirmation', confirmation);
                        body.append('_token', getCsrfToken());

                        passSubmit.disabled = true;
                        fetch('{{ $passwordUrl }}', {
                            method: 'POST',
                            body: body,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        }).then(function (res) {
                            if (res.ok) {
                                toast('رمز ورود شما تغییر کرد.');
                                document.getElementById('old-password').value = '';
                                document.getElementById('password').value = '';
                                document.getElementById('password-confirmation').value = '';
                                passSubmit.disabled = false;
                                return;
                            }
                            return res.json().then(function (data) {
                                toast(firstError(data.errors || {}));
                                passSubmit.disabled = false;
                            }).catch(function () {
                                toast('خطا در تغییر گذرواژه، دوباره تلاش کنید.');
                                passSubmit.disabled = false;
                            });
                        }).catch(function () {
                            toast('ارتباط با سرور برقرار نشد.');
                            passSubmit.disabled = false;
                        });
                    });
                }
            })();
        </script>
    @endpush
@endsection
