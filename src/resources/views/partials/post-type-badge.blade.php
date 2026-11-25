{{-- نشان ویدیو/پادکست روی تصویر مقاله — مشترک بین همهٔ بخش‌های نمایش مقالات --}}
{{-- فاز ۲۰۶ — ویدیو: آیکون پلی / پادکست: آیکون هدفون --}}
{{-- استفاده: @include('front::partials.post-type-badge', ['post' => $post]) --}}
@php
    $postTypeBadgePost = $post ?? ($article ?? null);
@endphp
@if($postTypeBadgePost && ($postTypeBadgePost->post_type === 'video' || $postTypeBadgePost->post_type === 'podcast'))
    @if($postTypeBadgePost->post_type === 'video')
        <span class="snr-type-badge" title="ویدیو" aria-label="ویدیو"><i class="bi bi-play-btn-fill"></i></span>
    @else
        <span class="snr-type-badge" title="پادکست" aria-label="پادکست"><i class="bi bi-headphones"></i></span>
    @endif
@endif
