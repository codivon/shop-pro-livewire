@php
    /* فاز ۲۱۲ — پوستهٔ رنگی پنل (تنظیمات ظاهری سایدبار) — مثل layout اصلی */
    try {
        $ap_appearance_payload = \App\Support\PaletteEngine::stateFromOption()['payload'] ?? null;
    } catch (\Throwable $ap_appearance_error) {
        $ap_appearance_payload = null;
    }
    if (! is_array($ap_appearance_payload) || empty($ap_appearance_payload['scale'])) {
        $ap_appearance_payload = \App\Support\PaletteEngine::defaultPayload();
    }
    $ap_admin_theme_cookie = request()->cookie('admin-theme');
    $ap_admin_theme_class = $ap_admin_theme_cookie === 'dark'
        ? 'dark'
        : ($ap_admin_theme_cookie === 'light'
            ? ''
            : (($ap_appearance_payload['mode'] ?? 'light') === 'dark' ? 'dark' : ''));
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl"
      class="h-full {{ $ap_admin_theme_class }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="admin-appearance-mode" content="{{ $ap_appearance_payload['mode'] ?? 'light' }}">

    <title>@yield('title', 'ورود') | پنل مدیریت {{ option('info_site_title', config('app.name')) }}</title>

    @vite(['resources/admin-panel/css/admin.css', 'resources/admin-panel/js/admin.js'])
    @livewireStyles

    {{-- فاز ۲۱۲ — پوستهٔ رنگی پنل: بازنویسی کامل توکن‌ها با پارشیال مشترک --}}
    @include('admin-panel::partials.appearance-vars')
</head>
<body class="min-h-screen">
{{ $slot }}

{{-- Toasts (برای خطاهای عمومی) --}}
<x-admin-panel::toasts />

@livewireScripts
@stack('scripts')
</body>
</html>
