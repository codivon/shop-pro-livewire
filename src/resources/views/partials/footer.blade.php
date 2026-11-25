{{-- فوتر سانیار — فاز ۲۰۴ (بازطراحی دقیق مطابق نمونهٔ کاربر) --}}
{{-- ساختار: نوار تماس (تلفن/ایمیل/آدرس/شبکه‌های اجتماعی) → ستون‌های لینک
     → نوار خبرنامه → دربارهٔ ما (لوگو + نشان‌های اعتماد + متن) → نوار کپی‌رایت --}}
<footer class="footer snr-footer">
    <div class="container-fluid">

        {{-- فاز ۱۹۳/۲۰۴ — فیلدهای بدون مقدار رندر نمی‌شوند تا فاصلهٔ خالی نسازند --}}
        @php
            $footerHasTel      = trim((string) option('info_tel', '')) !== '';
            $footerSupportTel  = trim((string) option('info_support_phone', '')) !== '';
            $footerHasEmail    = trim((string) option('info_email', '')) !== '';
            /* فاز ۲۰۴ — ریشه‌یابی «متن زیر عنوان آدرس، آدرس نیست»:
               قبلاً info_footer_text (متن بلند سئوی فوتر) زیر عنوانِ «آدرس شرکت»
               رندر می‌شد؛ آدرس واقعی فروشگاه در option('info_address') است
               (همان فیلدی که صفحهٔ تماس هم استفاده می‌کند). */
            $footerAddress     = trim((string) option('info_address', ''));
            $footerSocialKeys  = [
                'social_telegram'  => ['bi-telegram',      'تلگرام'],
                'social_instagram' => ['bi-instagram',     'اینستاگرام'],
                'social_whatsapp'  => ['bi-whatsapp',      'واتس‌اپ'],
                'social_aparat'    => ['bi-play-btn-fill', 'آپارات'],
                'social_eitaa'     => ['bi-send-fill',     'ایتا'],
                'social_rubika'    => ['bi-gem',           'روبیکا'],
                'social_bale'      => ['bi-chat-square',   'بله'],
                'social_x'         => ['bi-twitter-x',     'توییتر (ایکس)'],
                'social_facebook'  => ['bi-facebook',      'فیسبوک'],
                'social_youtube'   => ['bi-youtube',       'یوتیوب'],
                'social_linkedin'  => ['bi-linkedin',      'لینکدین'],
            ];
            /* فاز ۲۰۴ — قبلاً کلید ناموجودِ social_twitter چک می‌شد و بقیهٔ
               شبکه‌های پنل (آپارات/ایتا/روبیکا/بله/ایکس/فیسبوک/لینکدین) اصلاً
               دیده نمی‌شدند؛ اکنون همهٔ کلیدهای صفحهٔ تنظیمات شبکه‌های اجتماعی
               پشتیبانی می‌شوند. */
            $footerSocials     = collect($footerSocialKeys)
                ->filter(fn ($v, $k) => trim((string) option($k, '')) !== '');
            $footerHasSocials  = $footerSocials->isNotEmpty();
            $footerFilledGroups = collect($footer_links)->filter(
                fn ($group) => $links->where('link_group_id', $group['key'])->isNotEmpty()
            );
            /* متن بلند «دربارهٔ ما» مثل نمونه: اول متن فوتر، بعد درباره‌ما، بعد توضیح کوتاه */
            $footerAboutText = trim((string) option('info_footer_text', ''))
                ?: trim((string) option('info_about_us', ''))
                ?: trim((string) option('info_short_description', ''));
        @endphp

        {{-- ۱) نوار تماس — آیکون + عنوان در یک سطر، مقدار زیرش (مطابق نمونه) --}}
        @if ($footerHasTel || $footerHasEmail || trim((string) $footerAddress) !== '' || $footerHasSocials)
        <div class="snr-footer-contact">
            <div class="snr-footer-contact-grid">
                @if ($footerHasTel)
                <div class="snr-contact-cell">
                    <h4 class="snr-contact-title">
                        <i class="bi bi-telephone" aria-hidden="true"></i>
                        <span>شماره‌های ارتباطی</span>
                    </h4>
                    <div class="snr-contact-value" dir="rtl">
                        <a href="tel:{{ preg_replace('/\s+/', '', option('info_tel', '')) }}" dir="ltr">{{ option('info_tel', '') }}</a>
                        @if ($footerSupportTel)
                            <span class="snr-contact-sep">-</span>
                            <a href="tel:{{ preg_replace('/\s+/', '', option('info_support_phone', '')) }}" dir="ltr">{{ option('info_support_phone', '') }}</a>
                        @endif
                    </div>
                </div>
                @endif
                @if ($footerHasEmail)
                <div class="snr-contact-cell">
                    <h4 class="snr-contact-title">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <span>ایمیل‌های ارتباطی</span>
                    </h4>
                    <div class="snr-contact-value">
                        <a href="mailto:{{ option('info_email', '') }}" dir="ltr">{{ option('info_email', '') }}</a>
                    </div>
                </div>
                @endif
                @if (trim((string) $footerAddress) !== '')
                <div class="snr-contact-cell">
                    <h4 class="snr-contact-title">
                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                        <span>آدرس شرکت</span>
                    </h4>
                    <div class="snr-contact-value">
                        <span>{{ $footerAddress }}</span>
                    </div>
                </div>
                @endif
                @if ($footerHasSocials)
                <div class="snr-contact-cell">
                    <h4 class="snr-contact-title">
                        <i class="bi bi-share" aria-hidden="true"></i>
                        <span>فروشگاه در رسانه‌ها</span>
                    </h4>
                    <div class="snr-contact-value">
                        <div class="snr-footer-socials">
                            @foreach($footerSocials as $key => [$icon, $label])
                                <a href="{{ option($key) }}" target="_blank" rel="noopener nofollow"
                                   aria-label="{{ $label }}" title="{{ $label }}">
                                    <i class="bi {{ $icon }}"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- ۲) ستون‌های لینک — گروه‌های بدون هیچ لینک نمایش داده نمی‌شوند --}}
        @if ($footerFilledGroups->isNotEmpty())
        <div class="snr-footer-links">
            <div class="row row-cols-2 row-cols-lg-4 gy-4">
                @foreach($footerFilledGroups as $group)
                    <div class="col">
                        <div class="snr-links-col">
                            <h4 class="snr-links-title">{{ option('link_groups_' . $group['key'], $group['name']) }}</h4>
                            <ul>
                                @foreach($links->where('link_group_id', $group['key']) as $link)
                                    <li>
                                        <a href="{{ $link->link }}">
                                            <span>{{ $link->title }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- ۳) نوار خبرنامه (خبرنامهٔ فعلی که درست است، دست‌نخورده ماند) --}}
        <div class="snr-footer-newsletter">
            <div class="snr-nl-head">
                <span class="snr-nl-icon" aria-hidden="true"><i class="bi bi-megaphone"></i></span>
                <div>
                    <h4 class="snr-nl-title">عضویت در خبرنامه</h4>
                    <p class="snr-nl-sub">از تخفیف‌ها و محصولات تازه باخبر شوید</p>
                </div>
            </div>
            {{-- فاز ۱۸۵ — خبرنامهٔ فوتر کامپوننت لایووایر ۴ خود تم؛ فال‌بک: فرم کلاسیک --}}
            @if (class_exists(\Livewire\Livewire::class))
                <livewire:saniar-newsletter-box />
            @else
                <form id="newsletter-form" method="post" action="{{ route('front.newsletter.subscribe') }}"
                      class="footer-newsletter-form d-flex gap-2" novalidate>
                    @csrf
                    <input type="text" name="contact" class="form-control rounded-pill border-0"
                           placeholder="آدرس ایمیل یا شماره موبایل خود را وارد کنید"
                           aria-label="ایمیل یا شماره موبایل برای خبرنامه">
                    <button class="btn main-color-one-bg rounded-pill border-0" type="submit">ارسال</button>
                </form>
            @endif
        </div>

        {{-- ۴) دربارهٔ ما — لوگو راست، نشان‌های اعتماد چپ، متن زیر، «اطلاعات بیشتر» پایین-چپ (مطابق نمونه) --}}
        @php($snr_badges = front_trust_badges())
        @if ($footerAboutText !== '' || $snr_badges)
        <div class="snr-footer-about">
            <div class="snr-about-top">
                <img loading="lazy" decoding="async"
                     src="{{ option('info_logo', theme_asset('image/logo.png')) }}"
                     class="snr-about-logo" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                @if($snr_badges)
                    <div class="snr-footer-badges">
                        @foreach ($snr_badges as $snr_badge)
                            {{-- فاز ۲۰۷ — مجوزها از تنظیمات (تب مجوزها): html / تصویر / لینک --}}
                            @if ($snr_badge['type'] === 'html')
                                {!! $snr_badge['code'] !!}
                            @elseif ($snr_badge['type'] === 'image')
                                @if ($snr_badge['link'] !== '')
                                    <a href="{{ $snr_badge['link'] }}" target="_blank" rel="nofollow noopener"
                                       class="snr-footer-badge-link" title="{{ $snr_badge['title'] }}">
                                        <img loading="lazy" decoding="async" src="{{ $snr_badge['image'] }}"
                                             alt="{{ $snr_badge['title'] ?: 'مجوز' }}" class="snr-footer-badge-img">
                                    </a>
                                @else
                                    <img loading="lazy" decoding="async" src="{{ $snr_badge['image'] }}"
                                         alt="{{ $snr_badge['title'] ?: 'مجوز' }}" class="snr-footer-badge-img">
                                @endif
                            @else
                                <a href="{{ $snr_badge['link'] }}" target="_blank" rel="nofollow noopener"
                                   class="snr-footer-badge-text" title="{{ $snr_badge['title'] }}">
                                    {{ $snr_badge['title'] ?: 'مجوز' }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
            @if ($footerAboutText !== '')
                <p class="snr-about-text">
                    {{ $footerAboutText }}
                </p>
                <div class="snr-about-more-row">
                    <a href="{{ route('front.contact.index') }}" class="snr-about-more">
                        اطلاعات بیشتر
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    </a>
                </div>
            @endif
        </div>
        @endif

        {{-- ۵) نوار کپی‌رایت --}}
        <div class="snr-footer-copy">
            <p class="mb-0">
                تمامی حقوق این فروشگاه متعلق به <strong>{{ option('info_site_title', 'او پی شاپ') }}</strong> می‌باشد.
            </p>
        </div>
    </div>
</footer>
