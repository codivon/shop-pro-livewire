@php
    /* فاز ۱۹۲ — حالت روز/شب از کوکی (همراه localStorage) در سمت سرور:
       Livewire.navigate اتریبیوت‌های <html> را از HTML صفحهٔ جدید می‌گیرد؛
       اکنون کوکی saniar-theme مقدار اولیهٔ صحیح را تضمین می‌کند. */
    $saniar_theme_attr = ($_COOKIE['saniar-theme'] ?? '') === 'dark' ? 'dark' : 'light';
@endphp
{{--
    لایهٔ اختصاصی صفحهٔ چک‌اوت و بعد از آن (نتیجهٔ پرداخت)
    طبق نیاز محصول: نه هدر اصلی سایت و نه فوتر؛ فقط یک هدر ساده
    شبیه دیجی‌کالا (لوگو وسط + دکمهٔ بازگشت) و محتوای فرم تسویه.
--}}
@php
    $cart = get_cart();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl" data-theme="{{ $saniar_theme_attr }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    {{-- پشتیبانی تم تیرهٔ چک‌اوت: theme-init باید پیش از رندر اجرا شود
         تا اگر تم شب فعال بود صفحه از همان قاب اول تیره رندر شود (بدون فلش سفید) --}}
    <script src="{{ theme_asset('js/theme-init.js') }}"></script>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ option('info_icon', theme_asset('image/logo.png')) }}">

    {{-- برای ایجکسی‌های چک‌اوت (قیمت‌ها، آدرس، کد تخفیف) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' | ' : '' }}{{ option('info_site_title', 'او پی شاپ') }}</title>

    {{-- فونت‌ها --}}
    {{-- خودمیزبانی FA: cdnjs روی IPهای ایران کند است و رندر را بلاک می‌کرد --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" referrerpolicy="no-referrer">

    {{-- بوت‌استرپ ۵ RTL + آیکون‌ها (همان ست هدر اصلی) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('font/bootstrap-icon/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/materialdesignicons.css') }}">

    {{-- استایل‌های مشترک تم (متغیرهای رنگ، content-box، bank-item، pay-table و ...) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/style.css') }}?v=174">
    <link rel="stylesheet" href="{{ theme_asset('css/responsive.css') }}?v=173">

    {{-- تم تیرهٔ چک‌اوت (قوانین اختصاصی چک‌اوت انتهای همین فایل است) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/theme-dark.css') }}?v=27">

    {{-- توست‌ها --}}
    <link rel="stylesheet" href="{{ theme_asset('js/plugins/toastr/toastr.css') }}">

    @stack('styles')

    {{-- GTM & Meta Pixel — پیگیری تبدیل در چک‌اوت --}}
    @php
        $gtm_id = option('info_gtm_id', '');
        $meta_pixel_id = option('info_meta_pixel', '');
    @endphp

    @if(!empty($gtm_id))
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtm_id }}');</script>
    @endif

    @if(!empty($meta_pixel_id))
        <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $meta_pixel_id }}');fbq('track','PageView');</script>
        <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $meta_pixel_id }}&ev=PageView&noscript=1"/></noscript>
    @endif

</head>

<body class="checkout-simple-page">

{{-- هدر سادهٔ چک‌اوت — مثل دیجی‌کالا: دکمهٔ بازگشت + لوگو وسط --}}
<header class="snr-checkout-header">
    <div class="container-fluid">
        <div class="snr-checkout-header-inner">

            <a href="@yield('checkout-back-url', route('front.cart'))" class="snr-checkout-back">
                <i class="bi bi-arrow-right"></i>
                <span>بازگشت</span>
            </a>

            <a href="{{ route('front.index') }}" class="snr-checkout-logo"
                aria-label="{{ option('info_site_title', 'او پی شاپ') }}">
                <img src="{{ option('info_logo', theme_asset('image/logo.png')) }}"
                    alt="{{ option('info_site_title', 'او پی شاپ') }}">
            </a>

            <span class="snr-checkout-header-spacer" aria-hidden="true"></span>

        </div>
    </div>
</header>

<main class="snr-checkout-main">
    @yield('content')
</main>

<script>
    var BASE_URL = "{{ route('front.index') }}";
    var IS_RTL = {{ $current_local['direction'] == 'rtl' ? 1 : 0 }};
    var site_title = "{{ option('info_site_title', 'او پی شاپ') }}";
</script>

{{-- اسکریپت‌های پایه (jQuery + بوت‌استرپ + پلاگین‌های عملکردی چک‌اوت) --}}
{{-- فاز ۱۹۴ — data-navigate-once: اجرای مجدد jQuery در ناوبری SPA، window.jQuery
     را بازتعریف می‌کند و هندلرهای delegated صفحهٔ قبلی می‌میرند --}}
<script src="{{theme_asset('js/jquery.min.js')}}" data-navigate-once></script>
@stack('scripts-top-js')
<script src="{{theme_asset('js/bootstrap.bundle.min.js')}}" data-navigate-once></script>

{{-- پلاگین‌های عملکردی موردنیاز چک‌اوت (block/unblock + toast + sweetalert) --}}
{{-- فاز ۱۹۴ — نسخه‌ها + data-navigate-once با لایوت اصلی یکسان شد تا اجرای مجدد
     اسکریپت‌های مشترک در ناوبری SPA (خطای redeclaration مثل TOAST_MAX/performSearch) رخ ندهد --}}
<script src="{{ theme_asset("js/plugins/sweetalert2.all.min.js") }}" data-navigate-once></script>
<script src="{{ theme_asset("js/plugins/jquery.blockUI.js") }}" data-navigate-once></script>
<script src="{{ theme_asset("js/plugins/toastr/toastr.min.js") }}" data-navigate-once></script>
<script src="{{ asset('back/assets/js/customToast.js') }}?v=9" data-navigate-once></script>
{{-- block()/unblock() در scripts.js تعریف شده‌اند --}}
<script src="{{ theme_asset("js/scripts.js") }}?v=13" data-navigate-once></script>
<script src="{{theme_asset('js/main.js')}}?v=21" data-navigate-once></script>

{{-- فاز ۱۸۸ — اسکریپت‌های لایووایر و موتور SPA تم، تا ناوبری از چک‌اوت هم بدون رفرش باشد --}}
@if (class_exists(\Livewire\Livewire::class))
<livewire:scripts />
@endif

@stack('scripts')

{!! option('info_scripts') !!}

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
@if(session('toast-warning'))
    <script>
        showCustomToast('{{session('toast-warning')}}','warning');
    </script>
@endif

{{-- فاز ۱۸۸ — موتور SPA تم (intercept کلیک + prefetch) — data-navigate-once تا در ناوبری مجدد اجرا نشود --}}
<script src="{{theme_asset('js/saniar-spa.js')}}?v=8" data-navigate-once></script>

</body>

</html>
