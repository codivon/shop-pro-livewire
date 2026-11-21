@extends('front::user.layouts.master')

@section('user-content')

    {{-- پروفایل (داشبورد) — بازطراحی کامل فاز ۲۰۰ --}}
    <div class="usp-scope">

        {{-- سربرگ خوش‌آمد --}}
        <div class="usp-page-head">
            <div class="usp-page-head-title">
                <span class="usp-page-head-icon" aria-hidden="true"><i class="bi bi-house-door"></i></span>
                <div>
                    <h2>{{ $user->fullname }} عزیز، خوش آمدید 👋</h2>
                    <p>خلاصه‌ای از فعالیت حساب شما در این صفحه نمایش داده می‌شود.</p>
                </div>
            </div>
            <a href="{{ route('front.user.profile.edit') }}" class="btn btn-sm main-color-one-bg waves-effect waves-light">
                <i class="bi bi-pencil-square text-white"></i> ویرایش اطلاعات
            </a>
        </div>

        {{-- کاشی‌های آمار --}}
        <div class="usp-stat-grid">
            <a href="{{ route('front.orders.index') }}" class="usp-stat">
                <span class="usp-stat-icon is-blue" aria-hidden="true"><i class="bi bi-bag-check"></i></span>
                <span class="usp-stat-body">
                    <h6>سفارش‌ها</h6>
                    <strong>{{ $user->orders()->count() }}</strong>
                    <small>سفارش ثبت‌شده</small>
                </span>
            </a>
            <a href="{{ route('front.wallet.index') }}" class="usp-stat">
                <span class="usp-stat-icon is-green" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
                <span class="usp-stat-body">
                    <h6>کیف پول</h6>
                    <strong>{{ number_format($user->getWallet()->balance()) }}</strong>
                    <small>تومان موجودی</small>
                </span>
            </a>
            <a href="{{ route('front.favorites.index') }}" class="usp-stat">
                <span class="usp-stat-icon is-amber" aria-hidden="true"><i class="bi bi-heart"></i></span>
                <span class="usp-stat-body">
                    <h6>علاقه‌مندی‌ها</h6>
                    <strong>{{ $user->favorites()->count() }}</strong>
                    <small>محصول ذخیره‌شده</small>
                </span>
            </a>
            <a href="{{ route('front.comments.index') }}" class="usp-stat">
                <span class="usp-stat-icon is-red" aria-hidden="true"><i class="bi bi-chat-dots"></i></span>
                <span class="usp-stat-body">
                    <h6>دیدگاه‌ها</h6>
                    <strong>{{ $user->reviews()->count() }}</strong>
                    <small>نقد و نظر ثبت‌شده</small>
                </span>
            </a>
        </div>

        {{-- اطلاعات شخصی --}}
        <div class="usp-card">
            <div class="usp-card-head">
                <h4>اطلاعات شخصی</h4>
                <a href="{{ route('front.user.profile.edit') }}" class="btn btn-sm btn-light">ویرایش <i class="bi bi-pencil"></i></a>
            </div>
            <div class="usp-card-body p-2">
                <div class="usp-info-grid">
                    <div class="usp-info-item">
                        <h6><i class="bi bi-person"></i> نام و نام خانوادگی</h6>
                        <p>{{ $user->fullname }}</p>
                    </div>
                    <div class="usp-info-item">
                        <h6><i class="bi bi-envelope"></i> پست الکترونیک</h6>
                        <p>{{ $user->email ?: '-' }}</p>
                    </div>
                    <div class="usp-info-item">
                        <h6><i class="bi bi-phone"></i> شماره تلفن همراه</h6>
                        <p dir="ltr">{{ $user->mobile }}</p>
                    </div>
                    <div class="usp-info-item">
                        <h6><i class="bi bi-credit-card-2-front"></i> کد ملی</h6>
                        <p>{{ $user->national_code ?: '-' }}</p>
                    </div>
                    <div class="usp-info-item">
                        <h6><i class="bi bi-newspaper"></i> دریافت خبرنامه</h6>
                        <p>{{ $user->newsletter == 1 ? 'بله' : 'خیر' }}</p>
                    </div>
                    <div class="usp-info-item">
                        <h6><i class="bi bi-bank"></i> شماره کارت</h6>
                        <p>{{ $user->card_number ?: '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- آخرین سفارش ها --}}
        <div class="usp-card">
            <div class="usp-card-head">
                <h4>آخرین سفارش ها</h4>
                @if(count($last_orders))
                    <a href="{{ route('front.orders.index') }}" class="btn btn-sm btn-light">مشاهده لیست سفارش‌ها <i class="bi bi-chevron-left"></i></a>
                @endif
            </div>
            <div class="usp-card-body p-0">
                @if(count($last_orders))
                    <div class="responsive-table p-0">
                        <table class="table main-table">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>شماره سفارش</th>
                                <th>تاریخ ثبت</th>
                                <th>تخفیف</th>
                                <th>مبلغ کل</th>
                                <th>وضعیت پرداخت</th>
                                <th>جزئیات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($last_orders as $order)
                                <tr>
                                    <td class="font-14">{{ $loop->iteration }}</td>
                                    <td class="font-14 main-color-one-color">#{{ $order->id }}</td>
                                    <td class="font-14">{{ jdate($order->created_at)->format('%d %B %Y') }}</td>
                                    <td class="font-14">
                                        @if($order->totalDiscount() != 0)
                                            {{ number_format($order->totalDiscount()) }} تومان
                                        @else
                                            <span class="text-muted">بدون تخفیف</span>
                                        @endif
                                    </td>
                                    <td class="font-14">{{ number_format($order->price) }} تومان</td>
                                    <td>
                                        @if($order->status == 'paid')
                                            <span class="usp-badge is-success"><i class="bi bi-check-circle"></i> پرداخت شده</span>
                                        @elseif($order->status == 'unpaid')
                                            <span class="usp-badge is-danger"><i class="bi bi-clock"></i> پرداخت نشده</span>
                                        @else
                                            <span class="usp-badge is-muted"><i class="bi bi-x-circle"></i> لغو شده</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('front.orders.show', ['order' => $order]) }}"
                                           class="btn btn-sm border-0 main-color-one-bg waves-effect waves-light">مشاهده <i class="bi bi-chevron-left text-white font-13"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="usp-empty">
                        <span class="usp-empty-icon" aria-hidden="true"><i class="bi bi-bag"></i></span>
                        <h6>هنوز سفارشی ثبت نکرده‌اید</h6>
                        <p>با اولین خرید شما، سفارش‌هایتان اینجا نمایش داده می‌شوند.</p>
                        <a href="{{ route('front.products.index') }}" class="btn border-0 main-color-one-bg waves-effect waves-light">
                            <i class="bi bi-shop text-white"></i> شروع خرید
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- لیست علاقه مندی ها --}}
        <div class="usp-card">
            <div class="usp-card-head">
                <h4>لیست علاقه مندی</h4>
                @if ($user->favorites()->count())
                    <a href="{{ route('front.favorites.index') }}" class="btn btn-sm btn-light">مشاهده و ویرایش لیست علاقه مندی <i class="bi bi-chevron-left"></i></a>
                @endif
            </div>
            <div class="usp-card-body">
                @if ($user->favorites()->count())
                    <div class="product-list-row">
                        @foreach ($user->favorites()->latest()->take(2)->get() as $favorite)
                            <div class="product-row">
                                <div class="product-row-desc">
                                    <a href="{{ route('front.products.show', ['product' => $favorite->product]) }}">
                                        <div class="product-row-desc-item">
                                            <div class="product-row-img">
                                                <img src="{{ $favorite->product->image ? asset($favorite->product->image) : asset('/no-image-product.svg') }}"
                                                     alt="{{ $favorite->product->title }}" width="90">
                                            </div>
                                            <div class="product-row-title">
                                                <h6>{{ $favorite->product->title }}</h6>
                                                <div class="product-price">
                                                    <p>{{ $favorite->product->getLowestPrice() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="usp-empty usp-empty-sm">
                        <span class="usp-empty-icon" aria-hidden="true"><i class="bi bi-heart"></i></span>
                        <h6>لیست علاقه‌مندی خالی است</h6>
                        <p>با زدن آیکون قلب روی محصولات، آن‌ها را اینجا ذخیره کنید.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>

@endsection

@section('user-content-bottom')
    <div class="content mt-4">
        <div class="container-fluid">
            <div class="adplacement">
                {{-- در نصب تازه ممکن است ویجت «بنر چهارتایی» هنوز ساخته/فعال نشده باشد؛
                     پاس دادن null به پارشال ویجت باعث Fatal «Attempt to read property key on null» می‌شد. --}}
                @if($widgets)
                    @include('front::widgets.middle-banners-4',['widget'=>$widgets])
                @endif
            </div>

            @if($special_products->count())
                <div class="content-box mt-4">
                    <div class="container-fluid py-3">
                        <div class="title title-panel d-flex align-items-baseline">
                            <i class="bi bi-stars font-25 main-color-one-color"></i>
                            <h6 class="font-16 ms-2">محصولات ویژه</h6>
                        </div>
                        <div class="product-carousel owl-carousel owl-theme owl-rtl owl-loaded owl-drag">
                            <div class="owl-stage-outer">
                                <div class="owl-stage"
                                     style="transform: translate3d(0px, 0px, 0px); transition: all 0s ease 0s; width: 2234px;">
                                    @php $i=1; @endphp
                                    @foreach ($special_products as $special_product)
                                        @include('front::partials.product-block', ['product' => $special_product])
                                        @php $i++; @endphp
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    @include('front::user.partials.favorite-delete-modal')
    <script src="{{ theme_asset('js/pages/user/jquery-bs5-modal-compat.js') }}"></script>
    <script src="{{ theme_asset('js/pages/favorites/index.js') }}"></script>
@endpush
