@extends('front::user.layouts.master')

@section('user-content')

    {{-- سفارش‌های من — بازطراحی کامل فاز ۲۰۰ --}}
    <div class="usp-scope">

        {{-- سربرگ صفحه --}}
        <div class="usp-page-head">
            <div class="usp-page-head-title">
                <span class="usp-page-head-icon" aria-hidden="true"><i class="bi bi-bag-check"></i></span>
                <div>
                    <h2>سفارش‌های من</h2>
                    <p>تمام سفارش‌های ثبت‌شدهٔ شما با وضعیت لحظه‌ای</p>
                </div>
            </div>
            <a href="{{ route('front.products.index') }}" class="btn btn-sm main-color-one-bg waves-effect waves-light">
                <i class="bi bi-plus-lg text-white"></i> خرید جدید
            </a>
        </div>

        {{-- تب‌های فیلتر وضعیت --}}
        @php $allCount = auth('web')->user()->orders()->count(); @endphp
        @php $paidCount = auth('web')->user()->orders()->where('status', 'paid')->count(); @endphp
        @php $unpaidCount = auth('web')->user()->orders()->where('status', 'unpaid')->count(); @endphp
        @php $cancelledCount = auth('web')->user()->orders()->whereIn('status', ['cancelled', 'failed'])->count(); @endphp
        @php
            $returnedCount = 0;
            if (class_exists(\App\Models\ReturnRequest::class)) {
                $returnedCount = \App\Models\ReturnRequest::where('user_id', auth('web')->id())
                    ->whereNotIn('status', ['cancelled'])
                    ->distinct('order_id')
                    ->count('order_id');
            }
        @endphp

        <div class="usp-filter-pills mb-3">
            <a href="?status=all" class="usp-pill {{ (request('status', 'all') === 'all') ? 'is-active' : '' }}">
                <i class="bi bi-list"></i> همه
                @if($allCount > 0)<span class="usp-pill-count">{{ $allCount }}</span>@endif
            </a>
            <a href="?status=paid" class="usp-pill {{ (request('status') === 'paid') ? 'is-active' : '' }}">
                <i class="bi bi-check-circle"></i> تکمیل شده
                @if($paidCount > 0)<span class="usp-pill-count">{{ $paidCount }}</span>@endif
            </a>
            <a href="?status=unpaid" class="usp-pill {{ (request('status') === 'unpaid') ? 'is-active' : '' }}">
                <i class="bi bi-clock"></i> در انتظار پرداخت
                @if($unpaidCount > 0)<span class="usp-pill-count">{{ $unpaidCount }}</span>@endif
            </a>
            <a href="?status=cancelled" class="usp-pill {{ (request('status') === 'cancelled') ? 'is-active' : '' }}">
                <i class="bi bi-x-circle"></i> لغو شده
                @if($cancelledCount > 0)<span class="usp-pill-count">{{ $cancelledCount }}</span>@endif
            </a>
            @if(class_exists(\App\Models\ReturnRequest::class))
                <a href="?status=returned" class="usp-pill {{ (request('status') === 'returned') ? 'is-active' : '' }}">
                    <i class="bi bi-arrow-counterclockwise"></i> مرجوع شده
                    @if($returnedCount > 0)<span class="usp-pill-count">{{ $returnedCount }}</span>@endif
                </a>
            @endif
        </div>

        @php
            $query = auth('web')->user()->orders()->latest();
            if (request('status') === 'paid') {
                $query->where('status', 'paid');
            } elseif (request('status') === 'unpaid') {
                $query->where('status', 'unpaid');
            } elseif (request('status') === 'cancelled') {
                $query->whereIn('status', ['cancelled', 'failed']);
            } elseif (request('status') === 'returned' && class_exists(\App\Models\ReturnRequest::class)) {
                $returnedOrderIds = \App\Models\ReturnRequest::where('user_id', auth('web')->id())
                    ->whereNotIn('status', ['cancelled'])
                    ->pluck('order_id')
                    ->unique()
                    ->toArray();
                $query->whereIn('id', $returnedOrderIds);
            }
            $orders = $query->paginate(10);
        @endphp

        {{-- لیست سفارش‌ها --}}
        @if($orders->count())
            <div class="orders">
                @foreach ($orders as $order)

                    @php
                        // بررسی نوع پرداخت : پکیج ادغامی CreditPay (پرداخت قسطی و اعتباری) جایگزین پکیج قدیمی اقساطی شد
                        $isInstallment = false;
                        $isCredit = false;
                        if (function_exists('module_is_active') && module_is_active('CreditPay')) {
                            $isInstallment = \Modules\CreditPay\Models\InstallmentPlan::where('order_id', $order->id)->exists();
                            $isCredit = \Modules\CreditPay\Models\CreditOrder::where('order_id', $order->id)->exists();
                        }

                        // بررسی مرجوعی
                        $hasReturn = false;
                        if (class_exists(\App\Models\ReturnRequest::class)) {
                            $hasReturn = \App\Models\ReturnRequest::where('order_id', $order->id)
                                ->whereNotIn('status', ['cancelled'])
                                ->exists();
                        }

                        // محصولات سفارش
                        $orderProducts = $order->items->take(4);
                        $totalProducts = $order->items->count();
                        $remainingProducts = max(0, $totalProducts - 4);
                    @endphp

                    <div class="order-item">
                        {{-- وضعیت + لینک --}}
                        <div class="order-item-status">
                            <div class="order-item-status-item">
                                @if($hasReturn)
                                    <span class="usp-badge is-warning mb-1"><i class="bi bi-arrow-counterclockwise"></i> مرجوعی</span>
                                @endif
                                @if($order->status == 'paid')
                                    <span class="usp-badge is-success"><i class="bi bi-bag-check-fill"></i> تحویل شده</span>
                                @elseif($order->status == 'unpaid')
                                    <span class="usp-badge is-warning"><i class="bi bi-clock-fill"></i> پرداخت نشده</span>
                                @else
                                    <span class="usp-badge is-danger"><i class="bi bi-x-circle-fill"></i> لغو شده</span>
                                @endif
                            </div>
                            <div class="order-item-status-item">
                                <a href="{{ route('front.orders.show', ['order' => $order]) }}" class="usp-order-arrow" aria-label="مشاهده سفارش {{ $order->id }}">
                                    <i class="bi bi-arrow-left"></i>
                                </a>
                            </div>
                        </div>

                        {{-- ردیف اطلاعات --}}
                        <div class="order-item-detail">
                            <ul class="nav">
                                <li class="nav-item">
                                    <span class="text-mute">تاریخ ثبت</span>
                                    <strong>{{ jdate($order->created_at)->format('%d %B %Y') }}</strong>
                                </li>
                                <li class="nav-item">
                                    <span class="text-mute">کد سفارش</span>
                                    <strong>#{{ $order->id }}</strong>
                                </li>
                                <li class="nav-item">
                                    <span class="text-mute">پرداخت</span>
                                    @if($isInstallment)
                                        <strong><i class="bi bi-cash-coin"></i> اقساطی</strong>
                                    @elseif($isCredit)
                                        <strong><i class="bi bi-credit-card"></i> اعتباری</strong>
                                    @elseif ($order->walletHistory)
                                        <strong><i class="bi bi-wallet2"></i> کیف پول</strong>
                                    @else
                                        <strong><i class="bi bi-credit-card"></i> اینترنتی</strong>
                                    @endif
                                </li>
                                <li class="nav-item">
                                    <span class="text-mute">مبلغ قابل پرداخت</span>
                                    <strong>{{ number_format($order->price) }} تومان</strong>
                                </li>
                            </ul>
                        </div>

                        {{-- تصاویر محصولات --}}
                        <div class="order-item-product-list">
                            @foreach($orderProducts as $item)
                                <div class="order-item-product-list-item">
                                    @if($item->product && $item->product->image)
                                        <img src="{{ asset($item->product->image) }}" alt="{{ $item->title }}" class="img-thumbnail" width="70" height="70">
                                    @else
                                        <div class="img-thumbnail d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                            @if($remainingProducts > 0)
                                <div class="order-item-product-list-item">
                                    <div class="img-thumbnail d-flex align-items-center justify-content-center text-muted" style="width: 70px; height: 70px;">
                                        +{{ $remainingProducts }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- دکمه‌های عملیات --}}
                        <div class="order-item-show">
                            @if($order->totalDiscount() != 0)
                                <p class="text-success font-12">تخفیف: {{ number_format($order->totalDiscount()) }} تومان</p>
                            @endif
                            <div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
                                @if($order->status == 'unpaid')
                                    <a href="{{ route('front.orders.pay', ['order' => $order]) }}"
                                       class="btn btn-sm border-0 main-color-one-bg waves-effect waves-light">
                                        <i class="bi bi-credit-card text-white"></i> پرداخت
                                    </a>
                                @endif
                                <a href="{{ route('front.orders.show', ['order' => $order]) }}"
                                   class="btn btn-sm btn-light">
                                    <i class="bi bi-card-list"></i> جزئیات
                                </a>
                            </div>
                        </div>
                    </div>

                @endforeach
            </div>

            {{-- صفحه‌بندی --}}
            <div class="my-paginate mt-4 pb-3">
                <nav aria-label="Page navigation">
                    {{ $orders->links("pagination::bootstrap-4") }}
                </nav>
            </div>

        @else
            {{-- حالت خالی --}}
            <div class="usp-card">
                <div class="usp-card-body">
                    <div class="usp-empty">
                        <span class="usp-empty-icon" aria-hidden="true"><i class="bi bi-bag"></i></span>
                        <h6>هیچ سفارشی یافت نشد</h6>
                        <p>شما هنوز سفارشی ثبت نکرده‌اید یا در این وضعیت سفارشی ندارید.</p>
                        <a href="{{ route('front.products.index') }}" class="btn border-0 main-color-one-bg waves-effect waves-light">
                            <i class="bi bi-shop text-white"></i> شروع خرید
                        </a>
                    </div>
                </div>
            </div>
        @endif

    </div>

@endsection
