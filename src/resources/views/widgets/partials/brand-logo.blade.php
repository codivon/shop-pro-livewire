{{-- فاز ۱۹۳ — پارشیال مشترک لوگوی برند (ویجت برندها + هرجای فرانت)
     رفتار:
       • برند بدون تصویر → حرف اول نام با استایل برند (بدون آیکون شکسته)
       • تصویر موجود ولی ۴۰۴ → همین فالبک با snrBrandImgFallback (saniar-init.js)
       • فاز ۱۹۴ — اگر فایل روی دیسک سرور موجود نباشد (مثل رکوردهای قدیمی‌ای که
         فایلشان حذف شده)، اصلاً <img> رندر نمی‌شود ⇒ درخواست ۴۰۴ هم در کنسول
         نمی‌آید و از همان اول فالبک حرف اول نمایش داده می‌شود.
     استفاده: @include('front::widgets.partials.brand-logo', ['brand' => $brand]) --}}
@php
    $brandLogoName  = $brand->name_fa ?? $brand->name;
    $brandLogoChar  = mb_strtoupper(mb_substr(trim((string) $brandLogoName), 0, 1));

    /* فاز ۱۹۴ — بررسی وجود واقعی فایل روی دیسک (یک‌بار برای هر مسیر در هر ریکوئست) */
    $brandLogoExists = false;
    if (!empty($brand->image)) {
        static $brandLogoFileCache = [];
        $relative = ltrim((string) $brand->image, '/');
        if (!array_key_exists($relative, $brandLogoFileCache)) {
            $brandLogoFileCache[$relative] = @is_file(public_path($relative));
        }
        $brandLogoExists = $brandLogoFileCache[$relative];
    }
@endphp
@if ($brand->image && $brandLogoExists)
    <img src="{{ asset($brand->image) }}" alt="{{ $brandLogoName }}"
         loading="lazy" draggable="false" decoding="async"
         onerror="window.snrBrandImgFallback && window.snrBrandImgFallback(this)">
    <span class="saniar-brand-card__fallback" hidden aria-hidden="true">{{ $brandLogoChar }}</span>
@else
    <span class="saniar-brand-card__fallback">{{ $brandLogoChar }}</span>
@endif
