@extends('front::user.layouts.master')

@section('user-content')

    {{-- مشاهده تیکت — بازطراحی کامل فاز ۲۰۰ --}}
    <div class="usp-scope">

        {{-- سربرگ صفحه --}}
        <div class="usp-page-head">
            <div class="usp-page-head-title">
                <span class="usp-page-head-icon" aria-hidden="true"><i class="bi bi-ticket-detailed"></i></span>
                <div>
                    <h2>{{ $ticket->subject }}</h2>
                    <p>کد تیکت #{{ $ticket->id }} • تاریخ ایجاد {{ jdate($ticket->created_at)->format('%d %B %Y') }}</p>
                </div>
            </div>
            <a href="{{ route('front.tickets.index') }}" class="btn btn-sm btn-light">
                <i class="bi bi-arrow-right"></i> بازگشت به لیست تیکت ها
            </a>
        </div>

        {{-- خلاصه وضعیت تیکت --}}
        <div class="usp-card">
            <div class="usp-card-body">
                <div class="usp-info-grid">
                    <div class="usp-info-item">
                        <h6><i class="bi bi-chat-left-text"></i> موضوع تیکت</h6>
                        <p>{{ $ticket->subject }}</p>
                    </div>
                    <div class="usp-info-item">
                        <h6><i class="bi bi-clock"></i> تاریخ ایجاد</h6>
                        <p>{{ jdate($ticket->created_at)->format('%d %B %Y') }}</p>
                    </div>
                    <div class="usp-info-item">
                        <h6><i class="bi bi-exclamation-triangle"></i> اولویت</h6>
                        <p>{{ $ticket->priorityText() }}</p>
                    </div>
                    <div class="usp-info-item">
                        <h6><i class="bi bi-activity"></i> وضعیت</h6>
                        <p>
                            <span class="usp-badge {{ $ticket->status == 'answered' ? 'is-success' : ($ticket->status == 'close' ? 'is-danger' : 'is-warning') }}"><i class="bi bi-circle-fill"></i> {{ $ticket->statusText() }}</span>
                            <span class="text-muted font-14">#{{ $ticket->id }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- گفت وگوها --}}
        <div class="usp-card">
            <div class="usp-card-head">
                <h4>گفت وگو</h4>
            </div>
            <div class="usp-card-body">

                <div class="ticket-message orders">

                    {{-- Messages--}}
                    @foreach ($ticket->messages()->oldest()->get() as $message)

                        @if ($loop->first)
                            <div class="divider">
                                <div class="divider-text">{{ jdate($message->created_at)->ago() }}</div>
                            </div>
                        @endif

                        <div class="order-item">
                            @if ($message->admin_id)
                                @include('front::user.tickets.partials.message', ['own' => true])
                            @else
                                @include('front::user.tickets.partials.message', ['own' => false])
                            @endif
                        </div>

                    @endforeach

                </div>

            </div>
        </div>

        {{-- Reply Form --}}
        <div class="usp-card">
            <div class="usp-card-head">
                <h4>ثبت پیام جدید</h4>
            </div>
            <div class="usp-card-body">
                <form class="form" id="ticket-update-form" data-redirect="{{ route('front.tickets.show', ['ticket' => $ticket]) }}" action="{{ route('front.tickets.update', ['ticket' => $ticket]) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')

                    <div class="form-body">
                        <div class="row">

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

                            <div class="col-12 pb-3">
                                <div class="parent-btn">
                                    <button id="submit-btn" class="btn border-0 main-color-one-bg waves-effect waves-light py-2 px-5">
                                        ثبت پیام
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

    <script src="{{ theme_asset('js/pages/tickets/show.js') }}"></script>
@endpush
