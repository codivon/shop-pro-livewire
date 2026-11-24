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
    <section class="saniar-brands mb-3 pb-20" aria-label="{{ $title ?: 'برندهای فروشگاه' }}">
        <div class="container-fluid">
            <div class="saniar-brands__inner">

                {{-- تیتر سمت راست (RTL) با میلهٔ عمودی نارنجی مثل نمونه --}}
                @if ($title || $titleBelow)
                    <div class="saniar-brands__head">
                        @if ($title)
                            <h2 class="saniar-brands__title">{{ $title }}</h2>
                        @endif
                        @if ($titleBelow)
                            <p class="saniar-brands__subtitle">{{ $titleBelow }}</p>
                        @endif
                    </div>
                @endif

                {{-- اسلایدر/مارکی کارت‌های سفید --}}
                <div class="saniar-brands__body">
                    @if ($displayMode === 'marquee')
                        {{-- ===== حالت رونده (مارکی بی‌پایان) ===== --}}
                        <div class="saniar-brands__marquee" dir="ltr">
                            <div class="saniar-brands__track" style="animation-duration: {{ $marqueeSpeed }}s">
                                @for ($pass = 0; $pass < 2; $pass++)
                                    @foreach ($brands as $brand)
                                        <a href="{{ route('front.brands.show', $brand->slug) }}"
                                           class="saniar-brand-card"
                                           @if ($pass === 1) aria-hidden="true" tabindex="-1" @endif>
                                            @include('front::widgets.partials.brand-logo', ['brand' => $brand])
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
                                            @include('front::widgets.partials.brand-logo', ['brand' => $brand])
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                            <div class="swiper-button-next saniar-brands__nav"></div>
                            <div class="swiper-button-prev saniar-brands__nav"></div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </section>

    {{-- استایل ویجت — اسکوپ‌شده با کلاس‌های اختصاصی saniar-brands --}}
    <style>
        /* ===== نوار آبی سرتاسری مثل نمونه ===== */
        .saniar-brands {
            background: var(--main-color-one, #275fe0);
            padding-block: 18px;
        }

        .saniar-brands__inner {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        /* ===== تیتر + میلهٔ عمودی نارنجی (مثل نمونه) ===== */
        .saniar-brands__head {
            position: relative;
            flex-shrink: 0;
            max-width: 250px;
            padding-inline-start: 16px;
        }

        .saniar-brands__head::before {
            content: "";
            position: absolute;
            inset-inline-start: 0;
            top: 5px;
            width: 4px;
            height: calc(100% - 10px);
            border-radius: 99px;
            background: var(--main-color-two, #FFA41B);
        }

        .saniar-brands__title {
            color: #fff;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.5;
            margin: 0 0 6px;
        }

        .saniar-brands__subtitle {
            color: rgba(255, 255, 255, .78);
            font-size: 12px;
            font-weight: 500;
            margin: 0;
        }

        /* ===== بدنهٔ اسلایدر ===== */
        .saniar-brands__body {
            flex: 1 1 auto;
            min-width: 0;
        }

        /* ===== کارت سفید برند (مثل نمونه: لوگوی کامل، بدون خاکستری‌شدن) ===== */
        .saniar-brand-card {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 62px;
            padding: 10px 18px;
            background: #fff;
            border: 2px solid transparent;
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, .18);
            transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
            will-change: transform;
        }

        .saniar-brand-card img {
            max-width: 100%;
            max-height: 42px;
            width: auto;
            object-fit: contain;
            -webkit-user-drag: none;
        }

        /* فاز ۱۹۳ — فالبک ظریف وقتی لوگوی برند وجود ندارد یا ۴۰۴ می‌دهد:
           به‌جای آیکون تصویر شکسته، حرف اول نام برند با استایل برند نمایش داده می‌شود */
        .saniar-brand-card__fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 76px;
            height: 40px;
            padding: 2px 16px;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: .5px;
            color: var(--main-color-one, #275fe0);
            background: linear-gradient(135deg, rgba(39, 95, 224, .07), rgba(39, 95, 224, .16));
            border-radius: 8px;
            user-select: none;
        }

        .saniar-brand-card__fallback[hidden] { display: none; }

        .saniar-brand-card:hover,
        .saniar-brand-card:focus-visible {
            transform: translateY(-3px);
            border-color: #1e2536; /* حاشیهٔ تیره مثل کارت لنوو در نمونه */
            box-shadow: 0 10px 22px -8px rgba(15, 23, 42, .35);
            outline: none;
        }

        /* ===== حالت اسلایدر ===== */
        .saniar-brands__swiper { padding: 4px 2px 6px; }

        .saniar-brands__swiper .swiper-slide { height: auto; }

        .saniar-brands__nav {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #fff;
            color: var(--main-color-one, #275fe0);
            box-shadow: 0 3px 10px rgba(15, 23, 42, .22);
            font-size: 14px;
            transition: background .2s ease, color .2s ease;
        }

        .saniar-brands__nav:hover {
            background: var(--main-color-two, #FFA41B);
            color: #fff;
        }

        .saniar-brands__nav.swiper-button-lock { display: none; }

        .saniar-brands__nav::after { font-size: 14px; font-weight: 800; }

        /* ===== حالت رونده (مارکی) ===== */
        .saniar-brands__marquee {
            overflow: hidden;
            -webkit-mask-image: linear-gradient(to right, transparent, #000 5%, #000 95%, transparent);
            mask-image: linear-gradient(to right, transparent, #000 5%, #000 95%, transparent);
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

        /* ===== ریسپانسیو: موبایل/تبلت — تیتر بالا، اسلایدر زیر ===== */
        @media (max-width: 991.98px) {
            .saniar-brands { padding-block: 14px; }

            .saniar-brands__inner {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .saniar-brands__head { max-width: none; }

            .saniar-brands__title { font-size: 17px; }

            .saniar-brand-card { height: 54px; padding: 8px 14px; }

            .saniar-brand-card img { max-height: 36px; }
        }

        @media (max-width: 400px) {
            .saniar-brand-card { height: 48px; padding: 7px 12px; border-radius: 8px; }

            .saniar-brand-card img { max-height: 32px; }
        }
    </style>
@endif
