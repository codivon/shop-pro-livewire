{{-- تنظیمات کلی سایت — پنل جدید — پورت back/settings/information.blade.php --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());
@endphp
<div class="mx-auto max-w-6xl space-y-5">

    {{-- سربرگ --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/12 dark:text-brand-400">
                <x-admin-panel::icon name="store" class="size-5" />
            </span>
            <div>
                <h2 class="text-base font-bold">تنظیمات کلی</h2>
                <p class="text-xs text-ink-3 dark:text-night-ink-2">اطلاعات پایه و پیکربندی عمومی وبسایت</p>
            </div>
        </div>
        <button type="submit" form="information-form" class="ap-btn-primary !py-2.5">
            <x-admin-panel::icon name="check-circle" class="size-4" />
            ذخیره تغییرات
        </button>
    </div>

    {{-- novalidate: اعتبارسنجی با Livewire است — inputهای type=email/url با داده‌های واقعی
         (مثلاً شماره تماس در ایمیل) مانع submit بومی HTML5 می‌شدند و فرم هرگز ذخیره نمی‌شد --}}
    <form id="information-form" wire:submit="save" x-data="{ tab: 'base' }" class="space-y-5" novalidate>

        {{-- تب‌ها --}}
        <div class="ap-card overflow-hidden">
            <div class="flex gap-1 overflow-x-auto border-b border-line bg-surface-2 px-2 pt-2 dark:border-night-line dark:bg-night-3/50" role="tablist">
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'base'"
                        x-bind:aria-selected="(tab === 'base').toString()" role="tab"
                        x-bind:class="tab === 'base' ? 'is-active' : ''">
                    <x-admin-panel::icon name="info" class="size-4" />
                    اطلاعات کلی
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'seo'"
                        x-bind:aria-selected="(tab === 'seo').toString()" role="tab"
                        x-bind:class="tab === 'seo' ? 'is-active' : ''">
                    <x-admin-panel::icon name="search" class="size-4" />
                    سئو و متا
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'licenses'"
                        x-bind:aria-selected="(tab === 'licenses').toString()" role="tab"
                        x-bind:class="tab === 'licenses' ? 'is-active' : ''">
                    <x-admin-panel::icon name="award" class="size-4" />
                    مجوزها
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'scripts'"
                        x-bind:aria-selected="(tab === 'scripts').toString()" role="tab"
                        x-bind:class="tab === 'scripts' ? 'is-active' : ''">
                    <x-admin-panel::icon name="terminal" class="size-4" />
                    اسکریپت‌ها
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'map'"
                        x-bind:aria-selected="(tab === 'map').toString()" role="tab"
                        x-bind:class="tab === 'map' ? 'is-active' : ''">
                    <x-admin-panel::icon name="map" class="size-4" />
                    نقشه
                </button>
                <button type="button" class="ap-tab relative whitespace-nowrap" x-on:click="tab = 'torob'"
                        x-bind:aria-selected="(tab === 'torob').toString()" role="tab"
                        x-bind:class="tab === 'torob' ? 'is-active' : ''">
                    <x-admin-panel::icon name="link-2" class="size-4" />
                    ترب و ایمالز
                </button>
            </div>

            {{-- ============ تب اطلاعات کلی ============ --}}
            <div x-show="tab === 'base'" x-cloak class="animate-fade-in space-y-6 p-5 sm:p-6">

                {{-- تصاویر و لوگو --}}
                <section>
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="image" class="size-4 text-brand-600 dark:text-brand-400" />
                        تصاویر و لوگو
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            ['key' => 'info_icon', 'current' => 'current_info_icon', 'label' => 'آیکون سایت', 'hint' => 'بهترین اندازه: ۳۲×۳۲ پیکسل'],
                            ['key' => 'info_logo', 'current' => 'current_info_logo', 'label' => 'لوگوی شرکت', 'hint' => 'بهترین اندازه: ۲۰۰×۶۰ پیکسل'],
                            ['key' => 'info_logo_seller', 'current' => 'current_info_logo_seller', 'label' => 'لوگوی شرکت فروشندگان', 'hint' => 'بهترین اندازه: ۲۰۰×۶۰ پیکسل'],
                            ['key' => 'info_logo_panel_seller', 'current' => 'current_info_logo_panel_seller', 'label' => 'لوگوی پنل فروشندگان', 'hint' => 'بهترین اندازه: ۲۰۰×۶۰ پیکسل'],
                            ['key' => 'info_og_image', 'current' => 'current_info_og_image', 'label' => 'تصویر OG (اشتراک‌گذاری)', 'hint' => 'اندازه پیشنهادی: ۱۲۰۰×۶۳۰ پیکسل'],
                        ] as $img)
                            <div class="rounded-xl border border-line p-4 dark:border-night-line">
                                <x-admin-panel::media-picker
                                    wireModel="{{ $img['key'] }}"
                                    :value="${$img['current']}"
                                    :label="$img['label']"
                                    :hint="$img['hint']"
                                    height="h-28"
                                    aspect="contain"
                                />
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- اطلاعات پایه --}}
                <section>
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="info" class="size-4 text-brand-600 dark:text-brand-400" />
                        اطلاعات پایه
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">عنوان وبسایت <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="info_site_title" class="ap-input" placeholder="فروشگاه آنلاین ایران">
                            @error('info_site_title') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">ایمیل</label>
                            <input type="email" dir="ltr" wire:model="info_email" class="ap-input" placeholder="info@site.ir">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">تلفن</label>
                            <input type="text" dir="ltr" wire:model="info_tel" class="ap-input" placeholder="021-12345678">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">فکس</label>
                            <input type="text" dir="ltr" wire:model="info_fax" class="ap-input" placeholder="021-12345679">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">کد پستی</label>
                            <input type="text" dir="ltr" wire:model="info_postal_code" class="ap-input" placeholder="1234567890">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">شماره پشتیبانی</label>
                            <input type="text" dir="ltr" wire:model="info_support_phone" class="ap-input" placeholder="021-00000000">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">ساعات کاری</label>
                            <input type="text" wire:model="info_working_hours" class="ap-input" placeholder="شنبه تا پنجشنبه ۸ تا ۱۷">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">رنگ اصلی سایت</label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model="info_primary_color" class="h-10 w-12 shrink-0 cursor-pointer rounded-xl border border-line bg-transparent p-1 dark:border-night-line">
                                <input type="text" dir="ltr" wire:model="info_primary_color_text" class="ap-input font-mono" placeholder="#6366f1" maxlength="7">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">استان</label>
                            <select wire:model="info_province_id" class="ap-input">
                                <option value="">— انتخاب استان —</option>
                                @foreach ($this->provinces() as $province)
                                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">شهر</label>
                            <select wire:model="info_city_id" class="ap-input">
                                <option value="">— انتخاب شهر —</option>
                                @foreach ($this->cities as $city)
                                    <option value="{{ $city->id }}">{{ $city->name }}</option>
                                @endforeach
                            </select>
                            @error('info_city_id') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold">آدرس</label>
                            <textarea rows="3" wire:model="info_address" class="ap-input" placeholder="تهران، خیابان ولیعصر، پلاک ۱۰۰"></textarea>
                        </div>
                    </div>
                </section>

                {{-- پیکربندی پیشرفته --}}
                <section>
                    <h3 class="mb-3 flex items-center gap-2 text-sm font-bold">
                        <x-admin-panel::icon name="sliders-horizontal" class="size-4 text-brand-600 dark:text-brand-400" />
                        پیکربندی پیشرفته
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">پیشوند آدرس ورود به بخش مدیریت</label>
                            <div class="flex items-center gap-1.5">
                                <input type="text" dir="ltr" wire:model="admin_route_prefix" class="ap-input font-mono" placeholder="admin">
                                <span class="shrink-0 text-[11px] text-ink-3">/site.ir</span>
                            </div>
                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">پس از تغییر، کش مسیرها/کانفیگ باید روی سرور تازه شود.</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold">فعال کردن سایت چند زبانه <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">بزودی</span></label>
                            <select wire:model="multi_language_enabled" disabled class="ap-input cursor-not-allowed opacity-60">
                                <option value="0">🔒 غیرفعال</option>
                                <option value="1">🌐 فعال</option>
                            </select>
                        </div>
                    </div>
                </section>
            </div>

            {{-- ============ تب سئو و متا ============ --}}
            <div x-show="tab === 'seo'" x-cloak class="animate-fade-in space-y-6 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">کلمات کلیدی</label>
                        <input type="text" wire:model="info_tags" class="ap-input" placeholder="کلمات کلیدی را با کاما جدا کنید">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">آدرس کانونیکال</label>
                        <input type="url" dir="ltr" wire:model="info_canonical" class="ap-input font-mono" placeholder="https://site.ir">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">
                            توضیحات کوتاه (Meta Description)
                            <span class="ap-badge bg-surface-3 text-ink-2 dark:bg-night-3 dark:text-night-ink-2">حداکثر ۱۶۰ کاراکتر</span>
                        </label>
                        <textarea rows="3" maxlength="160" wire:model="info_short_description" class="ap-input" placeholder="توضیحات مختصری درباره سایت..."></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">متن فوتر</label>
                        <textarea rows="3" wire:model="info_footer_text" class="ap-input" placeholder="متن پایین صفحه سایت..."></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">Robots.txt</label>
                        <textarea rows="5" dir="ltr" wire:model="info_robots_txt" class="ap-input font-mono" placeholder="User-agent: *&#10;Allow: /"></textarea>
                    </div>
                </div>
            </div>

            {{-- ============ تب مجوزها (فاز ۲۰۷ — نشان‌های اعتماد تکرارشونده) ============ --}}
            <div x-show="tab === 'licenses'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-bold">
                            <x-admin-panel::icon name="award" class="size-4 text-brand-600 dark:text-brand-400" />
                            مجوزها و نشان‌های اعتماد
                        </h3>
                        <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">
                            اینماد به‌طور پیش‌فرض وجود دارد؛ می‌توانید مجوزهای دیگری (تصویر، کد HTML یا لینک) اضافه کنید. مجوزهای فعال در فوتر سایت نمایش داده می‌شوند.
                        </p>
                    </div>
                    <button type="button" wire:click="addLicense" class="ap-btn-primary !py-2 text-[13px]">
                        <x-admin-panel::icon name="plus" class="size-4" />
                        افزودن مجوز
                    </button>
                </div>

                <div class="space-y-3">
                    @forelse ($info_licenses as $i => $lic)
                        <div class="rounded-2xl border border-line bg-surface-2/40 dark:border-night-line dark:bg-night-3/40"
                             wire:key="license-row-{{ $i }}"
                             x-data="{ open: @js(!empty($lic['active']) || trim((string) ($lic['code'] ?? $lic['image'] ?? '')) === '') }">

                            {{-- سربرگ هر مجوز --}}
                            <div class="flex flex-wrap items-center gap-2 px-4 py-3">
                                {{-- آیکن بر اساس نوع --}}
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl
                                            {{ ($lic['type'] ?? 'html') === 'image' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400'
                                                : (($lic['type'] ?? 'html') === 'link' ? 'bg-sky-50 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400'
                                                : 'bg-teal-50 text-teal-600 dark:bg-teal-500/12 dark:text-teal-400') }}">
                                    <x-admin-panel::icon name="{{ ($lic['type'] ?? 'html') === 'image' ? 'image' : (($lic['type'] ?? 'html') === 'link' ? 'link-2' : 'code') }}" class="size-4" />
                                </span>

                                <button type="button" x-on:click="open = !open" class="min-w-0 flex-1 text-start">
                                    <span class="block text-[13px] font-bold">
                                        مجوز <span class="fa-digits">{{ $i + 1 }}</span>
                                        @if (!empty($lic['title']))
                                            <span class="ms-1 font-medium text-ink-3 dark:text-night-ink-2">— {{ $lic['title'] }}</span>
                                        @endif
                                    </span>
                                    <span class="text-[11px] text-ink-3 dark:text-night-ink-2">
                                        {{ \App\Livewire\Admin\Settings\Information::LICENSE_TYPES[$lic['type'] ?? 'html'] ?? '—' }}
                                    </span>
                                </button>

                                {{-- جابه‌جایی --}}
                                <span class="flex shrink-0 items-center gap-0.5">
                                    <button type="button" wire:click="moveLicense({{ $i }}, 'up')"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-brand-500/10"
                                            @if ($i === 0) disabled @endif title="انتقال به بالا" aria-label="انتقال به بالا">
                                        <x-admin-panel::icon name="chevron-up" class="size-4" />
                                    </button>
                                    <button type="button" wire:click="moveLicense({{ $i }}, 'down')"
                                            class="rounded-lg p-1.5 text-ink-3 transition-colors hover:bg-brand-50 hover:text-brand-600 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:bg-brand-500/10"
                                            @if ($i === count($info_licenses) - 1) disabled @endif title="انتقال به پایین" aria-label="انتقال به پایین">
                                        <x-admin-panel::icon name="chevron-down" class="size-4" />
                                    </button>
                                </span>

                                {{-- سوییچ نمایش --}}
                                <label class="flex shrink-0 cursor-pointer items-center gap-2 text-[12px] font-semibold">
                                    <span class="relative inline-flex items-center">
                                        <input type="checkbox" wire:model="info_licenses.{{ $i }}.active" class="peer sr-only">
                                        <span class="block h-5 w-9 rounded-full bg-surface-3 transition-colors duration-200 peer-checked:bg-emerald-500 dark:bg-night-line dark:peer-checked:bg-emerald-500"></span>
                                        <span class="absolute start-0.5 top-0.5 size-4 rounded-full bg-white shadow transition-all duration-200 peer-checked:start-[1.125rem]"></span>
                                    </span>
                                    نمایش
                                </label>

                                {{-- حذف --}}
                                <button type="button" wire:click="removeLicense({{ $i }})"
                                        wire:confirm="این مجوز حذف شود؟"
                                        class="flex shrink-0 items-center gap-1 rounded-lg p-1.5 text-red-500 transition-colors hover:bg-red-50 dark:hover:bg-red-500/10"
                                        title="حذف مجوز" aria-label="حذف مجوز">
                                    <x-admin-panel::icon name="trash-2" class="size-4" />
                                </button>
                            </div>

                            {{-- بدنه هر مجوز --}}
                            <div x-show="open" x-transition.opacity class="space-y-4 border-t border-line px-4 py-4 dark:border-night-line">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">عنوان (برای alt تصویر و متن جانشین)</label>
                                        <input type="text" wire:model="info_licenses.{{ $i }}.title" class="ap-input" placeholder="مثلاً: نماد اعتماد الکترونیکی">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">نوع مجوز</label>
                                        <select wire:model.live="info_licenses.{{ $i }}.type" class="ap-input">
                                            @foreach (\App\Livewire\Admin\Settings\Information::LICENSE_TYPES as $typeKey => $typeLabel)
                                                <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                @if (($lic['type'] ?? 'html') === 'image')
                                    {{-- نوع تصویر --}}
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold">آدرس تصویر (URL)</label>
                                            <input type="text" dir="ltr" wire:model="info_licenses.{{ $i }}.image" class="ap-input font-mono" placeholder="https://trustseal.enamad.ir/?...logo.png">
                                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">اگر فایل قابل کلیک باشد، به این آدرس لینک دارد.</p>
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-xs font-semibold">یا آپلود فایل تصویر</label>
                                            <input type="file"
                                                   accept=".jpg,.jpeg,.png,.webp,.gif"
                                                   wire:model="info_licenses.{{ $i }}.image_file"
                                                   class="block w-full cursor-pointer rounded-xl border border-line bg-surface p-2 text-xs file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-brand-600 hover:file:bg-brand-100 dark:border-night-line dark:bg-night-3 dark:file:bg-brand-500/12 dark:file:text-brand-400">
                                            <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">JPG، PNG، WEBP یا GIF — حداکثر ۳ مگابایت.</p>
                                            @error('info_licenses.' . $i . '.image_file') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    @if (!empty($lic['image']))
                                        <div class="flex items-center gap-3 rounded-xl border border-line bg-surface p-3 dark:border-night-line dark:bg-night-3/60">
                                            <img src="{{ $lic['image'] }}" alt="{{ $lic['title'] ?: 'پیش‌نمایش مجوز' }}" class="h-14 w-auto rounded-lg object-contain"
                                                 onerror="this.style.display='none'">
                                            <span class="ltr truncate font-mono text-[10px] text-ink-3 dark:text-night-ink-2">{{ $lic['image'] }}</span>
                                        </div>
                                    @endif
                                @elseif (($lic['type'] ?? 'html') === 'html')
                                    {{-- نوع کد HTML --}}
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">کد HTML مجوز</label>
                                        <textarea rows="6" dir="ltr" wire:model="info_licenses.{{ $i }}.code" class="ap-input font-mono text-xs" placeholder='<a href="..." target="_blank"><img src="..." alt=""/></a>'></textarea>
                                        <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">کد را عیناً از پنل مجوز (مثل اینماد) کپی کنید؛ فقط از کد رسمی استفاده کنید.</p>
                                    </div>
                                @else
                                    {{-- نوع لینک --}}
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">آدرس لینک (URL)</label>
                                        <input type="text" dir="ltr" wire:model="info_licenses.{{ $i }}.link" class="ap-input font-mono" placeholder="https://example.com/license">
                                    </div>
                                @endif

                                {{-- لینک اختیاری (برای تصویر و HTML) --}}
                                @if (($lic['type'] ?? 'html') !== 'link')
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold">لینک (اختیاری)</label>
                                        <input type="text" dir="ltr" wire:model="info_licenses.{{ $i }}.link" class="ap-input font-mono" placeholder="https://trustseal.enamad.ir/?id=...">
                                        <p class="mt-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">روی تصویر مجوز کلیک شود، به این آدرس برود.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <button type="button" wire:click="addLicense"
                                class="flex w-full flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-line py-10 text-ink-3 transition-colors hover:border-brand-400 hover:bg-brand-50/30 hover:text-brand-600 dark:border-night-line dark:hover:border-brand-500/50 dark:hover:bg-brand-500/5 dark:hover:text-brand-400">
                            <span class="flex size-12 items-center justify-center rounded-2xl bg-surface-2 dark:bg-night-3">
                                <x-admin-panel::icon name="award" class="size-6" />
                            </span>
                            <span class="text-[13px] font-bold">افزودن مجوز</span>
                            <span class="text-[11px]">نشان اعتماد اینماد، ساماندهی یا هر مجوز دیگر</span>
                        </button>
                    @endforelse
                </div>
            </div>

            {{-- ============ تب اسکریپت‌ها ============ --}}
            <div x-show="tab === 'scripts'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">شناسه Google Tag Manager</label>
                        <input type="text" dir="ltr" wire:model="info_gtm_id" class="ap-input font-mono" placeholder="GTM-XXXXXXX">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">شناسه Meta Pixel (Facebook)</label>
                        <input type="text" dir="ltr" wire:model="info_meta_pixel" class="ap-input font-mono" placeholder="000000000000000">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">کدهای هدر</label>
                        <p class="mb-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">این کدها داخل تگ <code class="font-mono">&lt;head&gt;</code> قرار می‌گیرند</p>
                        <textarea rows="7" dir="ltr" wire:model="info_header_codes" class="ap-input font-mono text-xs"></textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">اسکریپت‌های اضافه (قبل از بسته شدن body)</label>
                        <p class="mb-1.5 text-[11px] text-ink-3 dark:text-night-ink-2">این کدها قبل از بسته شدن تگ <code class="font-mono">&lt;/body&gt;</code> قرار می‌گیرند</p>
                        <textarea rows="7" dir="ltr" wire:model="info_scripts" class="ap-input font-mono text-xs"></textarea>
                    </div>
                </div>
            </div>

            {{-- ============ تب نقشه ============ --}}
            <div x-show="tab === 'map'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">عرض جغرافیایی (Latitude)</label>
                        <input type="number" step="any" dir="ltr" wire:model="info_latitude" class="ap-input font-mono" placeholder="38.07709880960678">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">طول جغرافیایی (Longitude)</label>
                        <input type="number" step="any" dir="ltr" wire:model="info_Longitude" class="ap-input font-mono" placeholder="46.28582686185837">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">سطح زوم</label>
                        <input type="number" min="1" max="20" dir="ltr" wire:model="info_map_zoom" class="ap-input font-mono" placeholder="13">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold">کلید API نقشه (map.ir)</label>
                        <input type="text" dir="ltr" wire:model="map_api" class="ap-input font-mono" placeholder="eyJ0eXAiOiJKV1Qi...">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-semibold">نوع نقشه</label>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 dark:has-checked:bg-brand-500/10"
                                   @class(['border-line dark:border-night-line', 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10' => $info_map_type === 'google'])>
                                <input type="radio" value="google" wire:model="info_map_type" class="size-4 text-brand-600">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-red-50 text-red-500">
                                    <x-admin-panel::icon name="map" class="size-4.5" />
                                </span>
                                <span>
                                    <span class="block text-[13px] font-bold">نقشه گوگل</span>
                                    <span class="block text-[11px] text-ink-3">Google Maps</span>
                                </span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition-colors has-checked:border-brand-500 has-checked:bg-brand-50/60 dark:has-checked:bg-brand-500/10"
                                   @class(['border-line dark:border-night-line', 'border-brand-500 bg-brand-50/60 dark:bg-brand-500/10' => $info_map_type !== 'google'])>
                                <input type="radio" value="mapir" wire:model="info_map_type" class="size-4 text-brand-600">
                                <span class="flex size-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                    <x-admin-panel::icon name="map-pin" class="size-4.5" />
                                </span>
                                <span>
                                    <span class="block text-[13px] font-bold">نقشه Map.ir</span>
                                    <span class="block text-[11px] text-ink-3">map.ir</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============ تب ترب و ایمالز ============ --}}
            <div x-show="tab === 'torob'" x-cloak class="animate-fade-in space-y-4 p-5 sm:p-6">
                <div class="rounded-xl border border-line p-4 dark:border-night-line">
                    <h4 class="text-[13px] font-bold">آدرس API ترب</h4>
                    <p class="mt-1 text-xs leading-6 text-ink-3 dark:text-night-ink-2">
                        اطلاعات meta tag ترب برای محصولات قرار داده شده است. همچنین با ارائه api زیر به ترب می‌توانید مکانیزم ایندکس شدن محصولات در ترب را به‌صورت خودکار و سریع انجام دهید.
                    </p>
                    @if (Route::has('api.torob.products'))
                        <code class="mt-2 block select-all rounded-lg bg-surface-2 px-3 py-2 font-mono text-[11px] dark:bg-night-3" dir="ltr">{{ route('api.torob.products') }}</code>
                    @endif
                </div>
                <div class="rounded-xl border border-line p-4 dark:border-night-line">
                    <h4 class="text-[13px] font-bold">آدرس API ایمالز</h4>
                    <p class="mt-1 text-xs leading-6 text-ink-3 dark:text-night-ink-2">
                        اطلاعات meta tag ایمالز برای محصولات قرار داده شده است. همچنین با ارائه api زیر به ایمالز می‌توانید مکانیزم ایندکس شدن محصولات در ایمالز را به‌صورت خودکار و سریع انجام دهید.
                    </p>
                    @if (Route::has('api.emalls.products'))
                        <code class="mt-2 block select-all rounded-lg bg-surface-2 px-3 py-2 font-mono text-[11px] dark:bg-night-3" dir="ltr">{{ route('api.emalls.products') }}</code>
                    @endif
                </div>
            </div>
        </div>

        {{-- نوار ذخیره‌ی چسبان --}}
        <div class="sticky bottom-4 z-10 flex items-center justify-between gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-lg backdrop-blur dark:border-night-line dark:bg-night-2/95">
            <span class="text-[11px] text-ink-3 dark:text-night-ink-2">تغییرات پس از ذخیره روی کل سایت اعمال می‌شوند.</span>
            <button type="submit" class="ap-btn-primary !py-2.5">
                <x-admin-panel::icon name="check-circle" class="size-4" />
                ذخیره تغییرات
            </button>
        </div>
    </form>
</div>
