{{-- جزئیات تراکنش کیف پول — پنل مدرن فروشنده (پورت sellers-old/panel/wallet/show) --}}
<div>
    {{-- ================= سربرگ صفحه ================= --}}
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="flex items-center gap-2 text-lg font-extrabold text-ink-900 dark:text-night-ink">
                <x-admin-panel::icon name="wallet" class="size-5 text-brand-600 dark:text-brand-400" />
                جزئیات تراکنش کیف پول
            </h1>
            <p class="mt-1 text-xs text-ink-500 dark:text-night-ink-2">مشخصات کامل تراکنش انتخاب‌شده</p>
        </div>
        <a href="{{ $indexUrl }}" wire:navigate
           class="ap-btn-outline inline-flex min-h-11 items-center gap-1.5 rounded-lg px-3 text-xs font-bold">
            <x-admin-panel::icon name="arrow-right" class="size-4" />
            بازگشت به کیف پول
        </a>
    </div>

    {{-- ================= کارت مشخصات تراکنش ================= --}}
    <section class="ap-card overflow-hidden">
        <div class="divide-y divide-line/70 dark:divide-night-line/70">

            {{-- شناسه --}}
            <div class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:gap-4">
                <span class="w-48 shrink-0 text-xs font-bold text-ink-500 dark:text-night-ink-2">آیدی</span>
                <span class="fa-digits text-sm font-bold text-ink-900 dark:text-night-ink-1">{{ number_format($history->id) }}</span>
            </div>

            {{-- مبلغ --}}
            <div class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:gap-4">
                <span class="w-48 shrink-0 text-xs font-bold text-ink-500 dark:text-night-ink-2">مبلغ</span>
                <span class="fa-digits text-sm font-extrabold text-ink-900 dark:text-night-ink-1">
                    {{ number_format($history->amount) }} {{ trans('messages.currency.suffix') }}
                </span>
            </div>

            {{-- نوع تراکنش --}}
            <div class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:gap-4">
                <span class="w-48 shrink-0 text-xs font-bold text-ink-500 dark:text-night-ink-2">نوع تراکنش</span>
                <span class="inline-flex items-center gap-2">
                    @if($history->withdraw)
                        <span class="ap-badge inline-flex items-center gap-1 bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                            <x-admin-panel::icon name="arrow-left" class="size-3.5" />
                            برداشت وجه
                        </span>
                    @elseif ($history->type == 'deposit')
                        <span class="ap-badge inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <x-admin-panel::icon name="arrow-up" class="size-3.5" />
                            افزایش اعتبار
                        </span>
                    @else
                        <span class="ap-badge inline-flex items-center gap-1 bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">
                            <x-admin-panel::icon name="arrow-down" class="size-3.5" />
                            کاهش اعتبار
                        </span>
                    @endif
                </span>
            </div>

            {{-- تاریخ --}}
            <div class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:gap-4">
                <span class="w-48 shrink-0 text-xs font-bold text-ink-500 dark:text-night-ink-2">تاریخ تراکنش</span>
                <span class="fa-digits text-sm text-ink-700 dark:text-night-ink-2" dir="ltr">{{ jdate($history->created_at) }}</span>
            </div>

            {{-- وضعیت --}}
            <div class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:gap-4">
                <span class="w-48 shrink-0 text-xs font-bold text-ink-500 dark:text-night-ink-2">وضعیت</span>
                <span class="inline-flex flex-wrap items-center gap-2">
                    @if($history->status == 'success')
                        <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">موفق</span>
                        @if($history->withdraw)
                            @if($history->status_pay == 'waiting')
                                <span class="ap-badge bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">در انتظار پرداخت</span>
                            @elseif($history->status_pay == 'pay')
                                <span class="ap-badge bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">پرداخت شده</span>
                            @elseif($history->status_pay == 'unpay')
                                <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">پرداخت نشده</span>
                            @endif
                        @endif
                    @else
                        <span class="ap-badge bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">ناموفق</span>
                    @endif
                </span>
            </div>

            {{-- توضیحات (خروجی HTML کنترل‌شدهٔ خودِ سیستم — مثل پنل قدیم) --}}
            <div class="flex flex-col gap-1 p-4 sm:flex-row sm:gap-4">
                <span class="w-48 shrink-0 text-xs font-bold text-ink-500 dark:text-night-ink-2">توضیحات</span>
                <span class="text-sm text-ink-700 dark:text-night-ink-2">{!! $history->description !!}</span>
            </div>

            {{-- شماره پیگیری پرداخت --}}
            @if($history->status_pay == 'pay' and $history->trackingId != null)
                <div class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:gap-4">
                    <span class="w-48 shrink-0 text-xs font-bold text-ink-500 dark:text-night-ink-2">شماره پیگیری (تراکنش)</span>
                    <span class="fa-digits text-sm font-bold text-ink-900 dark:text-night-ink-1">{{ $history->trackingId }}</span>
                </div>
            @endif

            {{-- سفارش مرتبط --}}
            @if ($history->order)
                <div class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:gap-4">
                    <span class="w-48 shrink-0 text-xs font-bold text-ink-500 dark:text-night-ink-2">شماره سفارش</span>
                    <a href="{{ route('seller.orders.show', ['order' => $history->order]) }}" wire:navigate
                       class="fa-digits inline-flex items-center gap-1 text-sm font-bold text-brand-600 hover:underline dark:text-brand-400">
                        {{ $history->order->id }}
                        <x-admin-panel::icon name="external-link" class="size-3.5" />
                    </a>
                </div>
            @endif

            {{-- شماره تراکنش درگاه --}}
            @if ($history->transaction)
                <div class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:gap-4">
                    <span class="w-48 shrink-0 text-xs font-bold text-ink-500 dark:text-night-ink-2">شماره تراکنش</span>
                    <span class="fa-digits text-sm text-ink-700 dark:text-night-ink-2" dir="ltr">{{ $history->transaction->transId }}</span>
                </div>
                <div class="flex flex-col gap-1 p-4 sm:flex-row sm:items-center sm:gap-4">
                    <span class="w-48 shrink-0 text-xs font-bold text-ink-500 dark:text-night-ink-2">شماره پیگیری</span>
                    <span class="fa-digits text-sm text-ink-700 dark:text-night-ink-2" dir="ltr">{{ $history->transaction->traceNumber }}</span>
                </div>
            @endif
        </div>
    </section>
</div>
