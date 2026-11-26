{{-- ============================================================
    فاز ۲۱۵ — لوگوی دوحالتهٔ سایت (روشن/تیره)
    پارامترها:
        width  (اختیاری) → مقدار ویژگی width تگ img
        class  (اختیاری) → کلاس اضافی برای هر دو تصویر (مثل snr-about-logo)
    منطق:
        همیشه لوگوی روشن (info_logo) رندر می‌شود؛ اگر لوگوی دارک
        (info_logo_dark) در پنل آپلود شده باشد، تصویر دوم با کلاس
        snr-logo-dark اضافه می‌شود و CSS بر اساس html[data-theme]
        بین دو تصویر سوییچ می‌کند (style.css + theme-dark.css).
        اگر لوگوی دارکی آپلود نشده باشد، در دارک‌مود رفتار قدیمی
        (فیلتر سفیدسازی لوگوی روشن) حفظ می‌شود.
    ============================================================ --}}
@php
    $__snrLogoLight = option('info_logo', theme_asset('images/logo-default.png'));
    $__snrLogoDark  = trim((string) option('info_logo_dark'));
    $__snrLogoAlt   = option('info_site_title', 'او پی شاپ');
    $__snrLogoExtra = trim((string) ($class ?? ''));
    $__snrLogoWidth = $width ?? null;
@endphp
<img src="{{ $__snrLogoLight }}"
     alt="{{ $__snrLogoAlt }}"
     class="img-fluid snr-logo snr-logo-light{{ $__snrLogoExtra !== '' ? ' ' . $__snrLogoExtra : '' }}"
     @if (!empty($__snrLogoWidth)) width="{{ $__snrLogoWidth }}" @endif>
@if ($__snrLogoDark !== '')
    <img src="{{ $__snrLogoDark }}"
         alt="{{ $__snrLogoAlt }}"
         class="img-fluid snr-logo snr-logo-dark{{ $__snrLogoExtra !== '' ? ' ' . $__snrLogoExtra : '' }}"
         @if (!empty($__snrLogoWidth)) width="{{ $__snrLogoWidth }}" @endif>
@endif
