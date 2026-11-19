@if ($product->addableToCart() && $selected_price)

    @php

        // جمع‌آوری همه seller_id های یکتا (بدون تکرار)
        $stores_attribute_prices=[];
        // گارد دفاعی: دادهٔ نامعتبر (غیرآرایه‌ای) هرگز نباید سایدبار را بترکاند
        if (is_array($get_stores) && !empty($get_stores['attribute_prices'])){
            $stores_attribute_prices=$get_stores['attribute_prices'];
        }
        $uniqueSellers = [];
        foreach($stores_attribute_prices as $attribute_prices){
            $sellerKey = $attribute_prices->seller_id ?? 'site';
            $uniqueSellers[$sellerKey] = true;
        }
        // تعداد فروشنده‌های یکتا
        $totalUniqueSellers = count($uniqueSellers);

        // فروشنده فعلی
        $currentSeller = $selected_price->seller_id ?? 'site';

        // تعداد فروشنده‌های دیگر (غیر از فروشنده فعلی)
        $otherSellersCount = 0;
        foreach($uniqueSellers as $seller => $value){
            if($seller != $currentSeller){
                $otherSellersCount++;
            }
        }
    @endphp

    <div class="theiaStickySidebar" style="padding-top: 0px; padding-bottom: 1px; position: static; transform: none;">
        <div class="product-seller-info">
            <div class="js-seller-info-changable product-actions">
                @if(option('multi_vendor_system_status','false')=="true")
                    <div class="product-seller-row">
                        <div>
                            <div class="product-seller-first-line d-inline-block"> فروشنده:</div>
                            <a href="#stores-tag" class="js-seller-count-row">
                                <span class="js-seller-count u-text-bold count_stores"></span>
                                @if($totalUniqueSellers > 1)
                                    <a href="#stores-tag" class="js-seller-count-row">
                                        <span class="js-seller-count u-text-bold count_stores"></span>
                                        <span class="u-text-bold">{{$otherSellersCount}} فروشنده دیگر</span>
                                    </a>
                                @endif

                            </a>
                        </div>
                        <div class="seller-container">
                            <ul>
                                @if($selected_price->seller)
                                <li>

                                        <div class="seller-avatar shadow-1 ml-2">
                                            @if($selected_price->seller->seller_info->logo)
                                                <img src="{{ asset($selected_price->seller->seller_info->logo) }}" class="rounded-circle object-fit-cover">
                                            @else
                                                <i class="fas fa-store fs-4 text-secondary"></i>
                                            @endif
                                        </div>
                                        <a class="table-name lts-05" href="{{route('front.showSellerStore',$selected_price->seller)}}">{{$selected_price->seller->seller_info->business_name}}
                                            <i class="ri-verified-badge-fill verify me-0"></i>
                                        </a>
                                        <span class="divider"></span>
                                        <span class="table-flag lts-05">فروشنده</span>
                                </li>
                                <li>
                                    <i class=" fas fa-chart-column"></i><span class="table-name lts-05" href="#">عملکرد</span><span class="divider"></span><span class="text-success-dark fw-bold">عالی</span>
                                </li>
                                @else
                                    <li>

                                        <div class="seller-avatar shadow-1 ml-2">
                                            <i class="fas fa-store fs-4 text-secondary m-0"></i>
                                        </div>
                                        <a class="table-name lts-05" href="/"> {{ option('info_site_title', 'او پی شاپ') }}
                                            <i class="ri-verified-badge-fill verify me-0"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <i class=" fas fa-chart-column"></i><span class="table-name lts-05" href="#">عملکرد</span><span class="divider"></span><span class="text-success-dark fw-bold">عالی</span>
                                    </li>
                                @endif
                                <li class="clickable" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="کپی شناسه محصول">
                                    <i class=" fas fa-qrcode"></i><span class="table-name lts-05">شناسه محصول</span><span class="divider"></span><span class="table-flag lts-05">{{$selected_price->product->product_id}}</span>
                                </li>
                                <li>
                                    @if($selected_price->seller)
                                        <i class="fa-solid fa-truck-fast"></i><span class="table-name lts-05">ارسال توسط فروشنده</span><span class="divider"></span><span class="table-flag lts-05 fs-9">آماده ارسال</span>
                                    @else
                                        <i class="fa-solid fa-truck-fast"></i><span class="table-name lts-05">ارسال فروشگاه اصلی</span><span class="divider"></span><span class="table-flag lts-05 fs-9">آماده ارسال</span>
                                    @endif
                                </li>
                                    @if ($product->labels->count())
                                <li>
                                    @foreach ($product->labels as $label)
                                        <span class="table-name badge bg-success fs-9 lts-05" style="padding: 4px 6px !important;">{{ $label->title }} </span>
                                    <span class="divider"></span>
                                    @endforeach
                                </li>
                                    @endif
                            </ul>
                        </div>


                    </div>
                @endif


              {{--  <div class="product-seller-row js-seller-info-shipment">
                    <div class="js-guarantee-text">
                        موجود در انبار فروشنده
                        <i class="mdi mdi-content-save-outline"></i>
                    </div>
                    <div class="product-delivery-warehouse">آماده ارسال</div>
                </div>--}}

                <div class="product-seller-row">
                    <div class="product-seller-row-price mb-3">
                        <div class="product-seller-price-label">
                            @if($selected_price->seller)
                                قیمت فروشنده
                            @else
                                قیمت
                            @endif
                        </div>
                        @if ($selected_price->hasDiscount())
                            <div class="product-seller-price-real text-danger">
                                <del class="product-seller-price-prev text-danger font-size-20">{{ number_format($selected_price->regularPrice()) }}</del>
                                تومان
                                <div class="discount show-discount mr-3 ">
                                    <span>{{ $selected_price->discount() }}%</span>
                                </div>
                            </div>
                        @endif
                        <div class="product-seller-price-real" id="price-real">
                            <div class="product-seller-price-prev">{{ number_format($selected_price->salePrice()) }} </div>
                            تومان
                        </div>
                    </div>


                    @php
                        /*
                        | /۸۱/۸۳ — هشدار کاهش قیمت
                        | متن/آیکون/رنگ/اندازه/گردی/موقعیت همه از «تنظیمات دیگر» سایت
                        | (تب قابلیت‌های فروشگاه) قابل شخصی‌سازی است. خود مودال + استایل + اسکریپت
                        | در show.blade.php اینلاین است تا بدون وابستگی به کپی assetها روی هر هاستی کار کند.
                        */
                        $paEnabled    = option('dt_price_alert_status', '1') == '1';
                        $paText       = option('dt_price_alert_button_text', 'هشدار کاهش قیمت') ?: 'هشدار کاهش قیمت';
                        $paIcon       = option('dt_price_alert_button_icon', 'mdi mdi-bell-ring-outline') ?: 'mdi mdi-bell-ring-outline';
                        $paColor      = option('dt_price_alert_button_color', 'red') ?: 'red';
                        $paSize       = (string) (option('dt_price_alert_button_size', 'md') ?: 'md');
                        $paRadius     = (int) (option('dt_price_alert_button_radius', '10') ?: 10);
                        $paPlacement  = option('dt_price_alert_button_placement', 'below-cart') ?: 'below-cart';

                        /*
                        | /۱۸۲ — رنگ دلخواه + رنگ متن + /۱۸۳ — اندازهٔ دلخواه (طول/عرض پیکسلی)
                        | همه به‌صورت «متغیر CSS اینلاین» روی خود دکمه اعمال می‌شوند تا مستقل از
                        | نسخهٔ CSS هاست قطعاً اثر کنند (مثل فاز ۱۸۱ در SaniarTheme):
                        |  - رنگ دلخواه: --dt-pa-bg/--dt-pa-bg2 (استپ دوم = تیره‌شدهٔ خودکار)
                        |  - رنگ متن:   --dt-pa-fg
                        |  - اندازه:    --dt-pa-h/--dt-pa-fs (پیش‌فرض ۳۸/۴۸/۵۸ یا طول/عرضِ دلخواه پیکسلی /۱۸۳)
                        */
                        $paHexSan = function (string $v, string $fallback): string {
                            $v = trim($v);
                            if ($v === '') return $fallback;
                            if (! str_starts_with($v, '#')) $v = '#' . $v;
                            if (preg_match('/^#([0-9a-fA-F]{3})$/', $v)) {
                                $v = '#' . $v[1] . $v[1] . $v[2] . $v[2] . $v[3] . $v[3];
                            }
                            return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : $fallback;
                        };
                        $paDarken = function (string $hex, float $amt = 0.18): string {
                            $hex = ltrim($hex, '#');
                            if (strlen($hex) === 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
                            if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) return '#c8102e';
                            $out = '#';
                            for ($i = 0; $i < 3; $i++) {
                                $out .= str_pad(dechex((int) max(0, min(255, (int) round(hexdec(substr($hex, $i * 2, 2)) * (1 - $amt))))), 2, '0', STR_PAD_LEFT);
                            }
                            return $out;
                        };

                        $paColorCustom = $paHexSan((string) option('dt_price_alert_button_color_custom', '#ef394e'), '#ef394e');
                        $paTextColor   = $paHexSan((string) option('dt_price_alert_button_text_color', '#ffffff'), '#ffffff');

                        // /۱۸۳ — اندازه: sm/md/lg ثابت — «دلخواه» = طول/عرضِ دستی پیکسلی (هم‌قرارداد با پنل:
                        // طول ۲۴ تا ۱۵۰ — پیش‌فرض ۴۸؛ فونت = طول × ۱۴/۴۸ با سقف ۲۴؛ عرض ۰ تا ۵۰۰ — صفر = خودکار)
                        $paSize        = in_array($paSize, ['sm', 'md', 'lg', 'custom'], true) ? $paSize : 'md';
                        $paSizeHeight  = (int) min(150, max(24, (int) (option('dt_price_alert_button_height', '48') ?: 48)));
                        $paSizeWidth   = (int) min(500, max(0, (int) (option('dt_price_alert_button_width', '0') ?: 0)));
                        $paSizeMap     = [
                            'sm' => ['h' => '38', 'fs' => '12.5'],
                            'md' => ['h' => '48', 'fs' => '14'],
                            'lg' => ['h' => '58', 'fs' => '16'],
                        ];
                        $paSizeCss = $paSize === 'custom'
                            ? [
                                'h'  => (string) $paSizeHeight,
                                'fs' => (string) max(7, min(24, round($paSizeHeight * 14 / 48, 1))),
                            ]
                            : $paSizeMap[$paSize];

                        // موقعیت دکمهٔ شناور (۹گانه) + سلکتورهای «تگ دلخواه»
                        $paFloatPos   = (string) (option('dt_price_alert_float_pos', 'bottom-right') ?: 'bottom-right');
                        if (! in_array($paFloatPos, ['top-left', 'top-center', 'top-right', 'middle-left', 'middle-center', 'middle-right', 'bottom-left', 'bottom-center', 'bottom-right'], true)) {
                            $paFloatPos = 'bottom-right';
                        }

                        /* /۱۸۴ — پوزیشن افقی دکمه در باکس فروش (راست/وسط/چپ):
                           فقط جاسازی‌های داخل باکس (below/above/under-price)؛ در RTL
                           right = flex-start · left = flex-end (هم‌قرارداد با پنل) */
                        $paAlign      = (string) (option('dt_price_alert_button_align', 'center') ?: 'center');
                        if (! in_array($paAlign, ['right', 'center', 'left'], true)) {
                            $paAlign = 'center';
                        }

                        $paSelectors  = [];
                        $paSelRaw     = trim((string) option('dt_price_alert_selectors', ''));
                        if ($paSelRaw !== '') {
                            $decoded     = str_starts_with($paSelRaw, '[') ? json_decode($paSelRaw, true) : preg_split('/[\r\n,]+/', $paSelRaw);
                            $paSelectors = collect(is_array($decoded) ? $decoded : [])
                                ->map(fn ($s) => trim(preg_replace('/[^A-Za-z0-9\-_\.\#\[\]\:"\' >+~*(),=\$]/', '', (string) $s)))
                                ->filter()->unique()->take(10)->values()->all();
                        }

                        $paStyleParts = [
                            '--dt-pa-radius: ' . e((string) $paRadius) . 'px',
                            '--dt-pa-h: ' . e($paSizeCss['h']) . 'px',
                            '--dt-pa-fs: ' . e($paSizeCss['fs']) . 'px',
                            '--dt-pa-fg: ' . e($paTextColor),
                            /* /۱۸۳ — رنگ متن (عنوان دکمه) با !important «اینلاین» روی خود دکمه:
                               هیچ قاعدهٔ CSS هاست (حتی با !important) نمی‌تواند بازنویسیش کند */
                            'color: ' . e($paTextColor) . ' !important',
                        ];
                        if ($paSize === 'custom' && $paSizeWidth > 0) {
                            $paStyleParts[] = 'width: ' . e((string) $paSizeWidth) . 'px; min-width: 0';
                        }
                        if ($paColor === 'custom') {
                            $paStyleParts[] = '--dt-pa-bg: ' . e($paColorCustom);
                            $paStyleParts[] = '--dt-pa-bg2: ' . e($paDarken($paColorCustom));
                        }

                        $paBtnHtml    = '<button type="button" id="dt_price_alert_btn" class="dt-pa-btn dt-pa-btn--'.e($paSize).' dt-pa-btn--'.e($paColor).'" style="'.implode('; ', $paStyleParts).'">'
                            .'<i class="'.e($paIcon).'" style="color: '.e($paTextColor).' !important" aria-hidden="true"></i>'
                            .'<span style="color: '.e($paTextColor).' !important">'.e($paText).'</span></button>';
                    @endphp

                    @if($paEnabled && $paPlacement === 'above-cart')
                        {{-- /۱۸۴ — پوزیشن افقی (راست/وسط/چپ) داخل باکس فروش --}}
                        <div class="dt-pa-wrap dt-pa-wrap--{{ e($paAlign) }}">{!! $paBtnHtml !!}</div>
                    @endif

                    <button data-price_id="{{ $selected_price->id }}"
                            data-action="{{ route('front.cart.store', ['product' => $product]) }}"
                            data-product="{{ $product->slug }}" type="button"
                            class="btn-add-to-cart mt-4 w-100 btn-primary-cm cursor-pointer btn-with-icon add-to-cart btn-show-product">
                        {{ $product->pre_order && option('dt_pre_order_status', '1') == '1' ? option('dt_pre_order_button_text', 'ثبت پیش‌فروش') : 'افزودن به سبد خرید' }}
                    </button>

                    @if($paEnabled && $paPlacement !== 'above-cart')
                        @if($paPlacement === 'under-price')
                            <div class="dt-pa-under-price dt-pa-wrap dt-pa-wrap--{{ e($paAlign) }}">{!! $paBtnHtml !!}</div>
                        @elseif($paPlacement === 'float')
                            {{-- دکمهٔ شناور (fixed) در گوشهٔ انتخابی صفحه --}}
                            <div class="dt-pa-float dt-pa-float--{{ e($paFloatPos) }}">{!! $paBtnHtml !!}</div>
                        @elseif($paPlacement === 'custom-selector')
                            {{-- تزریق در تگ دلخواه: دکمهٔ مخفی + پیکربندی سلکتورها؛
                                 JS در show.blade.php کلون‌ها را بعد از هر عنصرِ منطبق می‌گذارد؛
                                 اگر هیچ سلکتوری پیدا نشود، دکمه زیر دکمهٔ خرید برمی‌گردد --}}
                            <span id="dt_pa_inject_config" data-selectors="{{ json_encode($paSelectors) }}" hidden aria-hidden="true"></span>
                            {!! str_replace('<button type="button" id="dt_price_alert_btn"', '<button type="button" id="dt_price_alert_btn" hidden', $paBtnHtml) !!}
                        @else
                            {{-- /۱۸۴ — پوزیشن افقی (راست/وسط/چپ) داخل باکس فروش --}}
                            <div class="dt-pa-wrap dt-pa-wrap--{{ e($paAlign) }}">{!! $paBtnHtml !!}</div>
                        @endif
                    @endif


                </div>
            </div>

        </div>
        @elseif (!$product->addableToCart() || !$selected_price)
            <div class="theiaStickySidebar" style="padding-top: 0px; padding-bottom: 1px; position: static; transform: none;">
                <div class="product-seller-info">
                    <div class="product-stock-status">
                        <div class="product-stock-title">
                            <span>ناموجود</span>
                        </div>
                        <div class="product-stock-body">
                            متاسفانه این کالا در حال حاضر موجود نیست. می‌توانید از طریق لیست بالای
                            صفحه،
                            از محصولات مشابه این کالا دیدن نمایید
                        </div>
                        <button id="stock_notify_btn" data-user="{{ auth('web')->check() ? auth('web')->user()->id : '' }}"
                                data-product="{{ $product->id }}" class="product-stock-action btn btn-secondary  cart-not-available" type="submit">
                            <i class="mdi mdi-information"></i>
                            موجود شد به من اطلاع بده
                        </button>
                    </div>
                </div>
            </div>
@endif
