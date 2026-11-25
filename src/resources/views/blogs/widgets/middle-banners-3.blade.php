{{-- بنر سه‌تایی صفحهٔ مقالات — فاز ۲۰۶: افزوده‌شدن ویجت بنر سه‌تایی به صفحهٔ مقالات --}}
{{-- فیلتر گروه نمایش/صفحه در helpers (front_widget_banners) انجام می‌شود؛
     سه بنر کنار هم (col-md-4) نمایش داده می‌شوند --}}
@php
    $variables              = get_widget($widget);
    $index_middle_3_banners = $variables['index_middle_3_banners'] ?? collect();
@endphp

@if ($index_middle_3_banners->count())
    <div class="banner py-20">
        <div class="container-fluid">
            <div class="row g-3">
                @foreach ($index_middle_3_banners as $banner)
                    <div class="col-md-4 col-12">
                        <a href="{{ $banner->link ?: '#' }}" title="{{ $banner->title }}">
                            <img src="{{ $banner->image ? asset($banner->image) : asset('/no-image-product.svg') }}" alt="{{ $banner->title ?: 'بنر' }}" class="img-fluid rounded-3 shadow-box w-100 h-100" loading="lazy" decoding="async">
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
