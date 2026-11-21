{{-- فاز ۱۹۳ — پارشیال مشترک لوگوی برند (ویجت برندها + هرجای فرانت)
     رفتار:
       • برند بدون تصویر → حرف اول نام با استایل برند (بدون آیکون شکسته)
       • تصویر موجود ولی ۴۰۴ → همین فالبک با snrBrandImgFallback (saniar-init.js)
     استفاده: @include('front::widgets.partials.brand-logo', ['brand' => $brand]) --}}
@php
    $brandLogoName  = $brand->name_fa ?? $brand->name;
    $brandLogoChar  = mb_strtoupper(mb_substr(trim((string) $brandLogoName), 0, 1));
@endphp
@if ($brand->image)
    <img src="{{ asset($brand->image) }}" alt="{{ $brandLogoName }}"
         loading="lazy" draggable="false" decoding="async"
         onerror="window.snrBrandImgFallback && window.snrBrandImgFallback(this)">
    <span class="saniar-brand-card__fallback" hidden aria-hidden="true">{{ $brandLogoChar }}</span>
@else
    <span class="saniar-brand-card__fallback">{{ $brandLogoChar }}</span>
@endif
