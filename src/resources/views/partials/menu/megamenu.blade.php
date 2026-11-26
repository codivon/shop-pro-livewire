{{-- مگامنوی اصلی سانیار (main-menu-head + mega-container) --}}
{{-- بازطراحی مطابق نمونهٔ کاربر:
     ستون راست: ردیف دسته‌ها با آیکون (آیکون بعد از عنوان، مثل نمونه)
     ستون چپ: پنل ثابت ۳ ستونه؛ گروه‌های منویی با تیتر پررنگ و فرزندان زیر خودشان --}}
<li class="nav-item main-menu-head">
    <a href="{{ $menu->link ?: '#' }}"
       class="nav-link border-animate main-color-one-bg fromCenter btn nav-active fw-bold">
        <i class="bi bi-list"></i>
        {{ $menu->title }}
    </a>
    <ul class="main-menu mega-container">
        @foreach($menu->childrenmenus as $childMenu)
            @switch($childMenu->type)
                @case('category')
                    {{-- data-cat-image: تصویر پس‌زمینهٔ دسته برای نمایش پایین-چپ پنل فرعی --}}
                    {{-- فاز ۱۸۵ — کلاس main-menu-sub-active-li «هنگام لود» روی هیچ ردیفی
                         نباید باشد؛ فقط با هاور (jquery-app.js) ردیف فعال هایلایت می‌شود --}}
                    <li @if(!empty($childMenu->category->background_image) || !empty($childMenu->category->image)) data-cat-image="{{ asset($childMenu->category->background_image ?: $childMenu->category->image) }}" @endif>
                        <a href="{{ $childMenu->category->link }}">{{ $childMenu->title ?: $childMenu->category->title }}{!! menu_icon($childMenu->category->icon) !!}</a>
                        {{-- محتوای این ul در پنل ثابت snr-mega-flyout کپی می‌شود (jquery-app.js) --}}
                        <ul class="main-menu-sub back-menu">
                            @if(!$childMenu->category->getCategoriesCount() || !$childMenu->children)
                                <li><a href="{{ $childMenu->category->link }}">{{ $childMenu->category->title }}</a></li>
                            @else
                                <li><a class="title" href="{{ $childMenu->category->link }}">همه دسته بندی های {{ $childMenu->category->title }}</a></li>
                                {{-- فاز ۱۷۷ — زیرمنوی تو در تو مثل نمونه: دسته‌ای که خودش
                                     فرزند دارد، به‌صورت گروه با تیتر پررنگ و فرزندان زیر خودش
                                     رندر می‌شود (مثل «گوشی های اپل → آیفون …») --}}
                                {{-- فاز ۲۰۰ — همهٔ زیرمنوهای درجه ۲ باید کلاس snr-mega-group
                                     و تگ a کلاس title بگیرند (به‌درخواست کاربر)؛ قبلاً فقط
                                     گروه‌هایی که فرزند درجه ۳ داشتند این کلاس‌ها را می‌گرفتند.
                                     تیتر پررنگ با نوار رنگی حالا روی «همهٔ» ستون‌ها یکدست است.
                                     فلش فقط برای گروه‌هایی که فرزند دارند نمایش داده می‌شود. --}}
                                @foreach (front_category_children($childMenu->category) as $childCategory)
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
                            @endif

                            @foreach ($childMenu->childrenmenus as $childMenu2)
                                @if($childMenu2->type === 'normal')
                                    {{-- فاز ۲۰۰ — همهٔ آیتم‌های درجه ۲ کلاس snr-mega-group و
                                         تگ a کلاس title می‌گیرند؛ لیست فرزندان فقط وقتی رندر
                                         می‌شود که آیتم واقعاً فرزند داشته باشد (منطق قدیمی
                                         menus->isEmpty() حفظ شده) --}}
                                    <li class="snr-mega-group">
                                        <a class="title" href="{{ $childMenu2->link ?: '#' }}">{{ $childMenu2->title }}</a>
                                        @if(!$childMenu2->menus->isEmpty())
                                            <ul class="snr-mega-group-list">
                                                @foreach ($childMenu2->childrenmenus as $childMenu3)
                                                    <li><a href="{{ $childMenu3->type === 'category' ? $childMenu3->category->link : ($childMenu3->link ?: '#') }}">{{ $childMenu3->type === 'category' ? ($childMenu3->title ?: $childMenu3->category->title) : $childMenu3->title }}</a></li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </li>
                    @break

                @case('normal')
                    @if($childMenu->menus->isEmpty())
                        <li>
                            <a href="{{ $childMenu->link ?: '#' }}">{{ $childMenu->title }}{!! menu_icon($childMenu->icon) !!}</a>
                        </li>
                    @else
                        <li>
                            <a href="{{ $childMenu->link ?: '#' }}">{{ $childMenu->title }}{!! menu_icon($childMenu->icon) !!}</a>
                            <ul class="main-menu-sub back-menu">
                                @foreach ($childMenu->childrenmenus as $childMenu2)
                                    <li><a href="{{ $childMenu2->type === 'category' ? $childMenu2->category->link : ($childMenu2->link ?: '#') }}">{{ $childMenu2->type === 'category' ? ($childMenu2->title ?: $childMenu2->category->title) : $childMenu2->title }}</a></li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                    @break

                @default
            @endswitch
        @endforeach
    </ul>
</li>
