{{-- هدر وبلاگ سانیار (مطابق blog.html تمپلیت) --}}
{{-- جستجوی مقالات + فهرست دسته‌بندی‌ها (سایدبار) + بنر + خبرهای داغ --}}
{{-- استایلها در style.css تمپلیت (بخش .side-right-blog / .blog-news-item / .navbar-blog) --}}
{{-- فاز ۲۰۶ — بنر از ویجت‌های بنر (تکی/دوتایی/سه‌تایی/چهارتایی) با گروه نمایش
     (گروه اول تا چهارم) از تنظیمات ابزارک؛ ارتفاع سایدبار دسته‌بندی‌ها همیشه
     هم‌ترازِ ستون بنر/خبرهاست و اگر دسته‌بندی‌ها زیاد شوند اسکرول داخلی می‌گیرد
     (کلاس snr-blog-side--sync فقط وقتی ستون چپ وجود دارد فعال می‌شود) --}}
@php
    $variables            = get_widget($widget);
    $blogHeaderCategories = $variables['blog_header_categories'] ?? collect();
    $blogHeaderBanners    = $variables['blog_header_banners'] ?? collect();
    $blogHeaderNews       = $variables['blog_header_news'] ?? collect();

    /* آیکون‌های چرخشی فهرست دسته‌بندی‌ها (مطابق تمپلیت) */
    $blogCatIcons = ['bi-newspaper', 'bi-bounding-box-circles', 'bi-controller', 'bi-journal-richtext', 'bi-film', 'bi-cup-hot', 'bi-tag', 'bi-book'];

    /* ستون چپ (بنر + خبرهای داغ) — شرط هم‌ترازی ارتفاع سایدبار */
    $blogHeaderHasLeft = count($blogHeaderBanners) || count($blogHeaderNews);
    $blogHeaderHasSide = $widget->option('show_search', 'yes') === 'yes'
        || ($widget->option('show_categories', 'yes') === 'yes' && count($blogHeaderCategories));

    /* ستون‌بندی گرید بنر بر اساس تعداد (دوتایی: کنار هم، چهارتایی: ۴ ستون) */
    $blogBannerCol = [
        1 => 'col-12',
        2 => 'col-md-6 col-12',
        3 => 'col-md-4 col-12',
        4 => 'col-md-3 col-12',
    ][min(count($blogHeaderBanners), 4)] ?? 'col-12';
@endphp

@if($blogHeaderHasSide || $blogHeaderHasLeft)
    <div class="container-fluid py-20">
        <div class="row gy-3 snr-blog-header-row">
            {{-- سایدبار: جستجو + دسته‌بندی‌ها --}}
            @if($blogHeaderHasSide)
                <div class="col-lg-3 snr-blog-side {{ $blogHeaderHasLeft ? 'snr-blog-side--sync' : '' }}">
                    <div class="content-box h-100">
                        <div class="container-fluid">
                            <div class="side-right-blog">
                                @if($widget->option('show_search', 'yes') === 'yes')
                                    <div class="search-form">
                                        <form action="{{ route('front.articles.index') }}" method="get" role="search">
                                            <div class="search-filed">
                                                <label class="sr-only" for="blog-header-search">جستجوی مقالات</label>
                                                <input type="text" id="blog-header-search" name="search" value="{{ request('search') }}"
                                                       placeholder="جستجوی مقالات ..." class="form-control search-input">
                                                <button type="submit" class="btn search-btn main-color-one-bg rounded-pill" aria-label="جستجو">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                @endif

                                @if($widget->option('show_categories', 'yes') === 'yes' && count($blogHeaderCategories))
                                    <div class="navbar-blog">
                                        <nav class="navbar" aria-label="دسته بندی مقالات">
                                            <ul class="navbar-nav">
                                                <li class="nav-item mco-hover">
                                                    <a href="{{ route('front.articles.index') }}" class="nav-link">
                                                        <i class="bi bi-newspaper font-25 me-3"></i>جدیدترین مطالب
                                                    </a>
                                                </li>
                                                @foreach($blogHeaderCategories as $blogHeaderCategory)
                                                    <li class="nav-item">
                                                        <a href="{{ route('front.articles.index').'?cat='.$blogHeaderCategory->slug }}" class="nav-link">
                                                            <i class="bi {{ $blogCatIcons[$loop->index % count($blogCatIcons)] }} font-25 me-3"></i>{{ $blogHeaderCategory->title }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </nav>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- بنر (از ویجت‌های بنر: تکی/دوتایی/سه‌تایی/چهارتایی) + خبرهای داغ --}}
            @if($blogHeaderHasLeft)
                <div class="{{ $blogHeaderHasSide ? 'col-lg-9' : 'col-12' }}">
                    <div class="side-left-blog">
                        <div class="row gy-3">
                            @if(count($blogHeaderBanners))
                                @foreach($blogHeaderBanners as $blogHeaderBanner)
                                    <div class="{{ $blogBannerCol }}">
                                        @if($blogHeaderBanner->link)
                                            <a href="{{ $blogHeaderBanner->link }}" aria-label="{{ $blogHeaderBanner->title ?: 'بنر' }}">
                                                <img src="{{ $blogHeaderBanner->image ? asset($blogHeaderBanner->image) : asset('/no-image-product.svg') }}"
                                                     class="img-fluid shadow-box rounded-4 snr-blog-banner-img" loading="lazy" decoding="async"
                                                     alt="{{ $blogHeaderBanner->title ?: 'بنر' }}">
                                            </a>
                                        @else
                                            <img src="{{ $blogHeaderBanner->image ? asset($blogHeaderBanner->image) : asset('/no-image-product.svg') }}"
                                                 class="img-fluid shadow-box rounded-4 snr-blog-banner-img" loading="lazy" decoding="async"
                                                 alt="{{ $blogHeaderBanner->title ?: 'بنر' }}">
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            @foreach($blogHeaderNews as $blogHeaderNewsItem)
                                <div class="{{ $loop->index === 1 ? 'col-md-6' : 'col-md-3' }} col-12">
                                    <div class="blog-news-item">
                                        <a href="{{ route('front.articles.show', ['slug' => $blogHeaderNewsItem->slug]) }}" aria-label="{{ $blogHeaderNewsItem->title }}">
                                            <img src="{{ $blogHeaderNewsItem->image ? asset($blogHeaderNewsItem->image) : asset('/no-image-product.svg') }}"
                                                 @if(! $loop->first) loading="lazy" decoding="async" @endif
                                                 alt="{{ $blogHeaderNewsItem->title }}" class="img-fluid">
                                            <div class="desc">
                                                <h3 class="title">
                                                    {{-- فاز ۲۰۶ — نشان ویدیو (پلی) / پادکست (هدفون) --}}
                                                    @if($blogHeaderNewsItem->post_type === 'video')
                                                        <i class="bi bi-play-btn-fill main-color-one-color me-1"></i>
                                                    @elseif($blogHeaderNewsItem->post_type === 'podcast')
                                                        <i class="bi bi-headphones main-color-one-color me-1"></i>
                                                    @endif
                                                    {{ $blogHeaderNewsItem->title }}
                                                </h3>
                                                <div class="date">
                                                    <span class="text-date">{{ jdate($blogHeaderNewsItem->created_at)->format('d F Y') }}</span>
                                                    <i class="bi bi-arrow-left"></i>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
