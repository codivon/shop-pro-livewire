{{-- ============================================================
     SaniarTheme — تنظیمات ظاهری قالب (فاز ۲۰۸ — فاز ۲۱۰)
     کارت اختصاصی قالب در صفحهٔ «تنظیمات قالب» پنل مدیریت —
     دقیقاً مطابق نمونهٔ طراحی:
       • پالت‌های آماده (گرید ۴ ستونه) + شخصی‌سازی
       • اسلایدر گرمی/سردی + ردیف «حالت پیش‌فرض پالت»
       • طیف تولیدشدهٔ ۵۰..۹۵۰ (کلیک = کپی کد رنگ)
       • پیش‌نمایش زنده (دکمهٔ اصلی/خطی، برچسب، سبد خرید)
       • فاز ۲۱۰ — رنگ فونت (متن) سایت — جدا برای روشن و تم شب
       • حالت پیش‌فرض سایت (روشن/تیره)
       • بازگشت به پیش‌فرض + ذخیرهٔ پوسته (ذخیرهٔ مستقل از فرم والد)

     رنگ‌های لهجه (border/سایه/دکمه‌ها) با style درون‌خطی از
     $preview (رندر سرور) می‌آیند و JS همان لحظه به‌روزرسانی‌شان
     می‌کند؛ بقیهٔ ظاهر از کلاس‌های خود پنل (ap-card / ink / night) است.
     ============================================================ --}}
@php
    /** @var \Themes\SaniarTheme\src\Support\PaletteEngine[] $palettes */
    $base = $preview['base'];
    $scale = $preview['scale'];
    $gradientCss = 'linear-gradient('.$preview['gradient_angle'].'deg, '.implode(', ', $preview['gradient']).')';
    $rawBase = ($palette === 'custom') ? $custom : (collect($palettes)->firstWhere('key', $palette)['base'] ?? $base);
    $steps = [950, 900, 800, 700, 600, 500, 400, 300, 200, 100, 50];

    /* فاز ۲۱۰ — رنگ فونت: مقدار ذخیره‌شده یا پیش‌فرض قالب (برای نشانگر انتخابگر) */
    $saFontColor = $fontColor !== '' ? $fontColor : \Themes\SaniarTheme\src\Support\PaletteEngine::DEFAULT_FONT_COLOR;
    $saFontColorDark = $fontColorDark !== '' ? $fontColorDark : \Themes\SaniarTheme\src\Support\PaletteEngine::DEFAULT_FONT_COLOR_DARK;
@endphp
<div id="saniar-appearance" class="ap-card overflow-hidden p-0">

    {{-- ===== سربرگ کارت ===== --}}
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
                <p class="mt-0.5 text-[11px] text-ink-3 dark:text-night-ink-2">مخصوص قالب SaniarTheme — اعمال روی کل فروشگاه</p>
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
            همه‌چیز — از دکمه‌ها تا جزئیات — در همهٔ صفحات سایت هماهنگ تغییر می‌کند.
        </p>

        {{-- ===== پالت‌های آماده ===== --}}
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

        {{-- ===== میزان گرمی و سردیِ پالت ===== --}}
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

        {{-- ===== شخصی‌سازی — رنگ اصلی (فقط وقتی پالت «شخصی‌سازی» انتخاب است) ===== --}}
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

        {{-- ===== فاز ۲۱۰ — رنگ فونت (متن) سایت ===== --}}
        <div id="sa-font-box" class="rounded-2xl border-2 border-dashed p-4 sm:p-5"
             style="border-color: {{ $base }}55;">
            <h4 class="text-[13px] font-bold">رنگ فونت (متن سایت)</h4>
            <p class="mt-1 text-[11px] leading-5 text-ink-3 dark:text-night-ink-2">
                رنگ متنِ صفحات سایت را جدا برای حالت روشن و تم شب تنظیم کنید؛
                با دکمهٔ «پیش‌فرض» هم هر رنگ به مقدار اصلی قالب برمی‌گردد.
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
                    <div class="flex items-center gap-3">
                        <label class="relative flex size-11 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                               style="background: {{ $saFontColor }}" title="باز کردن انتخابگر رنگ">
                            <input type="color" id="sa-font-color" value="{{ $saFontColor }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ فونت حالت روشن">
                        </label>
                        <input type="text" id="sa-font-hex" dir="ltr" maxlength="7" value="{{ $fontColor }}"
                               class="ap-input w-36 text-center text-[12px] font-bold" placeholder="#333333"
                               onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                               aria-label="کد HEX رنگ فونت حالت روشن">
                        <button type="button" id="sa-font-reset"
                                class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                style="border-color: {{ $base }}4D; color: {{ $base }}"
                                title="بازگشت رنگ فونت روشن به پیش‌فرض قالب">
                            پیش‌فرض
                        </button>
                    </div>
                    {{-- نمونهٔ متن --}}
                    <div class="mt-3 rounded-xl bg-white px-3 py-2.5" style="border: 1px solid {{ $base }}22;">
                        <p id="sa-font-sample" class="m-0 text-[12.5px] leading-6 font-semibold"
                           style="color: {{ $saFontColor }}">
                            نمونهٔ متن — از هر رنگی که انتخاب کنید، متن سایت همین‌طور دیده می‌شود.
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
                    <div class="flex items-center gap-3">
                        <label class="relative flex size-11 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-black/10"
                               style="background: {{ $saFontColorDark }}" title="باز کردن انتخابگر رنگ">
                            <input type="color" id="sa-font-color-dark" value="{{ $saFontColorDark }}" class="absolute inset-0 size-full cursor-pointer opacity-0" aria-label="انتخاب رنگ فونت تم شب">
                        </label>
                        <input type="text" id="sa-font-hex-dark" dir="ltr" maxlength="7" value="{{ $fontColorDark }}"
                               class="ap-input w-36 text-center text-[12px] font-bold" placeholder="#E6E8EE"
                               onkeydown="if (event.key === 'Enter') { event.preventDefault(); }"
                               aria-label="کد HEX رنگ فونت تم شب">
                        <button type="button" id="sa-font-reset-dark"
                                class="rounded-full border px-3 py-1 text-[10px] font-bold transition-colors"
                                style="border-color: {{ $base }}4D; color: {{ $base }}"
                                title="بازگشت رنگ فونت تم شب به پیش‌فرض قالب">
                            پیش‌فرض
                        </button>
                    </div>
                    {{-- نمونهٔ متن --}}
                    <div class="mt-3 rounded-xl px-3 py-2.5" style="background: #111827; border: 1px solid {{ $base }}22;">
                        <p id="sa-font-sample-dark" class="m-0 text-[12.5px] leading-6 font-semibold"
                           style="color: {{ $saFontColorDark }}">
                            نمونهٔ متن در تم شب — سبد خرید، پرداخت و همهٔ صفحه‌ها.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== طیف تولیدشده ===== --}}
        <div class="rounded-2xl bg-surface-2/60 p-4 sm:p-5 dark:bg-night-3/50">
            <p class="mb-3 text-[12px] font-bold">
                طیف تولیدشده (۵۰ تا ۹۵۰)
                <span class="font-normal text-ink-3 dark:text-night-ink-2">— از این رنگ‌ها در کل اجرای سایت استفاده می‌شود:</span>
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

        {{-- ===== پیش‌نمایش زنده ===== --}}
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

        {{-- ===== حالت پیش‌فرض سایت ===== --}}
        <div>
            <h4 class="mb-3 text-[13px] font-bold">حالت پیش‌فرض سایت</h4>
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
                این انتخاب فقط حالت اولیهٔ سایت برای بازدیدکننده‌ها را تعیین می‌کند؛
                کاربران همچنان می‌توانند از دکمهٔ ماه، بین روشن و تیره جابه‌جا شوند.
            </p>
        </div>

        {{-- ===== پانویس: بازگشت به پیش‌فرض + ذخیره ===== --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-dashed pt-4" style="border-color: {{ $base }}33;">
            <p class="flex items-center gap-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-3.5 shrink-0" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
                </svg>
                بلافاصله پس از ذخیره، همهٔ صفحات سایت رنگ‌های جدید را می‌گیرند.
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

@script
<script>
    /* ============================================================
       فاز ۲۰۸ — پیش‌نمایش زندهٔ تنظیمات ظاهری (سمت مرورگر)
       • ریاضی رنگ قرینهٔ PaletteEngine (PHP) است؛ منبع حقیقتِ ذخیره
         همان PHP است و فقط هنگام «ذخیرهٔ پوسته» شبکه درگیر می‌شود.
       • این کامپوننت داخل فرم والد است — هیچ دکمه‌ای submit نمی‌کند.
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
    };

    /* فاز ۲۱۰ — پیش‌فرض‌های قالب برای دکمهٔ «پیش‌فرض» رنگ فونت */
    const saFontDefaults = {
        light: @json(\Themes\SaniarTheme\src\Support\PaletteEngine::DEFAULT_FONT_COLOR),
        dark: @json(\Themes\SaniarTheme\src\Support\PaletteEngine::DEFAULT_FONT_COLOR_DARK),
    };

    /* ---------- ریاضی رنگ (قرینهٔ PHP) ---------- */
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
        const anchor = saClamp(l, 40, 62);
        const L = {
            50: Math.min(98, anchor + 42), 100: Math.min(95, anchor + 36), 200: Math.min(90, anchor + 28),
            300: Math.min(84, anchor + 19), 400: Math.min(74, anchor + 11), 500: anchor,
            600: Math.max(34, anchor - 9), 700: Math.max(28, anchor - 16), 800: Math.max(22, anchor - 23),
            900: Math.max(16, anchor - 30), 950: Math.max(11, anchor - 37),
        };
        const S = {
            50: s * 0.40, 100: s * 0.48, 200: s * 0.62, 300: s * 0.78, 400: s * 0.92,
            500: s, 600: Math.min(100, s * 1.02), 700: Math.min(100, s * 1.05),
            800: s, 900: s * 0.94, 950: s * 0.88,
        };
        const out = {};
        Object.keys(L).forEach((k) => {
            out[k] = saHslToHex(h, saClamp(S[k], 12, 100), saClamp(L[k], 8, 98));
        });
        return out;
    }

    function saComputePreview() {
        const def = saIndex[saState.palette] || saIndex['instagram'];
        const rawBase = saState.palette === 'custom' ? saState.custom : def.base;
        const [base, rot] = saWarmthRotation(rawBase, saState.warmth);
        const scale = saGenerateScale(base);
        const stops = (def.gradient || [scale[400], scale[600]]).map((c) => saRotateHue(c, rot));
        return { base, scale, stops, rawBase, def, angle: def.gradient_angle || 135 };
    }

    /* ---------- اعمال روی UI ---------- */
    let saLast = saComputePreview();

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

        // ردیف «حالت پیش‌فرض پالت» (رنگ خام بدون گرمی/سردی)
        const defHexEl = document.getElementById('sa-default-hex');
        const defDotEl = document.getElementById('sa-default-dot');
        const defBtn = document.getElementById('sa-warmth-reset');
        if (defHexEl) defHexEl.textContent = pv.rawBase;
        if (defDotEl) defDotEl.style.background = pv.rawBase;
        if (defBtn) { defBtn.style.borderColor = base + '4D'; defBtn.style.color = base; }

        // جعبه‌های خط‌چین
        ['sa-warmth-box', 'sa-custom-box', 'sa-font-box'].forEach((id) => {
            const box = document.getElementById(id);
            if (box) box.style.borderColor = base + '55';
        });
        const customBox = document.getElementById('sa-custom-box');
        if (customBox) customBox.classList.toggle('hidden', saState.palette !== 'custom');

        // فاز ۲۱۰ — رنگ فونت: نمونه‌های زنده + دکمه‌های پیش‌فرض
        const fontSample = document.getElementById('sa-font-sample');
        const fontSampleDark = document.getElementById('sa-font-sample-dark');
        const fontColorInput = document.getElementById('sa-font-color');
        const fontColorInputDark = document.getElementById('sa-font-color-dark');
        const fontResetBtn = document.getElementById('sa-font-reset');
        const fontResetBtnDark = document.getElementById('sa-font-reset-dark');
        if (fontSample) fontSample.style.color = saState.fontColor || saFontDefaults.light;
        if (fontSampleDark) fontSampleDark.style.color = saState.fontColorDark || saFontDefaults.dark;
        if (fontColorInput) fontColorInput.value = saState.fontColor || saFontDefaults.light;
        if (fontColorInputDark) fontColorInputDark.value = saState.fontColorDark || saFontDefaults.dark;
        if (fontResetBtn) { fontResetBtn.style.borderColor = base + '4D'; fontResetBtn.style.color = base; }
        if (fontResetBtnDark) { fontResetBtnDark.style.borderColor = base + '4D'; fontResetBtnDark.style.color = base; }

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

    /* ---------- فاز ۲۱۰ — رویدادهای رنگ فونت ---------- */
    const saFontHexInput = document.getElementById('sa-font-hex');
    const saFontHexInputDark = document.getElementById('sa-font-hex-dark');

    const saFontColorInput = document.getElementById('sa-font-color');
    if (saFontColorInput) {
        saFontColorInput.addEventListener('input', () => {
            saState.fontColor = saFontColorInput.value.toUpperCase();
            if (saFontHexInput) saFontHexInput.value = saState.fontColor;
            saApplyUI();
        });
    }
    if (saFontHexInput) {
        saFontHexInput.addEventListener('input', () => {
            const v = saFontHexInput.value.trim();
            if (/^#?[0-9a-fA-F]{6}$/.test(v)) {
                saState.fontColor = ('#' + v.replace('#', '')).toUpperCase();
                if (saFontColorInput) saFontColorInput.value = saState.fontColor;
                saApplyUI();
            }
        });
    }
    const saFontResetBtn = document.getElementById('sa-font-reset');
    const saFontResetBtnDark = document.getElementById('sa-font-reset-dark');
    const saFontColorInputDark = document.getElementById('sa-font-color-dark');
    if (saFontColorInputDark) {
        saFontColorInputDark.addEventListener('input', () => {
            saState.fontColorDark = saFontColorInputDark.value.toUpperCase();
            if (saFontHexInputDark) saFontHexInputDark.value = saState.fontColorDark;
            saApplyUI();
        });
    }
    if (saFontHexInputDark) {
        saFontHexInputDark.addEventListener('input', () => {
            const v = saFontHexInputDark.value.trim();
            if (/^#?[0-9a-fA-F]{6}$/.test(v)) {
                saState.fontColorDark = ('#' + v.replace('#', '')).toUpperCase();
                if (saFontColorInputDark) saFontColorInputDark.value = saState.fontColorDark;
                saApplyUI();
            }
        });
    }
    if (saFontResetBtn) {
        saFontResetBtn.addEventListener('click', () => {
            saState.fontColor = '';
            saApplyUI();
        });
    }
    if (saFontResetBtnDark) {
        saFontResetBtnDark.addEventListener('click', () => {
            saState.fontColorDark = '';
            saApplyUI();
        });
    }

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

    // ذخیرهٔ پوسته — اول همگام‌سازی وضعیت، بعد فراخوانی اکشن
    const saSaveBtn = document.getElementById('sa-save');
    if (saSaveBtn) {
        saSaveBtn.addEventListener('click', async () => {
            saSaveBtn.disabled = true;
            saSaveBtn.style.opacity = '0.7';
            try {
                await $wire.syncState(saState.palette, saState.warmth, saState.custom, saState.mode, saState.fontColor, saState.fontColorDark);
                await $wire.call('saveSkin');
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

    // پس از بازگشت به پیش‌فرض، وضعیت JS با سرور همگام می‌شود
    $wire.on('appearance-reset', (payload) => {
        const data = Array.isArray(payload) ? payload[0] : (payload || {});
        if (data.palette) saState.palette = data.palette;
        if (data.warmth !== undefined && data.warmth !== null) saState.warmth = parseInt(data.warmth, 10) || 0;
        if (data.custom) saState.custom = data.custom;
        if (data.mode) saState.mode = data.mode;
        if (data.fontColor !== undefined && data.fontColor !== null) saState.fontColor = data.fontColor;
        if (data.fontColorDark !== undefined && data.fontColorDark !== null) saState.fontColorDark = data.fontColorDark;
        if (saHexInput) saHexInput.value = saState.custom;
        if (saColorInput) saColorInput.value = saState.custom;
        if (saFontHexInput) saFontHexInput.value = saState.fontColor;
        if (saFontHexInputDark) saFontHexInputDark.value = saState.fontColorDark;
        saApplyUI();
    });
</script>
@endscript
