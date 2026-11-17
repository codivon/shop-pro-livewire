@extends('back.layouts.printable', ['title' => 'تاریخچه موجودی — '.$product->title ?? ''])

@section('content')
    {{-- نسخهٔ کامل صفحهٔ تاریخچه موجودی (مسیر غیر-AJAX روت np.warehouses.product.stock-history) —
         پنل جدید این آدرس را در تب جدید باز می‌کند؛ قبل از این فایل، رندر 500 می‌داد. --}}
    <div class="container py-4 rtl">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0">
                <i class="feather icon-layers"></i>
                تاریخچه موجودی محصول
            </h5>
            <span class="badge bg-light text-dark">
                <i class="feather icon-home"></i> انبار: {{ $warehouse->title ?? $warehouse->name ?? '#' . $warehouse->id }}
            </span>
        </div>

        {{-- محتوای مشترک با AJAX (کارت محصول + آمار + جدول تاریخچه) --}}
        @include('back.warehouses.partials.stock-history-content', [
            'product' => $product,
            'warehouse' => $warehouse,
            'prices' => $prices,
            'stockHistory' => $stockHistory,
            'totalStock' => $totalStock,
            'totalReserved' => $totalReserved,
            'totalSold' => $totalSold,
            'variationsCount' => $variationsCount,
        ])

        {{-- صفحه‌بندی --}}
        <div class="mt-3">
            {{ $stockHistory->links() }}
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('back/app-assets/fonts/feather/iconfont.css') }}">
    <link rel="stylesheet" href="{{ asset('back/assets/css/pages/warehouses.css') }}">
    <style>
        body { background: #f5f6fa; }
        .rtl { direction: rtl; }
    </style>
@endpush
