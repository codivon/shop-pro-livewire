@extends('front::user.layouts.master')

@section('user-content')

    {{-- لیست تیکت ها — بازطراحی کامل فاز ۲۰۰ --}}
    <div class="usp-scope">

        {{-- سربرگ صفحه --}}
        <div class="usp-page-head">
            <div class="usp-page-head-title">
                <span class="usp-page-head-icon" aria-hidden="true"><i class="bi bi-question-circle"></i></span>
                <div>
                    <h2>تیکت های شما</h2>
                    <p>پیگیری درخواست‌ها و گفت‌وگو با تیم پشتیبانی</p>
                </div>
            </div>
            <a href="{{ route('front.tickets.create') }}" class="btn btn-sm main-color-one-bg waves-effect waves-light">
                <i class="bi bi-plus-circle text-white"></i> ثبت تیکت جدید
            </a>
        </div>

        {{-- لیست تیکت ها --}}
        @if($tickets->count())
            <div class="usp-card">
                <div class="usp-card-body p-0">
                    <div class="responsive-table p-0">
                        <table class="table main-table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>شماره تیکت</th>
                                <th>موضوع</th>
                                <th>تاریخ ثبت تیکت</th>
                                <th>اولویت</th>
                                <th>وضعیت</th>
                                <th>جزییات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($tickets as $ticket)
                                <tr>
                                    <td class="font-14">{{ $loop->iteration}}</td>
                                    <td class="font-14 main-color-one-color">{{ $ticket->id }}</td>
                                    <td class="font-14">{{ $ticket->subject }}</td>
                                    <td class="font-14">{{ jdate($ticket->created_at)->format('%d %B %Y') }}</td>
                                    <td class="font-14">{{ $ticket->priorityText() }}</td>
                                    <td class="font-14">
                                        @switch($ticket->status)
                                            @case('pending')
                                                <span class="usp-badge is-warning"><i class="bi bi-hourglass-split"></i> در انتظار پاسخ</span>
                                                @break
                                            @case('answered')
                                                <span class="usp-badge is-success"><i class="bi bi-check-circle"></i> پاسخ داده شده</span>
                                                @break
                                            @case('open')
                                                <span class="usp-badge is-info"><i class="bi bi-envelope-open"></i> باز</span>
                                                @break
                                            @default
                                                <span class="usp-badge is-danger"><i class="bi bi-x-circle"></i> بسته</span>
                                        @endswitch
                                    </td>
                                    <td class="font-14">
                                        <a href="{{ route('front.tickets.show', ['ticket' => $ticket]) }}"
                                           class="btn btn-sm border-0 main-color-three-bg waves-effect waves-light"> <i class="bi bi-eye me-1 text-white"></i> نمایش</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- صفحه‌بندی --}}
            <div class="my-paginate mt-4 pb-3">
                <nav aria-label="Page navigation">
                    {{$tickets->links("pagination::bootstrap-4")}}
                </nav>
            </div>
        @else
            {{-- حالت خالی --}}
            <div class="usp-card">
                <div class="usp-card-body">
                    <div class="usp-empty">
                        <span class="usp-empty-icon" aria-hidden="true"><i class="bi bi-inbox"></i></span>
                        <h6>هیچ تیکتی ثبت نشده است</h6>
                        <p>برای ارتباط با تیم پشتیبانی، نخستین تیکت خود را ثبت کنید.</p>
                        <a href="{{ route('front.tickets.create') }}" class="btn border-0 main-color-one-bg waves-effect waves-light">
                            <i class="bi bi-plus-circle text-white"></i> ثبت تیکت جدید
                        </a>
                    </div>
                </div>
            </div>
        @endif

    </div>

@endsection
