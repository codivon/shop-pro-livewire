{{-- =====================================================================
    نتیجهٔ پرداخت — بازطراحی کامل (فاز ۱۹۱)
    لایهٔ سادهٔ چک‌اوت: layouts/checkout.blade.php (بدون هدر/فوتر اصلی)
    ساختار مدرن: کارت وضعیت با آیکون انیمیشنی + رسید سفارش + اقلام + ارسال
    استایل‌ها به‌صورت خودکفا با @push('styles') تزریق می‌شوند تا بدون وابستگی
    به پایپ‌لاین پابلیش assetها، فقط با آپلود همین blade روی سایت اعمال شود.
    پشتیبانی کامل تم تیره (html[data-theme="dark"]).
====================================================================== --}}
@extends('front::layouts.checkout')

@php
    /* ---------- تشخیص وضعیت نهایی ---------- */
    $isSuccess = ($order->status == 'paid') || session('message') == 'ok';
    $isCancelled = ($order->status == 'cancelled');
    $txError = session('transaction-error');
    $sysError = session('error');

    if ($isSuccess) {
        $state = 'success';
        $stateTitle = 'پرداخت شما با موفقیت انجام شد';
        $stateDesc = 'سفارش شما با موفقیت ثبت و در حال پردازش است.';
        $stateIcon = 'bi-check-lg';
    } elseif ($isCancelled) {
        $state = 'cancel';
        $stateTitle = 'این سفارش لغو شده است';
        $stateDesc = 'سفارش شما به دلیل عدم پرداخت یا اتمام موجودی لغو شد.';
        $stateIcon = 'bi-x-octagon';
    } else {
        $state = 'fail';
        $stateTitle = 'پرداخت شما ناموفق بود';
        $stateDesc = $txError
            ? 'تراکنش تکمیل نشد: سفارش ثبت شده اما پرداخت آن انجام نشد.'
            : 'سفارش شما در سیستم ثبت شد اما پرداخت آن تکمیل نشد.';
        $stateIcon = 'bi-x-lg';
    }

    /* ---------- نگاشت وضعیت‌ها به برچسب فارسی ---------- */
    $shippingMap = [
        'w-pending' => ['در انتظار بررسی', 'b-amber', 'bi-hourglass-split'],
        'pending'   => ['در حال بررسی', 'b-blue', 'bi-arrow-repeat'],
        'waiting'   => ['منتظر ارسال', 'b-blue', 'bi-truck'],
        'sent'      => ['ارسال شده', 'b-green', 'bi-box-seam'],
        'canceled'  => ['ارسال لغو شده', 'b-red', 'bi-x-circle'],
    ];

    $payStatusMap = [
        'paid'      => ['پرداخت شده', 'b-green', 'bi-check-circle'],
        'unpaid'    => ['پرداخت نشده', 'b-red', 'bi-x-circle'],
        'cancelled' => ['لغو شده', 'b-amber', 'bi-x-octagon'],
    ];

    [$shipLabel, $shipClass, $shipIco] = $shippingMap[$order->shipping_status] ?? ['در انتظار بررسی', 'b-gray', 'bi-hourglass-split'];
    [$payLabel, $payClass, $payIco] = $payStatusMap[$order->status] ?? ['در انتظار پرداخت', 'b-gray', 'bi-hourglass-split'];

    $itemsCount = $order->items->sum('quantity');
    $orderDate = jdate($order->paid_at ?: $order->created_at)->format('d F Y');

    /* دکمهٔ بازگشت هدر: در وضعیت ناموفق، بازگشت به صفحهٔ سفارش (برای تلاش مجدد) */
    $backUrl = $state === 'fail'
        ? route('front.orders.show', $order)
        : route('front.index');
@endphp

@section('checkout-back-url', $backUrl)

@section('content')
    <div class="opr-wrap opr--{{ $state }}">

        <!-- مسیر صفحه -->
        <nav class="opr-breadcrumb opr-anim" aria-label="breadcrumb">
            <a href="{{ route('front.index') }}">خانه</a>
            <i class="bi bi-chevron-left"></i>
            <a href="{{ route('front.cart') }}">سبد خرید</a>
            <i class="bi bi-chevron-left"></i>
            <span>نتیجه پرداخت</span>
        </nav>

        <!-- ===== کارت وضعیت پرداخت ===== -->
        <section class="opr-hero opr-anim opr-d1" role="status">
            <div class="opr-icon" aria-hidden="true">
                <i class="bi {{ $stateIcon }}"></i>
            </div>

            <h1 class="opr-title">{{ $stateTitle }}</h1>
            <p class="opr-sub">{{ $stateDesc }}</p>

            <div class="opr-chips">
                <span class="opr-chip">
                    <i class="bi bi-receipt"></i>
                    سفارش <b>#{{ $order->id }}</b>
                </span>
                <span class="opr-chip">
                    <i class="bi bi-calendar3"></i>
                    {{ $orderDate }}
                </span>
                @if($itemsCount)
                    <span class="opr-chip">
                        <i class="bi bi-bag"></i>
                        {{ $itemsCount }} کالا
                    </span>
                @endif
            </div>

            <div class="opr-amount">
                <span class="opr-amount-label">{{ $isSuccess ? 'مبلغ پرداخت شده' : 'مبلغ سفارش' }}</span>
                <span class="opr-amount-value">
                    {{ number_format($order->price) }}
                    <small>تومان</small>
                </span>
            </div>
        </section>

        <!-- ===== خطای درگاه (در صورت وجود) ===== -->
        @if($txError)
            <div class="opr-notice n-error opr-anim opr-d2" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <div>
                    <b>پیام درگاه پرداخت:</b>
                    {{ $txError }}
                </div>
            </div>
        @elseif($sysError)
            <div class="opr-notice n-error opr-anim opr-d2" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <div>
                    <b>خطا:</b>
                    {{ $sysError }}
                </div>
            </div>
        @endif

        <!-- ===== رسید سفارش ===== -->
        <section class="opr-card opr-anim opr-d3">
            <div class="opr-card-head">
                <i class="bi bi-receipt-cutoff" aria-hidden="true"></i>
                <h3>جزئیات سفارش</h3>
            </div>
            <div class="opr-card-body">
                <div class="opr-row">
                    <span class="opr-row-label">شماره سفارش</span>
                    <span class="opr-row-value">#{{ $order->id }}</span>
                </div>
                <div class="opr-row">
                    <span class="opr-row-label">نام تحویل‌گیرنده</span>
                    <span class="opr-row-value">{{ $order->name }}</span>
                </div>
                <div class="opr-row">
                    <span class="opr-row-label">شماره تماس</span>
                    <span class="opr-row-value" dir="ltr">{{ $order->mobile }}</span>
                </div>
                <div class="opr-row">
                    <span class="opr-row-label">تعداد اقلام</span>
                    <span class="opr-row-value">{{ $itemsCount }} عدد</span>
                </div>
                <div class="opr-row">
                    <span class="opr-row-label">وضعیت پرداخت</span>
                    <span class="opr-row-value">
                        <span class="opr-badge {{ $payClass }}">
                            <i class="bi {{ $payIco }}"></i>
                            {{ $payLabel }}
                        </span>
                    </span>
                </div>
                <div class="opr-row">
                    <span class="opr-row-label">مبلغ کل</span>
                    <span class="opr-row-value money">{{ number_format($order->price) }} تومان</span>
                </div>
            </div>
        </section>

        <!-- ===== اقلام سفارش ===== -->
        @if($order->items->count())
            <section class="opr-card opr-anim opr-d4">
                <div class="opr-card-head">
                    <i class="bi bi-bag-check" aria-hidden="true"></i>
                    <h3>اقلام سفارش</h3>
                </div>
                <div class="opr-card-body">
                    @foreach($order->items as $item)
                        <div class="opr-item">
                            <span class="opr-item-ico" aria-hidden="true">
                                <i class="bi {{ ($item->product_type ?? 'physical') === 'download' ? 'bi-cloud-arrow-down' : 'bi-box-seam' }}"></i>
                            </span>
                            <span class="opr-item-title">{{ $item->title }}</span>
                            <span class="opr-item-qty">{{ $item->quantity }} عدد</span>
                            <span class="opr-item-price">
                                {{ number_format($item->price * $item->quantity) }}
                                <small>تومان</small>
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- ===== جزئیات ارسال ===== -->
        <section class="opr-card opr-anim opr-d5">
            <div class="opr-card-head">
                <i class="bi bi-truck" aria-hidden="true"></i>
                <h3>جزییات ارسال</h3>
            </div>
            <div class="opr-card-body">
                <div class="opr-row">
                    <span class="opr-row-label">وضعیت ارسال</span>
                    <span class="opr-row-value">
                        <span class="opr-badge {{ $shipClass }}">
                            <i class="bi {{ $shipIco }}"></i>
                            {{ $shipLabel }}
                        </span>
                    </span>
                </div>
                @if(trim((string) $order->address) !== '')
                    <div class="opr-row">
                        <span class="opr-row-label">
                            <i class="bi bi-geo-alt"></i>
                            آدرس تحویل
                        </span>
                        <span class="opr-row-value opr-address">{{ $order->address }}</span>
                    </div>
                @endif
            </div>
        </section>

        <!-- ===== هشدارهای وضعیت ناموفق / لغو شده ===== -->
        @if($state === 'fail')
            <div class="opr-notice n-warn opr-anim opr-d5" role="alert">
                <i class="bi bi-clock-history"></i>
                <div>
                    برای جلوگیری از لغو سیستمی سفارش، تا <b>۲۴ ساعت</b> آینده پرداخت را انجام دهید.
                    <br>
                    چنانچه طی این فرایند مبلغی از حساب شما کسر شده است، طی <b>۷۲ ساعت</b> آینده به حساب شما بازخواهد گشت.
                </div>
            </div>
        @elseif($state === 'cancel' && $order->failed_reason)
            <div class="opr-notice n-error opr-anim opr-d5" role="alert">
                <i class="bi bi-info-circle"></i>
                <div>
                    <b>دلیل لغو:</b>
                    {{ $order->failed_reason }}
                </div>
            </div>
        @endif

        <!-- ===== دکمه‌های عملیات ===== -->
        <div class="opr-actions opr-anim opr-d5">
            @if($state === 'fail')
                {{-- مسیر رسمی تلاش مجدد پرداخت — مانند user/orders/show با پارامتر gateway
                     (pay() مقدار gateway را required اعتبارسنجی می‌کند) --}}
                <a href="{{ route('front.orders.pay', ['order' => $order]) }}?gateway={{ $order->gatewayRelation ? $order->gatewayRelation->key : 'wallet' }}"
                    class="opr-btn opr-btn-primary">
                    <i class="bi bi-arrow-clockwise"></i>
                    تلاش مجدد برای پرداخت
                </a>
                <a href="{{ route('front.orders.show', $order) }}" class="opr-btn opr-btn-ghost">
                    <i class="bi bi-eye"></i>
                    مشاهده سفارش
                </a>
            @else
                <a href="{{ route('front.orders.show', $order) }}" class="opr-btn opr-btn-primary">
                    <i class="bi bi-bag-check"></i>
                    پیگیری سفارش
                </a>
                <a href="{{ route('front.index') }}" class="opr-btn opr-btn-ghost">
                    <i class="bi bi-shop"></i>
                    بازگشت به فروشگاه
                </a>
            @endif
        </div>

        <p class="opr-footnote opr-anim opr-d5">
            <i class="bi bi-shield-check"></i>
            وضعیت لحظه‌ای سفارش را می‌توانید در بخش «سفارش‌های من» دنبال کنید.
        </p>
    </div>
@endsection

@push('styles')
    {{-- فاز ۲۰۰ — checkout.css روی صفحهٔ نتیجهٔ پرداخت هم لود می‌شد:
         استایل هدر سادهٔ چک‌اوت (snr-checkout-header) فقط داخل همین فایل است و
         چون این صفحه آن را لود نمی‌کرد، لوگو با اندازهٔ طبیعی فایل تصویر رندر
         می‌شد (لوگوی غول‌پیکر در اسکرین‌شات کاربر) و دکمهٔ بازگشت هم بی‌استایل بود --}}
    <link rel="stylesheet" href="{{ theme_asset('css/checkout.css') }}?v=175" />

    <style>
        /* =========================================================
           نتیجهٔ پرداخت — استایل اختصاصی (فاز ۱۹۱)
           namespace: opr-  |  پشتیبانی تم تیره: html[data-theme="dark"]
        ========================================================= */
        .opr-wrap {
            --opr-c: #0d9a00;
            --opr-c2: #0b8502;
            --opr-soft: rgba(24, 157, 14, .10);
            max-width: 760px;
            margin: 0 auto;
            padding: 20px 16px 56px;
            font-family: var(--font-site);
        }

        .opr-wrap.opr--fail {
            --opr-c: #d02304;
            --opr-c2: #a81b03;
            --opr-soft: rgba(208, 35, 4, .10);
        }

        .opr-wrap.opr--cancel {
            --opr-c: #b4770a;
            --opr-c2: #8f5e07;
            --opr-soft: rgba(194, 129, 10, .14);
        }

        /* ---------- انیمیشن‌ها ---------- */
        @keyframes oprFadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: none; }
        }

        @keyframes oprPop {
            0%   { transform: scale(.35); opacity: 0; }
            60%  { transform: scale(1.08); }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes oprPulse {
            0%   { transform: scale(1); opacity: .55; }
            75%  { transform: scale(1.85); opacity: 0; }
            100% { transform: scale(1.85); opacity: 0; }
        }

        .opr-anim { animation: oprFadeUp .55s ease-out both; }
        .opr-d1 { animation-delay: .08s; }
        .opr-d2 { animation-delay: .16s; }
        .opr-d3 { animation-delay: .22s; }
        .opr-d4 { animation-delay: .30s; }
        .opr-d5 { animation-delay: .38s; }

        @media (prefers-reduced-motion: reduce) {
            .opr-anim, .opr-icon { animation: none !important; }
        }

        /* ---------- مسیر صفحه ---------- */
        .opr-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-muted-two);
            margin-bottom: 18px;
        }

        .opr-breadcrumb a {
            color: var(--text-muted-two);
            text-decoration: none;
            transition: color .2s;
        }

        .opr-breadcrumb a:hover { color: var(--main-color-one); }

        .opr-breadcrumb i { font-size: 11px; color: var(--text-muted-two); opacity: .7; }

        .opr-breadcrumb span { color: var(--color-site); font-weight: 600; }

        /* ---------- کارت وضعیت (Hero) ---------- */
        .opr-hero {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, .05);
            border-radius: 22px;
            box-shadow: 0 8px 28px rgba(15, 23, 42, .07);
            padding: 38px 26px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .opr-hero::before {
            content: '';
            position: absolute;
            top: 0; right: 0; left: 0;
            height: 130px;
            background: linear-gradient(180deg, var(--opr-soft), transparent);
            pointer-events: none;
        }

        .opr-icon {
            width: 92px;
            height: 92px;
            border-radius: 50%;
            margin: 0 auto 18px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--opr-c), var(--opr-c2));
            box-shadow: 0 12px 26px var(--opr-soft), 0 4px 10px rgba(0, 0, 0, .12);
            animation: oprPop .65s cubic-bezier(.2, 1.4, .4, 1) both;
        }

        .opr-icon i {
            color: #fff;
            font-size: 44px;
            line-height: 1;
        }

        .opr-icon::before,
        .opr-icon::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px solid var(--opr-c);
            animation: oprPulse 2.4s ease-out infinite;
            pointer-events: none;
        }

        .opr-icon::after { animation-delay: 1.2s; }

        .opr-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--opr-c);
            font-family: var(--title-font);
            position: relative;
        }

        .opr-sub {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 8px;
            line-height: 2;
            position: relative;
        }

        .opr-chips {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 16px;
            position: relative;
        }

        .opr-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--opr-soft);
            color: var(--opr-c);
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
        }

        .opr-chip i { font-size: 15px; }

        .opr-amount {
            margin: 22px auto 0;
            max-width: 330px;
            background: var(--opr-soft);
            border: 1.5px dashed var(--opr-c);
            border-radius: 16px;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
        }

        .opr-amount-label {
            font-size: 12.5px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .opr-amount-value {
            font-size: 25px;
            font-weight: 800;
            color: var(--opr-c);
            font-family: var(--title-font);
            line-height: 1.6;
        }

        .opr-amount-value small {
            font-size: 13px;
            font-weight: 600;
            margin-right: 4px;
        }

        /* ---------- کارت‌ها ---------- */
        .opr-card {
            background: #fff;
            border: 1px solid rgba(15, 23, 42, .05);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, .05);
            margin-top: 16px;
            overflow: hidden;
        }

        .opr-card-head {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px 10px;
        }

        .opr-card-head i.ico-badge,
        .opr-card-head > i {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: var(--main-color-one-shadow, rgba(39, 95, 224, .12));
            color: var(--main-color-one);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .opr-card-head h3 {
            font-size: 16px;
            font-weight: 700;
        }

        .opr-card-body { padding: 2px 20px 18px; }

        .opr-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            padding: 11px 0;
        }

        .opr-row + .opr-row { border-top: 1px dashed #e8eaf1; }

        .opr-row-label {
            color: var(--text-muted-two);
            font-size: 13.5px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 5px;
            padding-top: 2px;
        }

        .opr-row-label i { font-size: 15px; }

        .opr-row-value {
            font-size: 14.5px;
            font-weight: 700;
            text-align: left;
            color: var(--color-site);
            word-break: break-word;
        }

        .opr-row-value.money { color: var(--main-color-one); }

        .opr-address { line-height: 2.1; max-width: 420px; }

        /* ---------- نشان‌های وضعیت ---------- */
        .opr-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 5px 13px;
            font-size: 12.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .opr-badge i { font-size: 14px; }

        .opr-badge.b-green { background: rgba(24, 157, 14, .12); color: #0b8502; }
        .opr-badge.b-red   { background: rgba(208, 35, 4, .11);  color: #d02304; }
        .opr-badge.b-amber { background: rgba(255, 164, 27, .16); color: #9c6a08; }
        .opr-badge.b-blue  { background: rgba(39, 95, 224, .11); color: var(--main-color-one); }
        .opr-badge.b-gray  { background: rgba(108, 117, 125, .13); color: #5a6268; }

        /* ---------- اقلام سفارش ---------- */
        .opr-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
        }

        .opr-item + .opr-item { border-top: 1px dashed #e8eaf1; }

        .opr-item-ico {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #f4f5f9;
            color: var(--main-color-one);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }

        .opr-item-title {
            flex: 1;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.9;
            color: var(--color-site);
            min-width: 0;
        }

        .opr-item-qty {
            background: #f0f1f5;
            color: #5a6268;
            border-radius: 8px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
            white-space: nowrap;
        }

        .opr-item-price {
            font-weight: 800;
            font-size: 14px;
            color: var(--color-site);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .opr-item-price small {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted-two);
        }

        /* ---------- هشدارها ---------- */
        .opr-notice {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            border-radius: 16px;
            padding: 15px 18px;
            margin-top: 16px;
            font-size: 13.5px;
            line-height: 2.1;
            text-align: right;
        }

        .opr-notice i {
            font-size: 21px;
            flex-shrink: 0;
            margin-top: 4px;
        }

        .opr-notice.n-warn {
            background: rgba(255, 164, 27, .12);
            border: 1px solid rgba(255, 164, 27, .4);
            color: #8a5b00;
        }

        .opr-notice.n-warn i { color: #e28c00; }

        .opr-notice.n-error {
            background: rgba(208, 35, 4, .08);
            border: 1px solid rgba(208, 35, 4, .3);
            color: #a32b02;
        }

        .opr-notice.n-error i { color: #d02304; }

        /* ---------- دکمه‌ها ---------- */
        .opr-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 26px;
        }

        .opr-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 14px;
            padding: 13px 26px;
            font-size: 15px;
            font-weight: 700;
            transition: all .25s ease;
            border: 1px solid transparent;
            text-decoration: none !important;
            min-height: 48px;
        }

        .opr-btn i { font-size: 18px; }

        .opr-btn-primary {
            background: linear-gradient(135deg, var(--main-color-one), var(--main-color-one-hover));
            color: #fff !important;
            box-shadow: 0 8px 18px rgba(39, 95, 224, .30);
        }

        .opr-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(39, 95, 224, .38);
            color: #fff !important;
        }

        .opr-btn-ghost {
            background: #fff;
            border-color: #dfe3ec;
            color: var(--color-site) !important;
        }

        .opr-btn-ghost:hover {
            border-color: var(--main-color-one);
            color: var(--main-color-one) !important;
            background: rgba(39, 95, 224, .04);
        }

        .opr-footnote {
            text-align: center;
            color: var(--text-muted-two);
            font-size: 12.5px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .opr-footnote i { font-size: 14px; color: var(--main-color-three); }

        /* ---------- موبایل ---------- */
        @media (max-width: 575px) {
            .opr-wrap { padding: 14px 12px 44px; }

            .opr-hero { padding: 30px 16px 24px; border-radius: 18px; }

            .opr-icon { width: 76px; height: 76px; }

            .opr-icon i { font-size: 36px; }

            .opr-title { font-size: 18px; }

            .opr-sub { font-size: 13px; }

            .opr-amount-value { font-size: 21px; }

            .opr-card-head { padding: 14px 16px 8px; }

            .opr-card-body { padding: 2px 16px 16px; }

            .opr-item { flex-wrap: wrap; }

            .opr-item-title { flex-basis: calc(100% - 100px); }

            .opr-btn { width: 100%; justify-content: center; }
        }

        /* =========================================================
           تم تیره
        ========================================================= */
        html[data-theme="dark"] .opr-breadcrumb a { color: var(--dk-text-soft); }

        html[data-theme="dark"] .opr-breadcrumb span { color: var(--dk-text); }

        html[data-theme="dark"] .opr-hero,
        html[data-theme="dark"] .opr-card {
            background: var(--dk-surface);
            border-color: var(--dk-border);
            box-shadow: var(--dk-shadow);
        }

        html[data-theme="dark"] .opr-sub,
        html[data-theme="dark"] .opr-amount-label { color: var(--dk-text-soft); }

        html[data-theme="dark"] .opr-row + .opr-row,
        html[data-theme="dark"] .opr-item + .opr-item { border-color: var(--dk-border); }

        html[data-theme="dark"] .opr-row-label { color: var(--dk-text-muted); }

        html[data-theme="dark"] .opr-row-value,
        html[data-theme="dark"] .opr-item-title,
        html[data-theme="dark"] .opr-item-price { color: var(--dk-text); }

        html[data-theme="dark"] .opr-item-price small { color: var(--dk-text-muted); }

        html[data-theme="dark"] .opr-item-ico {
            background: var(--dk-surface-2);
            color: #5b8af5;
        }

        html[data-theme="dark"] .opr-item-qty {
            background: var(--dk-surface-2);
            color: var(--dk-text-soft);
        }

        html[data-theme="dark"] .opr-badge.b-green { background: rgba(24, 157, 14, .16); color: #6fdf66; }
        html[data-theme="dark"] .opr-badge.b-red   { background: rgba(229, 72, 77, .16);  color: #ff8a8d; }
        html[data-theme="dark"] .opr-badge.b-amber { background: rgba(255, 164, 27, .16);  color: #ffc266; }
        html[data-theme="dark"] .opr-badge.b-blue  { background: rgba(91, 138, 245, .16);  color: #5b8af5; }
        html[data-theme="dark"] .opr-badge.b-gray  { background: rgba(129, 136, 152, .18); color: var(--dk-text-soft); }

        html[data-theme="dark"] .opr-notice.n-warn {
            background: rgba(255, 164, 27, .10);
            border-color: rgba(255, 164, 27, .3);
            color: #ffb84d;
        }

        html[data-theme="dark"] .opr-notice.n-warn i { color: #ffb84d; }

        html[data-theme="dark"] .opr-notice.n-error {
            background: rgba(229, 72, 77, .10);
            border-color: rgba(229, 72, 77, .3);
            color: #ff8a8d;
        }

        html[data-theme="dark"] .opr-notice.n-error i { color: #ff8a8d; }

        html[data-theme="dark"] .opr-btn-ghost {
            background: var(--dk-surface-2);
            border-color: var(--dk-border);
            color: var(--dk-text) !important;
        }

        html[data-theme="dark"] .opr-btn-ghost:hover {
            border-color: var(--main-color-one);
            color: #5b8af5 !important;
            background: rgba(91, 138, 245, .08);
        }

        html[data-theme="dark"] .opr-footnote { color: var(--dk-text-muted); }
    </style>
@endpush
