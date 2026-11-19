{{-- باکس اقدام خرید — مارکاپ سانیار product-meta-action --}}
{{-- ردیف فروشنده/عملکرد/شناسه به ستون چپ منتقل شد (پارشال product-seller-aside) --}}
{{-- هوک‌های JS (show.js): .product-seller-info / .product-seller-row > button[data-price_id] / #price-real .product-seller-price-prev --}}
@if ($product->addableToCart() && $selected_price)

    <div class="product-seller-info mt-4">
        <div class="js-seller-info-changable product-actions">

            @php
                /*
                | /۸۱/۸۳ — هشدار کاهش قیمت
                | متن/آیکون/رنگ/اندازه/گردی/موقعیت همه از «تنظیمات دیگر» سایت
                | (تب قابلیت‌های فروشگاه) قابل شخصی‌سازی است.
                */
                $paEnabled    = option('dt_price_alert_status', '1') == '1';
                $paText       = option('dt_price_alert_button_text', 'هشدار کاهش قیمت') ?: 'هشدار کاهش قیمت';
                $paIcon       = option('dt_price_alert_button_icon', 'mdi mdi-bell-ring-outline') ?: 'mdi mdi-bell-ring-outline';
                $paColor      = option('dt_price_alert_button_color', 'red') ?: 'red';
                $paSize       = (string) (option('dt_price_alert_button_size', 'md') ?: 'md');
                $paSize       = in_array($paSize, ['sm', 'md', 'lg', 'custom'], true) ? $paSize : 'md';
                $paRadius     = (int) (option('dt_price_alert_button_radius', '10') ?: 10);
                $paPlacement  = option('dt_price_alert_button_placement', 'below-cart') ?: 'below-cart';

                /*
                | /۱۸۲ — رنگ دلخواه + رنگ متن + اندازهٔ درصدی (هم‌قرارداد با پنل):
                | همه به‌صورت «متغیر CSS اینلاین» روی خود دکمه اعمال می‌شوند تا مستقل از
                | نسخهٔ CSS هاست قطعاً اثر کنند (ادامهٔ رویکرد فاز ۱۸۱):
                |  - رنگ دلخواه (سواچ آخر پنل): --dt-pa-bg/--dt-pa-bg2 (استپ دوم = تیره‌شدهٔ خودکار ۱۸٪)
                |  - رنگ متن:  --dt-pa-fg (پیش‌فرض سفید؛ در CSS با var مصرف می‌شود)
                |  - اندازه:   --dt-pa-h/--dt-pa-fs (پیش‌فرض ۳۸/۴۸/۵۸ یا ۴۸/۱۴ × درصد دلخواه ۵۰ تا ۲۰۰٪)
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

                $paSizePercent = (int) min(200, max(50, (int) (option('dt_price_alert_button_size_percent', '100') ?: 100)));
                $paSizeMap     = [
                    'sm' => ['h' => '38', 'fs' => '12.5'],
                    'md' => ['h' => '48', 'fs' => '14'],
                    'lg' => ['h' => '58', 'fs' => '16'],
                ];
                $paSizeCss = $paSize === 'custom'
                    ? [
                        'h'  => (string) max(24, (int) round(48 * $paSizePercent / 100)),
                        'fs' => (string) max(7, round(14 * $paSizePercent / 100, 1)),
                    ]
                    : $paSizeMap[$paSize];

                // موقعیت دکمهٔ شناور (۹گانه) + سلکتورهای «تگ دلخواه»
                $paFloatPos   = (string) (option('dt_price_alert_float_pos', 'bottom-right') ?: 'bottom-right');
                if (! in_array($paFloatPos, ['top-left', 'top-center', 'top-right', 'middle-left', 'middle-center', 'middle-right', 'bottom-left', 'bottom-center', 'bottom-right'], true)) {
                    $paFloatPos = 'bottom-right';
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
                ];
                if ($paColor === 'custom') {
                    $paStyleParts[] = '--dt-pa-bg: ' . e($paColorCustom);
                    $paStyleParts[] = '--dt-pa-bg2: ' . e($paDarken($paColorCustom));
                }

                $paBtnHtml    = '<button type="button" id="dt_price_alert_btn" class="dt-pa-btn dt-pa-btn--'.e($paSize).' dt-pa-btn--'.e($paColor).'" style="'.implode('; ', $paStyleParts).'">'
                    .'<i class="'.e($paIcon).'" aria-hidden="true"></i><span>'.e($paText).'</span></button>';
            @endphp

            @if($paEnabled && $paPlacement === 'above-cart')
                {!! $paBtnHtml !!}
            @endif

            <div class="product-seller-row product-meta-action">
                <div class="row align-items-center gy-3">
                    <div class="col-lg-4 col-6 w-100-in-400">
                        @if ($selected_price->hasDiscount())
                            <div class="product-seller-price-real text-danger">
                                <del class="product-seller-price-prev font-size-20">{{ number_format($selected_price->regularPrice()) }}</del>
                                تومان
                                <div class="discount show-discount">
                                    <span>{{ $selected_price->discount() }}٪</span>
                                </div>
                            </div>
                        @else
                            <div class="product-seller-price-label text-muted font-13">
                                @if($selected_price->seller)
                                    قیمت فروشنده
                                @else
                                    قیمت
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-4 col-6 w-100-in-400">
                        <h6 class="font-16 new-price main-color-one-color mb-0 product-seller-price-real" id="price-real">
                            <span class="product-seller-price-prev">{{ number_format($selected_price->salePrice()) }}</span>
                            تومان
                        </h6>
                    </div>
                    <div class="col-lg-4 col-12">
                        <button data-price_id="{{ $selected_price->id }}"
                                data-action="{{ route('front.cart.store', ['product' => $product]) }}"
                                data-product="{{ $product->slug }}" type="button"
                                class="btn-add-to-cart w-100 btn border-0 main-color-three-bg btn-show-product add-to-cart">
                            <i class="bi bi-basket text-white font-20 me-1"></i>
                            {{ $product->pre_order && option('dt_pre_order_status', '1') == '1' ? option('dt_pre_order_button_text', 'ثبت پیش‌فروش') : 'افزودن به سبد خرید' }}
                        </button>
                    </div>
                </div>

                @if($paEnabled && $paPlacement !== 'above-cart')
                    @if($paPlacement === 'under-price')
                        <div class="dt-pa-under-price">{!! $paBtnHtml !!}</div>
                    @elseif($paPlacement === 'float')
                        {{-- دکمهٔ شناور (fixed) در گوشهٔ انتخابی صفحه --}}
                        <div class="dt-pa-float dt-pa-float--{{ e($paFloatPos) }}">{!! $paBtnHtml !!}</div>
                    @elseif($paPlacement === 'custom-selector')
                        {{-- تزریق در تگ دلخواه --}}
                        <span id="dt_pa_inject_config" data-selectors="{{ json_encode($paSelectors) }}" hidden aria-hidden="true"></span>
                        {!! str_replace('<button type="button" id="dt_price_alert_btn"', '<button type="button" id="dt_price_alert_btn" hidden', $paBtnHtml) !!}
                    @else
                        <div class="mt-2">{!! $paBtnHtml !!}</div>
                    @endif
                @endif
            </div>
        </div>
    </div>

@elseif (!$product->addableToCart() || !$selected_price)
    <div class="product-seller-info mt-4">
        <div class="product-stock-status content-box p-4 rounded-3 shadow-box text-center">
            <div class="product-stock-title mb-2">
                <span class="badge bg-secondary rounded-pill px-3 py-2 font-14">ناموجود</span>
            </div>
            <div class="product-stock-body text-muted font-14 mb-3">
                متاسفانه این کالا در حال حاضر موجود نیست. می‌توانید از طریق لیست بالای
                صفحه، از محصولات مشابه این کالا دیدن نمایید
            </div>
            <button id="stock_notify_btn" data-user="{{ auth('web')->check() ? auth('web')->user()->id : '' }}"
                    data-product="{{ $product->id }}"
                    class="product-stock-action btn border-0 main-color-one-bg rounded-pill" type="button">
                <i class="bi bi-info-circle"></i>
                موجود شد به من اطلاع بده
            </button>
        </div>
    </div>
@endif
