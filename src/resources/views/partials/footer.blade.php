{{-- فوتر سانیار — بازطراحی فاز ۲۰۳ (مطابق نمونهٔ کاربر: مدرن و مرتب) --}}
{{-- ساختار: نوار تماس (تلفن/ایمیل/آدرس/شبکه‌های اجتماعی) → ستون‌های لینک
     → نوار خبرنامه → دربارهٔ ما + نشان‌های اعتماد → نوار کپی‌رایت --}}
<footer class="footer snr-footer">
    <div class="container-fluid">

        {{-- فاز ۱۹۳ — ستون‌هایی که مقداری در پنل ندارند رندر نمی‌شوند تا فاصله‌های خالی بزرگ ایجاد نکنند --}}
        @php
            $footerHasTel      = trim((string) option('info_tel', '')) !== '';
            $footerHasEmail    = trim((string) option('info_email', '')) !== '';
            $footerHasAddress  = trim((string) option('info_footer_text', '')) !== '';
            $footerSocialKeys  = ['social_instagram', 'social_whatsapp', 'social_telegram', 'social_youtube', 'social_twitter'];
            $footerSocials     = collect([
                'social_instagram' => ['bi-instagram', 'اینستاگرام'],
                'social_whatsapp'  => ['bi-whatsapp', 'واتس‌اپ'],
                'social_telegram'  => ['bi-telegram', 'تلگرام'],
                'social_youtube'   => ['bi-youtube', 'یوتیوب'],
                'social_twitter'   => ['bi-twitter', 'توییتر'],
            ])->filter(fn ($v, $k) => trim((string) option($k, '')) !== '');
            $footerHasSocials  = $footerSocials->isNotEmpty();
            $footerFilledGroups = collect($footer_links)->filter(
                fn ($group) => $links->where('link_group_id', $group['key'])->isNotEmpty()
            );
            $footerAboutText = trim((string) option('info_about_us', option('info_short_description', '')));
        @endphp

        {{-- ۱) نوار تماس --}}
        @if ($footerHasTel || $footerHasEmail || $footerHasAddress || $footerHasSocials)
        <div class="snr-footer-contact">
            <div class="snr-footer-contact-grid">
                @if ($footerHasTel)
                <div class="snr-contact-cell">
                    <span class="snr-contact-icon" aria-hidden="true"><i class="bi bi-headset"></i></span>
                    <div class="snr-contact-body">
                        <h4 class="snr-contact-title">شماره‌های ارتباطی</h4>
                        <div class="snr-contact-value" dir="rtl">
                            <a href="tel:{{ preg_replace('/\s+/', '', option('info_tel', '')) }}" dir="ltr">{{ option('info_tel', '') }}</a>
                        </div>
                    </div>
                </div>
                @endif
                @if ($footerHasEmail)
                <div class="snr-contact-cell">
                    <span class="snr-contact-icon" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                    <div class="snr-contact-body">
                        <h4 class="snr-contact-title">ایمیل‌های ارتباطی</h4>
                        <div class="snr-contact-value">
                            <a href="mailto:{{ option('info_email', '') }}" dir="ltr">{{ option('info_email', '') }}</a>
                        </div>
                    </div>
                </div>
                @endif
                @if ($footerHasAddress)
                <div class="snr-contact-cell">
                    <span class="snr-contact-icon" aria-hidden="true"><i class="bi bi-geo-alt"></i></span>
                    <div class="snr-contact-body">
                        <h4 class="snr-contact-title">آدرس شرکت</h4>
                        <div class="snr-contact-value">
                            <span>{{ option('info_footer_text', '') }}</span>
                        </div>
                    </div>
                </div>
                @endif
                @if ($footerHasSocials)
                <div class="snr-contact-cell">
                    <span class="snr-contact-icon" aria-hidden="true"><i class="bi bi-share"></i></span>
                    <div class="snr-contact-body">
                        <h4 class="snr-contact-title">فروشگاه در رسانه‌ها</h4>
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
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- ۲) ستون‌های لینک — گروه‌های بدون هیچ لینک نمایش داده نمی‌شوند --}}
        @if ($footerFilledGroups->isNotEmpty())
        <div class="snr-footer-links">
            <div class="row gy-4">
                @foreach($footerFilledGroups as $group)
                    <div class="col-6 col-lg-3">
                        <div class="snr-links-col">
                            <h4 class="snr-links-title">{{ option('link_groups_' . $group['key'], $group['name']) }}</h4>
                            <ul>
                                @foreach($links->where('link_group_id', $group['key']) as $link)
                                    <li>
                                        <a href="{{ $link->link }}">
                                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
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

        {{-- ۳) نوار خبرنامه --}}
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

        {{-- ۴) دربارهٔ ما + نشان‌های اعتماد --}}
        @if ($footerAboutText !== '' || option('info_enamad') || option('info_samandehi'))
        <div class="snr-footer-about">
            <div class="row gy-4 align-items-start">
                <div class="col-lg-9">
                    <div class="snr-about-body">
                        @if ($footerAboutText !== '')
                            <img loading="lazy" decoding="async"
                                 src="{{ option('info_logo', theme_asset('image/logo.png')) }}"
                                 class="snr-about-logo" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                            <p class="snr-about-text">
                                {{ $footerAboutText }}
                            </p>
                            <a href="{{ route('front.contact.index') }}" class="snr-about-more">
                                اطلاعات بیشتر
                                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                            </a>
                        @else
                            <img loading="lazy" decoding="async"
                                 src="{{ option('info_logo', theme_asset('image/logo.png')) }}"
                                 class="snr-about-logo" alt="{{ option('info_site_title', 'او پی شاپ') }}">
                        @endif
                    </div>
                </div>
                @if(option('info_enamad') || option('info_samandehi'))
                <div class="col-lg-3">
                    <div class="snr-footer-badges">
                        @if(option('info_enamad'))
                            {!! option('info_enamad') !!}
                        @endif
                        @if(option('info_samandehi'))
                            {!! option('info_samandehi') !!}
                        @endif
                    </div>
                </div>
                @endif
            </div>
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
