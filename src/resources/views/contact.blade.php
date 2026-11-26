@extends('front::layouts.master', ['title' => 'تماس با ما'])

@push('styles')
    <link rel="stylesheet" href="{{ theme_asset('plugin/mapp/css/mapp.min.css') }}">
    <link rel="stylesheet" href="{{ theme_asset('plugin/mapp/css/fa/style.css') }}">
    {{-- فاز ۱۹۲ — بازطراحی مدرن صفحهٔ تماس با ما --}}
    <link rel="stylesheet" href="{{ theme_asset('css/pages/contact.css') }}?v=2">
@endpush

@push('meta')
    <link rel="canonical" href="{{ route('front.contact.index') }}" />
@endpush

@section('content')
    {{-- متغیرهای نقشه برای js/pages/blog/contact-page-vars.js (بدون اسکریپت اینلاین) --}}
    <div id="contact-page-vars" class="d-none"
         data-map-type="{{ option('info_map_type', 'google') }}"
         data-latitude="{{ option('info_latitude', '38.07709880960678') }}"
         data-longitude="{{ option('info_Longitude', '46.28582686185837') }}"
         data-site-title="{{ option('info_site_title', 'فروشگاه') }}"
         data-mapir-key="{{ option('map_api') }}"></div>

    {{-- بردکرامب --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('front.index') }}" class="font-14 text-muted">خانه</a></li>
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">تماس با ما</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- فاز ۱۹۲ — تماس با ما: بازطراحی مدرن (کارت‌ها + فرم + نقشه) --}}
    <div class="cnt-page">
        <div class="container-fluid py-3 py-lg-4">

            {{-- ===== هیرو ===== --}}
            <header class="cnt-hero rounded-4">
                <span class="cnt-hero__ring cnt-hero__ring--1" aria-hidden="true"></span>
                <span class="cnt-hero__ring cnt-hero__ring--2" aria-hidden="true"></span>
                <div class="cnt-hero__body">
                    <span class="cnt-hero__badge">
                        <i class="bi bi-headset" aria-hidden="true"></i>
                        پاسخگویی سریع و پشتیبانی واقعی
                    </span>
                    <h1 class="cnt-hero__title">تماس با ما</h1>
                    <p class="cnt-hero__text mb-0">
                        هر سوالی دارید بپرسید؛ تیم ما در سریع‌ترین زمان ممکن پاسخ می‌دهد.
                        از راه‌های ارتباطی زیر یا فرم همین صفحه می‌توانید با ما در تماس باشید.
                    </p>
                </div>
            </header>

            @if (option('dt_show_contact_top_description'))
                <div class="cnt-note rounded-4 mt-4">
                    {!! option('dt_show_contact_top_description') !!}
                </div>
            @endif

            {{-- ===== کارت‌های راه‌های ارتباطی (فقط موارد تنظیم‌شده در پنل) ===== --}}
            <div class="row g-3 g-lg-4 mt-4">
                @if (option('info_tel'))
                    <div class="col-12 col-sm-6 col-lg-3">
                        <a href="tel:{{ preg_replace('/\s+/', '', option('info_tel')) }}" class="cnt-card" dir="ltr">
                            <span class="cnt-card__ico cnt-card__ico--phone" aria-hidden="true"><i class="bi bi-telephone"></i></span>
                            <span class="cnt-card__meta" dir="rtl">
                                <span class="cnt-card__label">تلفن تماس</span>
                                <span class="cnt-card__value">{{ option('info_tel') }}</span>
                            </span>
                        </a>
                    </div>
                @endif

                @if (option('info_email'))
                    <div class="col-12 col-sm-6 col-lg-3">
                        <a href="mailto:{{ option('info_email') }}" class="cnt-card" dir="ltr">
                            <span class="cnt-card__ico cnt-card__ico--mail" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                            <span class="cnt-card__meta" dir="rtl">
                                <span class="cnt-card__label">ایمیل</span>
                                <span class="cnt-card__value">{{ option('info_email') }}</span>
                            </span>
                        </a>
                    </div>
                @endif

                @if (option('info_address'))
                    <div class="col-12 {{ option('info_tel') || option('info_email') ? 'col-lg-6' : 'col-lg-12' }}">
                        <div class="cnt-card cnt-card--static" dir="ltr">
                            <span class="cnt-card__ico cnt-card__ico--pin" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                            <span class="cnt-card__meta" dir="rtl">
                                <span class="cnt-card__label">آدرس فروشگاه</span>
                                <span class="cnt-card__value">{{ option('info_address') }}</span>
                                @if (option('info_postal_code'))
                                    <span class="cnt-card__hint">کد پستی: <span class="fa-digits">{{ option('info_postal_code') }}</span></span>
                                @endif
                            </span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ===== شبکه‌های اجتماعی (فقط موارد تنظیم‌شده در پنل) ===== --}}
            @php
                $cnt_socials = [];
                foreach ([
                    'social_telegram'  => ['telegram',  'bi-telegram'],
                    'social_whatsapp'  => ['whatsapp',  'bi-whatsapp'],
                    'social_instagram' => ['instagram', 'bi-instagram'],
                    'social_youtube'   => ['youtube',   'bi-youtube'],
                    'social_twitter'   => ['توییتر/ایکس', 'bi-twitter-x'],
                ] as $key => $conf) {
                    if (option($key)) {
                        $cnt_socials[] = ['url' => option($key), 'label' => $conf[0], 'icon' => $conf[1]];
                    }
                }
            @endphp
            @if (count($cnt_socials))
                <div class="cnt-socials mt-4" aria-label="شبکه‌های اجتماعی">
                    <span class="cnt-socials__title">ما را در شبکه‌های اجتماعی دنبال کنید:</span>
                    <div class="cnt-socials__list">
                        @foreach ($cnt_socials as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener"
                               class="cnt-social" title="{{ $social['label'] }}" aria-label="{{ $social['label'] }}">
                                <i class="bi {{ $social['icon'] }}" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ===== فرم + نقشه ===== --}}
            <div class="row g-4 mt-4">
                @if (option('dt_show_form_in_contact', 'yes') == 'yes')
                    <div class="col-lg-7">
                        <div class="cnt-form-card rounded-4">
                            <div class="cnt-form-card__head">
                                <span class="cnt-form-card__ico" aria-hidden="true"><i class="bi bi-chat-dots"></i></span>
                                <div>
                                    <h2 class="cnt-form-card__title">پیام خود را با ما در میان بگذارید</h2>
                                    <p class="cnt-form-card__sub mb-0">پاسخ از طریق ایمیل ارسال می‌شود؛ فیلدهای ستاره‌دار الزامی‌اند.</p>
                                </div>
                            </div>

                            <form id="contact-form" class="contact-us-form cnt-form" action="{{ route('front.contact.store') }}" method="POST">
                                @csrf
                                <div class="form-ui contact-us-form-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="cnt-label" for="name">نام و نام‌خانوادگی <span class="cnt-req">*</span></label>
                                            <input type="text" id="name" class="ui-input-field form-control cnt-input" name="name" autocomplete="name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="cnt-label" for="email">ایمیل <span class="cnt-req">*</span></label>
                                            <input type="email" id="email" name="email" class="ui-input-field form-control cnt-input cnt-input--ltr" dir="ltr" autocomplete="email">
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label class="cnt-label" for="subject">موضوع <span class="cnt-req">*</span></label>
                                        <input type="text" id="subject" name="subject" class="ui-input-field form-control cnt-input">
                                    </div>

                                    <div class="mt-3">
                                        <label class="cnt-label" for="message">متن پیام <span class="cnt-req">*</span></label>
                                        <textarea name="message" id="message" rows="6" class="ui-textarea-field form-control cnt-input cnt-textarea"></textarea>
                                    </div>

                                    <x-captcha />

                                    <button type="submit" class="cnt-submit mt-4">
                                        <i class="bi bi-send" aria-hidden="true"></i>
                                        ثبت و ارسال پیام
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif

                @if (option('dt_show_map_in_contact') == 'yes')
                    <div class="col-lg-5">
                        <div class="cnt-map-card rounded-4">
                            <div class="cnt-map-card__head">
                                <span class="cnt-map-card__ico" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                                <span class="cnt-map-card__title">موقعیت ما روی نقشه</span>
                            </div>
                            <div class="cnt-map" id="map" role="application" aria-label="نقشهٔ موقعیت فروشگاه"></div>
                        </div>
                    </div>
                @endif
            </div>

            @if (option('dt_show_contact_bottom_description') == 'yes')
                <div class="cnt-note rounded-4 mt-4">
                    {!! option('dt_contact_bottom_description') !!}
                </div>
            @endif

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    @if(option('info_map_type')=='mapir')
        <script type="text/javascript" src="{{ theme_asset('plugin/mapp/js/mapp.env.js') }}"></script>
        <script type="text/javascript" src="{{ theme_asset('plugin/mapp/js/mapp.min.js?v=1') }}"></script>
    @elseif(option('info_map_type')=='google')
        {{-- defer: اسکریپت گوگل‌مپ روی IP ایران چندثانیه پارس را قفل می‌کند؛ contact.js گارد typeof google دارد --}}
        <script src="https://maps.googleapis.com/maps/api/js?key={{ option('map_api') }}" defer></script>
    @endif

    <script src="{{ theme_asset('js/pages/blog/contact-page-vars.js') }}"></script>
    <script src="{{ theme_asset('js/pages/contact.js?v=2') }}"></script>
@endpush
