{{-- ============================================================
    سایدبار پنل مشتری — فاز ۲۰۰ (بازطراحی کامل پنل)
    یک پارشال مشترک برای سایدبار دسکتاپ و آفکانوس موبایل تا
    دو نسخهٔ کپی‌شده از هم جدا نشوند.
    استفاده: @include('front::user.partials.panel-sidebar')
============================================================= --}}
<div class="usp-side-card">

    {{-- کارت پروفایل --}}
    <div class="usp-profile-box">
        <a href="{{ route('front.user.profile.edit') }}" class="usp-avatar-link" title="ویرایش اطلاعات شخصی">
            <img src="{{ auth('web')->user()->image_url ?? theme_asset('images/user.png') }}"
                 class="usp-avatar" alt="{{ $user->fullname }}">
        </a>
        <div class="usp-profile-meta">
            <span class="usp-profile-hello">حساب کاربری من</span>
            <span class="usp-profile-name">{{ $user->fullname }}</span>
            <span class="usp-profile-mobile" dir="ltr">{{ $user->mobile }}</span>
        </div>
        <a href="{{ route('front.user.profile.edit') }}" class="usp-profile-edit" aria-label="ویرایش اطلاعات شخصی">
            <i class="bi bi-pencil-square"></i>
        </a>
    </div>

    {{-- کارت کیف پول --}}
    <a href="{{ route('front.wallet.index') }}" class="usp-wallet-card">
        <span class="usp-wallet-icon" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
        <span class="usp-wallet-meta">
            <span class="usp-wallet-label">کیف پول</span>
            <span class="usp-wallet-balance">
                <strong title="{{ convert_number($user->getWallet()->balance()) }}">{{ number_format($user->getWallet()->balance()) }}</strong>
                <small>تومان</small>
            </span>
        </span>
        <i class="bi bi-chevron-left usp-wallet-arrow"></i>
    </a>

    {{-- منوی پنل --}}
    <nav class="usp-nav" aria-label="منوی پنل کاربری">
        <ul class="navbar-nav flex-column">
            <li class="nav-item @if(@$active=="profile") active @endif">
                <a href="{{ route('front.user.profile') }}" class="nav-link"><i class="bi bi-house-door"></i><span>پروفایل</span></a>
            </li>
            <li class="nav-item @if(@$active=="orders") active @endif">
                <a href="{{ route('front.orders.index') }}" class="nav-link"><i class="bi bi-cart-check"></i><span>همه سفارش ها</span></a>
            </li>

            @if(class_exists(\App\Services\FrontModuleMenuRegistry::class))
                @foreach(\App\Services\FrontModuleMenuRegistry::all() as $menuView)
                    @if(view()->exists($menuView))
                        @include($menuView)
                    @endif
                @endforeach
            @endif

            <li class="nav-item @if(@$active=="wallet") active @endif">
                <a href="{{ route('front.wallet.index') }}" class="nav-link"><i class="bi bi-credit-card"></i><span>کیف پول</span></a>
            </li>
            <li class="nav-item @if(@$active=="favorites") active @endif">
                <a href="{{ route('front.favorites.index') }}" class="nav-link"><i class="bi bi-heart"></i><span>لیست علاقه مندی ها</span></a>
            </li>
            <li class="nav-item @if(@$active=="comments") active @endif">
                <a href="{{ route('front.comments.index') }}" class="nav-link"><i class="bi bi-chat-dots"></i><span>نقد و نظرات</span></a>
            </li>
            {{-- : «کد های تخفیف معرفی» به پکیج باشگاه مشتریان منتقل شد — بخش معرفی اکنون در صفحهٔ باشگاه مشتریان پنل مشتری است. --}}
            @if(false)
                <li>
                    <a href="{{ route('front.user.referrals.index') }}" class="nav-link">
                        <i class="bi bi-gift"></i><span>کد های تخفیف معرفی</span>
                    </a>
                </li>
            @endif
            <li class="nav-item @if(@$active=="messages") active @endif">
                <a href="{{ route('front.messages.index') }}" class="nav-link"><i class="bi bi-bell"></i><span>پیام ها و اطلاعیه ها</span></a>
            </li>
            <li class="nav-item @if(@$active=="addresses") active @endif">
                <a href="{{ route('front.addresses.index') }}" class="nav-link"><i class="bi bi-pin-map"></i><span>آدرس ها</span></a>
            </li>
            <li class="nav-item @if(@$active=="user-history") active @endif">
                <a href="{{ route('front.user.user-history') }}" class="nav-link"><i class="bi bi-clock-history"></i><span>بازدید های اخیر</span></a>
            </li>
            <li class="nav-item @if(@$active=="tickets") active @endif">
                <a href="{{ route('front.tickets.index') }}" class="nav-link"><i class="bi bi-question-circle"></i><span>تیکت های شما</span></a>
            </li>
            <li class="nav-item @if(@$active=="profileEdit") active @endif">
                <a href="{{ route('front.user.profile.edit') }}" class="nav-link"><i class="bi bi-person-gear"></i><span>اطلاعات شخصی</span></a>
            </li>
            <li class="nav-item usp-nav-logout">
                <a href="{{ route('logout') }}" data-no-navigate class="nav-link"><i class="bi bi-arrow-right-square"></i><span>خروج از حساب کاربری</span></a>
            </li>
        </ul>
    </nav>
</div>
