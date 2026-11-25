@php
    /* فاز ۲۰۸ — پوستهٔ رنگی پنل (تنظیمات ظاهری سایدبار):
       option «admin_appearance» (JSON ساخت App\Support\PaletteEngine) خوانده و
       به‌صورت بازنویسی متغیرهای --color-brand-* بعد از CSS کامپایل‌شده تزریق
       می‌شود — کل پنل (سایدبار/دکمه‌ها/فرم‌ها/دارک‌مود) یک‌جا رنگ می‌گیرد.
       وقتی چیزی ذخیره نشده، پوشهٔ پیش‌فرض = زمردیِ فعلی (بدون هیچ تغییر ظاهری). */
    try {
        $ap_appearance_state = \App\Support\PaletteEngine::stateFromOption();
    } catch (\Throwable $ap_appearance_error) {
        $ap_appearance_state = null;
    }

    $ap_appearance_payload = $ap_appearance_state['payload'] ?? null;

    if (! is_array($ap_appearance_payload) || empty($ap_appearance_payload['scale'])) {
        $ap_appearance_payload = \App\Support\PaletteEngine::defaultPayload();
    }

    /* حالت پیش‌فرض پنل: کوکیِ انتخابِ کاربر مقدم است؛ بدون کوکی، انتخابِ
       مدیر در صفحهٔ «تنظیمات ظاهری» (روشن/تیره) ملاک می‌شود. */
    $ap_admin_theme_cookie = request()->cookie('admin-theme');

    if ($ap_admin_theme_cookie === 'dark') {
        $ap_admin_theme_class = 'dark';
    } elseif ($ap_admin_theme_cookie === 'light') {
        $ap_admin_theme_class = '';
    } else {
        $ap_admin_theme_class = ($ap_appearance_payload['mode'] ?? 'light') === 'dark' ? 'dark' : '';
    }
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl"
      class="h-full lw-booting {{ $ap_admin_theme_class }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="admin-prefix" content="{{ admin_route_prefix() }}">
    {{-- فاز ۲۰۸ — مرجع حالت پیش‌فرض پنل برای ApTheme.get (وقتی کوکی‌ای نیست) --}}
    <meta name="admin-appearance-mode" content="{{ $ap_appearance_payload['mode'] ?? 'light' }}">

    <title>@yield('title', $title ?? 'داشبورد') | پنل مدیریت {{ option('info_site_title', config('app.name')) }}</title>

    {{-- کوکی «پنل فعال» — صفحات و منوهای پکیج‌ها با همین کوکی بین پنل جدید/قدیمی سوییچ می‌کنند --}}
    <script>document.cookie = 'ap_panel=new; path=/; max-age=31536000; SameSite=Lax';</script>

    {{-- رزرو ترتیب لایه‌ها (عیناً مثل module-new) قبل از هر CSSی. روی صفحه‌های پنل
        بی‌اثر است (هیچ قاعده‌ای در این لایه‌ها نیست) اما اگر با SPA-navigate/دکمه‌ی بازگشت به
        صفحه‌ی پل برگردیم، mergeNewHead بلوک‌های استایل پل را «دیرتر» به head اضافه می‌کند؛
        بدون این پرولود، legacy بعد از utilities می‌افتید و کروم پل می‌شکست. این نسخه با نسخه‌ی
        module-new عیناً یکسان است تا در mergeNewHead dedupe شود (دو بار تعریف نشود). --}}
    <style>@layer ap-early, theme, base, legacy, ap-late;</style>

    @vite(['resources/admin-panel/css/admin.css', 'resources/admin-panel/js/admin.js'])
    @livewireStyles

    {{-- Font Awesome برای آیکون‌های منوی پکیج‌ها (همان منبعی که پنل قدیمی/پل استفاده می‌کند) --}}
    {{-- خودمیزبانی FA: cdnjs روی IPهای ایران کند/مسدود است و رندر همهٔ صفحات پنل را بلاک می‌کرد --}}
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" referrerpolicy="no-referrer" />

    @php
        /* فاز ۲۰۸ — بازنویسی توکن‌های برند پنل از پوستهٔ انتخابی.
           بعد از @vite می‌آید تا برنده شود؛ پنل با Tailwind 4 همهٔ رنگ‌های
           برند را از var(--color-brand-*) می‌خواند ⇒ همین چند متغیر کل
           پنل را رنگ می‌گیرد. رنگ‌های هاردکدِ باقی‌مانده هم اینجا بازنویسی
           می‌شوند (سایهٔ دکمهٔ اصلی، خط drop ردیف‌ها، نقطهٔ پالس و CKEditor). */
        $ap_scale = $ap_appearance_payload['scale'];
        $ap_base = $ap_appearance_payload['base'] ?? ($ap_scale[600] ?? '#059669');

        /* فاز ۲۱۰ — رنگ فونت/سایدبار/صفحه از پوستهٔ فعال.
           null = «پیش‌فرض پنل» (بدون بازنویسی متغیر). رنگ متنِ سایدبار
           با کنتراست YIQ از خودِ رنگِ سایدبار مشتق می‌شود تا هر رنگی
           که مدیر انتخاب کند، متن همیشه خوانا می‌ماند. */
        $ap_font      = $ap_appearance_payload['font_color'] ?? null;
        $ap_font_dark = $ap_appearance_payload['font_color_dark'] ?? null;

        $ap_side      = $ap_appearance_payload['sidebar_color'] ?? null;
        $ap_side_dark = $ap_appearance_payload['sidebar_color_dark'] ?? null;
        $ap_side_ink      = $ap_side ? \App\Support\PaletteEngine::contrastInk($ap_side) : null;
        $ap_side_ink_dark = $ap_side_dark ? \App\Support\PaletteEngine::contrastInk($ap_side_dark) : null;

        $ap_page      = $ap_appearance_payload['page_color'] ?? null;
        $ap_page_dark = $ap_appearance_payload['page_color_dark'] ?? null;
    @endphp
    <style id="admin-appearance-vars">
        :root {
@if (! empty($ap_scale) && is_array($ap_scale))
@foreach ($ap_scale as $ap_step => $ap_hex)
            --color-brand-{{ $ap_step }}: {{ $ap_hex }};
@endforeach
@endif
@if ($ap_font)
            /* فاز ۲۱۰ — رنگ فونت پنل (حالت روشن) */
            --color-ink: {{ $ap_font }};
@endif
@if ($ap_font_dark)
            /* فاز ۲۱۰ — رنگ فونت پنل (تم شب) — فقط ابزارهای dark این var را می‌خوانند */
            --color-night-ink: {{ $ap_font_dark }};
@endif
            /* CKEditor — رنگ تأکیدی ویرایشگر هم‌رنگ برند پنل */
            --ck-color-base-action: var(--color-brand-600);
            --ck-color-base-action-hover: var(--color-brand-700);
            --ck-color-base-focus: var(--color-brand-600);
            --ck-color-base-active: var(--color-brand-600);
            --ck-color-base-action-muted: var(--color-brand-100);
            --ck-focus-border-color: var(--color-brand-600);
        }

        /* سایهٔ هاردکد دکمهٔ اصلی (rgb زمردی در admin.css) */
        .ap-btn-primary {
            box-shadow: 0 6px 16px -6px {{ $ap_base }}73;
        }

        /* خط راهنمای drop ردیف‌ها (طبقه‌بندی‌ها) */
        .cat-row.ap-drop-before {
            box-shadow: inset 0 3px 0 0 var(--color-brand-500), 0 6px 16px -8px {{ $ap_base }}59;
        }

        .cat-row.ap-drop-after {
            box-shadow: inset 0 -3px 0 0 var(--color-brand-500), 0 6px 16px -8px {{ $ap_base }}59;
        }

        /* نقطهٔ پالس (وضعیت‌ها) */
        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 0 0 {{ $ap_base }}80; }
            50% { box-shadow: 0 0 0 5px {{ $ap_base }}00; }
        }
    </style>

    {{-- فاز ۲۱۰ — رنگ سایدبار و رنگ صفحه.
         سایدبار: به‌جای دست‌زدن به کلاس‌ها، همان متغیرهای سطح (surface/line/ink)
         فقط داخل #ap-sidebar بازتعریف می‌شوند؛ همهٔ رنگ‌های فرعی (هاور، کارت
         پروفایل، خطوط، متن کم‌رنگ) با color-mix از خودِ رنگ انتخابی مشتق
         می‌شوند تا در حالت روشن و تم شب همیشه خوانا و هماهنگ بمانند.
         صفحه: پس‌زمینهٔ بدنه با !important بازنویسی می‌شود؛ کارت‌ها و
         سطوح سفید دست‌نخورده می‌مانند. --}}
    <style id="admin-appearance-shell">
        /* پس‌زمینهٔ سایدبار/صفحه همیشه از متغیر خوانده می‌شود تا در نبودِ رنگ
           سفارشی، دقیقاً مقدار پیش‌فرض پنل و در تم شب مقدار تیره اعمال شود؛
           و در JS پس از ذخیره هم فقط همین متغیرها ست شوند (بدون دست‌زدن به کلاس‌ها). */
        #ap-sidebar { background-color: var(--ap-side-bg, var(--color-surface)); }
        html.dark #ap-sidebar { background-color: var(--ap-side-bg-dark, var(--color-night-2)); }
        body { background-color: var(--ap-page-bg, var(--color-surface-2)); }
        html.dark body { background-color: var(--ap-page-bg-dark, var(--color-night)); }
@if ($ap_side)
        #ap-sidebar {
            --ap-side-bg: {{ $ap_side }};
            --color-surface: {{ $ap_side }};
            --ap-side-ink: {{ $ap_side_ink }};
            --color-surface-2: color-mix(in srgb, {{ $ap_side }} 93%, var(--ap-side-ink));
            --color-surface-3: color-mix(in srgb, {{ $ap_side }} 87%, var(--ap-side-ink));
            --color-line: color-mix(in srgb, {{ $ap_side }} 82%, var(--ap-side-ink));
            --color-ink: var(--ap-side-ink);
            --color-ink-2: color-mix(in srgb, var(--ap-side-ink) 76%, {{ $ap_side }});
            --color-ink-3: color-mix(in srgb, var(--ap-side-ink) 56%, {{ $ap_side }});
        }
@endif
@if ($ap_side_dark)
        html.dark #ap-sidebar {
            --ap-side-bg-dark: {{ $ap_side_dark }};
            --color-night-2: {{ $ap_side_dark }};
            --ap-side-ink-d: {{ $ap_side_ink_dark }};
            --color-night-3: color-mix(in srgb, {{ $ap_side_dark }} 86%, var(--ap-side-ink-d));
            --color-night-line: color-mix(in srgb, {{ $ap_side_dark }} 80%, var(--ap-side-ink-d));
            --color-night-ink: var(--ap-side-ink-d);
            --color-night-ink-2: color-mix(in srgb, var(--ap-side-ink-d) 72%, {{ $ap_side_dark }});
        }
@endif
@if ($ap_page)
        html { --ap-page-bg: {{ $ap_page }}; }
@endif
@if ($ap_page_dark)
        html.dark { --ap-page-bg-dark: {{ $ap_page_dark }}; }
@endif
    </style>

    {{-- پاک‌سازی استایل‌های پل بعد از SPA-navigate (عیناً مثل module-new — dedupe در
        mergeNewHead). اگر از صفحه‌ی پکیج (پل) به اینجا ناوبری SPA شده باشد، بلوک‌های
        data-ap-bridge-style پل هنوز در head هستند (Livewire assetها را حذف نمی‌کند) و اسکیل
        !important قدیمی به سایدبار/تاپ‌بار پنل ضربه زده است؛ این اسکریپت بعد از swap آن‌ها را
        حذف می‌کند (صفحه‌ی پنل #ap-chrome-side ندارد → شرط برقرار است). --}}
    <script>
        document.addEventListener('livewire:navigated', function () {
            if (! document.getElementById('ap-chrome-side')) {
                document.head.querySelectorAll('[data-ap-bridge-style]').forEach(function (el) { el.remove(); });
            }
        });
    </script>
</head>
<body class="min-h-screen">

{{-- بدنه با درایور Alpine سایدبار --}}
<div x-data="apSidebar" class="min-h-screen flex flex-col">

    {{-- پوشش موبایل --}}
    <div x-show="open" x-cloak x-transition.opacity.duration.200ms
         class="fixed inset-0 z-40 bg-slate-950/50 backdrop-blur-sm lg:hidden"
         x-on:click="open = false" aria-hidden="true"></div>

    <div class="flex flex-1">
        <x-admin-panel::sidebar />

        {{-- ستون اصلی --}}
        <div class="flex min-w-0 flex-1 flex-col lg:mr-[17rem]">
            {{-- : $title (از #[Title] Livewire) صریحاً پاس داده می‌شود — کامپوننت‌های anonymous اسکوپ والد را ارث نمی‌برند --}}
            <x-admin-panel::topbar :title="($title ?? null)" />

            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>

            <footer class="ap-footer px-4 py-4 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 text-xs text-ink-3 dark:text-night-ink-2">
                    <span>پنل مدیریت {{ option('info_site_title', config('app.name')) }} — نسخه جدید (Livewire 4)</span>
                    <span class="fa-digits">© {{ date('Y') }}</span>
                </div>
            </footer>
        </div>
    </div>

    {{-- Toasts --}}
    <x-admin-panel::toasts />
</div>

@if (session('np-toast'))
    @php $npToast = session('np-toast') @endphp
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.ApToast?.{{ in_array($npToast['type'] ?? '', ['success', 'error', 'info']) ? $npToast['type'] : 'info' }}(
                {{ json_encode($npToast['message'] ?? '') }});
        });
    </script>
@endif

@livewireScripts

{{-- گارد بوت لایووایر + نگهبان خودترمیم asset
     ۱) کلاس lw-booting تا لحظه راه‌اندازی لایووایر، spanهای wire:loading را مخفی نگه می‌دارد
        (رفع دکمه‌هایی که از ابتدا «در حال ذخیره…» نشان می‌دهند وقتی JS لایووایر لود نشده).
     ۲) اگر اسکریپت لایووایر بعد از ۶ ثانیه لود نشد (مثل 404 پس از تغییر APP_KEY که
        URL سرویسِ asset را عوض می‌کند)، یک‌بار صفحه تازه‌سازی می‌شود (بدون حلقه بی‌پایان). --}}
<script>
    (function () {
        var root = document.documentElement;
        var unboot = function () {
            root.classList.remove('lw-booting');
            document.removeEventListener('livewire:init', unboot);
            document.removeEventListener('livewire:initialized', unboot);
            document.removeEventListener('livewire:navigated', unboot);
        };
        document.addEventListener('livewire:init', unboot);
        document.addEventListener('livewire:initialized', unboot);
        document.addEventListener('livewire:navigated', unboot);
        setTimeout(function () {
            if (window.Livewire) { unboot(); return; }
            try {
                var stamp = 'ap-lw-boot-reload';
                var last = parseInt(sessionStorage.getItem(stamp) || '0', 10);
                if (Date.now() - last > 30000) {
                    sessionStorage.setItem(stamp, String(Date.now()));
                    location.reload();
                }
            } catch (e) { /* دسترسی به sessionStorage نیست — چیزی نکن */ }
        }, 6000);
    })();
</script>
@stack('scripts')
</body>
</html>
