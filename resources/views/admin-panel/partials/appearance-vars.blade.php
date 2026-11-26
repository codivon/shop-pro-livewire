{{-- ============================================================
     فاز ۲۱۲ — پارشیال مشترکِ تزریق پوستهٔ رنگی پنل
     همهٔ لایوت‌های پنل (layout / module-new / builder / login)
     این پارشیال را include می‌کنند تا پوستهٔ انتخابی مدیر در
     «تنظیمات ظاهری» یک‌جا و یکسان روی کل پنل اعمال شود.
     ------------------------------------------------------------
     ورودی: $ap_appearance_payload (JSON ساخت PaletteEngine v4)
     خروجی (دو بلوک استایل، بعد از @vite تا برنده شوند):
       ۱) admin-appearance-vars — بازنویسی کامل توکن‌های پنل:
          • طیف --color-brand-50..950 (دکمه‌ها/لینک‌ها/آیکن‌ها)
          • حالت روشن: surface / surface-2 / surface-3 / line /
            ink / ink-2 / ink-3 (کارت‌ها، صفحه، خط‌ها، متن‌ها)
          • تم شب: night / night-1..3 / night-line / night-ink-2
          • متغیرهای CKEditor هم‌رنگ برند
       ۲) admin-appearance-shell — سایدبار با رنگ اختصاصی خودش:
          توکن‌های سطح/خط/متن فقط داخل #ap-sidebar با رنگ سایدبار
          بازتعریف می‌شوند (متن با کنتراست YIQ همیشه خوانا).
     اگر چیزی ذخیره نشده باشد، پوشهٔ پیش‌فرض = زمردیِ فعلی پنل
     با توکن‌های عینِ admin.css ⇒ هیچ تغییر ظاهری رخ نمی‌دهد.
     ============================================================ --}}
@php
    $apv_scale      = $ap_appearance_payload['scale'] ?? [];
    $apv_base       = $ap_appearance_payload['base'] ?? '#059669';
    $apv_tokens     = $ap_appearance_payload['tokens'] ?? [];
    $apv_tokens_l   = is_array($apv_tokens['light'] ?? null) ? $apv_tokens['light'] : [];
    $apv_tokens_d   = is_array($apv_tokens['dark'] ?? null) ? $apv_tokens['dark'] : [];

    $apv_side       = $ap_appearance_payload['sidebar_color'] ?? null;
    $apv_side_d     = $ap_appearance_payload['sidebar_color_dark'] ?? null;
    $apv_side_ink   = $apv_side   ? \App\Support\PaletteEngine::contrastInk($apv_side)   : null;
    $apv_side_ink_d = $apv_side_d ? \App\Support\PaletteEngine::contrastInk($apv_side_d) : null;
@endphp
<style id="admin-appearance-vars">
    :root {
@if (! empty($apv_scale) && is_array($apv_scale))
@foreach ($apv_scale as $apv_step => $apv_hex)
        --color-brand-{{ $apv_step }}: {{ $apv_hex }};
@endforeach
@endif
@if (! empty($apv_tokens_l))
@foreach ($apv_tokens_l as $apv_key => $apv_hex)
        --color-{{ str_replace('_', '-', $apv_key) }}: {{ $apv_hex }};
@endforeach
@endif
        /* CKEditor — رنگ تأکیدی ویرایشگر هم‌رنگ برند پنل */
        --ck-color-base-action: var(--color-brand-600);
        --ck-color-base-action-hover: var(--color-brand-700);
        --ck-color-base-focus: var(--color-brand-600);
        --ck-color-base-active: var(--color-brand-600);
        --ck-color-base-action-muted: var(--color-brand-100);
        --ck-focus-border-color: var(--color-brand-600);
    }
@if (! empty($apv_tokens_d))
    html.dark {
@foreach ($apv_tokens_d as $apv_key => $apv_hex)
        --color-{{ str_replace('_', '-', $apv_key) }}: {{ $apv_hex }};
@endforeach
    }
@endif
    /* سایهٔ هاردکد دکمهٔ اصلی (قرینهٔ admin.css با رنگ پوستهٔ فعال) */
    .ap-btn-primary {
        box-shadow: 0 6px 16px -6px {{ $apv_base }}73;
    }
</style>

<style id="admin-appearance-shell">
    /* پس‌زمینهٔ سایدبار همیشه از متغیر خوانده می‌شود تا در نبودِ رنگ
       اختصاصی، دقیقاً مقدار پیش‌فرض پنل و در تم شب مقدار تیره اعمال شود.
       صفحه/کارت‌ها/خط‌ها/متن‌ها از توکن‌های بلوک بالا رنگ می‌گیرند. */
    #ap-sidebar { background-color: var(--ap-side-bg, var(--color-surface)); }
    html.dark #ap-sidebar { background-color: var(--ap-side-bg-dark, var(--color-night-2)); }
    body { background-color: var(--color-surface-2); }
    html.dark body { background-color: var(--color-night); }
@if ($apv_side)
    /* رنگ اختصاصی سایدبار (حالت روشن) — همهٔ رنگ‌های فرعیِ داخل سایدبار
       (هاور، کارت پروفایل، خطوط، متن کم‌رنگ) با color-mix از خودِ رنگ
       سایدبار مشتق می‌شوند تا متن همیشه خوانا بماند. */
    #ap-sidebar {
        --ap-side-bg: {{ $apv_side }};
        --color-surface: {{ $apv_side }};
        --ap-side-ink: {{ $apv_side_ink }};
        --color-surface-2: color-mix(in srgb, {{ $apv_side }} 93%, var(--ap-side-ink));
        --color-surface-3: color-mix(in srgb, {{ $apv_side }} 87%, var(--ap-side-ink));
        --color-line: color-mix(in srgb, {{ $apv_side }} 82%, var(--ap-side-ink));
        --color-ink: var(--ap-side-ink);
        --color-ink-2: color-mix(in srgb, var(--ap-side-ink) 76%, {{ $apv_side }});
        --color-ink-3: color-mix(in srgb, var(--ap-side-ink) 56%, {{ $apv_side }});
    }
@endif
@if ($apv_side_d)
    /* رنگ اختصاصی سایدبار (تم شب) */
    html.dark #ap-sidebar {
        --ap-side-bg-dark: {{ $apv_side_d }};
        --color-night-2: {{ $apv_side_d }};
        --ap-side-ink-d: {{ $apv_side_ink_d }};
        --color-night-3: color-mix(in srgb, {{ $apv_side_d }} 86%, var(--ap-side-ink-d));
        --color-night-line: color-mix(in srgb, {{ $apv_side_d }} 80%, var(--ap-side-ink-d));
        --color-night-ink: var(--ap-side-ink-d);
        --color-night-ink-2: color-mix(in srgb, var(--ap-side-ink-d) 72%, {{ $apv_side_d }});
    }
@endif
</style>
