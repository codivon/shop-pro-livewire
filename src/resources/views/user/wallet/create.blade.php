@extends('front::user.layouts.master')

@section('user-content')

    {{-- افزایش موجودی کیف پول — بازطراحی کامل فاز ۲۰۰ --}}
    <div class="usp-scope">

        @if ($errors->any())
            <div class="alert alert-danger mb-3">
                @foreach ($errors->all() as $error)
                    <p class="mb-0">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{-- سربرگ صفحه --}}
        <div class="usp-page-head">
            <div class="usp-page-head-title">
                <span class="usp-page-head-icon is-green" aria-hidden="true"><i class="bi bi-plus-circle"></i></span>
                <div>
                    <h2>افزایش موجودی کیف پول</h2>
                    <p>مبلغ موردنظر را وارد و درگاه پرداخت را انتخاب کنید</p>
                </div>
            </div>
            <a href="{{ route('front.wallet.index') }}" class="btn btn-sm btn-light">بازگشت <i class="bi bi-arrow-left"></i></a>
        </div>

        {{-- فرم افزایش موجودی --}}
        <div class="usp-card">
            <div class="usp-card-head">
                <h4>اطلاعات پرداخت</h4>
            </div>
            <div class="usp-card-body">
                <form id="wallet-create-form" action="{{ route('front.wallet.store') }}" class="setting_form" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="amount">مبلغ (تومان)</label>
                                <input type="number" class="form-control amount-input" name="amount" id="amount">
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="gateway">انتخاب درگاه پرداخت</label>
                                <select class="form-select" name="gateway" id="gateway" required>
                                    <option value="">انتخاب کنید</option>
                                    @foreach ($gateways as $gateway)
                                        <option value="{{ $gateway->key }}">{{ $gateway->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-center mt-3">
                        <button id="submit-btn" type="submit" class="btn border-0 main-color-one-bg waves-effect waves-light py-2 px-5">
                            <i class="bi bi-arrow-left text-white me-1"></i>
                            افزایش موجودی
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>

    <script src="{{ theme_asset('js/pages/wallet/create.js') }}"></script>
@endpush
