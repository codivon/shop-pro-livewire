{{-- Layout تمام‌صفحه‌ی ویجت‌ساز — مستقل از سایدبار/تاپ‌بار پنل --}}
@php
    /* فاز ۲۰۸ — پوستهٔ رنگی پنل (تنظیمات ظاهری سایدبار) — مثل layout اصلی */
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
    $ap_scale = $ap_appearance_payload['scale'];
    $ap_base = $ap_appearance_payload['base'] ?? ($ap_scale[600] ?? '#059669');
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl"
      class="h-full {{ $ap_admin_theme_class }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="admin-prefix" content="{{ admin_route_prefix() }}">
    <meta name="admin-appearance-mode" content="{{ $ap_appearance_payload['mode'] ?? 'light' }}">

    <title>@yield('title', 'ویجت‌ساز') | پنل مدیریت {{ option('info_site_title', config('app.name')) }}</title>

    @vite(['resources/admin-panel/css/admin.css', 'resources/admin-panel/js/admin.js'])
    @livewireStyles

    <style id="admin-appearance-vars">
        :root {
@if (! empty($ap_scale) && is_array($ap_scale))
@foreach ($ap_scale as $ap_step => $ap_hex)
            --color-brand-{{ $ap_step }}: {{ $ap_hex }};
@endforeach
@endif
            --ck-color-base-action: var(--color-brand-600);
            --ck-color-base-action-hover: var(--color-brand-700);
            --ck-color-base-focus: var(--color-brand-600);
            --ck-color-base-active: var(--color-brand-600);
            --ck-color-base-action-muted: var(--color-brand-100);
            --ck-focus-border-color: var(--color-brand-600);
        }

        .ap-btn-primary {
            box-shadow: 0 6px 16px -6px {{ $ap_base }}73;
        }

        .cat-row.ap-drop-before {
            box-shadow: inset 0 3px 0 0 var(--color-brand-500), 0 6px 16px -8px {{ $ap_base }}59;
        }

        .cat-row.ap-drop-after {
            box-shadow: inset 0 -3px 0 0 var(--color-brand-500), 0 6px 16px -8px {{ $ap_base }}59;
        }

        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 0 0 {{ $ap_base }}80; }
            50% { box-shadow: 0 0 0 5px {{ $ap_base }}00; }
        }
    </style>
</head>
<body class="min-h-screen">
{{ $slot }}

{{-- Toasts (رویداد toast dispatch لایووایر + ap-toast) --}}
<x-admin-panel::toasts />

@livewireScripts
@stack('scripts')
</body>
</html>
