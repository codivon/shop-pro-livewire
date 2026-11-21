@extends('front::layouts.master')

@push('styles')
    {{-- فاز ۲۰۰ — استایل بازطراحی‌شدهٔ پنل مشتری (فقط صفحات پنل) --}}
    <link rel="stylesheet" href="{{ theme_asset('css/user-panel.css') }}?v=201">
@endpush

@section('content')

    {{-- پنل کاربر — بازطراحی کامل (فاز ۲۰۰) --}}
    <div class="content">
        <div class="container-fluid">

            {{-- start side-menu in mobile --}}
            <div class="custom-filter d-lg-none d-block">
                <button class="btn btn-filter-float border-0 main-color-two-bg shadow-box px-4 rounded-3 position-fixed"
                        style="z-index: 999;bottom:80px;" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasUserPanel" aria-controls="offcanvasUserPanel">
                    <i class="bi bi-list font-20 fw-bold text-white"></i>
                    <span class="d-block font-14 text-white">منو</span>
                </button>

                <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasUserPanel"
                     aria-labelledby="offcanvasUserPanelLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title" id="offcanvasUserPanelLabel">منو</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        @include('front::user.partials.panel-sidebar')
                    </div>
                </div>
            </div>

            {{-- end side-menu in mobile --}}

            <div class="dashboard-panel">
                <div class="row gy-3">

                    {{-- start sidebar --}}
                    <div class="col-lg-3 d-lg-block d-none">
                        @include('front::user.partials.panel-sidebar')
                    </div>
                    {{-- end sidebar --}}

                    {{-- start main content --}}
                    <div class="col-lg-9">
                        @yield('user-content')
                    </div>
                    {{-- end main content --}}

                </div>
            </div>
        </div>
    </div>

    @yield('user-content-bottom')

    @if($random_products->count())
        <div class="content mt-4">
            <div class="container-fluid">
                <div class="content-box">
                    <div class="container-fluid py-3">
                        <div class="title title-panel d-flex align-items-baseline">
                            <i class="bi bi-bag-heart font-25 main-color-one-color"></i>
                            <h6 class="font-16 ms-2">محصولات پیشنهادی برای شما</h6>
                        </div>
                        <div class="product-carousel owl-carousel owl-theme owl-rtl owl-loaded owl-drag">
                            <div class="owl-stage-outer">
                                <div class="owl-stage"
                                     style="transform: translate3d(0px, 0px, 0px); transition: all 0s ease 0s; width: 2234px;">
                                    @php $i=1; @endphp
                                    @foreach ($random_products as $product)
                                        @include('front::partials.product-block', ['product' => $product])
                                        @php $i++; @endphp
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection
