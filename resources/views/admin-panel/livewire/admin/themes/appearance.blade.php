{{-- ============================================================
     پنل مدیریت — تنظیمات ظاهری (فاز ۲۰۸ — فاز ۲۱۰)
     همان طراحیِ بخش «ظاهر و رنگ‌بندی» قالب سانیار، این‌بار برای خودِ پنل:
       • ۱۲ پالت آماده (گرید ۴ ستونه) + شخصی‌سازی
       • اسلایدر گرمی/سردی + ردیف «حالت پیش‌فرض پالت»
       • طیف تولیدشدهٔ ۵۰..۹۵۰ (کلیک = کپی کد رنگ)
       • پیش‌نمایش زنده (دکمهٔ اصلی/خطی، برچسب، سبد خرید)
       • فاز ۲۱۰ — رنگ فونت (جدا برای روشن و تم شب)
       • فاز ۲۱۰ — رنگ سایدبار و رنگ صفحه (جدا برای روشن و تم شب،
         هماهنگ با تم شب + کنتراست خودکار متن سایدبار)
       • حالت پیش‌فرض پنل (روشن/تیره)
       • بازگشت به پیش‌فرض + ذخیرهٔ پوسته

     اعمال: پس از ذخیره، layout پنل متغیرهای --color-brand-* (و متغیرهای
     فونت/سایدبار/صفحه) را بازنویسی می‌کند و همین صفحه هم بلافاصله
     متغیرها را inline روی <html> ست می‌کند تا بدون رفرش، کل پنل رنگ بگیرد.
     ============================================================ --}}
@php
    /** @var array $preview */
    /** @var array $palettes */
    $base = $preview['base'];
    $scale = $preview['scale'];
    $gradientCss = 'linear-gradient('.$preview['gradient_angle'].'deg, '.implode(', ', $preview['gradient']).')';
    $rawBase = ($palette === 'custom') ? $custom : (collect($palettes)->firstWhere('key', $palette)['base'] ?? $base);
    $steps = [950, 900, 800, 700, 600, 500, 400, 300, 200, 100, 50];

    /* فاز ۲۱۰ — رنگ فونت/سایدبار/صفحه: مقدار ذخیره‌شده یا پیش‌فرض پنل */
    $saFontColor      = $fontColor !== '' ? $fontColor : \App\Support\PaletteEngine::DEFAULT_FONT_COLOR;
    $saFontColorDark  = $fontColorDark !== '' ? $fontColorDark : \App\Support\PaletteEngine::DEFAULT_FONT_COLOR_DARK;
    $saSideColor      = $sidebarColor !== '' ? $sidebarColor : \App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR;
    $saSideColorDark  = $sidebarColorDark !== '' ? $sidebarColorDark : \App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR_DARK;
    $saPageColor      = $pageColor !== '' ? $pageColor : \App\Support\PaletteEngine::DEFAULT_PAGE_COLOR;
    $saPageColorDark  = $pageColorDark !== '' ? $pageColorDark : \App\Support\PaletteEngine::DEFAULT_PAGE_COLOR_DARK;
    $saSideInk        = \App\Support\PaletteEngine::contrastInk($saSideColor);
    $saSideInkDark    = \App\Support\PaletteEngine::contrastInk($saSideColorDark);
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
                        <span>رنگ‌بندی پنل مدیریت:</span>
                        <span class="rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-bold">{{ $savedLabel }}</span>
                    </p>
                </div>
            </div>
            <p class="hidden text-[11px] leading-6 text-white/80 sm:block sm:max-w-72">
                پوستهٔ انتخابی بلافاصله روی سایدبار، دکمه‌ها، فرم‌ها و همهٔ صفحات پنل اعمال می‌شود.
            </p>
        </div>
    </div>

    {{-- ===== کارت اصلی (همان طراحی بخش ظاهری قالب) ===== --}}
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
                    <h2 class="text-[15px] font-bold">ظاهر و رنگ‌بندی</h2>
                    <p class="mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">مخصوص پنل مدیریت — اعمال روی تمام صفحات پنل</p>
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

            <p class="text-[11.5px] leading-6 text-ink-3 dark:text-night-ink-2">
                یک پالت آماده انتخاب کنید یا با شخصی‌سازی، رنگ دلخواه خودتان را بسازید؛
                همه‌چیز — از دکمه‌ها تا جزئیات — در همهٔ صفحات پنل هماهنگ تغییر می‌کند.
            </p>

            {{-- پالت‌های آماده --}}
            <div>
                <h3 class="mb-3 text-[13px] font-bold">پالت‌های آماده</h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($palettes as $p)
                        @php $isSel = ($palette === $p['key']); @endphp
                        <button type="button" data-palette-card="{{ $p['key'] }}" wire:key="sa-card-{{ $p['key'] }}"
                                class="relative rounded-2xl border-2 bg-surface p-3 text-center transition-all duration-150 hover:-translate-y-0.5 dark:bg-night-2 {{ $isSel ? '' : 'border-line hover:border-ink-3/40 dark:border-night-line' }}"
                                style="{{ $isSel ? 'border-color: '.$base.'; box-shadow: 0 0 0 3px '.$base.'26;' : '' }}"
                                :aria-pressed="{{ $isSel ? 'true' : 'false' }}">
                            <span class="mb-2.5 block h-16 w-full rounded-xl" style="background: linear-gradient(135deg, {{ implode(', ', $p['swatch']) }})" aria-hidden="true"></span>
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

            {{-- میزان گرمی و سردیِ پالت --}}
            <div id="sa-warmth-box" class="rounded-2xl border-2 border-dashed p-4 sm:p-5"
                 style="border-color: {{ $base }}55;">
                <h4 class="text-[13px] font-bold">میزان گرمی و سردیِ پالت</h4>
                <p class="mt-1 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                    اسلایدر رنگِ پالت فعلی را تغییر می‌دهد؛ به سمت چپ گرم‌تر (سرخ/نارنجی و پررنگ‌تر)
                    و به سمت راست سردتر (آبی و کم‌رنگ‌تر) می‌شود.
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
                    <span class="text-[11px] font-semibold text-ink-3 dark:text-night-ink-2">حالت پیش‌فرض پالت:</span>
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

            {{-- شخصی‌سازی — رنگ اصلی --}}
            <div id="sa-custom-box" class="rounded-2xl border-2 border-dashed p-4 sm:p-5 @if($palette !== 'custom') hidden @endif"
                 style="border-color: {{ $base }}55;">
                <h4 class="text-[13px] font-bold">رنگ اصلی</h4>
                <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">کد HEX را وارد کنید یا انتخابگرِ رنگ را باز کنید.</p>
                <div class="mt-3 flex items-center gap-3">
                    <label class="relative flex size-11 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                           style="background: {{ $custom }}" title="باز کردن انتخابگر رنگ">
                        <input type="color" id="sa-color-input" value="{{ $custom }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ اصلی">
                    </label>
                    <input type="text" id="sa-custom-hex" dir="ltr" maxlength="7" value="{{ $custom }}"
                           class="ap-input w-40 text-center text-[12px] font-bold" placeholder="#D62976"
                           onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                           aria-label="کد HEX رنگ اصلی">
                </div>
            </div>

            {{-- ===== فاز ۲۱۰ — رنگ فونت ===== --}}
            <div id="sa-font-box" class="rounded-2xl border-2 border-dashed p-4 sm:p-5"
                 style="border-color: {{ $base }}55;">
                <h4 class="text-[13px] font-bold">رنگ فونت</h4>
                <p class="mt-1 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                    رنگ متنِ همهٔ صفحات پنل را جدا برای حالت روشن و تم شب تنظیم کنید؛
                    با دکمهٔ «پیش‌فرض» هر رنگ به مقدار اصلی پنل برمی‌گردد.
                </p>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    {{-- حالت روشن --}}
                    <div class="rounded-2xl border p-4" style="border-color: {{ $base }}33;">
                        <p class="mb-3 flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: {{ $base }}" aria-hidden="true">
                                <circle cx="12" cy="12" r="4"/>
                                <path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/>
                                <path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/>
                                <path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                            </svg>
                            حالت روشن
                        </p>
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="relative flex size-11 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                   style="background: {{ $saFontColor }}" title="باز کردن انتخابگر رنگ">
                                <input type="color" id="sa-font-color" value="{{ $saFontColor }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ فونت حالت روشن">
                            </label>
                            <input type="text" id="sa-font-hex" dir="ltr" maxlength="7" value="{{ $fontColor }}"
                                   class="ap-input w-32 text-center text-[12px] font-bold" placeholder="#1A2333"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                   aria-label="کد HEX رنگ فونت حالت روشن">
                            <button type="button" id="sa-font-reset"
                                    class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                    style="border-color: {{ $base }}4D; color: {{ $base }}"
                                    title="بازگشت رنگ فونت روشن به پیش‌فرض پنل">
                                پیش‌فرض
                            </button>
                        </div>
                        <div class="mt-3 rounded-xl bg-surface px-3 py-2.5 border border-line">
                            <p id="sa-font-sample" class="m-0 text-[12.5px] leading-6 font-semibold"
                               style="color: {{ $saFontColor }};">
                                نمونهٔ متن — لیست سفارش‌ها، محصولات و تنظیمات پنل.
                            </p>
                        </div>
                    </div>

                    {{-- تم شب --}}
                    <div class="rounded-2xl border p-4" style="border-color: {{ $base }}33;">
                        <p class="mb-3 flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: {{ $base }}" aria-hidden="true">
                                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                            </svg>
                            تم شب
                        </p>
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="relative flex size-11 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                   style="background: {{ $saFontColorDark }}" title="باز کردن انتخابگر رنگ">
                                <input type="color" id="sa-font-color-dark" value="{{ $saFontColorDark }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ فونت تم شب">
                            </label>
                            <input type="text" id="sa-font-hex-dark" dir="ltr" maxlength="7" value="{{ $fontColorDark }}"
                                   class="ap-input w-32 text-center text-[12px] font-bold" placeholder="#E5EAF2"
                                   onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                   aria-label="کد HEX رنگ فونت تم شب">
                            <button type="button" id="sa-font-reset-dark"
                                    class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                    style="border-color: {{ $base }}4D; color: {{ $base }}"
                                    title="بازگشت رنگ فونت تم شب به پیش‌فرض پنل">
                                پیش‌فرض
                            </button>
                        </div>
                        <div class="mt-3 rounded-xl px-3 py-2.5 border border-night-line" style="background: #0d1219;">
                            <p id="sa-font-sample-dark" class="m-0 text-[12.5px] leading-6 font-semibold"
                               style="color: {{ $saFontColorDark }};">
                                نمونهٔ متن در تم شب — سفارش‌ها، محصولات و تنظیمات پنل.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== فاز ۲۱۰ — رنگ سایدبار و صفحه ===== --}}
            <div id="sa-shell-box" class="rounded-2xl border-2 border-dashed p-4 sm:p-5"
                 style="border-color: {{ $base }}55;">
                <h4 class="text-[13px] font-bold">رنگ سایدبار و صفحه</h4>
                <p class="mt-1 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                    پس‌زمینهٔ منوی کنار (سایدبار) و پس‌زمینهٔ صفحات پنل را جدا برای حالت
                    روشن و تم شب انتخاب کنید؛ متن و هاورِ سایدبار خودکار با رنگ انتخابی
                    هماهنگ می‌شود و خوانایی در تم شب حفظ می‌ماند.
                </p>

                <div class="mt-4 space-y-4">
                    {{-- سایدبار --}}
                    <div class="rounded-2xl border p-4" style="border-color: {{ $base }}33;">
                        <p class="mb-3 flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: {{ $base }}" aria-hidden="true">
                                <rect width="18" height="18" x="3" y="3" rx="2"/>
                                <path d="M3 9h18"/><path d="M9 21V9"/>
                            </svg>
                            رنگ سایدبار
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2 w-12 shrink-0">روشن</span>
                                <label class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                       style="background: {{ $saSideColor }}" title="باز کردن انتخابگر رنگ">
                                    <input type="color" id="sa-side-color" value="{{ $saSideColor }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ سایدبار حالت روشن">
                                </label>
                                <input type="text" id="sa-side-hex" dir="ltr" maxlength="7" value="{{ $sidebarColor }}"
                                       class="ap-input w-28 text-center text-[12px] font-bold" placeholder="#FFFFFF"
                                       onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                       aria-label="کد HEX رنگ سایدبار روشن">
                                <button type="button" id="sa-side-reset"
                                        class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                        style="border-color: {{ $base }}4D; color: {{ $base }}"
                                        title="بازگشت رنگ سایدبار روشن به پیش‌فرض پنل">
                                    پیش‌فرض
                                </button>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2 w-12 shrink-0">تم شب</span>
                                <label class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                       style="background: {{ $saSideColorDark }}" title="باز کردن انتخابگر رنگ">
                                    <input type="color" id="sa-side-color-dark" value="{{ $saSideColorDark }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ سایدبار تم شب">
                                </label>
                                <input type="text" id="sa-side-hex-dark" dir="ltr" maxlength="7" value="{{ $sidebarColorDark }}"
                                       class="ap-input w-28 text-center text-[12px] font-bold" placeholder="#111827"
                                       onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                       aria-label="کد HEX رنگ سایدبار تم شب">
                                <button type="button" id="sa-side-reset-dark"
                                        class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                        style="border-color: {{ $base }}4D; color: {{ $base }}"
                                        title="بازگشت رنگ سایدبار تم شب به پیش‌فرض پنل">
                                    پیش‌فرض
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- صفحه --}}
                    <div class="rounded-2xl border p-4" style="border-color: {{ $base }}33;">
                        <p class="mb-3 flex items-center gap-1.5 text-[12px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" style="color: {{ $base }}" aria-hidden="true">
                                <rect width="20" height="14" x="2" y="5" rx="2"/>
                                <path d="M2 10h20"/>
                            </svg>
                            رنگ صفحه
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2 w-12 shrink-0">روشن</span>
                                <label class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                       style="background: {{ $saPageColor }}" title="باز کردن انتخابگر رنگ">
                                    <input type="color" id="sa-page-color" value="{{ $saPageColor }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ صفحه حالت روشن">
                                </label>
                                <input type="text" id="sa-page-hex" dir="ltr" maxlength="7" value="{{ $pageColor }}"
                                       class="ap-input w-28 text-center text-[12px] font-bold" placeholder="#F6F7F8"
                                       onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                       aria-label="کد HEX رنگ صفحه روشن">
                                <button type="button" id="sa-page-reset"
                                        class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                        style="border-color: {{ $base }}4D; color: {{ $base }}"
                                        title="بازگشت رنگ صفحه روشن به پیش‌فرض پنل">
                                    پیش‌فرض
                                </button>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-[11px] font-bold text-ink-3 dark:text-night-ink-2 w-12 shrink-0">تم شب</span>
                                <label class="relative flex size-10 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                                       style="background: {{ $saPageColorDark }}" title="باز کردن انتخابگر رنگ">
                                    <input type="color" id="sa-page-color-dark" value="{{ $saPageColorDark }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ صفحه تم شب">
                                </label>
                                <input type="text" id="sa-page-hex-dark" dir="ltr" maxlength="7" value="{{ $pageColorDark }}"
                                       class="ap-input w-28 text-center text-[12px] font-bold" placeholder="#0D1219"
                                       onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                                       aria-label="کد HEX رنگ صفحه تم شب">
                                <button type="button" id="sa-page-reset-dark"
                                        class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                        style="border-color: {{ $base }}4D; color: {{ $base }}"
                                        title="بازگشت رنگ صفحه تم شب به پیش‌فرض پنل">
                                    پیش‌فرض
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- پیش‌نمایش زندهٔ سایدبار/صفحه --}}
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <p class="mb-1.5 text-[10px] font-bold text-ink-3 dark:text-night-ink-2">پیش‌نمایش حالت روشن:</p>
                            <div class="flex h-24 overflow-hidden rounded-xl border" style="border-color: {{ $base }}33;">
                                <div id="sa-shell-preview-side" class="w-1/3 shrink-0 p-2"
                                     style="background: {{ $saSideColor }};">
                                    <div class="mb-2 flex items-center gap-1.5">
                                        <span class="flex size-4 items-center justify-center rounded-md text-[8px] text-white" style="background: {{ $base }}">P</span>
                                        <span id="sa-shell-preview-side-title" class="text-[8px] font-bold" style="color: {{ $saSideInk }};">پنل</span>
                                    </div>
                                    <div id="sa-shell-preview-link1" class="mb-1 rounded-md px-1.5 py-1 text-[8px] font-semibold" style="background: color-mix(in srgb, {{ $base }} 14%, {{ $saSideColor }}); color: {{ $saSideInk }};">منو فعال</div>
                                    <div id="sa-shell-preview-link2" class="rounded-md px-1.5 py-1 text-[8px]" style="color: color-mix(in srgb, {{ $saSideInk }} 72%, {{ $saSideColor }});">منو دیگر</div>
                                </div>
                                <div id="sa-shell-preview-page" class="flex-1 p-2"
                                     style="background: {{ $saPageColor }};">
                                    <div class="h-full rounded-lg border bg-white" style="border-color: rgba(0,0,0,.06);"></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="mb-1.5 text-[10px] font-bold text-ink-3 dark:text-night-ink-2">پیش‌نمایش تم شب:</p>
                            <div class="flex h-24 overflow-hidden rounded-xl border" style="border-color: {{ $base }}33;">
                                <div id="sa-shell-preview-side-dark" class="w-1/3 shrink-0 p-2"
                                     style="background: {{ $saSideColorDark }};">
                                    <div class="mb-2 flex items-center gap-1.5">
                                        <span class="flex size-4 items-center justify-center rounded-md text-[8px] text-white" style="background: {{ $base }}">P</span>
                                        <span id="sa-shell-preview-side-title-dark" class="text-[8px] font-bold" style="color: {{ $saSideInkDark }};">پنل</span>
                                    </div>
                                    <div id="sa-shell-preview-link1-dark" class="mb-1 rounded-md px-1.5 py-1 text-[8px] font-semibold" style="background: color-mix(in srgb, {{ $base }} 22%, {{ $saSideColorDark }}); color: {{ $saSideInkDark }};">منو فعال</div>
                                    <div id="sa-shell-preview-link2-dark" class="rounded-md px-1.5 py-1 text-[8px]" style="color: color-mix(in srgb, {{ $saSideInkDark }} 72%, {{ $saSideColorDark }});">منو دیگر</div>
                                </div>
                                <div id="sa-shell-preview-page-dark" class="flex-1 p-2"
                                     style="background: {{ $saPageColorDark }};">
                                    <div class="h-full rounded-lg border" style="background: #111827; border-color: rgba(255,255,255,.06);"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- طیف تولیدشده --}}
            <div class="rounded-2xl bg-surface-2/60 p-4 sm:p-5 dark:bg-night-3/50">
                <p class="mb-3 text-[12px] font-bold">
                    طیف تولیدشده (۵۰ تا ۹۵۰)
                    <span class="font-normal text-ink-3 dark:text-night-ink-2">— از این رنگ‌ها در کل پنل استفاده می‌شود:</span>
                </p>
                <div dir="ltr" class="grid grid-cols-6 gap-1.5 sm:grid-cols-11">
                    @foreach ($steps as $step)
                        <div class="sa-swatch cursor-pointer" wire:key="sa-sw-{{ $step }}" data-step="{{ $step }}"
                             title="{{ $scale[$step] }}" role="button" tabindex="0"
                             aria-label="کپی رنگ پلهٔ {{ $step }}">
                            <span class="sa-color-chip block h-10 rounded-lg border border-black/5 transition-transform hover:scale-105 sm:h-11"
                                  style="background: {{ $scale[$step] }}" aria-hidden="true"></span>
                            <span class="mt-1 block text-center text-[9px] font-bold text-ink-3 dark:text-night-ink-2">{{ $step }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- پیش‌نمایش زنده --}}
            <div class="rounded-2xl border-2 border-dashed p-4 sm:p-5" style="border-color: {{ $base }}55;">
                <p class="mb-3 text-[12px] font-bold">پیش‌نمایش زنده:</p>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" id="sa-preview-solid" class="rounded-xl px-4 py-2 text-[12px] font-bold text-white"
                            style="background: {{ $base }}; box-shadow: 0 5px 12px {{ $base }}40;">
                        دکمه اصلی
                    </button>
                    <button type="button" id="sa-preview-outline" class="rounded-xl border-2 bg-transparent px-4 py-2 text-[12px] font-bold"
                            style="border-color: {{ $base }}; color: {{ $base }};">
                        دکمه خطی
                    </button>
                    <span id="sa-preview-badge" class="rounded-full px-3 py-1 text-[11px] font-bold"
                          style="background: {{ $scale['50'] }}; color: {{ $scale['700'] }};">
                        برچسب
                    </span>
                    <button type="button" id="sa-preview-cart" class="flex size-9 items-center justify-center rounded-full text-white"
                            style="background: {{ $base }}; box-shadow: 0 4px 10px {{ $base }}40;" title="سبد خرید" aria-label="پیش‌نمایش دکمهٔ سبد خرید">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                            <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- حالت پیش‌فرض پنل --}}
            <div>
                <h4 class="mb-3 text-[13px] font-bold">حالت پیش‌فرض پنل</h4>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" data-mode="light"
                            class="sa-mode-card rounded-2xl border-2 p-4 text-center transition-all duration-150"
                            style="{{ $mode === 'light' ? 'border-color: '.$base.'; background: '.$base.'0F; color: '.$base.';' : '' }}"
                            :aria-pressed="{{ $mode === 'light' ? 'true' : 'false' }}">
                        <span class="flex items-center justify-center gap-1.5 text-[13px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                                <circle cx="12" cy="12" r="4"/>
                                <path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/>
                                <path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/>
                                <path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
                            </svg>
                            روشن
                        </span>
                    </button>
                    <button type="button" data-mode="dark"
                            class="sa-mode-card rounded-2xl border-2 p-4 text-center transition-all duration-150"
                            style="{{ $mode === 'dark' ? 'border-color: '.$base.'; background: '.$base.'0F; color: '.$base.';' : '' }}"
                            :aria-pressed="{{ $mode === 'dark' ? 'true' : 'false' }}">
                        <span class="flex items-center justify-center gap-1.5 text-[13px] font-bold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true">
                                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                            </svg>
                            تیره
                        </span>
                    </button>
                </div>
                <p class="mt-2 text-[10px] leading-5 text-ink-3 dark:text-night-ink-2">
                    این انتخاب فقط حالت اولیهٔ پنل را تعیین می‌کند؛ کاربرانِ پنل همچنان می‌توانند
                    از دکمهٔ ماه (بالا)، بین روشن و تیره جابه‌جا شوند.
                </p>
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
       فاز ۲۰۸ — پیش‌نمایش زندهٔ تنظیمات ظاهری پنل (سمت مرورگر)
       • ریاضی رنگ قرینهٔ App\Support\PaletteEngine (PHP) است؛
         منبع حقیقتِ ذخیره همان PHP است.
       • پس از ذخیره/بازگشت، متغیرهای --color-brand-* همان لحظه
         روی <html> اعمال می‌شوند تا کل پنل بدون رفرش رنگ بگیرد.
       ============================================================ */
    const saRoot = document.getElementById('saniar-appearance');
    const saPalettes = @json($palettes);
    const saIndex = {};
    saPalettes.forEach((p) => { saIndex[p.key] = p; });

    const saState = {
        palette: @json($palette),
        warmth: {{ $warmth }},
        custom: @json($custom),
        mode: @json($mode),
        fontColor: @json($fontColor),
        fontColorDark: @json($fontColorDark),
        sidebarColor: @json($sidebarColor),
        sidebarColorDark: @json($sidebarColorDark),
        pageColor: @json($pageColor),
        pageColorDark: @json($pageColorDark),
    };

    /* فاز ۲۱۰ — پیش‌فرض‌های پنل برای دکمه‌های «پیش‌فرض» */
    const saShellDefaults = {
        font: @json(\App\Support\PaletteEngine::DEFAULT_FONT_COLOR),
        fontDark: @json(\App\Support\PaletteEngine::DEFAULT_FONT_COLOR_DARK),
        side: @json(\App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR),
        sideDark: @json(\App\Support\PaletteEngine::DEFAULT_SIDEBAR_COLOR_DARK),
        page: @json(\App\Support\PaletteEngine::DEFAULT_PAGE_COLOR),
        pageDark: @json(\App\Support\PaletteEngine::DEFAULT_PAGE_COLOR_DARK),
    };

    /* کنتراست YIQ روی رنگ پس‌زمینه — قرینهٔ PaletteEngine::contrastInk (PHP) */
    function saContrastInk(hex) {
        const r = parseInt(hex.slice(1, 3), 16);
        const g = parseInt(hex.slice(3, 5), 16);
        const b = parseInt(hex.slice(5, 7), 16);
        const yiq = (r * 299 + g * 587 + b * 114) / 1000;
        return yiq >= 150 ? saShellDefaults.font : '#F2F5FA';
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

    function saComputePreview() {
        const def = saIndex[saState.palette] || saIndex['emerald'];
        const rawBase = saState.palette === 'custom' ? saState.custom : def.base;
        const [base, rot] = saWarmthRotation(rawBase, saState.warmth);
        const scale = (!saState.warmth && def.fixed_scale) ? { ...def.fixed_scale } : saGenerateScale(base);
        const stops = (def.gradient || [scale[400], scale[600]]).map((c) => saRotateHue(c, rot));
        return { base: scale[600], scale, stops, rawBase, def, angle: def.gradient_angle || 135 };
    }

    /* ---------- اعمال روی UI ---------- */
    let saLast = saComputePreview();

    function saApplyAdminVars(scale) {
        if (!scale) return;
        const s = document.documentElement.style;
        Object.keys(scale).forEach((step) => s.setProperty('--color-brand-' + step, scale[step]));
    }

    /* فاز ۲۱۰ — اعمال فوری فونت/سایدبار/صفحه پس از ذخیره یا بازگشت (بدون رفرش).
       مهم: متغیرهای سطحِ سایدبار باید روی خودِ #ap-sidebar ست شوند نه روی <html>؛
       وگرنه کارت‌ها و سطوح سفیدِ کل پنل هم رنگ می‌گرفتند. حالت «پیش‌فرض» هم با
       ست‌کردن مقدار پیش‌فرض پنل اعمال می‌شود تا بلوک استایل قبلیِ ذخیره‌شده بی‌اثر شود. */
    function saApplyShellVars() {
        const s = document.documentElement.style;
        const sideColor = saState.sidebarColor || saShellDefaults.side;
        const sideColorDark = saState.sidebarColorDark || saShellDefaults.sideDark;

        /* رنگ فونت — متغیرهای سراسری؛ خالی = پیش‌فرض پنل */
        s.setProperty('--color-ink', saState.fontColor || saShellDefaults.font);
        s.setProperty('--color-night-ink', saState.fontColorDark || saShellDefaults.fontDark);

        /* پس‌زمینهٔ سایدبار/صفحه — فقط متغیرها (قواعد تزریقی layout از همین‌ها می‌خوانند) */
        s.setProperty('--ap-side-bg', sideColor);
        s.setProperty('--ap-side-bg-dark', sideColorDark);
        s.setProperty('--ap-page-bg', saState.pageColor || saShellDefaults.page);
        s.setProperty('--ap-page-bg-dark', saState.pageColorDark || saShellDefaults.pageDark);

        /* متغیرهای مشتق سطح سایدبار — فقط داخل #ap-sidebar */
        const side = document.getElementById('ap-sidebar');
        if (side) {
            const ink = saContrastInk(sideColor);
            const inkD = saContrastInk(sideColorDark);
            const st = side.style;
            st.setProperty('--color-surface', sideColor);
            st.setProperty('--color-surface-2', 'color-mix(in srgb, ' + sideColor + ' 93%, ' + ink + ')');
            st.setProperty('--color-surface-3', 'color-mix(in srgb, ' + sideColor + ' 87%, ' + ink + ')');
            st.setProperty('--color-line', 'color-mix(in srgb, ' + sideColor + ' 82%, ' + ink + ')');
            st.setProperty('--color-ink', ink);
            st.setProperty('--color-ink-2', 'color-mix(in srgb, ' + ink + ' 76%, ' + sideColor + ')');
            st.setProperty('--color-ink-3', 'color-mix(in srgb, ' + ink + ' 56%, ' + sideColor + ')');
            st.setProperty('--color-night-2', sideColorDark);
            st.setProperty('--color-night-3', 'color-mix(in srgb, ' + sideColorDark + ' 86%, ' + inkD + ')');
            st.setProperty('--color-night-line', 'color-mix(in srgb, ' + sideColorDark + ' 80%, ' + inkD + ')');
            st.setProperty('--color-night-ink', inkD);
            st.setProperty('--color-night-ink-2', 'color-mix(in srgb, ' + inkD + ' 72%, ' + sideColorDark + ')');
        }
    }

    function saApplyUI() {
        const pv = saComputePreview();
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

        // ردیف «حالت پیش‌فرض پالت»
        const defHexEl = document.getElementById('sa-default-hex');
        const defDotEl = document.getElementById('sa-default-dot');
        const defBtn = document.getElementById('sa-warmth-reset');
        if (defHexEl) defHexEl.textContent = pv.rawBase;
        if (defDotEl) defDotEl.style.background = pv.rawBase;
        if (defBtn) { defBtn.style.borderColor = base + '4D'; defBtn.style.color = base; }

        // جعبه‌های خط‌چین
        ['sa-warmth-box', 'sa-custom-box', 'sa-font-box', 'sa-shell-box'].forEach((id) => {
            const box = document.getElementById(id);
            if (box) box.style.borderColor = base + '55';
        });
        const customBox = document.getElementById('sa-custom-box');
        if (customBox) customBox.classList.toggle('hidden', saState.palette !== 'custom');

        /* فاز ۲۱۰ — رنگ فونت: نمونه‌های زنده + نشانگر انتخابگر + دکمهٔ پیش‌فرض */
        const fontSample = document.getElementById('sa-font-sample');
        const fontSampleDark = document.getElementById('sa-font-sample-dark');
        const fontInput = document.getElementById('sa-font-color');
        const fontInputDark = document.getElementById('sa-font-color-dark');
        const fontReset = document.getElementById('sa-font-reset');
        const fontResetDark = document.getElementById('sa-font-reset-dark');
        if (fontSample) fontSample.style.color = saState.fontColor || saShellDefaults.font;
        if (fontSampleDark) fontSampleDark.style.color = saState.fontColorDark || saShellDefaults.fontDark;
        if (fontInput) fontInput.value = saState.fontColor || saShellDefaults.font;
        if (fontInputDark) fontInputDark.value = saState.fontColorDark || saShellDefaults.fontDark;
        if (fontReset) { fontReset.style.borderColor = base + '4D'; fontReset.style.color = base; }
        if (fontResetDark) { fontResetDark.style.borderColor = base + '4D'; fontResetDark.style.color = base; }

        /* فاز ۲۱۰ — رنگ سایدبار/صفحه: پیش‌نمایش زنده + نشانگرها + دکمه‌های پیش‌فرض */
        const sideColor = saState.sidebarColor || saShellDefaults.side;
        const sideColorDark = saState.sidebarColorDark || saShellDefaults.sideDark;
        const pageColor = saState.pageColor || saShellDefaults.page;
        const pageColorDark = saState.pageColorDark || saShellDefaults.pageDark;
        const sideInk = saContrastInk(sideColor);
        const sideInkDark = saContrastInk(sideColorDark);

        const pvSide = document.getElementById('sa-shell-preview-side');
        const pvSideDark = document.getElementById('sa-shell-preview-side-dark');
        const pvPage = document.getElementById('sa-shell-preview-page');
        const pvPageDark = document.getElementById('sa-shell-preview-page-dark');
        if (pvSide) pvSide.style.background = sideColor;
        if (pvSideDark) pvSideDark.style.background = sideColorDark;
        if (pvPage) pvPage.style.background = pageColor;
        if (pvPageDark) pvPageDark.style.background = pageColorDark;

        const pvSideTitle = document.getElementById('sa-shell-preview-side-title');
        const pvSideTitleDark = document.getElementById('sa-shell-preview-side-title-dark');
        const pvLink1 = document.getElementById('sa-shell-preview-link1');
        const pvLink1Dark = document.getElementById('sa-shell-preview-link1-dark');
        const pvLink2 = document.getElementById('sa-shell-preview-link2');
        const pvLink2Dark = document.getElementById('sa-shell-preview-link2-dark');
        if (pvSideTitle) pvSideTitle.style.color = sideInk;
        if (pvSideTitleDark) pvSideTitleDark.style.color = sideInkDark;
        if (pvLink1) { pvLink1.style.background = 'color-mix(in srgb, ' + base + ' 14%, ' + sideColor + ')'; pvLink1.style.color = sideInk; }
        if (pvLink1Dark) { pvLink1Dark.style.background = 'color-mix(in srgb, ' + base + ' 22%, ' + sideColorDark + ')'; pvLink1Dark.style.color = sideInkDark; }
        if (pvLink2) pvLink2.style.color = 'color-mix(in srgb, ' + sideInk + ' 72%, ' + sideColor + ')';
        if (pvLink2Dark) pvLink2Dark.style.color = 'color-mix(in srgb, ' + sideInkDark + ' 72%, ' + sideColorDark + ')';

        const sideInput = document.getElementById('sa-side-color');
        const sideInputDark = document.getElementById('sa-side-color-dark');
        const pageInput = document.getElementById('sa-page-color');
        const pageInputDark = document.getElementById('sa-page-color-dark');
        if (sideInput) sideInput.value = sideColor;
        if (sideInputDark) sideInputDark.value = sideColorDark;
        if (pageInput) pageInput.value = pageColor;
        if (pageInputDark) pageInputDark.value = pageColorDark;
        ['sa-side-reset', 'sa-side-reset-dark', 'sa-page-reset', 'sa-page-reset-dark'].forEach((id) => {
            const btn = document.getElementById(id);
            if (btn) { btn.style.borderColor = base + '4D'; btn.style.color = base; }
        });

        // طیف تولیدشده
        saRoot.querySelectorAll('.sa-swatch').forEach((sw) => {
            const chip = sw.querySelector('.sa-color-chip');
            const hex = pv.scale[sw.getAttribute('data-step')];
            if (!chip || !hex) return;
            chip.style.background = hex;
            sw.setAttribute('title', hex);
        });

        // پیش‌نمایش زنده
        const solid = document.getElementById('sa-preview-solid');
        const outline = document.getElementById('sa-preview-outline');
        const badge = document.getElementById('sa-preview-badge');
        const cart = document.getElementById('sa-preview-cart');
        if (solid) { solid.style.background = base; solid.style.boxShadow = '0 5px 12px ' + base + '40'; }
        if (outline) { outline.style.borderColor = base; outline.style.color = base; }
        if (badge) { badge.style.background = pv.scale[50]; badge.style.color = pv.scale[700]; }
        if (cart) { cart.style.background = base; cart.style.boxShadow = '0 4px 10px ' + base + '40'; }

        // دکمهٔ ذخیره + آیکن سربرگ = گرادیان پالت
        const gradientCss = 'linear-gradient(' + pv.angle + 'deg, ' + pv.stops.join(', ') + ')';
        const saveBtn = document.getElementById('sa-save');
        const headIcon = document.getElementById('sa-head-icon');
        if (saveBtn) saveBtn.style.background = gradientCss;
        if (headIcon) headIcon.style.background = gradientCss;

        // دکمهٔ بازگشت به پیش‌فرض
        const resetBtn = document.getElementById('sa-reset');
        if (resetBtn) { resetBtn.style.borderColor = base + '66'; resetBtn.style.color = base; }

        saApplyModeUI(pv.base);
    }

    function saApplyModeUI(baseColor) {
        const base = baseColor || saComputePreview().base;
        saRoot.querySelectorAll('[data-mode]').forEach((btn) => {
            const sel = btn.getAttribute('data-mode') === saState.mode;
            btn.style.borderColor = sel ? base : '';
            btn.style.background = sel ? base + '0F' : '';
            btn.style.color = sel ? base : '';
        });
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

    const saColorInput = document.getElementById('sa-color-input');
    const saHexInput = document.getElementById('sa-custom-hex');
    if (saColorInput) {
        saColorInput.addEventListener('input', () => {
            saState.custom = saColorInput.value.toUpperCase();
            if (saHexInput) saHexInput.value = saState.custom;
            saApplyUI();
        });
    }
    if (saHexInput) {
        saHexInput.addEventListener('input', () => {
            const v = saHexInput.value.trim();
            if (/^#?[0-9a-fA-F]{6}$/.test(v)) {
                saState.custom = ('#' + v.replace('#', '')).toUpperCase();
                if (saColorInput) saColorInput.value = saState.custom;
                saApplyUI();
            }
        });
    }

    saRoot.querySelectorAll('[data-mode]').forEach((btn) => {
        btn.addEventListener('click', () => {
            saState.mode = btn.getAttribute('data-mode');
            saApplyUI();
        });
    });

    /* ---------- فاز ۲۱۰ — رویدادهای رنگ فونت/سایدبار/صفحه ---------- */
    const saBindPair = (inputId, hexId, stateKey, applyKey) => {
        const input = document.getElementById(inputId);
        const hexInput = document.getElementById(hexId);
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
        const resetBtn = document.getElementById(applyKey);
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                saState[stateKey] = '';
                saApplyUI();
            });
        }
    };

    saBindPair('sa-font-color', 'sa-font-hex', 'fontColor', 'sa-font-reset');
    saBindPair('sa-font-color-dark', 'sa-font-hex-dark', 'fontColorDark', 'sa-font-reset-dark');
    saBindPair('sa-side-color', 'sa-side-hex', 'sidebarColor', 'sa-side-reset');
    saBindPair('sa-side-color-dark', 'sa-side-hex-dark', 'sidebarColorDark', 'sa-side-reset-dark');
    saBindPair('sa-page-color', 'sa-page-hex', 'pageColor', 'sa-page-reset');
    saBindPair('sa-page-color-dark', 'sa-page-hex-dark', 'pageColorDark', 'sa-page-reset-dark');

    // کلیک روی پله‌های طیف = کپی کد رنگ
    saRoot.querySelectorAll('.sa-swatch').forEach((sw) => {
        sw.addEventListener('click', () => {
            const hex = saLast.scale[sw.getAttribute('data-step')] || '';
            if (!hex) return;
            const notifyCopied = () => $wire.dispatch('toast', { message: 'کد رنگ ' + hex + ' کپی شد.', type: 'info' });
            const notifyPlain = () => $wire.dispatch('toast', { message: 'کد رنگ: ' + hex, type: 'info' });
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(hex).then(notifyCopied).catch(notifyPlain);
                } else {
                    notifyPlain();
                }
            } catch (e) {
                notifyPlain();
            }
        });
    });

    // ذخیرهٔ پوسته — اول همگام‌سازی وضعیت، بعد فراخوانی اکشن + رنگ‌کردن فوری پنل
    const saSaveBtn = document.getElementById('sa-save');
    if (saSaveBtn) {
        saSaveBtn.addEventListener('click', async () => {
            saSaveBtn.disabled = true;
            saSaveBtn.style.opacity = '0.7';
            try {
                await $wire.syncState(
                    saState.palette, saState.warmth, saState.custom, saState.mode,
                    saState.fontColor, saState.fontColorDark,
                    saState.sidebarColor, saState.sidebarColorDark,
                    saState.pageColor, saState.pageColorDark
                );
                await $wire.call('saveSkin');
                saApplyAdminVars(saComputePreview().scale);
                saApplyShellVars();
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
        if (data.mode) saState.mode = data.mode;
        if (data.fontColor !== undefined && data.fontColor !== null) saState.fontColor = data.fontColor;
        if (data.fontColorDark !== undefined && data.fontColorDark !== null) saState.fontColorDark = data.fontColorDark;
        if (data.sidebarColor !== undefined && data.sidebarColor !== null) saState.sidebarColor = data.sidebarColor;
        if (data.sidebarColorDark !== undefined && data.sidebarColorDark !== null) saState.sidebarColorDark = data.sidebarColorDark;
        if (data.pageColor !== undefined && data.pageColor !== null) saState.pageColor = data.pageColor;
        if (data.pageColorDark !== undefined && data.pageColorDark !== null) saState.pageColorDark = data.pageColorDark;
        if (saHexInput) saHexInput.value = saState.custom;
        if (saColorInput) saColorInput.value = saState.custom;
        saApplyUI();
        saApplyAdminVars(saComputePreview().scale);
        saApplyShellVars();
    });
</script>
@endscript
