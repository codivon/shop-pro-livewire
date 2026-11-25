{{-- ============================================================
     فاز ۲۱۰ — پوستهٔ رنگی قالب برای لایوت‌های مستقل (ورود/ثبت‌نام،
     چک‌اوت و نتیجهٔ پرداخت).
     ------------------------------------------------------------
     صفحات ورود و چک‌اوت لایوت اختصاصی دارند و تا فاز ۲۰۹ از
     تزریق متغیرهای پوسته جا مانده بودند («صفحهٔ لاگین و سبد خرید
     با تنظیمات رنگ قالب هماهنگ نبود»). این پارشال همان بلوک
     master.blade.php است — با همان منطق و همان خروجی تا در
     mergeNewHead ناوبری SPA بین صفحات، تگ تکراری dedupe شود.
     ============================================================ --}}
@php
    try {
        $sa_partial_state = \Themes\SaniarTheme\src\Support\PaletteEngine::stateFromOption();
    } catch (\Throwable $sa_partial_error) {
        $sa_partial_state = null;
    }

    $sa_partial_payload = $sa_partial_state['payload'] ?? null;

    if (! is_array($sa_partial_payload) || empty($sa_partial_payload['base'])) {
        $sa_partial_payload = [
            'base'           => '#D62976',
            'hover'          => '#B4215F',
            'dark_base'      => '#E8468A',
            'dark_hover'     => '#F06AA5',
            'scale'          => [],
            'gradient'       => ['#F58529', '#DD2A7B', '#8134AF', '#515BD4'],
            'gradient_angle' => 45,
            'mode'           => 'light',
            'font_color'      => null,
            'font_color_dark' => null,
        ];
    }

    try {
        $sa_partial_rgb = \Themes\SaniarTheme\src\Support\PaletteEngine::hexToRgbString($sa_partial_payload['base'] ?? '#D62976');
        $sa_partial_rgb_dark = \Themes\SaniarTheme\src\Support\PaletteEngine::hexToRgbString($sa_partial_payload['dark_base'] ?? ($sa_partial_payload['base'] ?? '#D62976'));
    } catch (\Throwable $sa_partial_rgb_error) {
        $sa_partial_rgb = '214, 41, 118';
        $sa_partial_rgb_dark = $sa_partial_rgb;
    }

    $sa_partial_font = $sa_partial_payload['font_color'] ?? null;
    $sa_partial_font_dark = $sa_partial_payload['font_color_dark'] ?? null;

    $sa_partial_gradient = 'linear-gradient(' . (int) ($sa_partial_payload['gradient_angle'] ?? 135) . 'deg, '
        . implode(', ', $sa_partial_payload['gradient'] ?? ['#D62976']) . ')';
@endphp
<style id="saniar-appearance-vars">
    :root {
        --main-color-one: {{ $sa_partial_payload['base'] }};
        --main-color-one-hover: {{ $sa_partial_payload['hover'] }};
        --main-color-one-shadow: {{ $sa_partial_payload['base'] }}31;
        --main-color-one-rgb: {{ $sa_partial_rgb }};
        --st-gradient: {{ $sa_partial_gradient }};
@if (! empty($sa_partial_payload['scale']) && is_array($sa_partial_payload['scale']))
@foreach ($sa_partial_payload['scale'] as $sa_partial_step => $sa_partial_hex)
        --st-primary-{{ $sa_partial_step }}: {{ $sa_partial_hex }};
@endforeach
@endif
@if ($sa_partial_font)
        --color-site: {{ $sa_partial_font }};
@endif
    }

    html[data-theme="dark"] {
        --main-color-one: {{ $sa_partial_payload['dark_base'] ?? $sa_partial_payload['base'] }};
        --main-color-one-hover: {{ $sa_partial_payload['dark_hover'] ?? $sa_partial_payload['hover'] }};
        --main-color-one-shadow: {{ $sa_partial_payload['dark_base'] ?? $sa_partial_payload['base'] }}31;
        --main-color-one-rgb: {{ $sa_partial_rgb_dark }};
@if ($sa_partial_font_dark)
        --color-site: {{ $sa_partial_font_dark }};
@endif
    }

    {{-- ابزارهای گرادیان پالت برای بخش‌های ویژهٔ قالب (عیناً مثل master.blade.php) --}}
    .st-gradient {
        background-image: var(--st-gradient) !important;
    }

    .st-gradient-text {
        background-image: var(--st-gradient);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent !important;
    }
</style>
