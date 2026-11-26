{{-- ============================================================
     فاز ۲۱۳ — پارشیال مشترکِ تزریق پوستهٔ رنگی پنل
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
          • تازهٔ ۲۱۳: گرادیانِ امضای پالت (--ap-grad) روی دکمهٔ
            اصلی، لوگوی سایدبار و نشانگر صفحهٔ نوار بالا + شستوی
            محیطی صفحه (body::before) + اسکرول‌بار هم‌رنگ پوسته
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

    /* فاز ۲۱۳ — گرادیان دوتُنیِ پالت (جفت رنگ دست‌چین هر پالت؛ اگر پوستهٔ
       قدیمیِ بدون گرادیان ذخیره شده باشد، از طیف برند مشتق می‌شود) */
    $apv_grad    = is_array($ap_appearance_payload['gradient'] ?? null) ? $ap_appearance_payload['gradient'] : [];
    $apv_grad_a  = $apv_grad[0] ?? ($apv_scale[400] ?? $apv_base);
    $apv_grad_b  = $apv_grad[1] ?? $apv_base;
    $apv_angle   = (int) ($ap_appearance_payload['gradient_angle'] ?? 135);
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

        /* فاز ۲۱۳ — گرادیانِ امضای پالت: دکمه‌ها و نشانگرهای اصلی پنل */
        --ap-grad: linear-gradient({{ $apv_angle }}deg, {{ $apv_grad_a }}, {{ $apv_grad_b }});
        --ap-grad-hover: linear-gradient({{ $apv_angle }}deg, {{ $apv_grad_b }}, {{ $apv_grad_a }});
        --ap-grad-soft: linear-gradient({{ $apv_angle }}deg,
            color-mix(in srgb, {{ $apv_grad_a }} 18%, transparent),
            color-mix(in srgb, {{ $apv_grad_b }} 10%, transparent));
    }
@if (! empty($apv_tokens_d))
    html.dark {
@foreach ($apv_tokens_d as $apv_key => $apv_hex)
        --color-{{ str_replace('_', '-', $apv_key) }}: {{ $apv_hex }};
@endforeach
    }
@endif

    /* ================================================================
       فاز ۲۱۳ — لایهٔ زیباییِ سراسری پنل (همهٔ لایوت‌ها، روشن و تم شب)
       ================================================================ */

    /* ۱) شستوی محیطی صفحه: هالهٔ ملایمِ هم‌خانوادهٔ برند بالای صفحه —
          زیر محتوا (z-index:-1) و بی‌اثر روی موس؛ کل حس پنل را مدرن می‌کند */
    body::before {
        content: '';
        position: fixed;
        top: 0;
        inset-inline: 0;
        height: 420px;
        z-index: -1;
        pointer-events: none;
        background:
            radial-gradient(56% 130% at 88% -12%, color-mix(in srgb, var(--color-brand-600) 10%, transparent), transparent 66%),
            radial-gradient(42% 110% at 10% -10%, color-mix(in srgb, var(--color-brand-400) 8%, transparent), transparent 62%);
    }
    html.dark body::before {
        background:
            radial-gradient(56% 130% at 88% -12%, color-mix(in srgb, var(--color-brand-500) 15%, transparent), transparent 66%),
            radial-gradient(42% 110% at 10% -10%, color-mix(in srgb, var(--color-brand-700) 18%, transparent), transparent 62%);
    }

    /* ۲) دکمهٔ اصلی: گرادیان دوتُنیِ پالت + سایهٔ رنگیِ هم‌خانواده؛
          هاور = جابه‌جایی نرم دو رنگ گرادیان (کمی عمیق‌تر) */
    .ap-btn-primary {
        background-image: var(--ap-grad);
        box-shadow: 0 8px 20px -8px color-mix(in srgb, {{ $apv_grad_b }} 62%, transparent);
    }
    .ap-btn-primary:hover:not(:disabled) {
        background-image: var(--ap-grad-hover);
        box-shadow: 0 10px 24px -8px color-mix(in srgb, {{ $apv_grad_b }} 74%, transparent);
    }

    /* ۳) لوگوی سایدبار و نشانگر صفحهٔ نوار بالا: گرادیان امضای پالت */
    #ap-sidebar .bg-brand-600 {
        background-image: var(--ap-grad);
    }
    .ap-page-chip {
        background-image: var(--ap-grad) !important;
        box-shadow: 0 6px 14px -6px color-mix(in srgb, {{ $apv_grad_b }} 55%, transparent);
    }

    /* ۴) اسکرول‌بار هم‌رنگ پوسته (به‌جای خاکستری ثابت) */
    *::-webkit-scrollbar-thumb {
        background: color-mix(in srgb, var(--color-brand-600) 34%, transparent);
    }
    *::-webkit-scrollbar-thumb:hover {
        background: color-mix(in srgb, var(--color-brand-600) 55%, transparent);
    }
    html {
        scrollbar-color: color-mix(in srgb, var(--color-brand-600) 40%, transparent) transparent;
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
