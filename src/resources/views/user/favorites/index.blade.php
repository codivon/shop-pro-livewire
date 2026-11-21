@extends('front::user.layouts.master')

@section('user-content')

    {{-- لیست علاقه مندی ها — بازطراحی کامل فاز ۲۰۰ --}}
    <div class="usp-scope">

        {{-- سربرگ صفحه --}}
        <div class="usp-page-head">
            <div class="usp-page-head-title">
                <span class="usp-page-head-icon is-amber" aria-hidden="true"><i class="bi bi-heart"></i></span>
                <div>
                    <h2>لیست علاقه مندی ها</h2>
                    <p>محصولاتی که ذخیره کرده‌اید و هر وقت خواستید می‌توانید بررسی کنید</p>
                </div>
            </div>
        </div>

        @if($favorites->count())
            <div class="usp-card">
                <div class="usp-card-head">
                    <h4>محصولات ذخیره‌شده</h4>
                </div>
                <div class="usp-card-body">
                    <div class="product-list-row">
                        @foreach ($favorites as $favorite)
                            <div class="product-row">
                                <div class="product-row-desc">
                                    <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}">
                                        <div class="product-row-desc-item">
                                            <div class="product-row-img">
                                                <img src="{{ $favorite->product->image ? asset($favorite->product->image) : asset('/no-image-product.svg') }}"
                                                     alt="{{ $favorite->product->title }}" width="100">
                                            </div>
                                            <div class="product-row-title">
                                                <h6>{{ $favorite->product->title }}</h6>
                                                <div class="product-price">
                                                    <p>{{ $favorite->product->getLowestPrice() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="product-row-icon">
                                        <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}"
                                           class="btn btn-light btn-sm" title="مشاهده محصول"><i class="bi bi-eye"></i></a>
                                        <a class="btn btn-danger btn-sm favorite-remove-btn"
                                           data-action="{{ route('front.favorites.destroy', ['favorite' => $favorite]) }}"
                                           data-bs-toggle="modal" data-bs-target="#favorite-delete-modal"
                                           title="حذف از علاقه مندی ها"><i class="bi bi-trash text-white"></i></a>
                                        <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}"
                                           class="btn main-color-one-bg btn-sm" title="افزودن به سبد"><i class="bi bi-cart-plus text-white"></i></a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="usp-card">
                <div class="usp-card-body">
                    <div class="usp-empty">
                        <span class="usp-empty-icon" aria-hidden="true"><i class="bi bi-heart"></i></span>
                        <h6>لیست علاقه مندی خالی است</h6>
                        <p>با زدن آیکون قلب روی محصولات، آن‌ها را برای بررسی بعدی اینجا ذخیره کنید.</p>
                        <a href="{{ route('front.products.index') }}" class="btn border-0 main-color-one-bg waves-effect waves-light">
                            <i class="bi bi-shop text-white"></i> مشاهده محصولات
                        </a>
                    </div>
                </div>
            </div>
        @endif

        @if($favorites->count())
            <div class="my-paginate mt-4 pb-3">
                <nav aria-label="Page navigation">
                    {{$favorites->links("pagination::bootstrap-4")}}
                </nav>
            </div>
        @endif

    </div>

@endsection

@push('scripts')
    @include('front::user.partials.favorite-delete-modal')
    <script src="{{ theme_asset('js/pages/user/jquery-bs5-modal-compat.js') }}"></script>
    <script src="{{ theme_asset('js/pages/favorites/index.js') }}"></script>
@endpush
