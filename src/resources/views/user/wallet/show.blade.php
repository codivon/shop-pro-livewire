<div class="usp-scope">
    {{-- جزئیات تراکنش کیف پول — بارگذاری آجاکسی در #history-detail --}}
    <div class="usp-page-head">
        <div class="usp-page-head-title">
            <span class="usp-page-head-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
            <div>
                <h2>جزئیات تراکنش</h2>
                <p>مشخصات کامل این تراکنش کیف پول</p>
            </div>
        </div>
        <a href="{{ route('front.wallet.index') }}" class="btn btn-sm btn-light">بازگشت به کیف پول <i class="bi bi-arrow-left"></i></a>
    </div>

    <div class="usp-card">
        <div class="usp-card-body p-2">
            <div class="usp-info-grid">
                <div class="usp-info-item">
                    <h6><i class="bi bi-hash"></i> آیدی</h6>
                    <p>{{ $history->id }}</p>
                </div>

                <div class="usp-info-item">
                    <h6><i class="bi bi-cash-coin"></i> مبلغ</h6>
                    <p>{{ number_format($history->amount) }} تومان</p>
                </div>

                <div class="usp-info-item">
                    <h6><i class="bi bi-arrow-left-right"></i> نوع تراکنش</h6>
                    <p>
                        @if($history->withdraw)
                            برداشت وجه
                            <span class="usp-badge is-danger ms-1"><i class="bi bi-arrow-left"></i></span>
                        @else
                            @if ($history->type == 'deposit')
                                افزایش اعتبار
                                <span class="usp-badge is-success ms-1"><i class="bi bi-arrow-up"></i></span>
                            @else
                                کاهش اعتبار
                                <span class="usp-badge is-danger ms-1"><i class="bi bi-arrow-down"></i></span>
                            @endif
                        @endif
                    </p>
                </div>

                <div class="usp-info-item">
                    <h6><i class="bi bi-calendar3"></i> تاریخ تراکنش</h6>
                    <p class="ltr">{{ jdate($history->created_at) }}</p>
                </div>

                <div class="usp-info-item">
                    <h6><i class="bi bi-clipboard-check"></i> وضعیت</h6>
                    <p class="d-flex align-items-center flex-wrap gap-1">
                        @if($history->status == 'success')
                            <span class="usp-badge is-success">موفق</span>
                            @if($history->withdraw)
                                @if($history->status_pay=="waiting")
                                    <span class="usp-badge is-warning">در انتظار پرداخت</span>
                                @elseif($history->status_pay=="pay")
                                    <span class="usp-badge is-success">پرداخت شده</span>
                                @elseif($history->status_pay=="unpay")
                                    <span class="usp-badge is-danger">پرداخت نشده</span>
                                @endif

                            @endif
                        @else
                            <span class="usp-badge is-danger">ناموفق</span>
                        @endif
                    </p>
                </div>

                @if($history->status_pay=="pay" and $history->trackingId!=null)
                    <div class="usp-info-item">
                        <h6><i class="bi bi-qr-code"></i> شماره پیگیری (تراکنش)</h6>
                        <p>{{ $history->trackingId }}</p>
                    </div>
                @endif

                @if ($history->transaction)
                    <div class="usp-info-item">
                        <h6><i class="bi bi-credit-card"></i> شماره تراکنش</h6>
                        <p>{{ $history->transaction->transId }}</p>
                    </div>
                    <div class="usp-info-item">
                        <h6><i class="bi bi-route"></i> شماره پیگیری</h6>
                        <p>{{ $history->transaction->traceNumber }}</p>
                    </div>
                @endif

                @if ($history->order)
                    <div class="usp-info-item">
                        <h6><i class="bi bi-bag"></i> شماره سفارش</h6>
                        <p><a target="_blank" href="{{ route('front.orders.show', ['order' => $history->order]) }}">{{ $history->order->id }}</a></p>
                    </div>
                @endif

                <div class="usp-info-item">
                    <h6><i class="bi bi-card-text"></i> توضیحات</h6>
                    <p>{!! $history->description !!}</p>
                </div>
            </div>
        </div>
    </div>
</div>
