{{-- ============================================================
     پنل مدیریت — تنظیمات ظاهری (فاز ۲۱۲ — بازطراحی کامل)
     ------------------------------------------------------------
     هر پالتِ آماده «کل توکن‌های پنل» را با هم عوض می‌کند:
       • طیف brand → دکمه‌ها، لینک‌ها، آیکن‌ها، حالت فعال
       • سایدبار = رنگِ عمیقِ مدرن (حتی در حالت روشن) با متنِ
         خودکارِ خوانا (کنتراست YIQ) + نسخهٔ مخصوص تم شب
       • صفحه = شستوی رنگیِ هم‌خانواده؛ کارت‌ها سفیدِ تمیز
       • خط‌ها، متن‌ها (اصلی/متوسط/کم‌رنگ) و هاورها هم‌خانواده
       • تم شب = تیرهٔ رنگ‌گرفتهٔ همان خانواده (نه خاکستری خام)
     ⇒ با انتخاب هر پالت «کل پنل» همان لحظه رنگ می‌گیرد (زنده)،
       تا ذخیره موقتی است؛ متن‌ها و دکمه‌ها در روشن و تیره همیشه
       خوانا می‌مانند (زوج‌های کنتراست قبل از انتشار تایید شده‌اند).
     بخش‌ها: پیش‌نمایش زندهٔ دوحالته | پالت‌های آماده |
       شخصی‌سازی (رنگ دکمه‌ها/متن/سایدبار/صفحه) | گرمی/سردی
     حالت پیش‌فرض روشن/تیره از جای دیگر تنظیم می‌شود (کوکی کاربر)
     و اینجا فقط «سازگاری» تضمین می‌شود — بدون هیچ کلیدی.
     JS همین صفحه قرینهٔ دقیق App\Support\PaletteEngine است؛
     پس از ذخیره، layout پنل توکن‌ها را سراسری تزریق می‌کند.
     ============================================================ --}}
@php
    /** @var array $preview */
    /** @var array $palettes */
    /** @var array $previews */

    $pe = \App\Support\PaletteEngine::class;

    $base = $preview['base'];
    $scale = $preview['scale'];
    $tL = $preview['tokens']['light'];
    $tD = $preview['tokens']['dark'];
    $gradientCss = 'linear-gradient('.$preview['gradient_angle'].'deg, '.implode(', ', $preview['gradient']).')';
    $rawBase = ($palette === 'custom') ? $custom : (collect($palettes)->firstWhere('key', $palette)['base'] ?? $base);

    /* نقش‌های فعالِ فعلی — مقادیر اولیهٔ پیش‌نمایش زندهٔ دوحالته */
    $pvSide      = $preview['sidebar_color'] ?: $pe::DEFAULT_SIDEBAR_COLOR;
    $pvSideInk   = $preview['side_ink'] ?: $pe::contrastInk($pvSide);
    $pvSideD     = $preview['sidebar_color_dark'] ?: $pe::DEFAULT_SIDEBAR_COLOR_DARK;
    $pvSideInkD  = $preview['side_ink_dark'] ?: $pe::contrastInk($pvSideD);
    $pvFont      = $preview['font_color'] ?: $pe::DEFAULT_FONT_COLOR;
    $pvPage      = $tL['surface_2'];
    $pvPageD     = $tD['night'];

    /* مینیاتورِ هر پالت با توکن‌های واقعیِ خودش */
    $cards = [];
    foreach ($palettes as $p) {
        $r = $previews[$p['key']];
        $rtl = $r['tokens']['light'];
        $rtd = $r['tokens']['dark'];
        $cSide  = $r['sidebar_color'] ?: $pe::DEFAULT_SIDEBAR_COLOR;
        $cSideD = $r['sidebar_color_dark'] ?: $pe::DEFAULT_SIDEBAR_COLOR_DARK;
        $cards[$p['key']] = [
            'primary'  => $r['base'],
            'side'     => $cSide,
            'sideInk'  => $r['side_ink'] ?: $pe::contrastInk($cSide),
            'sideD'    => $cSideD,
            'sideInkD' => $r['side_ink_dark'] ?: $pe::contrastInk($cSideD),
            'page'     => $rtl['surface_2'],
            'card'     => $rtl['surface'],
            'line'     => $rtl['line'],
            'ink'      => $rtl['ink'],
            'ink2'     => $rtl['ink_2'],
            'pageD'    => $rtd['night'],
            'cardD'    => $rtd['night_2'],
            'lineD'    => $rtd['night_line'],
            'inkD'     => $rtd['night_ink'],
            'ink2D'    => $rtd['night_ink_2'],
        ];
    }
@endphp
<div class="mx-auto max-w-6xl space-y-5">

    {{-- ===== سربرگ صفحه ===== --}}
    <div class="relative overflow-hidden rounded-3xl bg-linear-to-bl from-brand-700 via-brand-600 to-brand-500 p-5 text-white shadow-lg shadow-brand-600/20 sm:p-6">
        <span class="pointer-events-none absolute -top-10 -left-10 size-44 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></span>
        <span class="pointer-events-none absolute -bottom-14 left-1/3 size-52 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></span>

        <div class="relative flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <span class="flex size-13 items-center justify-center rounded-2xl bg-white/15 shadow-inner backdrop-blur">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-7" aria-hidden="true">
                        <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/>
                        <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/>
                        <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/>
                        <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/>
                        <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>
                    </svg>
                </span>
                <div>
                    <h1 class="text-lg font-bold sm:text-xl">تنظیمات ظاهری</h1>
                    <p class="mt-1 flex flex-wrap items-center gap-2 text-xs text-white/85">
                        <span>پوستهٔ رنگی پنل مدیریت:</span>
                        <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-bold">{{ $savedLabel }}</span>
                    </p>
                </div>
            </div>
            <p class="hidden text-[11px] leading-6 text-white/80 sm:block sm:max-w-96">
                با انتخاب هر پالت، کل پنل — سایدبار، صفحه، کارت‌ها، خط‌ها، متن‌ها و دکمه‌ها — همان لحظه و هماهنگ رنگ می‌گیرد.
            </p>
        </div>
    </div>

    <div id="saniar-appearance" class="ap-card overflow-hidden p-0"
         style="--sa-base: {{ $base }}; --sa-base-rgb: {{ $pe::hexToRgbString($base) }}; --sa-deep: {{ $scale['700'] }}; --sa-grad: {{ $gradientCss }};">

        <style>
            /* استایل اختصاصی صفحهٔ تنظیمات ظاهری */
            #saniar-appearance .sa-selected {
                border-color: var(--sa-base) !important;
                box-shadow: 0 0 0 3px rgba(var(--sa-base-rgb), .16) !important;
            }
            #saniar-appearance input[type="color"] { cursor: pointer; }
            #saniar-appearance input[type="range"] { accent-color: var(--sa-base); }
        </style>

        {{-- سربرگ کارت --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span id="sa-head-icon" class="flex size-11 shrink-0 items-center justify-center rounded-2xl text-white shadow-md" style="background: var(--sa-grad)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5" aria-hidden="true">
                        <circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/>
                        <circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/>
                        <circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/>
                        <circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/>
                        <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/>
                    </svg>
                </span>
                <div>
                    <h2 class="text-[15px] font-bold">ظاهر و رنگ‌بندی پنل</h2>
                    <p class="mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">اعمال زنده روی تمام صفحات پنل — روشن و تم شب</p>
                </div>
            </div>

            <span id="sa-saved-pill" class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[11px] font-bold"
                  style="border-color: rgba(var(--sa-base-rgb), .2); background: rgba(var(--sa-base-rgb), .08); color: var(--sa-deep)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-3.5" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4"/>
                    <path d="M12 8h.01"/>
                </svg>
                <span>ذخیره‌سازی: {{ $savedLabel }}</span>
            </span>
        </div>

        <div class="space-y-5 px-4 pb-5 pt-1 sm:px-6">

            {{-- ===== پیش‌نمایش زندهٔ دوحالته ===== --}}
            <div id="sa-preview-box" class="rounded-2xl border-2 border-dashed p-4 sm:p-5" style="border-color: rgba(var(--sa-base-rgb), .35);">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-[13px] font-bold">پیش‌نمایش زنده</h3>
                    <p class="text-[10px] text-ink-3 dark:text-night-ink-2">سازگاری با روشن و تم شب — به‌صورت خودکار تضمین می‌شود</p>
                </div>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    {{-- حالت روشن --}}
                    <div>
                        <p class="mb-1.5 flex items-center gap-1.5 text-[10.5px] font-bold text-ink-3 dark:text-night-ink-2">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true">
                                <circle cx="12" cy="12" r="4"/>
                                <path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/>
                                <path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/>
                                <path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                            </svg>
                            حالت روشن
                        </p>
                        <div id="sa-pv-light" class="h-52 overflow-hidden rounded-2xl border"
                             style="--pv-side: {{ $pvSide }}; --pv-side-ink: {{ $pvSideInk }}; --pv-page: {{ $pvPage }}; --pv-card: {{ $tL['surface'] }}; --pv-line: {{ $tL['line'] }}; --pv-ink: {{ $tL['ink'] }}; --pv-ink2: {{ $tL['ink_2'] }}; --pv-ink3: {{ $tL['ink_3'] }}; --pv-primary: {{ $base }};">
                            <div class="flex h-full">
                                {{-- سایدبار (RTL — سمت راست) --}}
                                <div class="flex w-[30%] shrink-0 flex-col gap-1.5 p-2.5" style="background: var(--pv-side)">
                                    <div class="mb-0.5 flex items-center gap-1.5">
                                        <span class="flex size-5 shrink-0 items-center justify-center rounded-lg text-[8px] font-black text-white" style="background: var(--pv-primary)">P</span>
                                        <span class="truncate text-[9.5px] font-bold" style="color: var(--pv-side-ink)">پنل مدیریت</span>
                                    </div>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px] font-bold" style="background: color-mix(in srgb, var(--pv-primary) 20%, var(--pv-side)); color: var(--pv-side-ink)">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: var(--pv-primary)"></span>
                                        داشبورد
                                    </span>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px]" style="color: color-mix(in srgb, var(--pv-side-ink) 64%, var(--pv-side))">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-side-ink) 40%, var(--pv-side))"></span>
                                        سفارش‌ها
                                    </span>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px]" style="color: color-mix(in srgb, var(--pv-side-ink) 64%, var(--pv-side))">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-side-ink) 40%, var(--pv-side))"></span>
                                        محصولات
                                    </span>
                                    <span class="mt-auto flex items-center gap-1.5 border-t px-1 pt-2 text-[9px]" style="border-color: color-mix(in srgb, var(--pv-side-ink) 16%, transparent); color: var(--pv-side-ink)">
                                        <span class="size-4 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-primary) 45%, var(--pv-side))"></span>
                                        حساب مدیر
                                    </span>
                                </div>
                                {{-- بدنه --}}
                                <div class="flex min-w-0 flex-1 flex-col" style="background: var(--pv-page)">
                                    <div class="flex items-center justify-between border-b px-3 py-2" style="background: var(--pv-card); border-color: var(--pv-line)">
                                        <span class="text-[9.5px] font-bold" style="color: var(--pv-ink)">داشبورد</span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="flex size-5 items-center justify-center rounded-full text-white" style="background: var(--pv-primary)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-2.5" aria-hidden="true">
                                                    <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                                                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                                </svg>
                                            </span>
                                            <span class="size-5 rounded-full" style="background: color-mix(in srgb, var(--pv-ink) 12%, var(--pv-card))"></span>
                                        </div>
                                    </div>
                                    <div class="grid flex-1 grid-cols-2 gap-2 p-2.5">
                                        <div class="flex flex-col rounded-xl border p-2" style="background: var(--pv-card); border-color: var(--pv-line)">
                                            <span class="text-[8px]" style="color: var(--pv-ink3)">فروش امروز</span>
                                            <span class="mt-auto text-[11px] font-black" style="color: var(--pv-ink)">۲٬۴۵۰٬۰۰۰</span>
                                            <span class="mt-1 inline-flex w-fit items-center gap-1 rounded-full px-1.5 py-0.5 text-[7.5px] font-bold" style="background: color-mix(in srgb, var(--pv-primary) 14%, var(--pv-card)); color: var(--pv-primary)">
                                                <span class="size-1 rounded-full" style="background: var(--pv-primary)"></span>
                                                ۱۲٪ رشد
                                            </span>
                                        </div>
                                        <div class="flex flex-col rounded-xl border p-2" style="background: var(--pv-card); border-color: var(--pv-line)">
                                            <span class="text-[8px]" style="color: var(--pv-ink3)">وضعیت سفارش</span>
                                            <span class="mt-1.5 w-fit rounded-full px-1.5 py-0.5 text-[7.5px] font-bold text-white" style="background: var(--pv-primary)">فعال</span>
                                            <div class="mt-auto flex gap-1.5">
                                                <span class="rounded-lg px-2 py-1 text-[8px] font-bold text-white" style="background: var(--pv-primary)">ذخیره</span>
                                                <span class="rounded-lg border px-2 py-1 text-[8px] font-bold" style="border-color: color-mix(in srgb, var(--pv-primary) 45%, transparent); color: var(--pv-primary)">انصراف</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- تم شب --}}
                    <div>
                        <p class="mb-1.5 flex items-center gap-1.5 text-[10.5px] font-bold text-ink-3 dark:text-night-ink-2">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true">
                                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                            </svg>
                            تم شب
                        </p>
                        <div id="sa-pv-dark" class="h-52 overflow-hidden rounded-2xl border"
                             style="--pv-side: {{ $pvSideD }}; --pv-side-ink: {{ $pvSideInkD }}; --pv-page: {{ $pvPageD }}; --pv-card: {{ $tD['night_2'] }}; --pv-line: {{ $tD['night_line'] }}; --pv-ink: {{ $tD['night_ink'] }}; --pv-ink2: {{ $tD['night_ink_2'] }}; --pv-ink3: {{ $tD['night_ink_2'] }}; --pv-primary: {{ $base }};">
                            <div class="flex h-full">
                                {{-- سایدبار --}}
                                <div class="flex w-[30%] shrink-0 flex-col gap-1.5 p-2.5" style="background: var(--pv-side)">
                                    <div class="mb-0.5 flex items-center gap-1.5">
                                        <span class="flex size-5 shrink-0 items-center justify-center rounded-lg text-[8px] font-black text-white" style="background: var(--pv-primary)">P</span>
                                        <span class="truncate text-[9.5px] font-bold" style="color: var(--pv-side-ink)">پنل مدیریت</span>
                                    </div>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px] font-bold" style="background: color-mix(in srgb, var(--pv-primary) 24%, var(--pv-side)); color: var(--pv-side-ink)">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: var(--pv-primary)"></span>
                                        داشبورد
                                    </span>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px]" style="color: color-mix(in srgb, var(--pv-side-ink) 64%, var(--pv-side))">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-side-ink) 40%, var(--pv-side))"></span>
                                        سفارش‌ها
                                    </span>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px]" style="color: color-mix(in srgb, var(--pv-side-ink) 64%, var(--pv-side))">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-side-ink) 40%, var(--pv-side))"></span>
                                        محصولات
                                    </span>
                                    <span class="mt-auto flex items-center gap-1.5 border-t px-1 pt-2 text-[9px]" style="border-color: color-mix(in srgb, var(--pv-side-ink) 16%, transparent); color: var(--pv-side-ink)">
                                        <span class="size-4 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-primary) 45%, var(--pv-side))"></span>
                                        حساب مدیر
                                    </span>
                                </div>
                                {{-- بدنه --}}
                                <div class="flex min-w-0 flex-1 flex-col" style="background: var(--pv-page)">
                                    <div class="flex items-center justify-between border-b px-3 py-2" style="background: var(--pv-card); border-color: var(--pv-line)">
                                        <span class="text-[9.5px] font-bold" style="color: var(--pv-ink)">داشبورد</span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="flex size-5 items-center justify-center rounded-full text-white" style="background: var(--pv-primary)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-2.5" aria-hidden="true">
                                                    <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                                                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                                </svg>
                                            </span>
                                            <span class="size-5 rounded-full" style="background: color-mix(in srgb, var(--pv-ink) 12%, var(--pv-card))"></span>
                                        </div>
                                    </div>
                                    <div class="grid flex-1 grid-cols-2 gap-2 p-2.5">
                                        <div class="flex flex-col rounded-xl border p-2" style="background: var(--pv-card); border-color: var(--pv-line)">
                                            <span class="text-[8px]" style="color: var(--pv-ink3)">فروش امروز</span>
                                            <span class="mt-auto text-[11px] font-black" style="color: var(--pv-ink)">۲٬۴۵۰٬۰۰۰</span>
                                            <span class="mt-1 inline-flex w-fit items-center gap-1 rounded-full px-1.5 py-0.5 text-[7.5px] font-bold" style="background: color-mix(in srgb, var(--pv-primary) 18%, var(--pv-card)); color: var(--pv-primary)">
                                                <span class="size-1 rounded-full" style="background: var(--pv-primary)"></span>
                                                ۱۲٪ رشد
                                            </span>
                                        </div>
                                        <div class="flex flex-col rounded-xl border p-2" style="background: var(--pv-card); border-color: var(--pv-line)">
                                            <span class="text-[8px]" style="color: var(--pv-ink3)">وضعیت سفارش</span>
                                            <span class="mt-1.5 w-fit rounded-full px-1.5 py-0.5 text-[7.5px] font-bold text-white" style="background: var(--pv-primary)">فعال</span>
                                            <div class="mt-auto flex gap-1.5">
                                                <span class="rounded-lg px-2 py-1 text-[8px] font-bold text-white" style="background: var(--pv-primary)">ذخیره</span>
                                                <span class="rounded-lg border px-2 py-1 text-[8px] font-bold" style="border-color: color-mix(in srgb, var(--pv-primary) 45%, transparent); color: var(--pv-primary)">انصراف</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== پالت‌های آماده ===== --}}
            <div>
                <h3 class="mb-1 text-[13px] font-bold">پالت‌های آماده</h3>
                <p class="mb-3 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                    هر پالت یک بستهٔ رنگیِ کامل است: سایدبارِ عمیق با متنِ خودکارِ خوانا، صفحهٔ رنگ‌گرفته، کارت‌های
                    تمیز، خط‌ها و متن‌های هم‌خانواده و دکمه‌های رنگِ اصلی — در روشن و تم شب با نسخهٔ مخصوص خودشان.
                    با کلیک روی هر پالت، کل پنل همان لحظه رنگ می‌گیرد (تا ذخیره، موقتی است).
                </p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($palettes as $p)
                        @php
                            $isSel = ($palette === $p['key']);
                            $c = $cards[$p['key']];
                        @endphp
                        <button type="button" data-palette-card="{{ $p['key'] }}" wire:key="sa-card-{{ $p['key'] }}"
                                class="sa-palette-card relative rounded-2xl border-2 bg-surface p-3 text-center transition-all duration-150 hover:-translate-y-0.5 dark:bg-night-2 {{ $isSel ? 'sa-selected' : 'border-line hover:border-ink-3/40 dark:border-night-line' }}"
                                aria-pressed="{{ $isSel ? 'true' : 'false' }}">

                            {{-- مینیاتور دوحالته: روشن (بالا) + تم شب (پایین) --}}
                            <span class="mb-2.5 block overflow-hidden rounded-xl border border-black/[.06] dark:border-white/10" aria-hidden="true">
                                {{-- روشن --}}
                                <span class="flex h-10" style="background: {{ $c['page'] }}">
                                    <span class="w-[30%] shrink-0 p-1" style="background: {{ $c['side'] }}">
                                        <span class="flex items-center gap-1">
                                            <span class="size-2 rounded-[3px]" style="background: {{ $c['primary'] }}"></span>
                                            <span class="h-[3px] w-3 rounded-full" style="background: color-mix(in srgb, {{ $c['sideInk'] }} 72%, transparent)"></span>
                                        </span>
                                        <span class="mt-1 block h-[5px] w-full rounded-[3px]" style="background: color-mix(in srgb, {{ $c['primary'] }} 22%, {{ $c['side'] }})"></span>
                                        <span class="mt-1 block h-[3px] w-3/4 rounded-full" style="background: color-mix(in srgb, {{ $c['sideInk'] }} 28%, transparent)"></span>
                                    </span>
                                    <span class="flex min-w-0 flex-1 flex-col gap-1 p-1">
                                        <span class="flex items-center justify-between">
                                            <span class="h-[3px] w-7 rounded-full" style="background: color-mix(in srgb, {{ $c['ink'] }} 78%, transparent)"></span>
                                            <span class="size-2 rounded-full" style="background: {{ $c['primary'] }}"></span>
                                        </span>
                                        <span class="block flex-1 rounded-[4px] border" style="background: {{ $c['card'] }}; border-color: {{ $c['line'] }}"></span>
                                        <span class="block h-[6px] w-9 rounded-[3px]" style="background: {{ $c['primary'] }}"></span>
                                    </span>
                                </span>
                                {{-- تم شب --}}
                                <span class="flex h-10 border-t" style="background: {{ $c['pageD'] }}; border-color: rgba(0,0,0,.35)">
                                    <span class="w-[30%] shrink-0 p-1" style="background: {{ $c['sideD'] }}">
                                        <span class="flex items-center gap-1">
                                            <span class="size-2 rounded-[3px]" style="background: {{ $c['primary'] }}"></span>
                                            <span class="h-[3px] w-3 rounded-full" style="background: color-mix(in srgb, {{ $c['sideInkD'] }} 72%, transparent)"></span>
                                        </span>
                                        <span class="mt-1 block h-[5px] w-full rounded-[3px]" style="background: color-mix(in srgb, {{ $c['primary'] }} 26%, {{ $c['sideD'] }})"></span>
                                        <span class="mt-1 block h-[3px] w-3/4 rounded-full" style="background: color-mix(in srgb, {{ $c['sideInkD'] }} 28%, transparent)"></span>
                                    </span>
                                    <span class="flex min-w-0 flex-1 flex-col gap-1 p-1">
                                        <span class="flex items-center justify-between">
                                            <span class="h-[3px] w-7 rounded-full" style="background: color-mix(in srgb, {{ $c['inkD'] }} 78%, transparent)"></span>
                                            <span class="size-2 rounded-full" style="background: {{ $c['primary'] }}"></span>
                                        </span>
                                        <span class="block flex-1 rounded-[4px] border" style="background: {{ $c['cardD'] }}; border-color: {{ $c['lineD'] }}"></span>
                                        <span class="block h-[6px] w-9 rounded-[3px]" style="background: {{ $c['primary'] }}"></span>
                                    </span>
                                </span>
                            </span>

                            <span class="block text-[12.5px] font-bold leading-5">{{ $p['title'] }}</span>
                            <span class="mt-0.5 block text-[10px] leading-4 text-ink-3 dark:text-night-ink-2">{{ $p['desc'] }}</span>

                            <span class="sa-check absolute left-2 top-2 hidden size-5 items-center justify-center rounded-full text-white"
                                  style="{{ $isSel ? 'display: flex; background: var(--sa-base);' : '' }}"
                                  aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="size-3">
                                    <path d="M20 6 9 17l-5-5"/>
                                </svg>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- ===== شخصی‌سازی (فقط وقتی پالت «شخصی‌سازی» انتخاب شده) ===== --}}
            <div id="sa-custom-box" class="rounded-2xl border-2 border-dashed p-4 sm:p-5 @if($palette !== 'custom') hidden @endif"
                 style="border-color: rgba(var(--sa-base-rgb), .35);">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h4 class="text-[13px] font-bold">شخصی‌سازی رنگ‌ها</h4>
                    <p class="text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">خط‌ها، هاورها و نسخهٔ تم‌شبِ هر رنگ خودکار و هماهنگ ساخته می‌شوند</p>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    {{-- دکمه‌ها و تأکیدها --}}
                    <div class="rounded-2xl border p-3.5" style="border-color: rgba(var(--sa-base-rgb), .2);">
                        <p class="flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: var(--sa-base)" aria-hidden="true">
                                <rect width="14" height="8" x="5" y="2" rx="3"/>
                                <path d="m15 10 4 5H5l4-5"/>
                                <path d="M12 15v7"/>
                            </svg>
                            دکمه‌ها و تأکیدها
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2.5">
                            <label id="sa-c-primary-sw" class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                   style="background: {{ $custom }}" title="باز کردن انتخابگر رنگ">
                                <input type="color" id="sa-c-primary" value="{{ $custom }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ دکمه‌ها و تأکیدها">
                            </label>
                            <input type="text" id="sa-c-primary-hex" dir="ltr" maxlength="7" value="{{ $custom }}"
                                   class="ap-input w-24 text-center text-[11px] font-bold" placeholder="#D62976"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                   aria-label="کد HEX رنگ دکمه‌ها">
                            <button type="button" id="sa-c-primary-reset"
                                    class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                    style="border-color: rgba(var(--sa-base-rgb), .3); color: var(--sa-base)"
                                    title="بازگشت رنگ دکمه‌ها به رنگ پیش‌فرض شخصی‌سازی">
                                پیش‌فرض
                            </button>
                        </div>
                    </div>

                    {{-- رنگ متن --}}
                    <div class="rounded-2xl border p-3.5" style="border-color: rgba(var(--sa-base-rgb), .2);">
                        <p class="flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: var(--sa-base)" aria-hidden="true">
                                <path d="M4 7V5h16v2"/>
                                <path d="M12 5v14"/>
                                <path d="M9 19h6"/>
                            </svg>
                            رنگ متن
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2.5">
                            <label id="sa-c-font-sw" class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                   style="background: {{ $pvFont }}" title="باز کردن انتخابگر رنگ">
                                <input type="color" id="sa-c-font" value="{{ $pvFont }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ متن">
                            </label>
                            <input type="text" id="sa-c-font-hex" dir="ltr" maxlength="7" value="{{ $customFont }}"
                                   class="ap-input w-24 text-center text-[11px] font-bold" placeholder="پیش‌فرض"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                   aria-label="کد HEX رنگ متن">
                            <span class="flex shrink-0 items-center gap-1" title="نسخهٔ خودکار رنگ متن در تم شب">
                                <span class="text-[9px] font-bold text-ink-3 dark:text-night-ink-2">شب:</span>
                                <span id="sa-c-font-dark-chip" class="inline-block size-6 rounded-lg border border-black/10" style="background: {{ $preview['font_color_dark'] ?: \App\Support\PaletteEngine::DEFAULT_FONT_COLOR_DARK }}" aria-hidden="true"></span>
                            </span>
                            <button type="button" id="sa-c-font-reset"
                                    class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                    style="border-color: rgba(var(--sa-base-rgb), .3); color: var(--sa-base)"
                                    title="بازگشت رنگ متن به پیش‌فرض پنل">
                                پیش‌فرض
                            </button>
                        </div>
                    </div>

                    {{-- رنگ سایدبار --}}
                    <div class="rounded-2xl border p-3.5" style="border-color: rgba(var(--sa-base-rgb), .2);">
                        <p class="flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: var(--sa-base)" aria-hidden="true">
                                <rect width="18" height="18" x="3" y="3" rx="2"/>
                                <path d="M3 9h18"/><path d="M9 21V9"/>
                            </svg>
                            رنگ سایدبار
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2.5">
                            <label id="sa-c-side-sw" class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                   style="background: {{ $preview['sidebar_color'] ?: \App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR }}" title="باز کردن انتخابگر رنگ">
                                <input type="color" id="sa-c-side" value="{{ $preview['sidebar_color'] ?: \App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ سایدبار">
                            </label>
                            <input type="text" id="sa-c-side-hex" dir="ltr" maxlength="7" value="{{ $customSidebar }}"
                                   class="ap-input w-24 text-center text-[11px] font-bold" placeholder="پیش‌فرض"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                   aria-label="کد HEX رنگ سایدبار">
                            <span class="flex shrink-0 items-center gap-1" title="نسخهٔ خودکار رنگ سایدبار در تم شب">
                                <span class="text-[9px] font-bold text-ink-3 dark:text-night-ink-2">شب:</span>
                                <span id="sa-c-side-dark-chip" class="inline-block size-6 rounded-lg border border-black/10" style="background: {{ $preview['sidebar_color_dark'] ?: \App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR_DARK }}" aria-hidden="true"></span>
                            </span>
                            <button type="button" id="sa-c-side-reset"
                                    class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                    style="border-color: rgba(var(--sa-base-rgb), .3); color: var(--sa-base)"
                                    title="بازگشت رنگ سایدبار به پیش‌فرض پنل">
                                پیش‌فرض
                            </button>
                        </div>
                    </div>

                    {{-- رنگ صفحه --}}
                    <div class="rounded-2xl border p-3.5" style="border-color: rgba(var(--sa-base-rgb), .2);">
                        <p class="flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: var(--sa-base)" aria-hidden="true">
                                <rect width="20" height="14" x="2" y="5" rx="2"/>
                                <path d="M2 10h20"/>
                            </svg>
                            رنگ صفحه
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2.5">
                            <label id="sa-c-page-sw" class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                   style="background: {{ $pvPage }}" title="باز کردن انتخابگر رنگ">
                                <input type="color" id="sa-c-page" value="{{ $pvPage }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ صفحه">
                            </label>
                            <input type="text" id="sa-c-page-hex" dir="ltr" maxlength="7" value="{{ $customPage }}"
                                   class="ap-input w-24 text-center text-[11px] font-bold" placeholder="پیش‌فرض"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                   aria-label="کد HEX رنگ صفحه">
                            <span class="flex shrink-0 items-center gap-1" title="نسخهٔ خودکار رنگ صفحه در تم شب">
                                <span class="text-[9px] font-bold text-ink-3 dark:text-night-ink-2">شب:</span>
                                <span id="sa-c-page-dark-chip" class="inline-block size-6 rounded-lg border border-black/10" style="background: {{ $pvPageD }}" aria-hidden="true"></span>
                            </span>
                            <button type="button" id="sa-c-page-reset"
                                    class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                    style="border-color: rgba(var(--sa-base-rgb), .3); color: var(--sa-base)"
                                    title="بازگشت رنگ صفحه به پیش‌فرض پنل">
                                پیش‌فرض
                            </button>
                        </div>
                    </div>
                </div>

                <p class="mt-3.5 text-[10px] leading-5 text-ink-3 dark:text-night-ink-2">
                    با هر تغییری، خط‌ها، هاورها و متن‌های متوسط/کم‌رنگ هم به‌صورت خودکار هم‌خانواده می‌شوند و
                    نسخهٔ تم‌شبِ هر رنگ ساخته می‌شود؛ سایدبار یا صفحه‌ای که خودش تیره است در تم شب دست‌نخورده
                    می‌ماند. خالی‌بودن هر رنگ یعنی همان پیش‌فرض پنل.
                </p>
            </div>

            {{-- ===== میزان گرمی و سردیِ پالت ===== --}}
            <div id="sa-warmth-box" class="rounded-2xl border-2 border-dashed p-4 sm:p-5"
                 style="border-color: rgba(var(--sa-base-rgb), .35);">
                <h4 class="text-[13px] font-bold">میزان گرمی و سردیِ پالت</h4>
                <p class="mt-1 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                    اسلایدر کلِ بستهٔ رنگی (رنگ اصلی، سایدبار، صفحه، خط‌ها و متن‌ها) را با هم و هماهنگ می‌چرخاند؛
                    به سمت چپ گرم‌تر (سرخ/نارنجی و پررنگ‌تر) و به سمت راست سردتر (آبی و کم‌رنگ‌تر).
                </p>

                <div dir="ltr" class="mt-4 flex items-center gap-3">
                    <span class="flex shrink-0 items-center gap-1.5 text-[11px] font-bold" style="color: var(--sa-base)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                            <circle cx="12" cy="12" r="4"/>
                            <path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/>
                            <path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/>
                            <path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                        </svg>
                        <span>گرم</span>
                    </span>
                    <input type="range" id="sa-warmth" min="-100" max="100" step="1" value="{{ $warmth }}"
                           class="h-1.5 w-full flex-1 cursor-pointer"
                           aria-label="میزان گرمی و سردی رنگ">
                    <span class="flex shrink-0 items-center gap-1.5 text-[11px] font-bold" style="color: var(--sa-deep)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                            <path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/>
                        </svg>
                        <span>سرد</span>
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-surface-2/70 px-3 py-2.5 dark:bg-night-3/60">
                    <span class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">رنگ پایهٔ پالت:</span>
                    <span class="flex items-center gap-2">
                        <button type="button" id="sa-warmth-reset"
                                class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                style="border-color: rgba(var(--sa-base-rgb), .3); color: var(--sa-base)">
                            پیش‌فرض
                        </button>
                        <code id="sa-default-hex" dir="ltr" class="text-[11px] font-bold">{{ $rawBase }}</code>
                        <span id="sa-default-dot" class="inline-block size-4 rounded-full border border-black/10" style="background: {{ $rawBase }}" aria-hidden="true"></span>
                    </span>
                </div>
            </div>

            {{-- پانویس: بازگشت به پیش‌فرض + ذخیره --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-dashed pt-4" style="border-color: rgba(var(--sa-base-rgb), .2);">
                <p class="flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-3.5 shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
                    </svg>
                    تغییرها همان لحظه روی کل پنل دیده می‌شوند؛ با «ذخیره» برای همه دائمی می‌شوند.
                </p>
                <div class="flex items-center gap-2.5">
                    <button type="button" id="sa-reset"
                            class="rounded-full border-2 bg-transparent px-4 py-2 text-[12px] font-bold transition-colors"
                            style="border-color: rgba(var(--sa-base-rgb), .4); color: var(--sa-base);">
                        بازگشت به پیش‌فرض
                    </button>
                    <button type="button" id="sa-save"
                            class="inline-flex items-center gap-1.5 rounded-full px-5 py-2 text-[12px] font-bold text-white shadow-md transition-all"
                            style="background: var(--sa-grad);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                        ذخیره پوسته
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    /* ============================================================
       فاز ۲۱۲ — پیش‌نمایش زندهٔ تنظیمات ظاهری پنل (سمت مرورگر)
       • ریاضی رنگ قرینهٔ دقیق App\Support\PaletteEngine (PHP) است؛
         منبع حقیقتِ ذخیره همان PHP است.
       • هر تغییر (پالت/گرمی/شخصی‌سازی) همان لحظه:
           ۱) متغیرهای کل پنل را در <style id="ap-live-vars"> می‌نویسد
              (کارت‌ها، خط‌ها، متن‌ها، سایدبار، تم شب — همه با هم)
           ۲) پیش‌نمایش دوحالته و رنگ‌های تأکیدی صفحه را به‌روز می‌کند
       • پیش از ناوبری SPA، استایل زنده حذف می‌شود تا صفحهٔ بعدی
         همان پوستهٔ «ذخیره‌شده» را نشان دهد.
       ============================================================ */
    const saRoot = document.getElementById('saniar-appearance');
    const saPalettes = @json($palettes);
    const saIndex = {};
    saPalettes.forEach((p) => { saIndex[p.key] = p; });

    const saState = {
        palette: @json($palette),
        warmth: {{ $warmth }},
        custom: @json($custom),
        customFont: @json($customFont),
        customSidebar: @json($customSidebar),
        customPage: @json($customPage),
    };

    /* ثابت‌ها و پیش‌فرض‌ها — قرینهٔ دقیق PaletteEngine */
    const saR = {
        defaultPalette: @json(\App\Support\PaletteEngine::DEFAULT_PALETTE),
        defaultCustom: @json(\App\Support\PaletteEngine::DEFAULT_CUSTOM),
        defaults: {
            font: @json(\App\Support\PaletteEngine::DEFAULT_FONT_COLOR),
            fontDark: @json(\App\Support\PaletteEngine::DEFAULT_FONT_COLOR_DARK),
            side: @json(\App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR),
            sideDark: @json(\App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR_DARK),
            page: @json(\App\Support\PaletteEngine::DEFAULT_PAGE_COLOR),
            pageDark: @json(\App\Support\PaletteEngine::DEFAULT_PAGE_COLOR_DARK),
        },
        surface3S: @json(\App\Support\PaletteEngine::SURFACE3_S), surface3L: @json(\App\Support\PaletteEngine::SURFACE3_L),
        lineS: @json(\App\Support\PaletteEngine::LINE_S), lineL: @json(\App\Support\PaletteEngine::LINE_L),
        inkS: @json(\App\Support\PaletteEngine::INK_S), inkL: @json(\App\Support\PaletteEngine::INK_L),
        ink2S: @json(\App\Support\PaletteEngine::INK2_S), ink2L: @json(\App\Support\PaletteEngine::INK2_L),
        ink3S: @json(\App\Support\PaletteEngine::INK3_S), ink3L: @json(\App\Support\PaletteEngine::INK3_L),
        nightS: @json(\App\Support\PaletteEngine::NIGHT_S), nightL: @json(\App\Support\PaletteEngine::NIGHT_L),
        night1S: @json(\App\Support\PaletteEngine::NIGHT1_S), night1L: @json(\App\Support\PaletteEngine::NIGHT1_L),
        night2S: @json(\App\Support\PaletteEngine::NIGHT2_S), night2L: @json(\App\Support\PaletteEngine::NIGHT2_L),
        night3S: @json(\App\Support\PaletteEngine::NIGHT3_S), night3L: @json(\App\Support\PaletteEngine::NIGHT3_L),
        nightlineS: @json(\App\Support\PaletteEngine::NIGHTLINE_S), nightlineL: @json(\App\Support\PaletteEngine::NIGHTLINE_L),
        nightinkS: @json(\App\Support\PaletteEngine::NIGHTINK_S), nightinkL: @json(\App\Support\PaletteEngine::NIGHTINK_L),
        nightink2S: @json(\App\Support\PaletteEngine::NIGHTINK2_S), nightink2L: @json(\App\Support\PaletteEngine::NIGHTINK2_L),
        sideNightS: @json(\App\Support\PaletteEngine::SIDE_NIGHT_S), sideNightL: @json(\App\Support\PaletteEngine::SIDE_NIGHT_L),
        sideLightYiq: @json(\App\Support\PaletteEngine::SIDE_LIGHT_YIQ),
        pageLightYiq: @json(\App\Support\PaletteEngine::PAGE_LIGHT_YIQ),
        fontDarkYiq: @json(\App\Support\PaletteEngine::FONT_DARK_YIQ),
        sideDarkS: @json(\App\Support\PaletteEngine::SIDE_DARK_S), sideDarkL: @json(\App\Support\PaletteEngine::SIDE_DARK_L),
        pageDarkS: @json(\App\Support\PaletteEngine::PAGE_DARK_S), pageDarkL: @json(\App\Support\PaletteEngine::PAGE_DARK_L),
        fontDarkS: @json(\App\Support\PaletteEngine::FONT_DARK_S), fontDarkL: @json(\App\Support\PaletteEngine::FONT_DARK_L),
    };

    /* ============ ریاضی رنگ (قرینهٔ PHP) ============ */
    function normHex(v, fallback) {
        v = String(v || '').trim().replace(/^#/, '').toUpperCase();
        return /^[0-9A-F]{6}$/.test(v) ? '#' + v : fallback;
    }
    function normHexOrNull(v) {
        v = String(v || '').trim().replace(/^#/, '').toUpperCase();
        return /^[0-9A-F]{6}$/.test(v) ? '#' + v : null;
    }
    function hexToRgb(hex) {
        hex = String(hex).replace('#', '');
        return [parseInt(hex.substr(0, 2), 16), parseInt(hex.substr(2, 2), 16), parseInt(hex.substr(4, 2), 16)];
    }
    function rgbToHex(r, g, b) {
        const c = (v) => ('0' + Math.max(0, Math.min(255, Math.round(v))).toString(16)).slice(-2);
        return ('#' + c(r) + c(g) + c(b)).toUpperCase();
    }
    function hexToHsl(hex) {
        let [r, g, b] = hexToRgb(hex).map((v) => v / 255);
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        let h = 0, s = 0;
        const l = (max + min) / 2;
        if (max !== min) {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            if (max === r) h = ((g - b) / d) + (g < b ? 6 : 0);
            else if (max === g) h = ((b - r) / d) + 2;
            else h = ((r - g) / d) + 4;
            h *= 60;
        }
        return [h, s * 100, l * 100];
    }
    function hue2rgb(p, q, t) {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
    }
    function hslToRgb(h, s, l) {
        h = ((h % 360) + 360) % 360 / 360;
        s = Math.max(0, Math.min(100, s)) / 100;
        l = Math.max(0, Math.min(100, l)) / 100;
        if (s === 0) return [l * 255, l * 255, l * 255];
        const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
        const p = 2 * l - q;
        return [hue2rgb(p, q, h + 1 / 3), hue2rgb(p, q, h), hue2rgb(p, q, h - 1 / 3)].map((v) => v * 255);
    }
    function hslToHex(h, s, l) {
        const [r, g, b] = hslToRgb(h, s, l);
        return rgbToHex(r, g, b);
    }
    const tinted = (h, s, l) => hslToHex(((h % 360) + 360) % 360, s, l);
    function yiq(hex) {
        const [r, g, b] = hexToRgb(hex);
        return (r * 299 + g * 587 + b * 114) / 1000;
    }
    function contrastInk(hex) {
        return yiq(hex) >= 150 ? saR.defaults.font : '#F2F5FA';
    }
    function wrapHue(h) { return ((h % 360) + 360) % 360; }
    function shortestDelta(from, to) {
        let d = (to - from) % 360;
        if (d > 180) d -= 360;
        if (d < -180) d += 360;
        return d;
    }
    function rotateHue(hex, rot) {
        if (!rot) return String(hex).toUpperCase();
        const [h, s, l] = hexToHsl(hex);
        return hslToHex(wrapHue(h + rot), s, l);
    }
    function warmthRotation(hex, warmth) {
        if (!warmth) return [String(hex).toUpperCase(), 0];
        const [h, s, l] = hexToHsl(hex);
        const t = Math.abs(warmth) / 100;
        const target = warmth < 0 ? 28 : 215;
        const rot = shortestDelta(h, target) * t * 0.55;
        const s2 = warmth < 0 ? Math.min(100, s + 8 * t) : Math.max(8, s - 6 * t);
        return [hslToHex(wrapHue(h + rot), s2, l), rot];
    }
    /* طیف حول رنگ اصلی — قرینهٔ PaletteEngine::scale() */
    function scaleFromBase(baseHex) {
        const [h, s, l] = hexToHsl(baseHex);
        const anchor = Math.max(38, Math.min(60, l));
        const L = {
            50: Math.min(98, anchor + 49), 100: Math.min(95, anchor + 43), 200: Math.min(91, anchor + 36),
            300: Math.min(85, anchor + 27), 400: Math.min(76, anchor + 19), 500: Math.min(68, anchor + 10),
            600: anchor, 700: Math.max(30, anchor - 8), 800: Math.max(24, anchor - 15),
            900: Math.max(18, anchor - 22), 950: Math.max(12, anchor - 29),
        };
        const S = {
            50: s * 0.40, 100: s * 0.48, 200: s * 0.62, 300: s * 0.78, 400: s * 0.92,
            500: Math.min(100, s * 1.02), 600: s, 700: Math.min(100, s * 1.04),
            800: s, 900: s * 0.94, 950: s * 0.88,
        };
        const out = {};
        Object.keys(L).forEach((k) => { out[k] = hslToHex(wrapHue(h), Math.max(12, Math.min(100, S[k])), Math.max(8, Math.min(98, L[k]))); });
        return out;
    }
    /* توکن‌های کامل از هیؤ مؤثر — قرینهٔ PaletteEngine::deriveTokens() */
    function deriveTokens(hue, pageLight) {
        const ink = tinted(hue, saR.inkS, saR.inkL);
        const [ih, is, il] = hexToHsl(ink);
        return {
            light: {
                surface: '#FFFFFF',
                surface_2: pageLight,
                surface_3: tinted(hue, saR.surface3S, saR.surface3L),
                line: tinted(hue, saR.lineS, saR.lineL),
                ink: ink,
                ink_2: tinted(ih, Math.min(60, is * 0.6), Math.max(20, Math.min(70, il * 2.4 + 8))),
                ink_3: tinted(ih, Math.min(55, is * 0.5), Math.max(30, Math.min(78, il * 3 + 14))),
            },
            dark: {
                night: tinted(hue, saR.nightS, saR.nightL),
                night_1: tinted(hue, saR.night1S, saR.night1L),
                night_2: tinted(hue, saR.night2S, saR.night2L),
                night_3: tinted(hue, saR.night3S, saR.night3L),
                night_line: tinted(hue, saR.nightlineS, saR.nightlineL),
                night_ink: tinted(hue, saR.nightinkS, saR.nightinkL),
                night_ink_2: tinted(hue, saR.nightink2S, saR.nightink2L),
            },
            side_night: tinted(hue, saR.sideNightS, saR.sideNightL),
        };
    }

    /* ============ resolve قرینهٔ PaletteEngine::resolve() ============ */
    function resolveSkin(key, warmth, customHex, extras) {
        const p = saIndex[key] || saIndex[saR.defaultPalette];
        const warm = Math.max(-100, Math.min(100, warmth | 0));
        const custom = normHex(customHex, saR.defaultCustom);
        const rawBase = p.custom ? custom : p.base;
        const [base, rotation] = warmthRotation(rawBase, warm);
        const hue = hexToHsl(base)[0];

        let scale;
        if (p.custom) {
            scale = scaleFromBase(base);
        } else {
            scale = {};
            Object.keys(p.scale).forEach((k) => { scale[k] = rotateHue(p.scale[k], rotation); });
        }

        let tokens, side, sideDark, font, fontDark, page, pageDark;
        if (p.custom) {
            const f = normHexOrNull(extras && extras.font_color);
            const s = normHexOrNull(extras && extras.sidebar_color);
            const pg = normHexOrNull(extras && extras.page_color);

            const sideDarkC = s ? (yiq(s) >= saR.sideLightYiq ? tinted(hue, saR.sideDarkS, saR.sideDarkL) : s) : null;
            const pageDarkC = pg ? (yiq(pg) >= saR.pageLightYiq ? tinted(hue, saR.pageDarkS, saR.pageDarkL) : pg) : null;
            const fontDarkC = f ? (yiq(f) < saR.fontDarkYiq ? tinted(hue, saR.fontDarkS, saR.fontDarkL) : f) : null;

            const ink = f || saR.defaults.font;
            const [ih, is, il] = hexToHsl(ink);

            tokens = {
                light: {
                    surface: '#FFFFFF',
                    surface_2: pg || saR.defaults.page,
                    surface_3: tinted(hue, saR.surface3S, saR.surface3L),
                    line: tinted(hue, saR.lineS, saR.lineL),
                    ink: ink,
                    ink_2: tinted(ih, Math.min(60, is * 0.6), Math.max(20, Math.min(70, il * 2.4 + 8))),
                    ink_3: tinted(ih, Math.min(55, is * 0.5), Math.max(30, Math.min(78, il * 3 + 14))),
                },
                dark: {
                    night: pageDarkC || tinted(hue, saR.nightS, saR.nightL),
                    night_1: tinted(hue, saR.night1S, saR.night1L),
                    night_2: tinted(hue, saR.night2S, saR.night2L),
                    night_3: tinted(hue, saR.night3S, saR.night3L),
                    night_line: tinted(hue, saR.nightlineS, saR.nightlineL),
                    night_ink: fontDarkC || tinted(hue, saR.nightinkS, saR.nightinkL),
                    night_ink_2: tinted(hue, saR.nightink2S, saR.nightink2L),
                },
            };
            side = s; sideDark = sideDarkC; page = pg; pageDark = pageDarkC; font = f; fontDark = fontDarkC;
        } else if (p.fixed_tokens) {
            if (!rotation) {
                tokens = { light: Object.assign({}, p.fixed_tokens.light), dark: Object.assign({}, p.fixed_tokens.dark) };
            } else {
                const rot = (map) => { const out = {}; Object.keys(map).forEach((k) => { out[k] = rotateHue(map[k], rotation); }); return out; };
                tokens = { light: rot(p.fixed_tokens.light), dark: rot(p.fixed_tokens.dark) };
            }
            side = rotateHue(p.side_light, rotation);
            sideDark = rotateHue(p.fixed_tokens.side_night, rotation);
            page = tokens.light.surface_2; pageDark = tokens.dark.night;
        } else {
            tokens = deriveTokens(hue, rotateHue(p.page_light, rotation));
            side = rotateHue(p.side_light, rotation);
            sideDark = tokens.side_night;
            page = tokens.light.surface_2; pageDark = tokens.dark.night;
        }

        return {
            key: key in saIndex ? key : saR.defaultPalette,
            warmth: warm,
            base: scale[600],
            scale: scale,
            tokens: tokens,
            side: side,
            sideDark: sideDark,
            sideInk: side ? contrastInk(side) : contrastInk(saR.defaults.side),
            sideInkDark: sideDark ? contrastInk(sideDark) : contrastInk(saR.defaults.sideDark),
            gradient: [scale[400], scale[600]],
            angle: 135,
        };
    }

    /* ============ اعمال زنده روی کل پنل ============ */
    function applySkin(skin) {
        const T = skin.tokens;
        let css = ':root{';
        Object.keys(skin.scale).forEach((k) => { css += '--color-brand-' + k + ':' + skin.scale[k] + ';'; });
        Object.keys(T.light).forEach((k) => { css += '--color-' + k.replace(/_/g, '-') + ':' + T.light[k] + ';'; });
        css += '--ck-color-base-action:var(--color-brand-600);--ck-color-base-action-hover:var(--color-brand-700);'
            + '--ck-color-base-focus:var(--color-brand-600);--ck-color-base-active:var(--color-brand-600);'
            + '--ck-color-base-action-muted:var(--color-brand-100);--ck-focus-border-color:var(--color-brand-600);';
        css += '}';
        css += 'html.dark{';
        Object.keys(T.dark).forEach((k) => { css += '--color-' + k.replace(/_/g, '-') + ':' + T.dark[k] + ';'; });
        css += '}';
        css += 'body{background-color:var(--color-surface-2);}html.dark body{background-color:var(--color-night);}';

        if (skin.side) {
            css += '#ap-sidebar{'
                + '--ap-side-bg:' + skin.side + ';'
                + '--color-surface:' + skin.side + ';'
                + '--ap-side-ink:' + skin.sideInk + ';'
                + '--color-surface-2:color-mix(in srgb,' + skin.side + ' 93%,var(--ap-side-ink));'
                + '--color-surface-3:color-mix(in srgb,' + skin.side + ' 87%,var(--ap-side-ink));'
                + '--color-line:color-mix(in srgb,' + skin.side + ' 82%,var(--ap-side-ink));'
                + '--color-ink:var(--ap-side-ink);'
                + '--color-ink-2:color-mix(in srgb,var(--ap-side-ink) 76%,' + skin.side + ');'
                + '--color-ink-3:color-mix(in srgb,var(--ap-side-ink) 56%,' + skin.side + ');'
                + '}';
        }
        if (skin.sideDark) {
            css += 'html.dark #ap-sidebar{'
                + '--ap-side-bg-dark:' + skin.sideDark + ';'
                + '--color-night-2:' + skin.sideDark + ';'
                + '--ap-side-ink-d:' + skin.sideInkDark + ';'
                + '--color-night-3:color-mix(in srgb,' + skin.sideDark + ' 86%,var(--ap-side-ink-d));'
                + '--color-night-line:color-mix(in srgb,' + skin.sideDark + ' 80%,var(--ap-side-ink-d));'
                + '--color-night-ink:var(--ap-side-ink-d);'
                + '--color-night-ink-2:color-mix(in srgb,var(--ap-side-ink-d) 72%,' + skin.sideDark + ');'
                + '}';
        }

        let el = document.getElementById('ap-live-vars');
        if (!el) {
            el = document.createElement('style');
            el.id = 'ap-live-vars';
            document.head.appendChild(el);
        }
        el.textContent = css;
    }

    /* ============ پیش‌نمایش دوحالته + رنگ‌های تأکیدی صفحه ============ */
    function updatePreview(skin) {
        const L = skin.tokens.light, D = skin.tokens.dark;
        const set = (id, css) => { const el = document.getElementById(id); if (el) el.style.cssText = css; };
        set('sa-pv-light',
            '--pv-side:' + (skin.side || saR.defaults.side) + ';'
            + '--pv-side-ink:' + (skin.side ? skin.sideInk : contrastInk(saR.defaults.side)) + ';'
            + '--pv-page:' + L.surface_2 + ';--pv-card:' + L.surface + ';--pv-line:' + L.line + ';'
            + '--pv-ink:' + L.ink + ';--pv-ink2:' + L.ink_2 + ';--pv-ink3:' + L.ink_3 + ';'
            + '--pv-primary:' + skin.base + ';');
        set('sa-pv-dark',
            '--pv-side:' + (skin.sideDark || saR.defaults.sideDark) + ';'
            + '--pv-side-ink:' + (skin.sideDark ? skin.sideInkDark : contrastInk(saR.defaults.sideDark)) + ';'
            + '--pv-page:' + D.night + ';--pv-card:' + D.night_2 + ';--pv-line:' + D.night_line + ';'
            + '--pv-ink:' + D.night_ink + ';--pv-ink2:' + D.night_ink_2 + ';--pv-ink3:' + D.night_ink_2 + ';'
            + '--pv-primary:' + skin.base + ';');
    }

    function updateAccents(skin) {
        if (!saRoot) return;
        saRoot.style.setProperty('--sa-base', skin.base);
        saRoot.style.setProperty('--sa-base-rgb', hexToRgb(skin.base).join(', '));
        saRoot.style.setProperty('--sa-deep', skin.scale[700]);
        saRoot.style.setProperty('--sa-grad', 'linear-gradient(135deg, ' + skin.gradient.join(', ') + ')');
    }

    function updateCustomUi(skin) {
        const sw = (id, color) => { const el = document.getElementById(id); if (el) el.style.background = color; };
        sw('sa-c-primary-sw', saState.custom);
        sw('sa-c-font-sw', saState.customFont || saR.defaults.font);
        sw('sa-c-side-sw', saState.customSidebar || saR.defaults.side);
        sw('sa-c-page-sw', saState.customPage || saR.defaults.page);

        const chip = (id, color) => { const el = document.getElementById(id); if (el) el.style.background = color; };
        chip('sa-c-font-dark-chip', skin.tokens.dark.night_ink);
        chip('sa-c-side-dark-chip', skin.sideDark || saR.defaults.sideDark);
        chip('sa-c-page-dark-chip', skin.tokens.dark.night);
    }

    function updateSelection() {
        document.querySelectorAll('[data-palette-card]').forEach((btn) => {
            const sel = btn.getAttribute('data-palette-card') === saState.palette;
            btn.classList.toggle('sa-selected', sel);
            const check = btn.querySelector('.sa-check');
            if (check) check.style.display = sel ? 'flex' : 'none';
            btn.setAttribute('aria-pressed', sel ? 'true' : 'false');
        });
        const customBox = document.getElementById('sa-custom-box');
        if (customBox) customBox.classList.toggle('hidden', saState.palette !== 'custom');
    }

    /* ============ وضعیت + رفرش کامل ============ */
    function currentExtras() {
        return {
            font_color: saState.customFont,
            sidebar_color: saState.customSidebar,
            page_color: saState.customPage,
        };
    }
    function currentSkin() {
        return resolveSkin(saState.palette, saState.warmth, saState.custom, currentExtras());
    }
    function refreshAll() {
        const skin = currentSkin();
        applySkin(skin);
        updatePreview(skin);
        updateAccents(skin);
        updateCustomUi(skin);
        updateSelection();
    }

    /* ============ رویدادها ============ */
    document.querySelectorAll('[data-palette-card]').forEach((btn) => {
        btn.addEventListener('click', () => {
            saState.palette = btn.getAttribute('data-palette-card');
            refreshAll();
        });
    });

    const warmthInput = document.getElementById('sa-warmth');
    if (warmthInput) {
        let warmthRaf = null;
        warmthInput.addEventListener('input', () => {
            if (warmthRaf) return;
            warmthRaf = requestAnimationFrame(() => {
                warmthRaf = null;
                saState.warmth = parseInt(warmthInput.value || '0', 10);
                refreshAll();
            });
        });
    }
    const warmthReset = document.getElementById('sa-warmth-reset');
    if (warmthReset) {
        warmthReset.addEventListener('click', () => {
            saState.warmth = 0;
            if (warmthInput) warmthInput.value = '0';
            refreshAll();
        });
    }

    /* شخصی‌سازی: انتخابگر رنگ + ورودی HEX + دکمهٔ پیش‌فرض برای هر نقش */
    function bindCustom(colorId, hexId, swId, resetId, prop) {
        const colorEl = document.getElementById(colorId);
        const hexEl = document.getElementById(hexId);
        const swEl = document.getElementById(swId);
        const resetEl = document.getElementById(resetId);

        const applyValue = (value) => {
            saState[prop] = value;
            if (hexEl && document.activeElement !== hexEl) hexEl.value = value;
            refreshAll();
        };
        if (colorEl) colorEl.addEventListener('input', () => applyValue(colorEl.value));
        if (hexEl) {
            hexEl.addEventListener('change', () => {
                const v = normHexOrNull(hexEl.value);
                if (v) applyValue(v);
                else hexEl.value = saState[prop] || '';
            });
        }
        if (resetEl) resetEl.addEventListener('click', () => applyValue(''));
    }
    bindCustom('sa-c-primary', 'sa-c-primary-hex', 'sa-c-primary-sw', 'sa-c-primary-reset', 'custom');
    bindCustom('sa-c-font', 'sa-c-font-hex', 'sa-c-font-sw', 'sa-c-font-reset', 'customFont');
    bindCustom('sa-c-side', 'sa-c-side-hex', 'sa-c-side-sw', 'sa-c-side-reset', 'customSidebar');
    bindCustom('sa-c-page', 'sa-c-page-hex', 'sa-c-page-sw', 'sa-c-page-reset', 'customPage');

    /* ذخیره — یک رفت‌وبرگشت برای همهٔ مقادیر، بعد ذخیرهٔ سمت سرور */
    const saveBtn = document.getElementById('sa-save');
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            $wire.call('syncState', saState.palette, saState.warmth, saState.custom, saState.customFont, saState.customSidebar, saState.customPage);
            $wire.call('saveSkin');
        });
    }

    /* بازگشت به پیش‌فرض */
    const resetBtn = document.getElementById('sa-reset');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => { $wire.call('resetSkin'); });
    }

    $wire.on('appearance-reset', (data) => {
        saState.palette = data.palette || saR.defaultPalette;
        saState.warmth = parseInt(data.warmth || '0', 10);
        saState.custom = data.custom || saR.defaultCustom;
        saState.customFont = data.customFont || '';
        saState.customSidebar = data.customSidebar || '';
        saState.customPage = data.customPage || '';
        if (warmthInput) warmthInput.value = String(saState.warmth);
        ['sa-c-primary-hex', 'sa-c-primary'].forEach((id) => { const el = document.getElementById(id); if (el) el.value = saState.custom; });
        ['sa-c-font-hex', 'sa-c-font'].forEach((id) => { const el = document.getElementById(id); if (el) el.value = saR.defaults.font; });
        ['sa-c-side-hex', 'sa-c-side'].forEach((id) => { const el = document.getElementById(id); if (el) el.value = saR.defaults.side; });
        ['sa-c-page-hex', 'sa-c-page'].forEach((id) => { const el = document.getElementById(id); if (el) el.value = saR.defaults.page; });
        refreshAll();
    });

    /* پیش از ناوبری SPA، استایل زنده حذف می‌شود تا صفحهٔ بعدی همان
       پوستهٔ «ذخیره‌شده» را نشان دهد (ذخیره‌نشده‌ها موقتی‌اند). */
    document.addEventListener('livewire:navigating', () => {
        const el = document.getElementById('ap-live-vars');
        if (el) el.remove();
    });
</script>
@endscript
