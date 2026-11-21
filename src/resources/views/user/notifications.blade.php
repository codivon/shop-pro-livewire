@extends('front::user.layouts.master')

@section('user-content')

    {{-- اطلاعیه ها — بازطراحی کامل فاز ۲۰۰ --}}
    <div class="usp-scope">

        {{-- سربرگ صفحه + شمارنده‌ها --}}
        <div class="usp-page-head">
            <div class="usp-page-head-title">
                <span class="usp-page-head-icon" aria-hidden="true"><i class="bi bi-bell"></i></span>
                <div>
                    <h2>{{ trans('front::messages.profile.all-notifications') }}</h2>
                    <p>آخرین خبرها و اعلان‌های فروشگاه را اینجا دنبال کنید</p>
                </div>
            </div>
            <div class="usp-filter-pills">
                <span class="usp-pill cursor-pointer">
                    <i class="bi bi-stack"></i> کل اعلان‌ها:
                    <span class="usp-pill-count">{{ $notifications->total() ?? $notifications->count() }}</span>
                </span>
                <span class="usp-pill cursor-pointer">
                    <i class="bi bi-envelope-exclamation"></i> خوانده‌نشده:
                    <span class="usp-pill-count">{{ Auth::user()->unreadNotifications()->count() }}</span>
                </span>
            </div>
        </div>

        @if($notifications->count())
            <div class="orders">
                @foreach($notifications as $notification)
                    @php
                        $isRead = ! is_null($notification->userRecipients()->first()->read_at);
                    @endphp

                    <div class="order-item {{ $isRead ? 'opacity-75' : '' }}">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <h6 class="mb-0 fw-bold d-flex align-items-center flex-wrap">
                                @if(! $isRead)
                                    <span class="usp-badge is-info ms-1"><i class="bi bi-star-fill"></i> جدید</span>
                                @endif
                                {{ $notification->title }}
                            </h6>
                            <p class="text-muted mb-0 font-13">
                                <i class="bi bi-clock"></i>
                                {{ jdate($notification->created_at)->ago() }}
                            </p>
                        </div>
                        <div class="notifi-desc mt-3 pt-3 border-top">
                            <p class="mb-0 font-14">{{ $notification->message }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="usp-card">
                <div class="usp-card-body">
                    <div class="usp-empty">
                        <span class="usp-empty-icon" aria-hidden="true"><i class="bi bi-bell-slash"></i></span>
                        <h6>صندوق اعلان شما خالی است</h6>
                        <p>وقتی اعلان جدیدی دریافت کنید، اینجا نمایش داده می‌شود</p>
                    </div>
                </div>
            </div>
        @endif

        @if($notifications->count())
            <div class="my-paginate mt-4 pb-3">
                <nav aria-label="Page navigation">
                    {{ $notifications->links('front::components.paginate') }}
                </nav>
            </div>
        @endif

    </div>

@endsection

@include('back.partials.plugins', ['plugins' => ['persian-datepicker', 'jquery.validate']])
