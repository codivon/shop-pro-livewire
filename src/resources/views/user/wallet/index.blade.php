@extends('front::user.layouts.master')

@section('user-content')

    {{-- کیف پول — بازطراحی کامل فاز ۲۰۰ --}}
    <div class="usp-scope">

        @if(session('message') == 'ok')
            <div class="alert alert-success mb-3" role="alert">
                <strong>افزایش موجودی با موفقیت انجام شد</strong>.
            </div>
        @elseif(session('transaction-error'))
            <div class="alert alert-danger mb-3" role="alert">
                <strong>{{ session('transaction-error') }}</strong>.
            </div>
        @endif

        {{-- سربرگ صفحه --}}
        <div class="usp-page-head">
            <div class="usp-page-head-title">
                <span class="usp-page-head-icon is-green" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                <div>
                    <h2>کیف پول</h2>
                    <p>مدیریت موجودی و تراکنش‌های شما</p>
                </div>
            </div>
        </div>

        {{-- کارت موجودی --}}
        <div class="usp-card">
            <div class="usp-card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <span class="usp-stat-icon is-green ms-3" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                    <div>
                        <span class="font-14 text-muted d-block">موجودی کیف پول شما</span>
                        <div class="d-flex align-items-baseline mt-1">
                            <span class="font-30 fw-bold me-1">{{ number_format($user->getWallet()->balance()) }}</span>
                            <span class="font-14 text-muted">تومان</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="btn border-0 main-color-one-bg waves-effect waves-light js-trigger-wallet-modal cursor-pointer"
                          data-bs-toggle="modal" data-bs-target="#new-wallet-show-modal">افزایش اعتبار کیف پول
                        <i class="bi bi-plus-lg text-white"></i>
                    </span>
                    <span class="btn btn-outline-secondary js-trigger-wallet-modal cursor-pointer {{number_format($user->getWallet()->balance())>0 ? '' : 'disabled'}}"
                          data-bs-toggle="modal" data-bs-target="#wallet-show-modal">
                        برداشت از کیف پول
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </span>
                </div>
            </div>
        </div>

        {{-- تاریخچه تراکنش‌ها --}}
        <div class="usp-card">
            <div class="usp-card-head">
                <h4>تاریخچه کیف پول</h4>
                <span class="btn btn-sm btn-light cursor-pointer" data-bs-toggle="modal" data-bs-target="#new-wallet-show-modal">افزایش موجودی کیف پول</span>
            </div>
            <div class="usp-card-body p-0">
                @if($histories->count())
                    <div class="responsive-table p-0">
                        <table class="table main-table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>مبلغ (تومان)</th>
                                <th>نوع تراکنش</th>
                                <th>تاریخ</th>
                                <th class="text-center">وضعیت</th>
                                <th>جزییات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($histories as $history)
                                @php
                                    $is_deposit = $history->type == 'deposit';
                                @endphp
                                <tr>
                                    <td class="font-14">{{ $loop->iteration }}</td>
                                    <td class="font-14 {{ $is_deposit ? 'text-success' : 'text-danger' }}">{{ number_format($history->amount) }}</td>
                                    <td class="font-14">
                                        @if($history->withdraw)
                                            برداشت وجه
                                            <span class="usp-badge is-danger ms-1"><i class="bi bi-arrow-left"></i></span>
                                        @else
                                            @if ($is_deposit)
                                                افزایش اعتبار
                                                <span class="usp-badge is-success ms-1"><i class="bi bi-arrow-up"></i></span>
                                            @else
                                                کاهش اعتبار
                                                <span class="usp-badge is-danger ms-1"><i class="bi bi-arrow-down"></i></span>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="font-14 ltr">{{ jdate($history->created_at) }}</td>

                                    <td class="font-14">
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

                                    </td>

                                    <td class="font-14">
                                        <a class="show-history cursor-pointer" data-action="{{ route('front.wallet.show', ['wallet' => $history]) }}" onclick="return false;">
                                            <i class="bi bi-chevron-left main-color-one-color"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="usp-empty">
                        <span class="usp-empty-icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                        <h6>هنوز تراکنشی ثبت نشده است</h6>
                        <p>شما تا به حال تراکنشی در کیف پول خود نداشته‌اید.</p>
                    </div>
                @endif
            </div>
        </div>

        @if($histories->count())
            <div class="my-paginate mt-4 pb-3">
                <nav aria-label="Page navigation">
                    {{$histories->links("pagination::bootstrap-4")}}
                </nav>
            </div>
        @endif

    </div>

@endsection

@push('scripts')
    <div class="usp-scope">
        {{-- show Modal --}}
        <div class="modal fade" id="new-wallet-show-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel21">افزایش موجودی کیف پول</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body middle-container">
                        <form id="wallet-create-form" action="{{ route('front.wallet.store') }}" class="setting_form" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 col-12 form-group">
                                    <label for="amount">مبلغ (تومان) <span class="text-danger">*</span></label>
                                    <input type="number" id="amount" name="amount" class="form-control amount-input" placeholder="مثال : 10,000" required>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group">
                                        <label for="gateway">انتخاب درگاه پرداخت
                                            <span class="text-danger">*</span></label>
                                        <select class="form-select" name="gateway" id="gateway" required>
                                            <option value="">درگاه مورد نظر خود را انتخاب کنید</option>
                                            @foreach ($gateways as $gateway)
                                                <option value="{{ $gateway->key }}">{{ $gateway->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>


                            <hr>
                            <div class="row justify-content-center">
                                <div class="col-md-5">
                                    <div class="d-grid">
                                        <button id="submit-btn" class="btn border-0 main-color-one-bg waves-effect waves-light">
                                            افزایش موجودی
                                            <i class="bi bi-check-lg text-white"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>


                        </form>


                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="wallet-show-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel21">برداشت از کیف پول</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body middle-container">
                        <form id="wallet-withdraw-form" action="{{ route('front.wallet.withdraw') }}" class="setting_form" data-amount="{{ $user->getWallet()->balance() }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 col-12 form-group">
                                    <label for="amount">مبلغ (تومان) <span class="text-danger">*</span></label>
                                    <input type="number" id="amount" name="amount" class="form-control amount-input" placeholder="مثال : 10,000" required>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="font-14 text-muted">موجودی کیف پول:
                                        {{ number_format($user->getWallet()->balance()) }}
                                        تومان
                                    </div>
                                </div>
                            </div>


                            <hr>
                            <div class="row justify-content-center">
                                <div class="col-md-5">
                                    <div class="d-grid">
                                        <button id="submit-btn" class="btn border-0 main-color-one-bg waves-effect waves-light">
                                            درخواست برداشت
                                            <i class="bi bi-check-lg text-white"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>


                        </form>


                    </div>
                </div>
            </div>
        </div>
        {{-- show Modal --}}
        <div class="modal fade" id="history-show-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="myModalLabel21">جزئیات تراکنش</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div id="history-detail" class="modal-body">


                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ theme_asset('js/pages/user/jquery-bs5-modal-compat.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>
    <script src="{{ theme_asset('js/pages/wallet/index.js') }}"></script>
@endpush
