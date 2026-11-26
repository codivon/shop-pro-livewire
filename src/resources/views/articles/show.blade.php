@extends('front::layouts.master', ['title' => $post->meta_title ?: $post->title])

@section('og_image')
    <meta property="og:image" content="{{ asset($post->image) }}">
@endsection

@push('meta')
    <meta property="og:type" content="article">
    <meta property="og:description" content="{{ $post->meta_description ?? $post->short_description }}">
    <meta name="description" content="{{ $post->meta_description ?? $post->short_description }}">
    <meta name="twitter:description" content="{{ $post->meta_description ?? $post->short_description }}">
    <meta name="twitter:image" content="{{ asset($post->image) }}">
    <link rel="canonical" href="{{ route('front.articles.show', $post) }}">
@endpush

@push('styles')
    <link rel="stylesheet" href="{{theme_asset('js/plugins/plyr/plyr.css')}}" />
    {{-- فاز ۲۰۲ — استایل بازطراحی تک‌صفحه مقاله --}}
    <link rel="stylesheet" href="{{ theme_asset('css/pages/articles/show.css') }}?v=2">
@endpush

@section('content')
    {{-- Breadcrumb — سانیار bread-crumb --}}
    <div class="bread-crumb pt-3">
        <div class="container-fluid">
            <div class="content-box">
                <div class="container-fluid">
                    <nav aria-label="breadcrumb" class="my-lg-0 my-2">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ route('front.articles.index') }}" class="font-14 text-muted">مجله {{ option('info_site_title') }}</a>
                            </li>
                            @if($post->categories->isNotEmpty())
                                <li class="breadcrumb-item">
                                    <a href="{{ route('front.articles.index', ['cat' => $post->categories->first()->slug]) }}" class="font-14 text-muted">
                                        {{ $post->categories->first()->title }}
                                    </a>
                                </li>
                            @endif
                            <li class="breadcrumb-item active main-color-one-color font-14" aria-current="page">{{ $post->title }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    @php
        $readingTime = ceil(str_word_count(strip_tags($post->body ?: $post->content)) / 200);
        $isVideo = $post->post_type == 'video' && $post->video_url;
        $isPodcast = $post->post_type == 'podcast' && $post->podcast_url;
        $isMedia = $isVideo || $isPodcast;
        $mediaUrl = $isVideo ? $post->video_url : ($isPodcast ? $post->podcast_url : null);
    @endphp

    <div class="content article-page snr-article-page">
        <div class="container-fluid py-3">
            <div class="row gy-4">
                {{-- ================= ستون اصلی ================= --}}
                <div class="col-lg-9">
                    {{-- هدر مقاله --}}
                    <div class="snr-article-hero">
                        <div class="snr-article-hero-top">
                            @if($post->categories->isNotEmpty())
                                <a href="{{ route('front.articles.index', ['cat' => $post->categories->first()->slug]) }}"
                                   class="snr-article-cat-badge">
                                    <i class="bi bi-tag-fill"></i>
                                    {{ $post->categories->first()->title }}
                                </a>
                            @endif
                            @if($post->is_editor_pick)
                                <span class="snr-article-pick-badge"><i class="bi bi-award-fill"></i> انتخاب سردبیر</span>
                            @endif
                        </div>

                        <h1 class="snr-article-title">{{ $post->title }}</h1>

                        @if($post->short_description)
                            <p class="snr-article-lead">{{ $post->short_description }}</p>
                        @endif

                        <div class="snr-article-meta">
                            @if($post->admin)
                                <a href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}" class="snr-meta-chip snr-meta-author">
                                    <img src="{{ $post->admin->imageUrl }}" class="rounded-circle" width="30" height="30" alt="{{ $post->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                                    <span>{{ $post->admin->full_name ?? 'ناشناس' }}</span>
                                </a>
                            @endif
                            <span class="snr-meta-chip"><i class="bi bi-calendar3"></i> {{ jdate($post->created_at)->format('d F Y') }}</span>
                            <span class="snr-meta-chip"><i class="bi bi-clock-history"></i> {{ $readingTime }} دقیقه مطالعه</span>
                            <span class="snr-meta-chip"><i class="bi bi-eye-fill"></i> {{ number_format($post->view) }} بازدید</span>
                            <span class="snr-meta-chip"><i class="bi bi-chat-dots"></i> {{ $post->acceptedComments()->count() }} دیدگاه</span>
                        </div>

                        {{-- نوار لایک و اشتراک گذاری — قرارداد show.js حفظ شده --}}
                        <div class="snr-article-actions">
                            <button class="like-button btn light-btn shadow-sm rounded-3 border-0 d-flex align-items-center" data-action="{{ route("front.articles.like") }}" data-post-id="{{ $post->id }}">
                                <i class="bi bi-heart main-color-one-color {{ $isLiked ? 'liked' : '' }}"></i>
                                <span class="likes-count ms-1">{{ $post->likes_count ?? 0 }}</span>
                            </button>
                            <a href="#article-comments" class="btn light-btn shadow-sm rounded-3 border-0 d-flex align-items-center">
                                <i class="bi bi-chat-dots main-color-one-color"></i>
                            </a>
                            <span class="snr-share-label"><i class="bi bi-share"></i> اشتراک گذاری:</span>
                            <div class="snr-share-links">
                                <a href="https://telegram.me/share/url?url={{ urlencode(request()->url()) }}" target="_blank" rel="noopener" class="snr-share-btn snr-share-telegram" aria-label="تلگرام"><i class="bi bi-telegram"></i></a>
                                <a href="https://wa.me/?text={{ urlencode(request()->url()) }}" target="_blank" rel="noopener" class="snr-share-btn snr-share-whatsapp" aria-label="واتساپ"><i class="bi bi-whatsapp"></i></a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" target="_blank" rel="noopener" class="snr-share-btn snr-share-facebook" aria-label="فیسبوک"><i class="bi bi-facebook"></i></a>
                                <a href="https://twitter.com/intent/tweet?text={{ urlencode(request()->url()) }}" target="_blank" rel="noopener" class="snr-share-btn snr-share-twitter" aria-label="توییتر"><i class="bi bi-twitter"></i></a>
                                <button type="button" class="snr-share-btn snr-share-copy" aria-label="کپی لینک" data-url="{{ request()->url() }}"><i class="bi bi-link-45deg"></i></button>
                            </div>
                        </div>
                    </div>

                    {{-- بدنه مقاله --}}
                    <div class="snr-article-card">
                        <div class="snr-article-media">
                            @if($isMedia)
                                <video class="articles-video-player shadow-box rounded-3 w-100" controls poster="{{ $post->image ? asset($post->image) : theme_asset('images/blog-1.jpg') }}">
                                    <source src="{{ asset($mediaUrl) }}" type="video/mp4">
                                    مرورگر شما از ویدیو پشتیبانی نمی‌کند.
                                </video>
                            @else
                                <figure class="snr-article-figure mb-0">
                                    <img src="{{ $post->image ? asset($post->image) : theme_asset('images/blog-1.jpg') }}" alt="{{ $post->title }}" loading="lazy">
                                </figure>
                            @endif
                        </div>

                        {{-- متن مقاله — تایپوگرافی اختصاصی در show.css --}}
                        <div class="snr-article-body">
                            {!! $post->content !!}
                        </div>

                        {{-- هشتگ‌ها --}}
                        @if($post->tags->isNotEmpty())
                            <div class="snr-article-tags">
                                <i class="bi bi-hash snr-tags-ico"></i>
                                <ul class="hashtags mb-0">
                                    @foreach($post->tags as $tag)
                                        <li>
                                            <a href="{{ route('front.articles.index', ['tag' => $tag->slug]) }}">
                                                {{ $tag->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- منبع --}}
                        @if($post->source)
                            <div class="snr-article-source">
                                <i class="bi bi-link-45deg"></i> منبع: {{ $post->source }}
                            </div>
                        @endif
                    </div>

                    {{-- کارت نویسنده --}}
                    @if($post->admin)
                        <div class="snr-author-card">
                            <img class="snr-author-avatar" src="{{ $post->admin->imageUrl }}" alt="{{ $post->admin->full_name ?? 'ناشناس' }}" loading="lazy">
                            <div class="snr-author-info">
                                <h5 class="snr-author-name">
                                    <a href="{{ route('front.articles.index', ['profile' => $post->admin->username]) }}" class="text-decoration-none">
                                        {{ $post->admin->full_name ?? 'ناشناس' }}
                                    </a>
                                    <span class="badge rounded-pill main-color-one-bg text-white"><i class="bi bi-patch-check-fill"></i> مدیریت</span>
                                </h5>
                                <p class="snr-author-bio">{{ $post->admin->bio ?? 'توضیحاتی برای نویسنده ثبت نشده است.' }}</p>
                                <div class="snr-author-foot">
                                    <span><i class="bi bi-newspaper"></i> منتشر شده: <b>{{ $post->admin->published_posts_count }}</b></span>
                                    <span><i class="bi bi-calendar3"></i> عضویت: <b>{{ jdate($post->admin->created_at)->format('d F Y') }}</b></span>
                                    <div class="snr-author-socials">
                                        @php
                                            $socials = [
                                                'instagram' => ['icon' => '<i class="bi bi-instagram"></i>', 'name' => 'اینستاگرام'],
                                                'whatsapp' => ['icon' => '<i class="bi bi-whatsapp"></i>', 'name' => 'واتساپ'],
                                                'telegram' => ['icon' => '<i class="bi bi-telegram"></i>', 'name' => 'تلگرام'],
                                                'twitter' => ['icon' => '<i class="bi bi-twitter"></i>', 'name' => 'توییتر'],
                                                'facebook' => ['icon' => '<i class="bi bi-facebook"></i>', 'name' => 'فیسبوک'],
                                                'rubika' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/rubika.png').'" width="16" alt="روبیکا">', 'name' => 'روبیکا'],
                                                'eitaa' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/eita.png').'" width="16" alt="ایتا">', 'name' => 'ایتا'],
                                                'bale' => ['icon' => '<img src="'.asset('back/app-assets/images/ico/bale.png').'" width="16" alt="بله">', 'name' => 'بله'],
                                            ];
                                        @endphp

                                        @foreach($socials as $platform => $info)
                                            @php $link = $post->admin->getSocialLink($platform); @endphp
                                            @if($link)
                                                <a href="{{ $link }}" class="snr-author-social" title="{{ $info['name'] }}" target="_blank" rel="noopener noreferrer">
                                                    {!! $info['icon'] !!}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ================= سایدبار ================= --}}
                <div class="col-lg-3">
                    <div class="snr-article-side position-sticky">
                        {{-- آخرین مقالات --}}
                        <div class="snr-side-card">
                            <div class="snr-side-head">
                                <i class="bi bi-clock-history"></i>
                                <h6>آخرین مقالات</h6>
                                <a href="{{ route('front.articles.index') }}" class="snr-side-more">مشاهده همه</a>
                            </div>
                            <div class="snr-side-body">
                                @forelse(($latestPosts ?? collect())->take(5) as $latestPost)
                                    <a href="{{ route('front.articles.show', $latestPost->slug) }}" class="snr-mini-post">
                                        <div class="snr-mini-post-img">
                                            <img src="{{ $latestPost->image ? asset($latestPost->image) : theme_asset('images/blog-1.jpg') }}" alt="{{ $latestPost->title }}" loading="lazy">
                                        </div>
                                        <div class="snr-mini-post-body">
                                            <h5>{{ $latestPost->title }}</h5>
                                            <span class="snr-mini-post-date">
                                                <i class="bi bi-calendar3"></i>
                                                {{ jdate($latestPost->created_at)->format('d F Y') }}
                                            </span>
                                        </div>
                                        <i class="bi bi-chevron-left snr-mini-post-arrow"></i>
                                    </a>
                                @empty
                                    <p class="snr-side-empty">هنوز مقاله‌ای ثبت نشده است.</p>
                                @endforelse
                            </div>
                        </div>

                        {{-- داغ ترین مطالب --}}
                        @if(($trendingPosts ?? collect())->isNotEmpty())
                            <div class="snr-side-card">
                                <div class="snr-side-head">
                                    <i class="bi bi-fire"></i>
                                    <h6>داغ‌ترین مطالب</h6>
                                </div>
                                <div class="snr-side-body snr-trending-list">
                                    @foreach($trendingPosts as $trendingPost)
                                        <a href="{{ route('front.articles.show', $trendingPost->slug) }}" class="snr-trending-item">
                                            <div class="snr-trending-img">
                                                <img src="{{ $trendingPost->image ? asset($trendingPost->image) : theme_asset('images/blog-1.jpg') }}" alt="{{ $trendingPost->title }}" loading="lazy">
                                                <span class="snr-trending-views"><i class="bi bi-eye-fill"></i> {{ number_format($trendingPost->view) }}</span>
                                            </div>
                                            <span class="snr-trending-title">{{ $trendingPost->title }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- هشتگ های داغ --}}
                        @if(($hotTags ?? collect())->isNotEmpty())
                            <div class="snr-side-card">
                                <div class="snr-side-head">
                                    <i class="bi bi-hash"></i>
                                    <h6>هشتگ‌های داغ</h6>
                                </div>
                                <div class="snr-side-body">
                                    <ul class="hashtags snr-hashtags">
                                        @foreach($hotTags as $tag)
                                            <li>
                                                <a href="{{ route('front.articles.index', ['tag' => $tag->slug]) }}">
                                                    {{ $tag->name }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ================= مقالات مرتبط (اسلایدر) ================= --}}
            @if(($relatedPosts ?? collect())->isNotEmpty())
                <div class="snr-related mt-4">
                    <div class="snr-related-head">
                        <div class="snr-related-title">
                            <i class="bi bi-grid-fill"></i>
                            <h4>مطالب مرتبط <span class="main-color-one-color">وبلاگ</span></h4>
                        </div>
                        <a href="{{ route('front.articles.index') }}" class="snr-related-all">
                            مشاهده همه
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </div>

                    <div class="swiper snr-related-swiper js-related-posts">
                        <div class="swiper-wrapper">
                            @foreach($relatedPosts as $relatedPost)
                                <div class="swiper-slide">
                                    <a href="{{ route('front.articles.show', $relatedPost->slug) }}" class="snr-related-card">
                                        <div class="snr-related-img">
                                            <img src="{{ $relatedPost->image ? asset($relatedPost->image) : theme_asset('images/blog-1.jpg') }}" alt="{{ $relatedPost->title }}" loading="lazy">
                                            @if($relatedPost->post_type == 'video')
                                                <span class="snr-related-video"><i class="bi bi-play-circle-fill"></i></span>
                                            @elseif($relatedPost->post_type == 'podcast')
                                                <span class="snr-related-video"><i class="bi bi-headphones"></i></span>
                                            @endif
                                        </div>
                                        <div class="snr-related-body">
                                            <div class="snr-related-meta">
                                                <span><i class="bi bi-tag"></i> {{ $relatedPost->categories->first()->title ?? 'مجله' }}</span>
                                                <span><i class="bi bi-calendar3"></i> {{ jdate($relatedPost->created_at)->format('d F Y') }}</span>
                                            </div>
                                            <h5>{{ $relatedPost->title }}</h5>
                                            <span class="snr-related-link">ادامه مطلب <i class="bi bi-arrow-left"></i></span>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <div class="swiper-button-next d-lg-flex d-none"></div>
                        <div class="swiper-button-prev d-lg-flex d-none"></div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- نظرات --}}
    @include('front::articles.partials.comments', ['post' => $post])
@endsection

@push('scripts')
    <script src="{{theme_asset('js/plugins/plyr/plyr.js')}}"></script>
    <script src="{{ theme_asset('js/pages/comments.js') }}"></script>
    <script src="{{ theme_asset('js/pages/articles/show.js') }}?v=4"></script>
@endpush
