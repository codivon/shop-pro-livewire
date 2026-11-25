{{-- بنر دوتایی صفحهٔ مقالات — فاز ۲۰۶: حذف فیلتر معیوب places از ویو --}}
{{-- فیلتر گروه نمایش/صفحه در helpers (front_widget_banners) انجام می‌شود؛
     دو بنر کنار هم (col-lg-6) نمایش داده می‌شوند --}}
@php
    $variables              = get_widget($widget);
    $index_middle_2_banners = $variables['index_middle_2_banners'] ?? collect();
@endphp

@if ($index_middle_2_banners->count())
    <div class="banner py-20">
        <div class="container-fluid">
            <div class="row g-3">
                @foreach ($index_middle_2_banners as $banner)
                    <div class="col-lg-6 col-12">
                        <a href="{{ $banner->link ?: '#' }}" title="{{ $banner->title }}">
                            <img src="{{ $banner->image ? asset($banner->image) : asset('/no-image-product.svg') }}" alt="{{ $banner->title ?: 'بنر' }}" class="img-fluid rounded-3 shadow-box w-100 h-100" loading="lazy" decoding="async">
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
