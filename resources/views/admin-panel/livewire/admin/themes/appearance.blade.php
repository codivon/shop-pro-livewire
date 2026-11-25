{{-- ============================================================
     پنل مدیریت — تنظیمات ظاهری (فاز ۲۱۱ — طراحی تازه)
     هر پالتِ آماده یک «بستهٔ رنگیِ هماهنگِ چندنقشی» است:
       • رنگ اصلی → دکمه‌ها، لینک‌ها، آیکن‌ها و حالت‌های فعال
       • رنگ سایدبار و رنگ صفحه → هرکدام تِینِ لطیفِ هم‌خانواده،
         جدا برای حالت روشن و تم شب (نه همه‌چیز یک‌رنگ!)
       • متن‌ها پیش‌فرضِ خوانای پنل می‌مانند ⇒ در تیره و روشن
         هیچ متن یا دکمه‌ای نامعلوم نمی‌شود.
     بخش‌های صفحه:
       • پیش‌نمایش زندهٔ دوحالته (روشن | تم شب) — با متغیرهای CSS
       • پالت‌های آماده — هر کارت یک مینیاتورِ دوحالته از پنل
       • شخصی‌سازی — رنگ دکمه‌ها/متن/سایدبار/صفحه؛ نسخهٔ تم‌شبِ
         هر رنگ خودکار و هماهنگ ساخته می‌شود
       • میزان گرمی و سردیِ پالت — کل بسته با هم می‌چرخد
     حالت پیش‌فرضِ روشن/تیرهٔ پنل از جای دیگر تنظیم می‌شود
     (کوکی کاربر / دکمهٔ ماه) و اینجا فقط پیش‌نمایشِ سازگاری است.
     اعمال: پس از ذخیره، layout پنل متغیرهای --color-brand-* و
     متغیرهای سایدبار/صفحه/متن را بازنویسی می‌کند و همین صفحه هم
     بلافاصله متغیرها را inline روی <html> ست می‌کند.
     ============================================================ --}}
@php
    /** @var array $preview */
    /** @var array $palettes */

    $base = $preview['base'];
    $scale = $preview['scale'];
    $gradientCss = 'linear-gradient('.$preview['gradient_angle'].'deg, '.implode(', ', $preview['gradient']).')';
    $rawBase = ($palette === 'custom') ? $custom : (collect($palettes)->firstWhere('key', $palette)['base'] ?? $base);

    /* رنگ اصلیِ پیش‌نمایش تم‌شب هم همان پلهٔ ۶۰۰ است — پنل واقعی در دکمه‌ها/آیکن‌های
       اصلی در تم شب هم از brand-600 استفاده می‌کند (بدون تعویض پله). */

    /* نقش‌های فعالِ فعلی — مقادیر اولیهٔ پیش‌نمایش زندهٔ دوحالته */
    $pvFont      = $preview['font_color'] ?: \App\Support\PaletteEngine::DEFAULT_FONT_COLOR;
    $pvFontDark  = $preview['font_color_dark'] ?: \App\Support\PaletteEngine::DEFAULT_FONT_COLOR_DARK;
    $pvSide      = $preview['sidebar_color'] ?: \App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR;
    $pvSideDark  = $preview['sidebar_color_dark'] ?: \App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR_DARK;
    $pvPage      = $preview['page_color'] ?: \App\Support\PaletteEngine::DEFAULT_PAGE_COLOR;
    $pvPageDark  = $preview['page_color_dark'] ?: \App\Support\PaletteEngine::DEFAULT_PAGE_COLOR_DARK;
    $pvSideInk   = \App\Support\PaletteEngine::contrastInk($pvSide);
    $pvSideInkDark = \App\Support\PaletteEngine::contrastInk($pvSideDark);

    /* کارت‌های پالت — رنگ‌های مینیاتورِ دوحالته (بدون گرمی/سردی) */
    $cards = [];
    foreach ($palettes as $p) {
        $r = \App\Support\PaletteEngine::resolve($p['key'], 0, \App\Support\PaletteEngine::DEFAULT_CUSTOM, 'light');
        $sideC = $r['sidebar_color'] ?: \App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR;
        $sideDarkC = $r['sidebar_color_dark'] ?: \App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR_DARK;
        $cards[$p['key']] = [
            'primary'      => $r['base'],
            'primaryDark'  => $r['base'],
            'side'         => $sideC,
            'sideDark'     => $sideDarkC,
            'page'         => $r['page_color'] ?: \App\Support\PaletteEngine::DEFAULT_PAGE_COLOR,
            'pageDark'     => $r['page_color_dark'] ?: \App\Support\PaletteEngine::DEFAULT_PAGE_COLOR_DARK,
            'sideInk'      => \App\Support\PaletteEngine::contrastInk($sideC),
            'sideInkDark'  => \App\Support\PaletteEngine::contrastInk($sideDarkC),
            'ink'          => \App\Support\PaletteEngine::DEFAULT_FONT_COLOR,
            'inkDark'      => \App\Support\PaletteEngine::DEFAULT_FONT_COLOR_DARK,
            'card'         => '#FFFFFF',
            'cardDark'     => '#161D2B',
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
            <p class="hidden text-[11px] leading-6 text-white/80 sm:block sm:max-w-80">
                هر پالت، دکمه‌ها، سایدبار و صفحه را با رنگ‌های هماهنگِ خودش — در روشن و تم شب — یک‌جا تغییر می‌دهد.
            </p>
        </div>
    </div>

    <div id="saniar-appearance" class="ap-card overflow-hidden p-0">

        {{-- سربرگ کارت --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span id="sa-head-icon" class="flex size-11 shrink-0 items-center justify-center rounded-2xl text-white shadow-md" style="background: {{ $gradientCss }}">
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
                    <p class="mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">اعمال روی تمام صفحات پنل — روشن و تم شب</p>
                </div>
            </div>

            <span id="sa-saved-pill" class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-[11px] font-bold"
                  style="border-color: {{ $base }}33; background: {{ $base }}14; color: {{ $scale['700'] }}">
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
            <div id="sa-preview-box" class="rounded-2xl border-2 border-dashed p-4 sm:p-5" style="border-color: {{ $base }}55;">
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
                        <div id="sa-pv-light" class="h-48 overflow-hidden rounded-2xl border"
                             style="--pv-side: {{ $pvSide }}; --pv-side-ink: {{ $pvSideInk }}; --pv-page: {{ $pvPage }}; --pv-card: #FFFFFF; --pv-ink: {{ $pvFont }}; --pv-primary: {{ $base }};">
                            <div class="flex h-full">
                                {{-- سایدبار (RTL — سمت راست) --}}
                                <div class="flex w-[31%] shrink-0 flex-col gap-1.5 p-2.5" style="background: var(--pv-side)">
                                    <div class="mb-0.5 flex items-center gap-1.5">
                                        <span class="flex size-5 shrink-0 items-center justify-center rounded-lg text-[8px] font-black text-white" style="background: var(--pv-primary)">P</span>
                                        <span class="truncate text-[9.5px] font-bold" style="color: var(--pv-side-ink)">پنل مدیریت</span>
                                    </div>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px] font-bold" style="background: color-mix(in srgb, var(--pv-primary) 16%, var(--pv-side)); color: var(--pv-side-ink)">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: var(--pv-primary)"></span>
                                        داشبورد
                                    </span>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px]" style="color: color-mix(in srgb, var(--pv-side-ink) 68%, var(--pv-side))">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-side-ink) 40%, var(--pv-side))"></span>
                                        سفارش‌ها
                                    </span>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px]" style="color: color-mix(in srgb, var(--pv-side-ink) 68%, var(--pv-side))">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-side-ink) 40%, var(--pv-side))"></span>
                                        محصولات
                                    </span>
                                    <span class="mt-auto flex items-center gap-1.5 border-t px-1 pt-2 text-[9px]" style="border-color: color-mix(in srgb, var(--pv-side-ink) 14%, transparent); color: var(--pv-side-ink)">
                                        <span class="size-4 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-primary) 45%, var(--pv-side))"></span>
                                        حساب مدیر
                                    </span>
                                </div>
                                {{-- بدنه --}}
                                <div class="flex min-w-0 flex-1 flex-col" style="background: var(--pv-page)">
                                    <div class="flex items-center justify-between border-b px-3 py-2" style="border-color: color-mix(in srgb, var(--pv-ink) 8%, transparent)">
                                        <span class="text-[9.5px] font-bold" style="color: var(--pv-ink)">داشبورد</span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="flex size-5 items-center justify-center rounded-full text-white" style="background: var(--pv-primary)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-2.5" aria-hidden="true">
                                                    <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                                                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                                </svg>
                                            </span>
                                            <span class="size-5 rounded-full" style="background: color-mix(in srgb, var(--pv-primary) 35%, var(--pv-page))"></span>
                                        </div>
                                    </div>
                                    <div class="grid flex-1 grid-cols-2 gap-2 p-2.5">
                                        <div class="flex flex-col rounded-xl border p-2" style="background: var(--pv-card); border-color: color-mix(in srgb, var(--pv-ink) 8%, transparent)">
                                            <span class="text-[8px]" style="color: color-mix(in srgb, var(--pv-ink) 62%, transparent)">فروش امروز</span>
                                            <span class="mt-auto text-[11px] font-black" style="color: var(--pv-ink)">۲٬۴۵۰٬۰۰۰</span>
                                            <span class="mt-1 inline-flex w-fit items-center gap-1 rounded-full px-1.5 py-0.5 text-[7.5px] font-bold" style="background: color-mix(in srgb, var(--pv-primary) 14%, var(--pv-card)); color: var(--pv-primary)">
                                                <span class="size-1 rounded-full" style="background: var(--pv-primary)"></span>
                                                ۱۲٪ رشد
                                            </span>
                                        </div>
                                        <div class="flex flex-col rounded-xl border p-2" style="background: var(--pv-card); border-color: color-mix(in srgb, var(--pv-ink) 8%, transparent)">
                                            <span class="text-[8px]" style="color: color-mix(in srgb, var(--pv-ink) 62%, transparent)">وضعیت سفارش</span>
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
                        <div id="sa-pv-dark" class="h-48 overflow-hidden rounded-2xl border"
                             style="--pv-side: {{ $pvSideDark }}; --pv-side-ink: {{ $pvSideInkDark }}; --pv-page: {{ $pvPageDark }}; --pv-card: #161D2B; --pv-ink: {{ $pvFontDark }}; --pv-primary: {{ $base }};">
                            <div class="flex h-full">
                                {{-- سایدبار (RTL — سمت راست) --}}
                                <div class="flex w-[31%] shrink-0 flex-col gap-1.5 p-2.5" style="background: var(--pv-side)">
                                    <div class="mb-0.5 flex items-center gap-1.5">
                                        <span class="flex size-5 shrink-0 items-center justify-center rounded-lg text-[8px] font-black text-white" style="background: var(--pv-primary)">P</span>
                                        <span class="truncate text-[9.5px] font-bold" style="color: var(--pv-side-ink)">پنل مدیریت</span>
                                    </div>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px] font-bold" style="background: color-mix(in srgb, var(--pv-primary) 22%, var(--pv-side)); color: var(--pv-side-ink)">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: var(--pv-primary)"></span>
                                        داشبورد
                                    </span>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px]" style="color: color-mix(in srgb, var(--pv-side-ink) 68%, var(--pv-side))">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-side-ink) 40%, var(--pv-side))"></span>
                                        سفارش‌ها
                                    </span>
                                    <span class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[9px]" style="color: color-mix(in srgb, var(--pv-side-ink) 68%, var(--pv-side))">
                                        <span class="size-1.5 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-side-ink) 40%, var(--pv-side))"></span>
                                        محصولات
                                    </span>
                                    <span class="mt-auto flex items-center gap-1.5 border-t px-1 pt-2 text-[9px]" style="border-color: color-mix(in srgb, var(--pv-side-ink) 14%, transparent); color: var(--pv-side-ink)">
                                        <span class="size-4 shrink-0 rounded-full" style="background: color-mix(in srgb, var(--pv-primary) 45%, var(--pv-side))"></span>
                                        حساب مدیر
                                    </span>
                                </div>
                                {{-- بدنه --}}
                                <div class="flex min-w-0 flex-1 flex-col" style="background: var(--pv-page)">
                                    <div class="flex items-center justify-between border-b px-3 py-2" style="border-color: color-mix(in srgb, var(--pv-ink) 8%, transparent)">
                                        <span class="text-[9.5px] font-bold" style="color: var(--pv-ink)">داشبورد</span>
                                        <div class="flex items-center gap-1.5">
                                            <span class="flex size-5 items-center justify-center rounded-full text-white" style="background: var(--pv-primary)">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-2.5" aria-hidden="true">
                                                    <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                                                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                                </svg>
                                            </span>
                                            <span class="size-5 rounded-full" style="background: color-mix(in srgb, var(--pv-primary) 35%, var(--pv-page))"></span>
                                        </div>
                                    </div>
                                    <div class="grid flex-1 grid-cols-2 gap-2 p-2.5">
                                        <div class="flex flex-col rounded-xl border p-2" style="background: var(--pv-card); border-color: color-mix(in srgb, var(--pv-ink) 8%, transparent)">
                                            <span class="text-[8px]" style="color: color-mix(in srgb, var(--pv-ink) 62%, transparent)">فروش امروز</span>
                                            <span class="mt-auto text-[11px] font-black" style="color: var(--pv-ink)">۲٬۴۵۰٬۰۰۰</span>
                                            <span class="mt-1 inline-flex w-fit items-center gap-1 rounded-full px-1.5 py-0.5 text-[7.5px] font-bold" style="background: color-mix(in srgb, var(--pv-primary) 20%, var(--pv-card)); color: var(--pv-primary)">
                                                <span class="size-1 rounded-full" style="background: var(--pv-primary)"></span>
                                                ۱۲٪ رشد
                                            </span>
                                        </div>
                                        <div class="flex flex-col rounded-xl border p-2" style="background: var(--pv-card); border-color: color-mix(in srgb, var(--pv-ink) 8%, transparent)">
                                            <span class="text-[8px]" style="color: color-mix(in srgb, var(--pv-ink) 62%, transparent)">وضعیت سفارش</span>
                                            <span class="mt-1.5 w-fit rounded-full px-1.5 py-0.5 text-[7.5px] font-bold text-white" style="background: var(--pv-primary)">فعال</span>
                                            <div class="mt-auto flex gap-1.5">
                                                <span class="rounded-lg px-2 py-1 text-[8px] font-bold text-white" style="background: var(--pv-primary)">ذخیره</span>
                                                <span class="rounded-lg border px-2 py-1 text-[8px] font-bold" style="border-color: color-mix(in srgb, var(--pv-primary) 55%, transparent); color: var(--pv-primary)">انصراف</span>
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
                    هر پالت یک بستهٔ رنگیِ هماهنگ است: رنگ اصلی برای دکمه‌ها و آیکن‌ها، تِینِ لطیف برای سایدبار و
                    عمیق‌تر برای صفحه — در روشن و تم شب، هرکدام با نسخهٔ مخصوص خودشان.
                </p>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($palettes as $p)
                        @php
                            $isSel = ($palette === $p['key']);
                            $c = $cards[$p['key']];
                        @endphp
                        <button type="button" data-palette-card="{{ $p['key'] }}" wire:key="sa-card-{{ $p['key'] }}"
                                class="relative rounded-2xl border-2 bg-surface p-3 text-center transition-all duration-150 hover:-translate-y-0.5 dark:bg-night-2 {{ $isSel ? '' : 'border-line hover:border-ink-3/40 dark:border-night-line' }}"
                                style="{{ $isSel ? 'border-color: '.$base.'; box-shadow: 0 0 0 3px '.$base.'26;' : '' }}"
                                :aria-pressed="{{ $isSel ? 'true' : 'false' }}">

                            {{-- مینیاتور دوحالته: روشن (بالا) + تم شب (پایین) --}}
                            <span class="mb-2.5 block overflow-hidden rounded-xl border border-black/[.06] dark:border-white/10" aria-hidden="true">
                                {{-- روشن --}}
                                <span class="flex h-9" style="background: {{ $c['page'] }}">
                                    <span class="w-[30%] shrink-0 p-1" style="background: {{ $c['side'] }}">
                                        <span class="flex items-center gap-1">
                                            <span class="size-2 rounded-[3px]" style="background: {{ $c['primary'] }}"></span>
                                            <span class="h-[3px] w-3 rounded-full" style="background: color-mix(in srgb, {{ $c['sideInk'] }} 72%, transparent)"></span>
                                        </span>
                                        <span class="mt-1 block h-[5px] w-full rounded-[3px]" style="background: color-mix(in srgb, {{ $c['primary'] }} 20%, {{ $c['side'] }})"></span>
                                        <span class="mt-1 block h-[3px] w-3/4 rounded-full" style="background: color-mix(in srgb, {{ $c['sideInk'] }} 26%, transparent)"></span>
                                    </span>
                                    <span class="flex min-w-0 flex-1 flex-col gap-1 p-1">
                                        <span class="flex items-center justify-between">
                                            <span class="h-[3px] w-7 rounded-full" style="background: color-mix(in srgb, {{ $c['ink'] }} 78%, transparent)"></span>
                                            <span class="size-2 rounded-full" style="background: {{ $c['primary'] }}"></span>
                                        </span>
                                        <span class="block flex-1 rounded-[4px] border" style="background: {{ $c['card'] }}; border-color: color-mix(in srgb, {{ $c['ink'] }} 8%, transparent)"></span>
                                        <span class="block h-[6px] w-9 rounded-[3px]" style="background: {{ $c['primary'] }}"></span>
                                    </span>
                                </span>
                                {{-- تم شب --}}
                                <span class="flex h-9 border-t" style="background: {{ $c['pageDark'] }}; border-color: rgba(0,0,0,.35)">
                                    <span class="w-[30%] shrink-0 p-1" style="background: {{ $c['sideDark'] }}">
                                        <span class="flex items-center gap-1">
                                            <span class="size-2 rounded-[3px]" style="background: {{ $c['primaryDark'] }}"></span>
                                            <span class="h-[3px] w-3 rounded-full" style="background: color-mix(in srgb, {{ $c['sideInkDark'] }} 72%, transparent)"></span>
                                        </span>
                                        <span class="mt-1 block h-[5px] w-full rounded-[3px]" style="background: color-mix(in srgb, {{ $c['primaryDark'] }} 26%, {{ $c['sideDark'] }})"></span>
                                        <span class="mt-1 block h-[3px] w-3/4 rounded-full" style="background: color-mix(in srgb, {{ $c['sideInkDark'] }} 26%, transparent)"></span>
                                    </span>
                                    <span class="flex min-w-0 flex-1 flex-col gap-1 p-1">
                                        <span class="flex items-center justify-between">
                                            <span class="h-[3px] w-7 rounded-full" style="background: color-mix(in srgb, {{ $c['inkDark'] }} 78%, transparent)"></span>
                                            <span class="size-2 rounded-full" style="background: {{ $c['primaryDark'] }}"></span>
                                        </span>
                                        <span class="block flex-1 rounded-[4px] border" style="background: {{ $c['cardDark'] }}; border-color: color-mix(in srgb, {{ $c['inkDark'] }} 8%, transparent)"></span>
                                        <span class="block h-[6px] w-9 rounded-[3px]" style="background: {{ $c['primaryDark'] }}"></span>
                                    </span>
                                </span>
                            </span>

                            <span class="block text-[12.5px] font-bold leading-5">{{ $p['title'] }}</span>
                            <span class="mt-0.5 block text-[10px] leading-4 text-ink-3 dark:text-night-ink-2">{{ $p['desc'] }}</span>

                            <span class="sa-check absolute left-2 top-2 hidden size-5 items-center justify-center rounded-full text-white"
                                  style="{{ $isSel ? 'display: flex; background: '.$base.';' : '' }}"
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
                 style="border-color: {{ $base }}55;">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h4 class="text-[13px] font-bold">شخصی‌سازی رنگ‌ها</h4>
                    <p class="text-[10px] font-semibold text-ink-3 dark:text-night-ink-2">نسخهٔ هماهنگِ هر رنگ در تم شب، خودکار ساخته می‌شود</p>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    {{-- دکمه‌ها و تأکیدها --}}
                    <div class="rounded-2xl border p-3.5" style="border-color: {{ $base }}33;">
                        <p class="flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: {{ $base }}" aria-hidden="true">
                                <rect width="14" height="8" x="5" y="2" rx="3"/>
                                <path d="m15 10 4 5H5l4-5"/>
                                <path d="M12 15v7"/>
                            </svg>
                            دکمه‌ها و تأکیدها
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2.5">
                            <label class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                   style="background: {{ $custom }}" title="باز کردن انتخابگر رنگ">
                                <input type="color" id="sa-c-primary" value="{{ $custom }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ دکمه‌ها و تأکیدها">
                            </label>
                            <input type="text" id="sa-c-primary-hex" dir="ltr" maxlength="7" value="{{ $custom }}"
                                   class="ap-input w-24 text-center text-[11px] font-bold" placeholder="#D62976"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                   aria-label="کد HEX رنگ دکمه‌ها">
                            <button type="button" id="sa-c-primary-reset"
                                    class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                    style="border-color: {{ $base }}4D; color: {{ $base }}"
                                    title="بازگشت رنگ دکمه‌ها به رنگ پیش‌فرض شخصی‌سازی">
                                پیش‌فرض
                            </button>
                        </div>
                    </div>

                    {{-- رنگ متن --}}
                    <div class="rounded-2xl border p-3.5" style="border-color: {{ $base }}33;">
                        <p class="flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: {{ $base }}" aria-hidden="true">
                                <path d="M4 7V5h16v2"/>
                                <path d="M12 5v14"/>
                                <path d="M9 19h6"/>
                            </svg>
                            رنگ متن
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2.5">
                            <label class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                   style="background: {{ $pvFont }}" title="باز کردن انتخابگر رنگ">
                                <input type="color" id="sa-c-font" value="{{ $pvFont }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ متن">
                            </label>
                            <input type="text" id="sa-c-font-hex" dir="ltr" maxlength="7" value="{{ $customFont }}"
                                   class="ap-input w-24 text-center text-[11px] font-bold" placeholder="پیش‌فرض"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                   aria-label="کد HEX رنگ متن">
                            <span class="flex shrink-0 items-center gap-1" title="نسخهٔ خودکار رنگ متن در تم شب">
                                <span class="text-[9px] font-bold text-ink-3 dark:text-night-ink-2">شب:</span>
                                <span id="sa-c-font-dark-chip" class="inline-block size-6 rounded-lg border border-black/10" style="background: {{ $pvFontDark }}" aria-hidden="true"></span>
                            </span>
                            <button type="button" id="sa-c-font-reset"
                                    class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                    style="border-color: {{ $base }}4D; color: {{ $base }}"
                                    title="بازگشت رنگ متن به پیش‌فرض پنل">
                                پیش‌فرض
                            </button>
                        </div>
                    </div>

                    {{-- رنگ سایدبار --}}
                    <div class="rounded-2xl border p-3.5" style="border-color: {{ $base }}33;">
                        <p class="flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: {{ $base }}" aria-hidden="true">
                                <rect width="18" height="18" x="3" y="3" rx="2"/>
                                <path d="M3 9h18"/><path d="M9 21V9"/>
                            </svg>
                            رنگ سایدبار
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2.5">
                            <label class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                   style="background: {{ $pvSide }}" title="باز کردن انتخابگر رنگ">
                                <input type="color" id="sa-c-side" value="{{ $pvSide }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ سایدبار">
                            </label>
                            <input type="text" id="sa-c-side-hex" dir="ltr" maxlength="7" value="{{ $customSidebar }}"
                                   class="ap-input w-24 text-center text-[11px] font-bold" placeholder="پیش‌فرض"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                   aria-label="کد HEX رنگ سایدبار">
                            <span class="flex shrink-0 items-center gap-1" title="نسخهٔ خودکار رنگ سایدبار در تم شب">
                                <span class="text-[9px] font-bold text-ink-3 dark:text-night-ink-2">شب:</span>
                                <span id="sa-c-side-dark-chip" class="inline-block size-6 rounded-lg border border-black/10" style="background: {{ $pvSideDark }}" aria-hidden="true"></span>
                            </span>
                            <button type="button" id="sa-c-side-reset"
                                    class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                    style="border-color: {{ $base }}4D; color: {{ $base }}"
                                    title="بازگشت رنگ سایدبار به پیش‌فرض پنل">
                                پیش‌فرض
                            </button>
                        </div>
                    </div>

                    {{-- رنگ صفحه --}}
                    <div class="rounded-2xl border p-3.5" style="border-color: {{ $base }}33;">
                        <p class="flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: {{ $base }}" aria-hidden="true">
                                <rect width="20" height="14" x="2" y="5" rx="2"/>
                                <path d="M2 10h20"/>
                            </svg>
                            رنگ صفحه
                        </p>
                        <div class="mt-3 flex flex-wrap items-center gap-2.5">
                            <label class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                   style="background: {{ $pvPage }}" title="باز کردن انتخابگر رنگ">
                                <input type="color" id="sa-c-page" value="{{ $pvPage }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ صفحه">
                            </label>
                            <input type="text" id="sa-c-page-hex" dir="ltr" maxlength="7" value="{{ $customPage }}"
                                   class="ap-input w-24 text-center text-[11px] font-bold" placeholder="پیش‌فرض"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                   aria-label="کد HEX رنگ صفحه">
                            <span class="flex shrink-0 items-center gap-1" title="نسخهٔ خودکار رنگ صفحه در تم شب">
                                <span class="text-[9px] font-bold text-ink-3 dark:text-night-ink-2">شب:</span>
                                <span id="sa-c-page-dark-chip" class="inline-block size-6 rounded-lg border border-black/10" style="background: {{ $pvPageDark }}" aria-hidden="true"></span>
                            </span>
                            <button type="button" id="sa-c-page-reset"
                                    class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                    style="border-color: {{ $base }}4D; color: {{ $base }}"
                                    title="بازگشت رنگ صفحه به پیش‌فرض پنل">
                                پیش‌فرض
                            </button>
                        </div>
                    </div>
                </div>

                <p class="mt-3.5 text-[10px] leading-5 text-ink-3 dark:text-night-ink-2">
                    رنگِ سایدبار یا صفحه‌ای که خودش تیره است، در تم شب دست‌نخورده می‌ماند؛ رنگ‌های روشن
                    به تیرهٔ هم‌خانوادهٔ همان تم می‌روند و متنِ تیره در شب به روشنِ هم‌خانواده — تا هیچ‌وقت
                    متن یا دکمه‌ای نامعلوم نشود. خالی‌بودن هر رنگ یعنی همان پیش‌فرض پنل.
                </p>
            </div>

            {{-- ===== میزان گرمی و سردیِ پالت ===== --}}
            <div id="sa-warmth-box" class="rounded-2xl border-2 border-dashed p-4 sm:p-5"
                 style="border-color: {{ $base }}55;">
                <h4 class="text-[13px] font-bold">میزان گرمی و سردیِ پالت</h4>
                <p class="mt-1 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                    اسلایدر کلِ بستهٔ رنگی (رنگ اصلی، سایدبار و صفحه) را با هم تغییر می‌دهد؛
                    به سمت چپ گرم‌تر (سرخ/نارنجی و پررنگ‌تر) و به سمت راست سردتر (آبی و کم‌رنگ‌تر).
                </p>

                <div dir="ltr" class="mt-4 flex items-center gap-3">
                    <span class="flex shrink-0 items-center gap-1.5 text-[11px] font-bold" style="color: {{ $base }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                            <circle cx="12" cy="12" r="4"/>
                            <path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/>
                            <path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/>
                            <path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                        </svg>
                        <span>گرم</span>
                    </span>
                    <input type="range" id="sa-warmth" min="-100" max="100" step="1" value="{{ $warmth }}"
                           class="h-1.5 w-full flex-1 cursor-pointer" style="accent-color: {{ $base }}"
                           aria-label="میزان گرمی و سردی رنگ">
                    <span class="flex shrink-0 items-center gap-1.5 text-[11px] font-bold" style="color: {{ $scale['700'] }}">
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
                                style="border-color: {{ $base }}4D; color: {{ $base }}">
                            پیش‌فرض
                        </button>
                        <code id="sa-default-hex" dir="ltr" class="text-[11px] font-bold">{{ $rawBase }}</code>
                        <span id="sa-default-dot" class="inline-block size-4 rounded-full border border-black/10" style="background: {{ $rawBase }}" aria-hidden="true"></span>
                    </span>
                </div>
            </div>

            {{-- پانویس: بازگشت به پیش‌فرض + ذخیره --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-dashed pt-4" style="border-color: {{ $base }}33;">
                <p class="flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-3.5 shrink-0" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
                    </svg>
                    بلافاصله پس از ذخیره، همهٔ صفحات پنل رنگ‌های جدید را می‌گیرند.
                </p>
                <div class="flex items-center gap-2.5">
                    <button type="button" id="sa-reset"
                            class="rounded-full border-2 bg-transparent px-4 py-2 text-[12px] font-bold transition-colors"
                            style="border-color: {{ $base }}66; color: {{ $base }};">
                        بازگشت به پیش‌فرض
                    </button>
                    <button type="button" id="sa-save"
                            class="inline-flex items-center gap-1.5 rounded-full px-5 py-2 text-[12px] font-bold text-white shadow-md transition-all"
                            style="background: {{ $gradientCss }};">
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
       فاز ۲۱۱ — پیش‌نمایش زندهٔ تنظیمات ظاهری پنل (سمت مرورگر)
       • ریاضی رنگ قرینهٔ App\Support\PaletteEngine (PHP) است؛
         منبع حقیقتِ ذخیره همان PHP است.
       • نقش‌ها: هر پالتِ آماده سایدبار/صفحهٔ هم‌خانواده دارد و با
         گرمی/سردی همه با هم می‌چرخند؛ در شخصی‌سازی، نسخهٔ تم‌شبِ
         هر رنگ با همان قواعد PHP مشتق می‌شود.
       • پس از ذخیره/بازگشت، متغیرهای --color-brand-* و متغیرهای
         سایدبار/صفحه/متن همان لحظه روی <html> و #ap-sidebar
         اعمال می‌شوند تا کل پنل بدون رفرش رنگ بگیرد.
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

    /* پیش‌فرض‌های پنل (قرینهٔ ثابت‌های PaletteEngine) */
    const saDefaults = {
        font: @json(\App\Support\PaletteEngine::DEFAULT_FONT_COLOR),
        fontDark: @json(\App\Support\PaletteEngine::DEFAULT_FONT_COLOR_DARK),
        side: @json(\App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR),
        sideDark: @json(\App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR_DARK),
        page: @json(\App\Support\PaletteEngine::DEFAULT_PAGE_COLOR),
        pageDark: @json(\App\Support\PaletteEngine::DEFAULT_PAGE_COLOR_DARK),
    };

    /* آستانه‌های مشتق‌سازی — قرینهٔ دقیق PHP */
    const saThresholds = {
        sideLightYiq: @json(\App\Support\PaletteEngine::SIDE_LIGHT_YIQ),
        pageLightYiq: @json(\App\Support\PaletteEngine::PAGE_LIGHT_YIQ),
        fontDarkYiq: @json(\App\Support\PaletteEngine::FONT_DARK_YIQ),
        sideDarkS: @json(\App\Support\PaletteEngine::SIDE_DARK_S),
        sideDarkL: @json(\App\Support\PaletteEngine::SIDE_DARK_L),
        pageDarkS: @json(\App\Support\PaletteEngine::PAGE_DARK_S),
        pageDarkL: @json(\App\Support\PaletteEngine::PAGE_DARK_L),
        fontDarkS: @json(\App\Support\PaletteEngine::FONT_DARK_S),
        fontDarkL: @json(\App\Support\PaletteEngine::FONT_DARK_L),
    };

    /* کنتراست YIQ روی رنگ پس‌زمینه — قرینهٔ PaletteEngine::contrastInk (PHP) */
    function saContrastInk(hex) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        const yiq = (r * 299 + g * 587 + b * 114) / 1000;
        return yiq >= 150 ? saDefaults.font : '#F2F5FA';
    }

    function saYiq(hex) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        return (r * 299 + g * 587 + b * 114) / 1000;
    }

    /* ---------- ریاضی رنگ (قرینهٔ PHP — طیف با پلهٔ ۶۰۰ = رنگ اصلی) ---------- */
    const saClamp = (v, min, max) => Math.min(max, Math.max(min, v));
    const saWrapHue = (h) => ((h % 360) + 360) % 360;

    function saShortestHueDelta(from, to) {
        let d = (to - from) % 360;
        if (d > 180) d -= 360;
        if (d < -180) d += 360;
        return d;
    }

    function saHexToHsl(hex) {
        const h = hex.replace('#', '');
        const r = parseInt(h.slice(0, 2), 16) / 255;
        const g = parseInt(h.slice(2, 4), 16) / 255;
        const b = parseInt(h.slice(4, 6), 16) / 255;
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        const l = (max + min) / 2;
        let hue = 0;
        let s = 0;
        if (max !== min) {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            if (max === r) hue = ((g - b) / d) + (g < b ? 6 : 0);
            else if (max === g) hue = ((b - r) / d) + 2;
            else hue = ((r - g) / d) + 4;
            hue *= 60;
        }
        return [hue, s * 100, l * 100];
    }

    function saHueToRgb(p, q, t) {
        if (t < 0) t += 1;
        if (t > 1) t -= 1;
        if (t < 1 / 6) return p + (q - p) * 6 * t;
        if (t < 1 / 2) return q;
        if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
        return p;
    }

    function saHslToHex(h, s, l) {
        h = saWrapHue(h) / 360;
        s = saClamp(s, 0, 100) / 100;
        l = saClamp(l, 0, 100) / 100;
        let r, g, b;
        if (s === 0) {
            r = g = b = l;
        } else {
            const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            const p = 2 * l - q;
            r = saHueToRgb(p, q, h + 1 / 3);
            g = saHueToRgb(p, q, h);
            b = saHueToRgb(p, q, h - 1 / 3);
        }
        const to = (v) => Math.round(saClamp(v, 0, 255)).toString(16).padStart(2, '0').toUpperCase();
        return '#' + to(r * 255) + to(g * 255) + to(b * 255);
    }

    function saWarmthRotation(hex, warmth) {
        if (!warmth) return [hex.toUpperCase(), 0];
        const [h, s, l] = saHexToHsl(hex);
        const t = Math.abs(warmth) / 100;
        const target = warmth < 0 ? 28 : 215;
        const rot = saShortestHueDelta(h, target) * t * 0.55;
        const s2 = warmth < 0 ? Math.min(100, s + 8 * t) : Math.max(8, s - 6 * t);
        return [saHslToHex(saWrapHue(h + rot), s2, l), rot];
    }

    function saRotateHue(hex, rot) {
        if (!rot) return hex.toUpperCase();
        const [h, s, l] = saHexToHsl(hex);
        return saHslToHex(saWrapHue(h + rot), s, l);
    }

    function saTinted(hue, s, l) {
        return saHslToHex(saWrapHue(hue), s, l);
    }

    function saGenerateScale(baseHex) {
        const [h, s, l] = saHexToHsl(baseHex);
        const anchor = saClamp(l, 38, 60);
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
        Object.keys(L).forEach((k) => {
            out[k] = saHslToHex(h, saClamp(S[k], 12, 100), saClamp(L[k], 8, 98));
        });
        return out;
    }

    /* ---------- محاسبهٔ کامل وضعیت (قرینهٔ PaletteEngine::resolve) ---------- */
    function saCompute() {
        const def = saIndex[saState.palette] || saIndex['emerald'];
        const rawBase = saState.palette === 'custom' ? saState.custom : def.base;
        const [shifted, rot] = saWarmthRotation(rawBase, saState.warmth);
        const scale = (!saState.warmth && def.fixed_scale) ? { ...def.fixed_scale } : saGenerateScale(shifted);
        const stops = (def.gradient || [scale[400], scale[600]]).map((c) => saRotateHue(c, rot));

        let side = null, sideDark = null, page = null, pageDark = null, font = null, fontDark = null;

        if (saState.palette === 'custom') {
            const hue = saHexToHsl(shifted)[0];
            font = saState.customFont || null;
            side = saState.customSidebar || null;
            page = saState.customPage || null;

            if (side) {
                sideDark = saYiq(side) >= saThresholds.sideLightYiq
                    ? saTinted(hue, saThresholds.sideDarkS, saThresholds.sideDarkL)
                    : side;
            }
            if (page) {
                pageDark = saYiq(page) >= saThresholds.pageLightYiq
                    ? saTinted(hue, saThresholds.pageDarkS, saThresholds.pageDarkL)
                    : page;
            }
            if (font) {
                fontDark = saYiq(font) < saThresholds.fontDarkYiq
                    ? saTinted(hue, saThresholds.fontDarkS, saThresholds.fontDarkL)
                    : font;
            }
        } else if (def.roles) {
            side = saRotateHue(def.roles.side_light, rot);
            sideDark = saRotateHue(def.roles.side_dark, rot);
            page = saRotateHue(def.roles.page_light, rot);
            pageDark = saRotateHue(def.roles.page_dark, rot);
        }

        return {
            base: scale[600],
            darkBase: scale[400],
            scale,
            stops,
            angle: def.gradient_angle || 135,
            rawBase: rawBase.toUpperCase(),
            side: side || saDefaults.side,
            sideDark: sideDark || saDefaults.sideDark,
            page: page || saDefaults.page,
            pageDark: pageDark || saDefaults.pageDark,
            font: font || saDefaults.font,
            fontDark: fontDark || saDefaults.fontDark,
        };
    }

    /* ---------- اعمال روی UI ---------- */
    let saLast = saCompute();

    function saApplyPreviewVars(pv) {
        const light = document.getElementById('sa-pv-light');
        const dark = document.getElementById('sa-pv-dark');
        if (light) {
            light.style.setProperty('--pv-side', pv.side);
            light.style.setProperty('--pv-side-ink', saContrastInk(pv.side));
            light.style.setProperty('--pv-page', pv.page);
            light.style.setProperty('--pv-ink', pv.font);
            light.style.setProperty('--pv-primary', pv.base);
        }
        if (dark) {
            dark.style.setProperty('--pv-side', pv.sideDark);
            dark.style.setProperty('--pv-side-ink', saContrastInk(pv.sideDark));
            dark.style.setProperty('--pv-page', pv.pageDark);
            dark.style.setProperty('--pv-ink', pv.fontDark);
            dark.style.setProperty('--pv-primary', pv.base);
        }
    }

    /* اعمال فوری پس از ذخیره/بازگشت (بدون رفرش).
       متغیرهای سطحِ سایدبار باید روی خودِ #ap-sidebar ست شوند نه روی <html>؛
       وگرنه کارت‌ها و سطوحِ کل پنل هم رنگ می‌گرفتند. */
    function saApplyShellVars(pv) {
        const s = document.documentElement.style;
        s.setProperty('--color-ink', pv.font);
        s.setProperty('--color-night-ink', pv.fontDark);
        s.setProperty('--ap-side-bg', pv.side);
        s.setProperty('--ap-side-bg-dark', pv.sideDark);
        s.setProperty('--ap-page-bg', pv.page);
        s.setProperty('--ap-page-bg-dark', pv.pageDark);

        const sideEl = document.getElementById('ap-sidebar');
        if (sideEl) {
            const ink = saContrastInk(pv.side);
            const inkD = saContrastInk(pv.sideDark);
            const st = sideEl.style;
            st.setProperty('--color-surface', pv.side);
            st.setProperty('--color-surface-2', 'color-mix(in srgb, ' + pv.side + ' 93%, ' + ink + ')');
            st.setProperty('--color-surface-3', 'color-mix(in srgb, ' + pv.side + ' 87%, ' + ink + ')');
            st.setProperty('--color-line', 'color-mix(in srgb, ' + pv.side + ' 82%, ' + ink + ')');
            st.setProperty('--color-ink', ink);
            st.setProperty('--color-ink-2', 'color-mix(in srgb, ' + ink + ' 76%, ' + pv.side + ')');
            st.setProperty('--color-ink-3', 'color-mix(in srgb, ' + ink + ' 56%, ' + pv.side + ')');
            st.setProperty('--color-night-2', pv.sideDark);
            st.setProperty('--color-night-3', 'color-mix(in srgb, ' + pv.sideDark + ' 86%, ' + inkD + ')');
            st.setProperty('--color-night-line', 'color-mix(in srgb, ' + pv.sideDark + ' 80%, ' + inkD + ')');
            st.setProperty('--color-night-ink', inkD);
            st.setProperty('--color-night-ink-2', 'color-mix(in srgb, ' + inkD + ' 72%, ' + pv.sideDark + ')');
        }
    }

    function saApplyAdminVars(scale) {
        if (!scale) return;
        const s = document.documentElement.style;
        Object.keys(scale).forEach((step) => s.setProperty('--color-brand-' + step, scale[step]));
    }

    function saApplyUI() {
        const pv = saCompute();
        saLast = pv;
        const base = pv.base;

        // کارت‌های پالت
        saRoot.querySelectorAll('[data-palette-card]').forEach((card) => {
            const sel = card.getAttribute('data-palette-card') === saState.palette;
            card.style.borderColor = sel ? base : '';
            card.style.boxShadow = sel ? '0 0 0 3px ' + base + '26' : '';
            const badge = card.querySelector('.sa-check');
            if (badge) {
                badge.style.display = sel ? 'flex' : 'none';
                badge.style.background = base;
            }
        });

        // اسلایدر گرمی/سردی
        const range = document.getElementById('sa-warmth');
        if (range) {
            range.value = String(saState.warmth);
            range.style.accentColor = base;
        }

        // ردیف «رنگ پایهٔ پالت»
        const defHexEl = document.getElementById('sa-default-hex');
        const defDotEl = document.getElementById('sa-default-dot');
        const defBtn = document.getElementById('sa-warmth-reset');
        if (defHexEl) defHexEl.textContent = pv.rawBase;
        if (defDotEl) defDotEl.style.background = pv.rawBase;
        if (defBtn) { defBtn.style.borderColor = base + '4D'; defBtn.style.color = base; }

        // جعبه‌های خط‌چین
        ['sa-preview-box', 'sa-custom-box', 'sa-warmth-box'].forEach((id) => {
            const box = document.getElementById(id);
            if (box) box.style.borderColor = base + '55';
        });

        // شخصی‌سازی — نمایش/مخفی + مقادیر + چیپ‌های نسخهٔ شب
        const customBox = document.getElementById('sa-custom-box');
        if (customBox) customBox.classList.toggle('hidden', saState.palette !== 'custom');

        const isCustom = saState.palette === 'custom';
        const cPrimary = document.getElementById('sa-c-primary');
        const cPrimaryHex = document.getElementById('sa-c-primary-hex');
        const cFont = document.getElementById('sa-c-font');
        const cFontHex = document.getElementById('sa-c-font-hex');
        const cSide = document.getElementById('sa-c-side');
        const cSideHex = document.getElementById('sa-c-side-hex');
        const cPage = document.getElementById('sa-c-page');
        const cPageHex = document.getElementById('sa-c-page-hex');
        if (isCustom) {
            if (cPrimary) cPrimary.value = saState.custom;
            if (cPrimaryHex) cPrimaryHex.value = saState.custom;
            if (cFont) cFont.value = saState.customFont || saDefaults.font;
            if (cFontHex) cFontHex.value = saState.customFont;
            if (cSide) cSide.value = saState.customSidebar || saDefaults.side;
            if (cSideHex) cSideHex.value = saState.customSidebar;
            if (cPage) cPage.value = saState.customPage || saDefaults.page;
            if (cPageHex) cPageHex.value = saState.customPage;
        }
        const fontChip = document.getElementById('sa-c-font-dark-chip');
        const sideChip = document.getElementById('sa-c-side-dark-chip');
        const pageChip = document.getElementById('sa-c-page-dark-chip');
        if (fontChip) fontChip.style.background = pv.fontDark;
        if (sideChip) sideChip.style.background = pv.sideDark;
        if (pageChip) pageChip.style.background = pv.pageDark;

        // پیش‌نمایش زندهٔ دوحالته
        saApplyPreviewVars(pv);

        // دکمهٔ ذخیره + آیکن سربرگ = گرادیان پالت
        const gradientCss = 'linear-gradient(' + pv.angle + 'deg, ' + pv.stops.join(', ') + ')';
        const saveBtn = document.getElementById('sa-save');
        const headIcon = document.getElementById('sa-head-icon');
        if (saveBtn) saveBtn.style.background = gradientCss;
        if (headIcon) headIcon.style.background = gradientCss;

        // دکمهٔ بازگشت به پیش‌فرض
        const resetBtn = document.getElementById('sa-reset');
        if (resetBtn) { resetBtn.style.borderColor = base + '66'; resetBtn.style.color = base; }
    }

    /* ---------- رویدادها ---------- */
    saRoot.querySelectorAll('[data-palette-card]').forEach((card) => {
        card.addEventListener('click', () => {
            saState.palette = card.getAttribute('data-palette-card');
            saApplyUI();
        });
    });

    const saRange = document.getElementById('sa-warmth');
    if (saRange) {
        saRange.addEventListener('input', () => {
            saState.warmth = parseInt(saRange.value, 10) || 0;
            saApplyUI();
        });
    }

    const saWarmthReset = document.getElementById('sa-warmth-reset');
    if (saWarmthReset) {
        saWarmthReset.addEventListener('click', () => {
            saState.warmth = 0;
            saApplyUI();
        });
    }

    /* بایند انتخابگرهای شخصی‌سازی (color + hex + reset) */
    const saBindPicker = (inputId, hexId, resetId, stateKey, emptyFallback) => {
        const input = document.getElementById(inputId);
        const hexInput = document.getElementById(hexId);
        const resetBtn = document.getElementById(resetId);

        if (input) {
            input.addEventListener('input', () => {
                saState[stateKey] = input.value.toUpperCase();
                if (hexInput) hexInput.value = saState[stateKey];
                saApplyUI();
            });
        }
        if (hexInput) {
            hexInput.addEventListener('input', () => {
                const v = hexInput.value.trim();
                if (/^#?[0-9a-fA-F]{6}$/.test(v)) {
                    saState[stateKey] = ('#' + v.replace('#', '')).toUpperCase();
                    if (input) input.value = saState[stateKey];
                    saApplyUI();
                }
            });
        }
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                saState[stateKey] = emptyFallback || '';
                saApplyUI();
            });
        }
    };

    saBindPicker('sa-c-primary', 'sa-c-primary-hex', 'sa-c-primary-reset', 'custom', '{{ \App\Support\PaletteEngine::DEFAULT_CUSTOM }}');
    saBindPicker('sa-c-font', 'sa-c-font-hex', 'sa-c-font-reset', 'customFont', '');
    saBindPicker('sa-c-side', 'sa-c-side-hex', 'sa-c-side-reset', 'customSidebar', '');
    saBindPicker('sa-c-page', 'sa-c-page-hex', 'sa-c-page-reset', 'customPage', '');

    // ذخیرهٔ پوسته — اول همگام‌سازی وضعیت، بعد فراخوانی اکشن + رنگ‌کردن فوری پنل
    const saSaveBtn = document.getElementById('sa-save');
    if (saSaveBtn) {
        saSaveBtn.addEventListener('click', async () => {
            saSaveBtn.disabled = true;
            saSaveBtn.style.opacity = '0.7';
            try {
                await $wire.syncState(
                    saState.palette, saState.warmth, saState.custom,
                    saState.customFont, saState.customSidebar, saState.customPage
                );
                await $wire.call('saveSkin');
                const pv = saCompute();
                saApplyAdminVars(pv.scale);
                saApplyShellVars(pv);
            } finally {
                saSaveBtn.disabled = false;
                saSaveBtn.style.opacity = '1';
            }
        });
    }

    // بازگشت به پیش‌فرض
    const saResetBtn = document.getElementById('sa-reset');
    if (saResetBtn) {
        saResetBtn.addEventListener('click', async () => {
            saResetBtn.disabled = true;
            try {
                await $wire.call('resetSkin');
            } finally {
                saResetBtn.disabled = false;
            }
        });
    }

    // پس از بازگشت به پیش‌فرض، وضعیت JS با سرور همگام و پنل زمردی می‌شود
    $wire.on('appearance-reset', (payload) => {
        const data = Array.isArray(payload) ? payload[0] : (payload || {});
        if (data.palette) saState.palette = data.palette;
        if (data.warmth !== undefined && data.warmth !== null) saState.warmth = parseInt(data.warmth, 10) || 0;
        if (data.custom) saState.custom = data.custom;
        if (data.customFont !== undefined && data.customFont !== null) saState.customFont = data.customFont;
        if (data.customSidebar !== undefined && data.customSidebar !== null) saState.customSidebar = data.customSidebar;
        if (data.customPage !== undefined && data.customPage !== null) saState.customPage = data.customPage;
        saApplyUI();
        const pv = saCompute();
        saApplyAdminVars(pv.scale);
        saApplyShellVars(pv);
    });
</script>
@endscript
