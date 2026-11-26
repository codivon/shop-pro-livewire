@php
    $cart = get_cart();

    /* فاز ۲۰۸ — پوستهٔ رنگی قالب (تنظیمات ظاهری پنل مدیریت):
       option «saniar_appearance» (JSON ساخت PaletteEngine) خوانده می‌شود و
       در <head> به‌صورت متغیرهای CSS تزریق می‌گردد تا کل تم فرانت با رنگ‌های
       پوستهٔ انتخابی رنگ بگیرد. اگر چیزی ذخیره نشده یا مقدار خراب باشد،
       payload پیش‌فرض (گرادیان اینستاگرامی) استفاده می‌شود. */
    $saniar_theme_cookie = $_COOKIE['saniar-theme'] ?? '';

    try {
        $saniar_appearance_state = \Themes\SaniarTheme\src\Support\PaletteEngine::stateFromOption();
    } catch (\Throwable $saniar_appearance_error) {
        $saniar_appearance_state = null;
    }

    $saniar_appearance_payload = $saniar_appearance_state['payload'] ?? null;

    if (! is_array($saniar_appearance_payload) || empty($saniar_appearance_payload['base'])) {
        $saniar_appearance_payload = [
            'base'           => '#D62976',
            'hover'          => '#B4215F',
            'dark_base'      => '#E8468A',
            'dark_hover'     => '#F06AA5',
            'scale'          => [],
            'gradient'       => ['#F58529', '#DD2A7B', '#8134AF', '#515BD4'],
            'gradient_angle' => 45,
            'mode'           => 'light',
            'font_color'      => null,
            'font_color_dark' => null,
        ];
    }

    /* فاز ۱۹۲ — حالت روز/شب از کوکی (همراه localStorage) در سمت سرور:
       Livewire.navigate اتریبیوت‌های <html> را از HTML صفحهٔ جدید می‌گیرد؛
       اگر data-theme سمت سرور رندر نشود، بعد از هر ناوبری تم روشن می‌شد
       تا کاربر رفرش کند. اکنون کوکی saniar-theme مقدار اولیهٔ صحیح را
       هم در لود کامل و هم در ناوبری SPA تضمین می‌کند.

       فاز ۲۰۸ — حالت پیش‌فرض سایت از تنظیمات ظاهری پنل: کوکی/انتخابِ
       قبلیِ کاربر مقدم است؛ فقط وقتی کوکی‌ای نیست، انتخاب مدیر
       (روشن/تیره در کارت «ظاهر و رنگ‌بندی») ملاک می‌شود. */
    if ($saniar_theme_cookie === 'dark') {
        $saniar_theme_attr = 'dark';
    } elseif ($saniar_theme_cookie === 'light') {
        $saniar_theme_attr = 'light';
    } else {
        $saniar_theme_attr = ($saniar_appearance_payload['mode'] ?? 'light') === 'dark' ? 'dark' : 'light';
    }
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="rtl" data-theme="{{ $saniar_theme_attr }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    {{-- حالت روز/شب: مقداردهی قبل از رندر برای جلوگیری از فلش --}}
    <script src="{{ theme_asset('js/theme-init.js') }}?v=2"></script>

    {{-- Favicon --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ option('info_icon', theme_asset('images/logo-default.png')) }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ option('info_icon', theme_asset('images/logo-default.png')) }}">

    {{-- SEO & Robots --}}
    <meta name="robots" content="{{ $robots ?? 'index, follow' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="Auth" content="@auth('web') Yes @else No @endauth">
    <meta name="description" content="{{ $description ?? option('info_short_description') }}">

    {{-- Canonical URL (مهم) --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph (پایه - برای صفحه اصلی) --}}
    <meta property="og:title" content="@isset($title){{ $title }} | @endisset{{ option('info_site_title', 'او پی شاپ') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ option('info_site_title', 'او پی شاپ') }}">
    <meta property="og:description" content="{{ $description ?? option('info_short_description') }}">
    @php $ogImage = $og_image ?? option('info_logo', theme_asset('images/logo-default.png')); @endphp
    <meta property="og:image" content="{{ $ogImage }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@isset($title){{ $title }} | @endisset{{ option('info_site_title', 'او پی شاپ') }}">
    <meta name="twitter:description" content="{{ $description ?? option('info_short_description') }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    {{-- Dynamic Meta Tags from Pages --}}
    @stack('meta')

    {{-- Title --}}
    <title>{{ isset($title) ? $title . ' | ' : '' }}{{ option('info_site_title', 'او پی شاپ') }}</title>

    {{-- اندپوینت جستجوی زنده برای JS هدر --}}
    <meta name="saniar-ajax-search" content="{{ route('front.products.index') }}">
    {{-- اندپوینت جستجوی لحظه‌ای پیشرفته (محصولات/دسته‌بندی/برند) --}}
    <meta name="saniar-suggest" content="{{ route('front.products.suggest') }}">
    {{-- فاز ۱۸۵ — صفحهٔ ورود برای هدایت مهمان‌ها (علاقه‌مندی و …) --}}
    <meta name="saniar-login" content="{{ route('login') }}">

    {{-- Styles --}}
    @stack('befor-styles')

    {{-- خودمیزبانی FA: cdnjs روی IPهای ایران کند است و رندر را بلاک می‌کرد --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">

    {{-- سانیار — CSS تمپلیت (ترتیب دقیق مطابق index.html) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/bootstrap.rtl.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/bootstrap-icon/bootstrap-icons.css?v=2') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/waves/waves.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/timer/timer.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('css/style.css') }}?v=189">
    <link rel="stylesheet" href="{{ theme_asset('css/responsive.css') }}?v=175">

    {{-- اسلایدر owl در ویجت‌های پنل کاربری استفاده می‌شود؛
         بدون این CSS آفست افقی تمام‌صفحه ایجاد می‌شد --}}
    <link rel="stylesheet" href="{{ theme_asset('css/owl.carousel.min.css') }}">

    {{-- اصلاحات ظاهری ریز (هدر/محصول/صفحات) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/theme-header.css') }}?v=165">
    <link rel="stylesheet" href="{{ theme_asset('css/theme-product.css') }}?v=21">
    <link rel="stylesheet" href="{{ theme_asset('css/theme-pages.css') }}">

    {{-- /۱۵۸ — حالت شب (دارک مود) — بعد از همهٔ استایل‌ها تا بتواند بازنویسی کند --}}
    <link rel="stylesheet" href="{{ theme_asset('css/theme-dark.css') }}?v=33">

    {{-- CSS عملکردی مشترک (توست‌ها + آیکون‌های منوهای داینامیک) --}}
    <link rel="stylesheet" href="{{ theme_asset('js/plugins/toastr/toastr.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/rasta-contact/style.css') }}">

    {{-- فاز ۱۹۴ — CSS ویجت شناور و پیام popup به head منتقل شد:
         قبلاً <link> داخل body بود؛ در morph لایووایر لحظه‌ای حذف/جایگزین می‌شد
         و تا اعمال مجدد CSS، پنل ویجت (که با opacity:0 مخفی می‌شود) کاملاً
         دیده می‌شد و سپس با ترنزیشن جمع می‌شد — همان «باز و بسته شدن خودکار»
         ویجت پشتیبانی پس از هر ناوبری SPA (حتی دوبار). در head، mergeNewHead
         تگ یکسان را dedupe می‌کند و هرگز unload نمی‌شود. --}}
    <link rel="stylesheet" href="{{ theme_asset('css/floating-widget.css') }}?v=176">
    <link rel="stylesheet" href="{{ theme_asset('css/notifications-popup.css') }}?v=2">

    @php
        /* فاز ۲۰۸ — ساخت CSS پوستهٔ رنگی.
           بعد از theme-dark.css می‌آید تا هم :root و هم نسخهٔ تیره بازنویسی شود؛
           و قبل از info_header_codes تا CSS سفارشی مدیر همچنان آخرین حرف را بزند.
           کل تم (۸۳+ مصرف var(--main-color-one) و هدر/محصول/حالت شب) با همین
           چند متغیر یک‌جا رنگ می‌گیرد — بدون دست‌زدن به کلاس‌ها.

           فاز ۲۱۰ —
             • ‎--main-color-one-rgb: برای همهٔ سایه/پس‌زمینه‌های rgba(39,95,224,…)
               که در CSSهای قالب هاردکد بودند (محصول/پنل کاربر/برندها/نتیجهٔ
               پرداخت) — با تغییر پوسته همه هم‌رنگ می‌شوند.
             • ‎--color-site: رنگ فونت (متن) سایت از تنظیمات ظاهری؛ جدا برای
               حالت روشن و تم شب. خالی بودن = پیش‌فرض قالب (بدون بازنویسی). */
        $sa_payload = $saniar_appearance_payload;
        $sa_gradient = 'linear-gradient(' . (int) ($sa_payload['gradient_angle'] ?? 135) . 'deg, '
            . implode(', ', $sa_payload['gradient'] ?? ['#D62976']) . ')';

        try {
            $sa_engine = \Themes\SaniarTheme\src\Support\PaletteEngine::class;
            $sa_rgb = $sa_engine::hexToRgbString($sa_payload['base'] ?? '#D62976');
            $sa_rgb_dark = $sa_engine::hexToRgbString($sa_payload['dark_base'] ?? ($sa_payload['base'] ?? '#D62976'));
        } catch (\Throwable $sa_rgb_error) {
            $sa_rgb = '214, 41, 118';
            $sa_rgb_dark = $sa_rgb;
        }

        $sa_font_color = $sa_payload['font_color'] ?? null;
        $sa_font_color_dark = $sa_payload['font_color_dark'] ?? null;
    @endphp
    <style id="saniar-appearance-vars">
        :root {
            --main-color-one: {{ $sa_payload['base'] }};
            --main-color-one-hover: {{ $sa_payload['hover'] }};
            --main-color-one-shadow: {{ $sa_payload['base'] }}31;
            --main-color-one-rgb: {{ $sa_rgb }};
            --st-gradient: {{ $sa_gradient }};
@if (! empty($sa_payload['scale']) && is_array($sa_payload['scale']))
@foreach ($sa_payload['scale'] as $sa_step => $sa_hex)
            --st-primary-{{ $sa_step }}: {{ $sa_hex }};
@endforeach
@endif
@if ($sa_font_color)
            --color-site: {{ $sa_font_color }};
@endif
        }

        html[data-theme="dark"] {
            --main-color-one: {{ $sa_payload['dark_base'] ?? $sa_payload['base'] }};
            --main-color-one-hover: {{ $sa_payload['dark_hover'] ?? $sa_payload['hover'] }};
            --main-color-one-shadow: {{ $sa_payload['dark_base'] ?? $sa_payload['base'] }}31;
            --main-color-one-rgb: {{ $sa_rgb_dark }};
@if ($sa_font_color_dark)
            --color-site: {{ $sa_font_color_dark }};
@endif
        }

        {{-- ابزارهای گرادیان پالت برای بخش‌های ویژهٔ قالب --}}
        .st-gradient {
            background-image: var(--st-gradient) !important;
        }

        .st-gradient-text {
            background-image: var(--st-gradient);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent !important;
        }
    </style>

    {!! option('info_header_codes', '') !!}

    @if(auth('adminPanel')->check())
        <link rel="stylesheet" href="{{ theme_asset('css/admin-loggedin-navbar.css') }}?v=3">
    @endif

    {{-- آیکون‌های اعلان‌ها (mdi) — فقط برای کاربر وارد شده تا رندر هدر کند نشود --}}
    @if(auth('web')->check())
        <link rel="stylesheet" href="{{ theme_asset('css/materialdesignicons.css?v=2') }}">
    @endif

    @stack('styles')

    {{-- GTM & Meta Pixel --}}
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

    @if(function_exists('module_is_active') && module_is_active('SeoAudit'))
        {!! app(\Modules\SeoAudit\Services\BreadcrumbsService::class)->render() !!}
    @endif

</head>

<body>
@if(auth('adminPanel')->check())
    {{-- کلاس‌های قدیمی main-menu/dt-sl حذف شدند: JS مگامنو روی این نوار
         اعمال می‌شد و منوها بعد از هاور باز می‌ماندند + بک‌گراند سفید می‌گرفتند --}}
    <nav class="navbar navbar-expand-sm bg-dark navbar-dark admin-loggedin-navbar">
        <div class="container-fluid">

            <ul class="list float-right hidden-md new-list-menu">

                {{-- منوی محصولات --}}
                @if(auth('adminPanel')->user()->can('products'))
                    <li class="list-item list-item-has-children menu-col-1">
                        <a class="nav-link" >محصولات</a>
                        <ul class="sub-menu nav">
                            @if(auth('adminPanel')->user()->can('products.index'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.products.index') }}"><i
                                            class="fa-solid fa-list"></i> لیست محصولات</a>
                                </li>
                            @endif
                            @if(auth('adminPanel')->user()->can('products.create'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.products.create') }}"><i
                                            class="fa-solid fa-plus"></i> ایجاد محصول جدید</a>
                                </li>
                            @endif
                            @if(auth('adminPanel')->user()->can('products.category'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.categories.product') }}"><i
                                            class="fa-solid fa-tags"></i> دسته بندی ها</a>
                                </li>
                            @endif
                            @if(auth('adminPanel')->user()->can('products.brands'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.brands.index') }}"><i
                                            class="fa-solid fa-trademark"></i> برندها</a>
                                </li>
                            @endif
                            @if(auth('adminPanel')->user()->can('products.comments'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.comments.products') }}"><i
                                            class="fa-solid fa-comment"></i> دیدگاه ها</a>
                                </li>
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.reviews.index') }}"><i
                                            class="fa-solid fa-question-circle"></i> پرسش و پاسخ ها</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- منوی مقالات --}}
                @if(auth('adminPanel')->user()->can('posts'))
                    <li class="list-item list-item-has-children menu-col-1">
                        <a class="nav-link" >مقالات</a>
                        <ul class="sub-menu nav">
                            @if(auth('adminPanel')->user()->can('posts.index'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.posts.index') }}"><i
                                            class="fa-solid fa-newspaper"></i> لیست مقالات</a>
                                </li>
                            @endif

                            @if(auth('adminPanel')->user()->can('posts.create'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.posts.create') }}"><i
                                            class="fa-solid fa-plus"></i> ایجاد مقاله جدید</a>
                                </li>
                            @endif

                            @if(auth('adminPanel')->user()->can('posts.category'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.categories.post') }}"><i
                                            class="fa-solid fa-tags"></i> دسته بندی ها</a>
                                </li>
                            @endif
                            @if(auth('adminPanel')->user()->can('posts.comments'))
                                <li class="list-item">
                                    <a class="nav-link" href="{{ route('np.comments.posts') }}"><i
                                            class="fa-solid fa-comment"></i> دیدگاه ها</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if(auth('adminPanel')->user()->can('settings.information'))
                    <li class="list-item">
                        <a class="nav-link" href="{{ route('np.settings.information') }}"> تنظیمات</a>
                    </li>
                @endif
                @if(auth('adminPanel')->user()->can('themes.settings'))
                    <li class="list-item">
                        <a class="nav-link" href="{{ route('np.widgets.index') }}"> ویرایش صفحه</a>
                    </li>
                @endif

            </ul>

            {{-- منوی سمت چپ (آواتار و منوی کشویی) --}}

            <ul class="dropdown-user-menu list float-right hidden-md new-list-menu">
                <li class="list-item list-item-has-children menu-col-1">
                    <a class="navbar-brand" role="button" aria-haspopup="true" aria-expanded="false">
                        <img src="{{ auth('adminPanel')->user()->image_url }}" alt="Avatar Logo" class="rounded-pill">
                        <span class="dropdown-username">{{auth('adminPanel')->user()->full_name}}</span>
                    </a>
                    <ul class="sub-menu nav">
                        <li class="list-item">
                            <a class="nav-link" href="{{ route('np.dashboard') }}"><i
                                    class="fa-solid fa-chart-line"></i> پیشخوان</a>
                        </li>
                        <li class="list-item">
                            <a class="nav-link" href="{{ route('np.profile') }}"><i
                                    class="fa-solid fa-user-edit"></i>ویرایش پروفایل</a>
                        </li>
                        <li class="list-item">
                            <a class="nav-link" href="{{ route('np.sessions.index') }}"><i
                                    class="fa-solid fa-desktop"></i> نشست های فعال</a>
                        </li>
                        <li class="list-item">
                            <a class="nav-link" href="{{ route('np.notifications.index') }}"><i
                                    class="fa-solid fa-bell"></i> اعلان ها</a>
                        </li>
                        <li class="list-item">
                            <a class="nav-link" href="{{ route('np.logout') }}"><i
                                    class="fa-solid fa-sign-out-alt"></i> خروج از حساب کاربری</a>
                        </li>

                    </ul>
                </li>
            </ul>

        </div>
    </nav>
@endif
@if(count(highest_banner()))
    @foreach(highest_banner() as $banner)
        <div class='highest-banner' style=" height: auto; ">
            <a href="" class="image-data-src"><img
                    data-src="{{ $banner->image ? asset($banner->image) : asset('/no-image-product.svg') }}"
                    src="{{ theme_asset('images/600-600.png') }}" alt="{{ $banner->title }}"></a>
        </div>
    @endforeach

@endif

<header>
    <div class="container-fluid">
        <div class="row align-items-center">

            {{-- لوگو + منوی موبایل --}}
            <div class="col-lg-2 col-6 order-lg-1 order-1">
                <div class="d-flex align-items-center">
                    <div class="responsive-menu d-lg-none d-block">
                        <button class="btn border-0 p-0 btn-responsive-menu" type="button"
                                data-bs-toggle="offcanvas" data-bs-target="#responsiveMenu"
                                aria-controls="responsive menu">
                            <i class="bi bi-list font-30"></i>
                        </button>
                        <div class="offcanvas offcanvas-start" tabindex="-1" id="responsiveMenu"
                             aria-labelledby="responsive menu">
                            {{-- فاز ۲۱۴ — هدر منوی موبایل با دکمهٔ بستن (×) واضح و مدرن --}}
                            <div class="offcanvas-header snr-menu-top">
                                <h5 class="offcanvas-title" id="offcanvasRightLabel">{{ option('info_site_title', 'فروشگاه سانیار') }}</h5>
                                <button type="button" class="snr-menu-close" data-bs-dismiss="offcanvas"
                                        aria-label="بستن منو">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="offcanvas-body">
                                <a href="{{ route('front.index') }}" class="text-center d-block mb-3">
                                    <img src="{{ option('info_logo', theme_asset('images/logo-default.png')) }}" alt="{{ option('info_site_title', 'او پی شاپ') }}" class="img-fluid" width="200">
                                </a>
                                <div class="header-bottom-form mb-4 w-100">
                                    <div class="search-form">
                                        <form action="{{ route('front.products.index') }}" id="search-form-mobile">
                                            <div class="search-filed">
                                                <input type="text" name="q" placeholder="جستجو در همه کالاها ..."
                                                       class="form-control search-input">
                                                <button type="submit"
                                                        class="btn search-btn main-color-one-bg rounded-pill"><i
                                                        class="bi bi-search"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @include('front::partials.mobile-menu.menu')
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('front.index') }}">
                        <div class="logo">
                            <img src="{{ option('info_logo', theme_asset('images/logo-default.png')) }}"
                                 alt="{{ option('info_site_title', 'او پی شاپ') }}" class="img-fluid">
                        </div>
                    </a>
                </div>
            </div>
            {{-- پایان لوگو --}}

            {{-- اکشن موبایل (سبد + حساب) — فاز ۲۱۴: چیپ‌های کوچک و مدرن --}}
            <div class="d-lg-none d-block col-6 order-lg-5 order-2">
                <div class="d-flex align-items-center justify-content-end mobile-header-actions">
                    {{-- کلید روز/شب (موبایل) --}}
                    <button type="button" class="theme-toggle-btn shadow-sm"
                            aria-pressed="false" aria-label="تغییر حالت روز و شب"
                            title="تغییر حالت روز و شب">
                        <i class="bi bi-sun icon-sun"></i>
                        <i class="bi bi-moon-stars icon-moon"></i>
                    </button>
                    <a class="btn btn-light no-highlight btn-action-cart"
                       data-bs-toggle="offcanvas" href="#offcanvasCart" role="button"
                       aria-controls="offcanvasCart" aria-label="سبد خرید">
                        <i class="bi bi-basket"></i>
                        <span class="header-cart-counter main-color-one-bg d-inline-block rounded-pill">
                            {{ $cart ? $cart->products()->count() : 0 }}
                        </span>
                    </a>
                    <div class="dropdown">
                        @if(auth('web')->check())
                            <a href="#" data-bs-toggle="dropdown" aria-expanded="false" role="button"
                               class="btn btn-white header-register border-0 rounded-pill">
                                <figure class="avatar">
                                    <img src="{{ auth('web')->user()->image_url ?? theme_asset('images/user.png') }}" alt="user">
                                </figure>
                            </a>
                            @include('front::partials.user-menu')
                        @else
                            <a href="{{ route('login') }}" role="button" aria-label="حساب کاربری"
                               class="btn btn-white header-register border-0 rounded-pill">
                                <i class="bi bi-person"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            {{-- پایان اکشن موبایل --}}

            {{-- جستجو --}}
            <div class="col-lg-6 order-lg-2 d-lg-block d-none">
                <div class="search-form search-header">
                    <form action="{{ route('front.products.index') }}" id="search-form">
                        <div class="search-filed">
                            <input type="text" name="q" id="search-input" class="form-control search-input"
                                   autocomplete="off" placeholder="جستجو در همه کالاها ...">
                            <button type="submit" id="search-header-btn-search"
                                    class="btn search-btn main-color-one-bg rounded-pill"><i class="bi bi-search"></i></button>
                        </div>
                    </form>

                    {{-- پنل جستجوی لحظه‌ای توسط theme-header.js ساخته می‌شود --}}

                    {{-- فاز ۲۰۳ — اسلایدر جستجو (گروه «اسلایدر جستجو» بخش اسلایدرها):
                         تصویرهای این گروه در حالت خالیِ پنل جستجو به‌صورت یک اسلایدر
                         تمام‌عرض و کامل «زیر» جستجوهای اخیر و پرطرفدار نمایش داده می‌شوند
                         (چرخش خودکار + دات‌های قابل کلیک + توقف با هاور؛ دسکتاپ و موبایل).
                         قالب مخفی است؛ theme-header.js آن را داخل پنل کلون می‌کند.
                         مهم: دستور PHP در این layout باید همیشه بلوکی نوشته شود؛
                         ترکیب فرم درون‌خطی (با پرانتز) با بلوک‌های PHP باعث
                         بلعیده‌شدن نیمهٔ فایل توسط storePhpBlocks لاراول و
                         خطای «unexpected token» هنگام کامپایل می‌شود. --}}
                    @php
                        $searchSliders = function_exists('get_search_sliders') ? get_search_sliders() : collect();
                    @endphp
                    @if($searchSliders->count())
                        <div id="saniarSearchPromo" hidden aria-hidden="true">
                            @foreach($searchSliders as $promoSlider)
                                <a href="{{ $promoSlider->link ?: '#' }}"
                                   class="snr-search-promo-item {{ $loop->first ? 'is-active' : '' }}"
                                   data-title="{{ $promoSlider->title }}"
                                   tabindex="-1">
                                    <img src="{{ asset($promoSlider->image) }}"
                                         alt="{{ $promoSlider->title ?: 'اسلایدر جستجو' }}"
                                         loading="lazy" decoding="async">
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            {{-- پایان جستجو --}}

            {{-- ورود / حساب کاربری + علاقه مندی + سبد --}}
            <div class="col-lg-4 order-lg-3 d-lg-block d-none">
                <div class="d-flex align-items-center justify-content-end">
                    @if(auth('web')->check())
                        <div class="btn-group header-btn-group shadow-sm rounded-pill">
                            <div class="dropdown">
                                <a href="#" data-bs-toggle="dropdown" aria-expanded="false" role="button"
                                   class="btn btn-white header-register border-0 rounded-pill">
                                    <i class="bi bi-person-circle font-20 text-muted me-1"></i>
                                    <span>@if(auth('web')->user()->fullname !== null && auth('web')->user()->fullname != ''){{ auth('web')->user()->fullname }}@else{{ auth('web')->user()->mobile }}@endif</span>
                                </a>
                                @include('front::partials.user-menu')
                            </div>
                        </div>

                        @include('front::partials.notifications')
                    @else
                        <div class="btn-group header-btn-group shadow-sm rounded-pill">
                            <div class="dropdown">
                                {{-- فاز ۱۷۷ — لینک ثبت‌نام به /login: در این فروشگاه روت
                                     register مسیر نصب‌کننده (/setup) است و نباید در فرانت لینک شود --}}
                                <a href="{{ route('login') }}" class="btn btn-white header-register border-0 rounded-pill">
                                    <i class="bi bi-person-circle font-20 text-muted me-1"></i>
                                    <span>ثبت نام</span>
                                </a>
                            </div>
                            <a href="{{ route('login') }}" class="btn btn-white header-login border-0 rounded-pill">ورود</a>
                        </div>
                    @endif

                    {{-- کلید روز/شب: جایگزین آیکون علاقه‌مندی هدر --}}
                    <button type="button" class="theme-toggle-btn shadow-sm"
                            aria-pressed="false" aria-label="تغییر حالت روز و شب"
                            title="تغییر حالت روز و شب">
                        <i class="bi bi-sun icon-sun"></i>
                        <i class="bi bi-moon-stars icon-moon"></i>
                    </button>

                    <div class="header-cart ms-1">
                        <a class="btn header-cart-toggle rounded-pill btn-white border-0 shadow-sm"
                           data-bs-toggle="offcanvas" href="#offcanvasCart" role="button"
                           aria-controls="offcanvasCart">
                            <i class="bi bi-shop font-20 text-muted me-1"></i>
                            <span class="header-cart-counter main-color-one-bg d-inline-block rounded-pill">
                                {{ $cart ? $cart->products()->count() : 0 }}
                            </span>
                        </a>
                    </div>
                </div>
            </div>
            {{-- پایان ورود / حساب --}}

        </div>
    </div>
</header>

{{-- شروع مگا منو --}}
@include('front::partials.menu.menu')
{{-- پایان مگا منو --}}

@yield('content')

{{-- ویجت شناور (تماس / ابزارک‌های ادمین) --}}
@include('front::partials.floating-widget')

{{-- فوتر --}}
@include('front::partials.footer')

{{-- منوی پایین موبایل --}}
<div class="mobile-footer d-lg-none d-flex">
    <div class="parent">
        <div class="item" onclick="topFunction()">
            <i class="bi bi-chevron-up font-20"></i>
        </div>
        <div class="item">
            <a href="{{ route('front.index') }}">
                <i class="bi bi-house font-20"></i>
            </a>
        </div>
        <div class="item item-float">
            <a data-bs-toggle="offcanvas" href="#offcanvasCart" role="button"
               aria-controls="offcanvasCart">
                <i class="bi bi-bag font-20"></i>
            </a>
        </div>
        <div class="item">
            <a href="{{ route('front.products.index') }}">
                <i class="bi bi-archive"></i>
            </a>
        </div>
        <div class="item">
            <a href="{{ auth('web')->check() ? route('front.user.profile') : route('login') }}">
                <i class="bi bi-person"></i>
            </a>
        </div>
    </div>
</div>

{{-- سبد خرید کشویی (offcanvas) --}}
@include('front::partials.cart')

{{-- دکمهٔ «برگشت به بالا» — مستطیلی، لبهٔ چپ، وسط ارتفاع صفحه --}}
<button type="button" id="back-to-top" aria-label="برگشت به بالا">
    <i class="bi bi-arrow-up-short"></i>
    <span>برگشت به بالا</span>
</button>

<script>
    var BASE_URL = "{{ route('front.index') }}";
    var IS_RTL = {{ $current_local['direction'] == 'rtl' ? 1 : 0 }};
    var site_title = "{{ option('info_site_title', 'او پی شاپ') }}";
</script>

{{-- سانیار — اسکریپت‌های تمپلیت (ترتیب دقیق مطابق index.html) --}}
{{-- فاز ۱۸۵ (SPA) — همهٔ اسکریپت‌های سراسری data-navigate-once دارند:
     با هر ناوبری wire:navigate دوباره اجرا نمی‌شوند؛ هندلرهای delegated
     jQuery روی document زنده می‌مانند و اجزای وابسته به DOM با
     window.saniarReinit (saniar-spa.js) بعد از هر ناوبری مقداردهی می‌شوند --}}
<script src="{{theme_asset('js/jquery.min.js')}}" data-navigate-once></script>
@stack('scripts-top-js')
<script src="{{theme_asset('js/bootstrap.bundle.min.js')}}" data-navigate-once></script>
<script src="{{theme_asset('plugin/waves/waves.min.js')}}" data-navigate-once></script>
<script src="{{theme_asset('plugin/swiper/swiper-bundle.min.js')}}" data-navigate-once></script>
<script src="{{theme_asset('plugin/timer/timer.js')}}" data-navigate-once></script>
<script src="{{theme_asset('plugin/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js')}}" data-navigate-once></script>
<script src="{{theme_asset('js/app.js')}}?v=169" data-navigate-once></script>
<script src="{{theme_asset('js/jquery-app.js')}}?v=7" data-navigate-once></script>

{{-- سانیار — پلاگین‌های جانبی تمپلیت --}}
{{-- پلاگین go-to-top حذف شد؛ دکمهٔ مستطیلی «برگشت به بالا» در theme-header.js --}}
<script src="{{theme_asset('plugin/rasta-contact/script.js')}}" data-navigate-once></script>
<script src="{{theme_asset('js/saniar-init.js')}}?v=167" data-navigate-once></script>
<script src="{{theme_asset('js/theme-header.js')}}?v=30" data-navigate-once></script>

{{-- اسکریپت‌های عملکردی فروشگاه --}}
<script src="{{ theme_asset("js/plugins/sweetalert2.all.min.js") }}" data-navigate-once></script>
<script src="{{ theme_asset("js/plugins/jquery.blockUI.js") }}" data-navigate-once></script>
<script src="{{ theme_asset("js/plugins/toastr/toastr.min.js") }}" data-navigate-once></script>
<script src="{{ theme_asset('js/vendor/jquery.fancybox.min.js') }}" data-navigate-once></script>
<script src="{{ theme_asset('js/vendor/jquery.lazyloadxt.min.js') }}" data-navigate-once></script>
<script src="{{theme_asset('js/jquery.countdown.min.js')}}" data-navigate-once></script>
<script src="{{theme_asset('js/owl.carousel.min.js')}}" data-navigate-once></script>
<script src="{{ asset('back/assets/js/customToast.js') }}?v=9" data-navigate-once></script>
<script src="{{ theme_asset("js/scripts.js") }}?v=14" data-navigate-once></script>
<script src="{{theme_asset('js/main.js')}}?v=25" data-navigate-once></script>
@stack('scripts')

{!! option('info_scripts') !!}

{{-- فاز ۱۸۵ — اسکریپت‌های لایووایر (پیش از موتور SPA تم) --}}
{{-- فاز ۱۸۶ — گارد: اگر پکیج Livewire هنوز نصب نشده باشد، تگ خام در خروجی نمی‌آید؛
     پیش‌نیازها از پنل (قالب‌ها ← اجرای پیش‌نیازها) به‌صورت خودکار نصب می‌شود --}}
@if (class_exists(\Livewire\Livewire::class))
<livewire:scripts />
@endif

{{-- موتور SPA تم سانیار (intercept کلیک در فاز capture + Livewire.navigate) — باید بعد از livewire:scripts باشد --}}
<script src="{{theme_asset('js/saniar-spa.js')}}?v=10" data-navigate-once></script>

@if(session('toast-success'))
    <script data-navigate-once>
        showCustomToast('{{session('toast-success')}}','success');
    </script>
@endif
@if(session('toast-error'))
    <script data-navigate-once>
        showCustomToast('{{session('toast-error')}}','error');
    </script>
@endif
@if(session('toast-warning'))
    <script data-navigate-once>
        showCustomToast('{{session('toast-warning')}}','warning');
    </script>
@endif

@include('front::partials.popup-message')
</body>

</html>

@php
    session()->forget('showOrderResultInfo');
@endphp
@php session()->forget('toast-success'); @endphp
@php session()->forget('toast-error'); @endphp
@php session()->forget('toast-warning'); @endphp
