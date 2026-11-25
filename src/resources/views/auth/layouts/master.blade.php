@php
    /* فاز ۱۹۲ — حالت روز/شب از کوکی (همراه localStorage) در سمت سرور:
       Livewire.navigate اتریبیوت‌های <html> را از HTML صفحهٔ جدید می‌گیرد؛
       اکنون کوکی saniar-theme مقدار اولیهٔ صحیح را تضمین می‌کند. */
    $saniar_theme_attr = ($_COOKIE['saniar-theme'] ?? '') === 'dark' ? 'dark' : 'light';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl" data-theme="{{ $saniar_theme_attr }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        /* فاز ۲۱۰ — رنگ نوار مرورگر (theme-color) هم‌رنگ پوستهٔ فعال قالب؛
           قبلاً رنگ قدیمی قالب هاردکد شده بود و با تغییر پوسته عوض نمی‌شد */
        try {
            $sa_auth_meta_color = \Themes\SaniarTheme\src\Support\PaletteEngine::stateFromOption()['payload']['base'] ?? '#D62976';
        } catch (\Throwable $sa_auth_meta_error) {
            $sa_auth_meta_color = '#D62976';
        }
    @endphp
    <meta name="theme-color" content="{{ $sa_auth_meta_color }}">
    <meta name="msapplication-navbutton-color" content="{{ $sa_auth_meta_color }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="{{ $sa_auth_meta_color }}">
    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="16x16" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">

    <title>
        @isset($title)
            {{ $title }} |
        @endisset

        {{ option('info_site_title', 'او پی شاپ') }}
    </title>

    {{-- حالت روز/شب: مقداردهی قبل از رندر برای جلوگیری از فلش --}}
    <script src="{{ theme_asset('js/theme-init.js') }}?v=2"></script>

    {{-- قالب سانیار — همان فایل‌های css صفحات قالب (register.html) --}}
    {{-- فاز ۱۹۴ — نسخه‌ها با لایوت اصلی یکسان شد تا در mergeNewHead dedupe شود --}}
    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('font/bootstrap-icon/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/waves/waves.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/timer/timer.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/style.css') }}?v=178">
    <link rel="stylesheet" href="{{ theme_asset('css/responsive.css') }}?v=173">
    <link rel="stylesheet" href="{{ theme_asset('js/plugins/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/auth.css') }}">

    {{-- دارک مود در صفحات ورود/ثبت‌نام --}}
    <link rel="stylesheet" href="{{ theme_asset('css/theme-dark.css') }}?v=27">

    {{-- فاز ۲۱۰ — پوستهٔ رنگی قالب در صفحات ورود/ثبت‌نام:
         این لایوت تا فاز ۲۰۹ تزریق متغیرهای پوسته را نداشت و
         صفحهٔ لاگین همیشه با رنگ پیش‌فرض قالب (آبی) رندر می‌شد؛
         اکنون همان بلوک master.blade.php اینجا هم تزریق می‌شود
         (بعد از theme-dark.css تا نسخهٔ تیره هم بازنویسی شود) --}}
    @include('front::partials.appearance-vars')

    @yield('style')
    @stack('styles')
</head>

<body class="bg-auth">

    {{-- کارت احراز هویت — ساختار register.html قالب سانیار --}}
    <div class="content vh-100">
        <div class="container-fluid h-100">
            <div class="auth h-100 d-flex align-items-center">
                <div class="container-fluid">
                    <div class="auth-items">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- این br ها برای ایجاد اسکرول در صفحه هستند که تمام اجزای فرم به بدرستی نمایش داده شود --}}
    <br><br><br><br><br><br><br><br>

    {{-- فاز ۱۹۴ — ورود/ثبت‌نام حالا SPA است؛ نسخه‌های اسکریپت‌های مشترک +
         data-navigate-once با لایوت اصلی یکسان شد تا در ناوبری SPA اجرای مجدد
         (خطای redeclaration) یا دوبار لود رخ ندهد --}}
    <script src="{{theme_asset('js/jquery.min.js')}}" data-navigate-once></script>
    <script src="{{theme_asset('js/bootstrap.bundle.min.js')}}" data-navigate-once></script>
    <script src="{{theme_asset('plugin/waves/waves.min.js')}}" data-navigate-once></script>
    <script src="{{theme_asset('plugin/swiper/swiper-bundle.min.js')}}" data-navigate-once></script>
    <script src="{{theme_asset('plugin/timer/timer.js')}}" data-navigate-once></script>
    <script src="{{theme_asset('plugin/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js')}}" data-navigate-once></script>
    <script src="{{theme_asset('js/app.js')}}" data-navigate-once></script>
    <script src="{{theme_asset('js/jquery-app.js')}}?v=7" data-navigate-once></script>
    <script src="{{ theme_asset('js/vendor/ResizeSensor.min.js') }}" data-navigate-once></script>
    <script src="{{ theme_asset('js/plugins/jquery.blockUI.js') }}" data-navigate-once></script>
    <script src="{{ theme_asset('js/plugins/sweetalert2.all.min.js') }}" data-navigate-once></script>
    <script src="{{ theme_asset('js/plugins/toastr/toastr.min.js') }}" data-navigate-once></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}" data-navigate-once></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2" data-navigate-once></script>
    <script src="{{ asset('back/assets/js/customToast.js') }}?v=9" data-navigate-once></script>
    <script src="{{ theme_asset("js/scripts.js") }}?v=13" data-navigate-once></script>
    <script src="{{theme_asset('js/jquery.countdown.min.js')}}" data-navigate-once></script>
    <script src="{{theme_asset('js/owl.carousel.min.js')}}" data-navigate-once></script>
    <script src="{{theme_asset('js/main.js')}}?v=21" data-navigate-once></script>

    <script>
        var BASE_URL = "{{ route('front.index') }}";
    </script>

    {!! option('info_scripts') !!}
    @stack('scripts')
    @if(session('toast-success'))
        <script>
            showCustomToast('{{session('toast-success')}}','success');
        </script>
    @endif
    @if(session('toast-error'))
        <script>
            showCustomToast('{{session('toast-error')}}','error');
        </script>
    @endif
</body>

</html>
