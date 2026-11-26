{{-- منوی محصولات (static_type=products) — مگامنوی اصلی سانیار --}}
<li class="nav-item main-menu-head">
    <a href="{{ route('front.products.index') }}"
       class="nav-link border-animate main-color-one-bg fromCenter btn nav-active fw-bold">
        <i class="{{ icon_class($menu->icon) }}" @if(icon_style($menu->icon)) style="{{ icon_style($menu->icon) }}" @endif></i>
        {{ $menu->title }}
    </a>
    @if(count($productcats))
        <ul class="main-menu mega-container">
            @foreach ($productcats as $category)
                {{-- تصویر پس‌زمینهٔ دسته برای نمایش پایین-چپ پنل فرعی مگامنو --}}
                {{-- فاز ۱۸۵ — کلاس main-menu-sub-active-li هنگام لود حذف شد (فقط با هاور) --}}
                <li @if(!empty($category->background_image) || !empty($category->image)) data-cat-image="{{ asset($category->background_image ?: $category->image) }}" @endif>
                    {{-- آیکون بعد از عنوان (مطابق نمونهٔ منوی کاربر) --}}
                    <a href="{{ $category->link }}">{{ $category->title }}<i class="{{ icon_class($category->icon) }}" @if(icon_style($category->icon)) style="{{ icon_style($category->icon) }}" @endif></i></a>
                    @if ($category->getCategoriesCount())
                        <ul class="main-menu-sub back-menu">
                            <li><a class="title my-flex-baseline" href="{{ $category->link }}">همه دسته بندی های {{ $category->title }}</a></li>
                            {{-- FIX مقیاس: از relation ازقبل-لود شدهٔ getWithChilds استفاده می‌شود
                            (قبلاً به‌ازای هر دستهٔ ریشه یک کوئری اجرا می‌شد = N+1) --}}
                            {{-- فاز ۱۸۵ — زیرمنوی تو در تو مثل مگامنو: هر زیرمنو «زیر زیرمنوی پدرش»
                                 در ستون چپ (پنل فرعی) رندر می‌شود؛ دسته‌ای که خودش فرزند دارد
                                 به‌صورت گروه با تیتر پررنگ و فرزندان زیر خودش می‌آید --}}
                            {{-- فاز ۲۰۰ — همهٔ زیرمنوهای درجه ۲ کلاس snr-mega-group و تگ a
                                 کلاس title می‌گیرند (به‌درخواست کاربر)؛ فلش فقط برای گروه‌های دارای فرزند --}}
                            @foreach ($category->categories->where('published', true)->values() as $childCategory)
                                @if (front_category_children($childCategory)->isNotEmpty())
                                    <li class="snr-mega-group">
                                        <a class="title" href="{{ $childCategory->link }}">{{ $childCategory->title }}<i class="bi bi-chevron-left"></i></a>
                                        <ul class="snr-mega-group-list">
                                            @foreach (front_category_children($childCategory) as $subChildCategory)
                                                <li><a href="{{ $subChildCategory->link }}">{{ $subChildCategory->title }}</a></li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @else
                                    <li class="snr-mega-group">
                                        <a class="title" href="{{ $childCategory->link }}">{{ $childCategory->title }}</a>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</li>
