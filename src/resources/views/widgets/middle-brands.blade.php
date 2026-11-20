{{-- بنر تک‌عرضی سانیار --}}
@php
    $variables = get_widget($widget);
    $brands = $variables['index_middle_brands'];
@endphp

@if (count($brands))

    <div class="service">
        <div class="container-fluid">
            <div class="row gy-3 justify-content-between align-items-center">
                @if($widget->option('title'))
                    <div class="col-lg-3">
                        <div class="service-title">
                            <h6 class="font-25 text-white mb-3">
                                {{ $widget->option('title') }}
                            </h6>
                            <p class="mb-0 text-white font-13">{{ $widget->option('title_below') }}</p>
                        </div>
                    </div>
                @endif

                <div class="{{$widget->option('title') ? 'col-lg-9' : 'col-lg-12'}}">
                    <div
                        class="row gy-2 gx-2 service-logo justify-content-md-end justify-content-center align-items-center">
                        @foreach($banners as $banner)
                        <div class="col-md-2 col-4 item">
                            <a href="">
                                <img src="assets/image/brand1-1.png" alt="" class="img-fluid">
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif


