@extends('front::user.layouts.master')

@section('user-content')

    {{-- ثبت تیکت جدید — بازطراحی کامل فاز ۲۰۰ --}}
    <div class="usp-scope">

        {{-- سربرگ صفحه --}}
        <div class="usp-page-head">
            <div class="usp-page-head-title">
                <span class="usp-page-head-icon" aria-hidden="true"><i class="bi bi-plus-circle"></i></span>
                <div>
                    <h2>ثبت تیکت جدید</h2>
                    <p>پرسش یا مشکل خود را برای تیم پشتیبانی ارسال کنید.</p>
                </div>
            </div>
            <a href="{{ route('front.tickets.index') }}" class="btn btn-sm btn-light">
                <i class="bi bi-arrow-right"></i> بازگشت به لیست
            </a>
        </div>

        {{-- کارت فرم ثبت تیکت --}}
        <div class="usp-card">
            <div class="usp-card-head">
                <h4>مشخصات تیکت</h4>
            </div>
            <div class="usp-card-body">
                <form class="form" id="ticket-create-form" data-redirect="{{ route('front.tickets.index') }}" action="{{ route('front.tickets.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>موضوع</label>
                                    <input type="text" class="form-control" name="subject">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>اولویت</label>
                                    <select name="priority" class="form-select">
                                        <option value="low">کم</option>
                                        <option value="medium">متوسط</option>
                                        <option value="hight">زیاد</option>
                                    </select>
                                </div>
                            </div>


                            <div class="col-12">
                                <div class="form-group">
                                    <label for="message">پیام</label>
                                    <textarea id="message" class="form-control" rows="4" name="message"></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>فایل های پیوست</label>
                                    <input type="file" class="form-control" name="upload_files[]"  multiple>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="parent-btn">
                                    <button id="submit-btn" class="btn border-0 main-color-one-bg waves-effect waves-light py-2 px-5 w-100 w-md-auto">
                                        ایجاد تیکت
                                        <i class="bi bi-check-lg text-white"></i>
                                    </button>
                                </div>
                            </div>
                        </div>


                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ theme_asset('js/plugins/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ theme_asset('js/plugins/jquery-validation/localization/messages_fa.min.js') }}?v=2"></script>

    <script src="{{ theme_asset('js/pages/tickets/create.js') }}"></script>
@endpush
