@extends('front::user.layouts.master')

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('css/vendor/nice-select.css') }}">
@endpush

@section('user-content')

    {{-- ویرایش اطلاعات شخصی — بازطراحی کامل فاز ۲۰۰ --}}
    <div class="usp-scope">

        {{-- سربرگ صفحه --}}
        <div class="usp-page-head">
            <div class="usp-page-head-title">
                <span class="usp-page-head-icon" aria-hidden="true"><i class="bi bi-person-gear"></i></span>
                <div>
                    <h2>ویرایش اطلاعات شخصی</h2>
                    <p>اطلاعات حساب کاربری خود را کامل و به‌روز نگه دارید.</p>
                </div>
            </div>
            <a href="{{ route('front.user.password') }}" class="btn btn-sm btn-light">
                <i class="bi bi-shield-lock"></i> تغییر رمز ورود
            </a>
        </div>

        {{-- کارت فرم اطلاعات حساب --}}
        <div class="usp-card">
            <div class="usp-card-head">
                <h4>اطلاعات حساب</h4>
            </div>
            <div class="usp-card-body">
                <form id="profile-form" action="{{ route('front.user.profile.update') }}" class="setting_form" method="POST">
                    @method('put')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" value="{{ $user->first_name }}" name="first_name" id="name-first"
                                       class="form-control float-input" placeholder="نام خود را وارد کنید">
                                <label for="name-first">نام خود را وارد کنید</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input name="last_name" type="text" id="name-last"
                                       class="form-control float-input" placeholder="نام خانوادگی خود را وارد کنید"
                                       value="{{ $user->last_name }}">
                                <label for="name-last">نام خانوادگی خود را وارد کنید</label>
                            </div>
                        </div>
                    </div>

                    @php $birth_date=explode('/',$user->birth_date) @endphp
                    <div class="row">
                        <div class="col-md-4">
                            <div class="comment-item mb-3">
                                <label class="form-label label-float">تاریخ تولد</label>
                                <select name="day" id="day" class="form-select">
                                    <option value="date-desc" selected="selected">روز</option>
                                    @for($i=1;$i<=31;$i++)
                                        <option @if(@$birth_date[2]==$i) selected @endif value="{{$i}}">{{$i}}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comment-item mb-3">
                                <label class="form-label label-float">&nbsp;</label>
                                <select name="month" id="month" class="form-select">
                                    <option value="date-desc" selected="selected">ماه</option>
                                    <option @if(@$birth_date[1]==1) selected @endif value="1">فروردین</option>
                                    <option @if(@$birth_date[1]==2) selected @endif value="2">اردیبهشت</option>
                                    <option @if(@$birth_date[1]==3) selected @endif value="3">خرداد</option>
                                    <option @if(@$birth_date[1]==4) selected @endif value="4">تیر</option>
                                    <option @if(@$birth_date[1]==5) selected @endif value="5">مرداد</option>
                                    <option @if(@$birth_date[1]==6) selected @endif value="6">شهریور</option>
                                    <option @if(@$birth_date[1]==7) selected @endif value="7">مهر</option>
                                    <option @if(@$birth_date[1]==8) selected @endif value="8">آبان</option>
                                    <option @if(@$birth_date[1]==9) selected @endif value="9">آذر</option>
                                    <option @if(@$birth_date[1]==10) selected @endif value="10">دی</option>
                                    <option @if(@$birth_date[1]==11) selected @endif value="11">بهمن</option>
                                    <option @if(@$birth_date[1]==12) selected @endif value="12">اسفند</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="comment-item mb-3">
                                <label class="form-label label-float">&nbsp;</label>
                                <select name="year" id="year" class="form-select">
                                    <option value="date-desc" selected="selected">سال</option>
                                    @for($i=1360;$i<=1400;$i++)
                                        <option @if(@$birth_date[0]==$i) selected @endif value="{{$i}}">{{$i}}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" id="code-national" class="form-control float-input" name="national_code"
                                       value="{{$user->national_code}}" placeholder="کد ملی خود را وارد کنید">
                                <label for="code-national">کد ملی خود را وارد کنید</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" id="phone" class="form-control float-input" disabled
                                       value="{{ $user->username }}" placeholder="شماره موبایل خود را وارد کنید">
                                <label for="phone">شماره موبایل خود را وارد کنید</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" id="email" class="form-control float-input" name="email"
                                       value="{{ $user->email }}" placeholder="آدرس ایمیل خود را وارد کنید">
                                <label for="email">آدرس ایمیل خود را وارد کنید</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" id="number-card" name="card_number" class="form-control float-input"
                                       placeholder="شماره کارت خود را وارد کنید" value="{{ $user->card_number }}">
                                <label for="number-card">شماره کارت خود را وارد کنید</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" value="1" name="newsletter" @if($user->newsletter==1) checked @endif id="remember2" class="form-check-input">
                        <label for="remember2" class="form-check-label cursor-pointer">اشتراک در خبرنامه {{ option('info_site_title', 'او پی شاپ') }}</label>
                    </div>

                    <div class="mb-2">
                        <button id="submit-btn" type="submit"
                                class="btn border-0 main-color-one-bg waves-effect waves-light py-2 px-5">
                            <i class="bi bi-pencil-square text-white me-1"></i> ثبت اطلاعات کاربری
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script src="{{ theme_asset('js/vendor/jquery.nice-select.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>

    <script src="{{ theme_asset('js/pages/edit-profile.js?v=2') }}"></script>
@endpush
