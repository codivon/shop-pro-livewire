{{-- اسلایدر محصولات سانیار (product-slider) --}}
@php
    $variables = get_widget($widget);
    $products = $variables['products'];
@endphp

@if(count($products))
    <div class="product-slider product-slider-colorful site-slider pb-20">
        @if($widget->option('title'))
            <div class="container-fluid">
                <div class="header-content mb-4 bg-white shadow-box rounded-3 p-3 d-flex align-items-center justify-content-between flex-wrap">
                    <div class="title d-flex align-items-center">
                        <img loading="lazy" decoding="async" src="{{ theme_asset('images/square.png') }}" alt="" class="img-fluid">
                        <h5 class="font-16 ms-3"><span class="main-color-one-color d-inline-block ms-1">{{ $widget->option('title') }}</span></h5>
                    </div>
                    <div class="link">
                        <a href="{{ route('front.products.index') }}" class="border-animate fromCenter pb-1 fw-bold">
                            مشاهده همه
                            <i class="bi bi-chevron-double-left main-color-one-color"></i>
                        </a>
                    </div>
                </div>
            </div>
        @endif


        <div class="parent" style="--before-background-colorful: {{ $widget->option('image') ? "url('" . asset($widget->option('image')) . "')" : $widget->option('block_color') }};">
            <div class="container-fluid">
                <div class="swiper product-slider-swiper">
                    <div class="swiper-wrapper">
                        @foreach($products as $product)
                            <div class="swiper-slide">
                                @include('front::partials.product-card', ['product' => $product, 'withTimer' => false, 'hoverBtns' => true])
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-button-next d-lg-flex d-none"></div>
                    <div class="swiper-button-prev d-lg-flex d-none"></div>
                    <!-- <div class="swiper-pagination"></div> -->
                </div>
            </div>
        </div>
    </div>
@endif
