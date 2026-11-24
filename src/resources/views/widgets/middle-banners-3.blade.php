{{-- بنرهای میانی سانیار (3 بنر عرضی) --}}
@php
    $variables = get_widget($widget);
    $banners = $variables['index_middle_3_banners'];
@endphp

@if(count($banners))
    <div class="banner pb-20">
        <div class="container-fluid">
            <div class="row g-3">
                @foreach($banners as $banner)
                    <div class="col-12 col-md-4">
                        <a href="{{ $banner->link ?: '#' }}">
                            <img loading="lazy" decoding="async" src="{{ asset($banner->image) }}" class="img-fluid rounded-3 shadow-box" alt="{{ $banner->title ?: 'بنر' }}">
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
