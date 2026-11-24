{{-- رندر داینامیک options ابزارک بر اساس template config — پورت ۱:۱ back/widgets/template.blade.php + partials --}}
{{-- متغیرها: $options (لیست option های template)، $widget (Model|null)، $this → کامپوننت WidgetForm --}}
{{-- فاز ۱۹۱ — دفاع نهایی در لایهٔ ویو: حتی اگر دادهٔ خام/نرمال‌نشده به اینجا برسد
     (deployment ناقص، opcache قدیمی، config معیوب جدید)، هیچ آرایه‌ای echo نمی‌شود
     و خطای «Array to string conversion» در این ویو عملاً غیرممکن است. --}}
{{-- فاز ۲۰۳ — کلاس گرید کانفیگ هر قالب (مثلاً 'col-md-4 col-6' در HomeWidgets/PostsWidgets)
     با ap_widget_grid_class به ستون‌های واقعی گرید ۱۲ ستونهٔ فرم تبدیل می‌شود؛
     fallback پیش‌فرض برای فیلدهای بدون class: نصف‌عرض از sm به بالا (رفتار قبلی). --}}
@php
    $errors = $errors ?? (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag());

    /* همهٔ مقادیر اسکالر مورد استفادهٔ ویو با کمک رجیستری امن می‌شوند */
    $apOptString = fn ($value) => \App\Services\WidgetTemplateRegistry::scalarString($value);
    $apOptScalar = fn ($value) => \App\Services\WidgetTemplateRegistry::scalarOrNull($value);
    $apOptAttrs  = function ($value) {
        /* attributes یک نقطهٔ تزریق raw HTML است — فقط رشتهٔ واقعی مجاز است */
        return is_string($value) ? $value : '';
    };
    $apFieldFallback = $apFieldFallback ?? 'ap-wcol-12 ap-wcol-sm-6';
    $apFieldClass    = fn ($option) => ap_widget_grid_class(
        is_string($option['class'] ?? null) ? $option['class'] : null,
        $apFieldFallback
    );
@endphp
@foreach ($options as $option)
    @php $optionKey = $option['key']; $errorKey = 'optionValues.' . $optionKey; @endphp

    @switch($option['input-type'])

        @case('input')
            {{-- پورت back/widgets/partials/input.blade.php — عرض از کلاس کانفیگ قالب (فاز ۲۰۳) --}}
            <div class="{{ $apFieldClass($option) }}">
                <label class="mb-1.5 block text-xs font-semibold" for="option-input-{{ $optionKey }}">
                    {{ $apOptString($option['title'] ?? $optionKey) }}
                </label>
                <input id="option-input-{{ $optionKey }}"
                       type="{{ $apOptScalar($option['type'] ?? 'text') }}"
                       wire:model="optionValues.{{ $optionKey }}"
                       class="ap-input w-full"
                       value="{{ $this->optionValue($optionKey) ?? $apOptScalar($option['default'] ?? '') }}"
                       {!! $apOptAttrs($option['attributes'] ?? null) !!}>
                @isset($option['help'])
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $apOptString($option['help']) }}</p>
                @endisset
                @error($errorKey) <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            @break

        @case('select')
            {{-- پورت back/widgets/partials/select.blade.php — عرض از کلاس کانفیگ قالب (فاز ۲۰۳) --}}
            <div class="{{ $apFieldClass($option) }}">
                <label class="mb-1.5 block text-xs font-semibold" for="option-select-{{ $optionKey }}">
                    {{ $apOptString($option['title'] ?? $optionKey) }}
                </label>
                <select id="option-select-{{ $optionKey }}"
                        wire:model="optionValues.{{ $optionKey }}"
                        class="ap-input w-full"
                        {!! $apOptAttrs($option['attributes'] ?? null) !!}>
                    @foreach (($option['options'] ?? []) as $item)
                        @php $item = is_array($item) ? $item : ['value' => $item, 'title' => $item]; @endphp
                        <option value="{{ $apOptScalar($item['value'] ?? null) }}" @selected($this->optionValue($optionKey) == $item['value'])>
                            {{ $apOptString($item['title'] ?? ($item['value'] ?? '')) }}
                        </option>
                    @endforeach
                </select>
                @isset($option['help'])
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $apOptString($option['help']) }}</p>
                @endisset
                @error($errorKey) <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            @break

        @case('file')
            {{-- پورت back/widgets/partials/file.blade.php + آپلود WithFileUploads — عرض از کلاس کانفیگ قالب (فاز ۲۰۳) --}}
            <div class="{{ $apFieldClass($option) }}">
                <label class="mb-1.5 block text-xs font-semibold" for="option-file-{{ $optionKey }}">
                    {{ $option['title'] }}
                </label>

                <input id="option-file-{{ $optionKey }}" type="file"
                       wire:model="fileUploads.{{ $optionKey }}"
                       class="block w-full cursor-pointer rounded-xl border border-line bg-transparent text-xs text-ink-2
                              file:me-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-1.5
                              file:text-xs file:font-semibold file:text-white hover:file:bg-brand-700 dark:border-night-line"
                       {!! $apOptAttrs($option['attributes'] ?? null) !!}>

                @if (isset($this->fileUploads[$optionKey]))
                    <p class="mt-1.5 flex items-center gap-1.5 text-[11px] font-semibold text-brand-600 dark:text-brand-400">
                        <x-admin-panel::icon name="upload" class="size-3.5" />
                        فایل جدید: {{ $this->fileUploads[$optionKey]->getClientOriginalName() }}
                    </p>
                @elseif ($this->optionValue($optionKey))
                    <p class="mt-1.5 flex items-center gap-2 text-[11px] text-ink-3 dark:text-night-ink-2">
                        <x-admin-panel::icon name="check-circle" class="size-3.5 text-emerald-500" />
                        فایل فعلی: <span dir="ltr">{{ $this->optionValue($optionKey) }}</span>
                        @if (preg_match('/\.(png|jpe?g|gif|webp|svg)$/i', (string) $this->optionValue($optionKey)))
                            <img src="{{ asset($this->optionValue($optionKey)) }}" alt="{{ $option['title'] }}"
                                 class="size-8 rounded-lg border border-line object-cover dark:border-night-line">
                        @endif
                    </p>
                @endif

                @isset($option['help'])
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $option['help'] }}</p>
                @endisset
                @error($errorKey) <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
            @break

        @case('post_categories')
        @case('product_categories')
            {{-- پورت back/widgets/partials/{post,product}-categories.blade.php — درخت چک‌باکس به‌جای select2ToTree --}}
            @php
                $categoriesList = $option['input-type'] === 'post_categories'
                    ? $this->postCategories
                    : $this->productCategories;
                $selectedIds = is_array($this->optionValues[$optionKey] ?? null) ? $this->optionValues[$optionKey] : [];
            @endphp
            {{-- : col-span-full (سازگار با گرید تک‌ستونه مودال و گرید دوستونه فرم) --}}
            <div class="col-span-full">
                <label class="mb-1.5 block text-xs font-semibold">{{ $apOptString($option['title'] ?? $optionKey) }}</label>
                {{-- : انتخابگر کارتی به‌جای چک‌باکس خام --}}
                <div class="max-h-72 overflow-y-auto rounded-xl border border-line p-3 dark:border-night-line">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @forelse ($categoriesList as $category)
                            @php $depth = $category->parents()->count(); @endphp
                            <label class="ap-check-card" style="margin-inline-start: {{ max(0, $depth - 1) * 14 }}px">
                                <input type="checkbox" value="{{ $category->id }}"
                                       wire:model="optionValues.{{ $optionKey }}">
                                <span class="ap-check-mark" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <span class="ap-check-card-body truncate {{ $depth > 0 ? 'text-ink-2 dark:text-night-ink-2' : 'font-semibold' }}">
                                    {{ $category->title }}
                                </span>
                            </label>
                        @empty
                            <p class="py-3 text-center text-xs text-ink-3 dark:text-night-ink-2">دسته‌بندی‌ای یافت نشد</p>
                        @endforelse
                    </div>
                </div>
                @isset($option['help'])
                    <p class="mt-1 text-[11px] text-ink-3 dark:text-night-ink-2">{{ $apOptString($option['help']) }}</p>
                @endisset
                @error($errorKey) <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            </div>
@break

    @endswitch
@endforeach
