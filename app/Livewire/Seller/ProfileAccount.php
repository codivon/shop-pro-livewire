<?php

namespace App\Livewire\Seller;

use App\Models\Seller;
use App\Models\SellerInfo;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * فاز ۲۱۶ — صفحهٔ «حساب و امنیت» فروشنده (لوگو + گذرواژه).
 *
 * لوگوی فروشنده و تغییر گذرواژه از صفحهٔ پروفایل به اینجا منتقل شدند:
 * تغییر این دو نباید وارد چرخهٔ تایید مدیر شود (پروفایل با ثبت تغییرات
 * status_work را EditProfile می‌کند و برای بررسی به کارشناسان ارسال می‌شود)؛
 * لوگو و رمز ورود امور شخصی فروشنده‌اند و باید بلافاصله اعمال شوند.
 *
 * هر دو عملیات POST کلاسیک به seller.profile.account.logo و
 * seller.profile.account.password هستند (کنترلر قدیمی
 * ProfileSellerController@updateLogo / updatePassword — پاسخ response('success')
 * با fetch مدیریت می‌شود، هم‌الگوی سایر فرم‌های پنل).
 */
#[Layout('sellers.layouts.panel')]
#[Title('حساب و امنیت')]
class ProfileAccount extends Component
{
    public function render()
    {
        $seller = Seller::find(Auth::guard('seller')->id());
        $seller_info = SellerInfo::where('seller_id', $seller->id)->first();

        return view('sellers.panel.profile.account', [
            'seller' => $seller,
            'seller_info' => $seller_info,
            'logoUrl' => route('seller.profile.account.logo'),
            'passwordUrl' => route('seller.profile.account.password'),
        ]);
    }
}
