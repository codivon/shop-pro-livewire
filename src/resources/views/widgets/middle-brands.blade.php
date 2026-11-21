{{-- فاز ۱۹۱ — ویجت برندهای صفحهٔ اصلی (بازطراحی کامل) --}}
{{-- دو حالت نمایش از تنظیمات ابزارک:
       ۱) slider  : اسلایدر Swiper با پخش خودکار اختیاری (سرعت قابل تنظیم)
       ۲) marquee : حرکت روندهٔ بی‌پایان (سرعت = animation-duration)
   کارت برند: پس‌زمینهٔ روشن، لوگوی خاکستری که با هاور رنگی و بلند می‌شود.
   فیلتر «نمایش در صفحهٔ اصلی» برندها در سمت پنل مدیریت اعمال می‌شود. --}}
@php
    $variables     = get_widget($widget);
    $brands        = $variables['index_middle_brands'];

    $displayMode   = $widget->option('display_mode', 'slider');
    $autoplay      = $widget->option('autoplay', 'yes');
    $autoplaySpeed = (int) ($widget->option('autoplay_speed', 3000) ?: 3000);
    $marqueeSpeed  = (int) ($widget->option('marquee_speed', 30) ?: 30);
    $title         = $widget->option('title');
    $titleBelow    = $widget->option('title_below');
@endphp

@if (count($brands))
    <section class="saniar-brands py-4 py-lg-5" aria-label="{{ $title ?: 'برندهای فروشگاه' }}">
        <div class="container-fluid">

            @if ($title || $titleBelow)
                <div class="saniar-brands__head d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                    <div class="saniar-brands__head-text">
                        @if ($title)
                            <h2 class="saniar-brands__title mb-1">{{ $title }}</h2>
                        @endif
                        @if ($titleBelow)
                            <p class="saniar-brands__subtitle mb-0">{{ $titleBelow }}</p>
                        @endif
                    </div>
                    <span class="saniar-brands__line" aria-hidden="true"></span>
                </div>
            @endif

            @if ($displayMode === 'marquee')
                {{-- ===== حالت رونده (مارکی بی‌پایان) ===== --}}
                <div class="saniar-brands__marquee" dir="ltr">
                    <div class="saniar-brands__track" style="animation-duration: {{ $marqueeSpeed }}s">
                        @for ($pass = 0; $pass < 2; $pass++)
                            @foreach ($brands as $brand)
                                <a href="{{ route('front.brands.show', $brand->slug) }}"
                                   class="saniar-brand-card"
                                   @if ($pass === 1) aria-hidden="true" tabindex="-1" @endif>
                                    <img src="{{ asset($brand->image) }}" alt="{{ $brand->name }}"
                                         loading="lazy" draggable="false">
                                </a>
                            @endforeach
                        @endfor
                    </div>
                </div>
            @else
                {{-- ===== حالت اسلایدر (Swiper) ===== --}}
                <div class="saniar-brands__swiper swiper"
                     dir="rtl"
                     data-autoplay="{{ $autoplay === 'yes' ? '1' : '0' }}"
                     data-autoplay-speed="{{ $autoplaySpeed }}">
                    <div class="swiper-wrapper">
                        @foreach ($brands as $brand)
                            <div class="swiper-slide">
                                <a href="{{ route('front.brands.show', $brand->slug) }}" class="saniar-brand-card">
                                    <img src="{{ asset($brand->image) }}" alt="{{ $brand->name }}" loading="lazy">
                                </a>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-next saniar-brands__nav"></div>
                    <div class="swiper-button-prev saniar-brands__nav"></div>
                </div>
            @endif

        </div>
    </section>

    {{-- استایل ویجت — اسکوپ‌شده با کلاس‌های اختصاصی saniar-brands --}}
    <style>
        .saniar-brands {
            background: linear-gradient(180deg, rgba(120, 130, 145, .05), rgba(120, 130, 145, 0) 70%);
        }

        .saniar-brands__head-text { max-width: 100%; }

        .saniar-brands__title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
            position: relative;
            padding-inline-start: 14px;
        }

        .saniar-brands__title::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 5px;
            height: 26px;
            border-radius: 99px;
            background: var(--main-color-one, #0f9d8f);
        }

        .saniar-brands__subtitle {
            font-size: 13px;
            color: #8a94a6;
            padding-inline-start: 14px;
        }

        .saniar-brands__line {
            flex: 1 1 120px;
            height: 2px;
            min-width: 80px;
            border-radius: 99px;
            background: linear-gradient(to left, rgba(120, 130, 145, .25), rgba(120, 130, 145, 0));
        }

        /* ===== کارت برند ===== */
        .saniar-brand-card {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 84px;
            padding: 14px 18px;
            background: #fff;
            border: 1px solid rgba(120, 130, 145, .14);
            border-radius: 14px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
            will-change: transform;
        }

        .saniar-brand-card img {
            max-width: 100%;
            max-height: 48px;
            width: auto;
            object-fit: contain;
            filter: grayscale(1);
            opacity: .68;
            transition: filter .25s ease, opacity .25s ease;
            -webkit-user-drag: none;
        }

        .saniar-brand-card:hover,
        .saniar-brand-card:focus-visible {
            transform: translateY(-4px);
            border-color: rgba(var(--main-color-one-rgb, 15 157 143), .45);
            box-shadow: 0 10px 24px -10px rgba(15, 23, 42, .25);
            outline: none;
        }

        .saniar-brand-card:hover img,
        .saniar-brand-card:focus-visible img {
            filter: grayscale(0);
            opacity: 1;
        }

        /* ===== حالت اسلایدر ===== */
        .saniar-brands__swiper { padding: 4px 2px 8px; }

        .saniar-brands__swiper .swiper-slide { height: auto; }

        .saniar-brands__nav {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #fff;
            color: #45506a;
            box-shadow: 0 3px 10px rgba(15, 23, 42, .12);
            font-size: 14px;
            transition: background .2s ease, color .2s ease;
        }

        .saniar-brands__nav:hover {
            background: var(--main-color-one, #0f9d8f);
            color: #fff;
        }

        .saniar-brands__nav.swiper-button-lock { display: none; }

        .saniar-brands__nav::after { font-size: 15px; font-weight: 800; }

        /* ===== حالت رونده (مارکی) ===== */
        .saniar-brands__marquee {
            overflow: hidden;
            -webkit-mask-image: linear-gradient(to right, transparent, #000 6%, #000 94%, transparent);
            mask-image: linear-gradient(to right, transparent, #000 6%, #000 94%, transparent);
        }

        .saniar-brands__track {
            display: flex;
            align-items: center;
            gap: 12px;
            width: max-content;
            animation-name: saniar-brands-marquee;
            animation-timing-function: linear;
            animation-iteration-count: infinite;
            padding-block: 4px;
        }

        .saniar-brands__track:hover { animation-play-state: paused; }

        @keyframes saniar-brands-marquee {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }

        @media (prefers-reduced-motion: reduce) {
            .saniar-brands__track { animation: none; }
        }

        @media (max-width: 575.98px) {
            .saniar-brand-card { height: 68px; padding: 10px 12px; border-radius: 12px; }
            .saniar-brand-card img { max-height: 38px; }
        }
    </style>
@endif
