<?php

namespace App\Livewire\Seller;

use App\Models\Category;
use App\Models\Province;
use App\Models\Seller;
use App\Models\SellerEcontract;
use App\Models\SellerInfo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * پروفایل فروشنده (پورت ProfileSellerController@index).
 *
 * فرم کلاسیک PUT به seller.profile.update با «عیناً» همان فیلدهای ویوی قدیمی
 * (resources/views/sellers-old/panel/profile/index.blade.php) — کنترلر قدیمی
 * به‌جای redirect با response("success") + session toast پاسخ می‌دهد، پس فرم با
 * fetch ارسال و در موفقیت به seller.profile.index بازگردانده می‌شود (هم‌الگوی JS قدیمی).
 *
 * امضای قرارداد الکترونیک: POST کلاسیک به seller.profile.set_econtract
 * (کنترلر قدیمی JSON {status} برمی‌گرداند — پاسخ با fetch مدیریت می‌شود).
 *
 * نکته: روت seller.profile.store در کنترلر قدیمی متدی ندارد (از ابتدا
 * نوشته نشده) و فرم قدیمی فقط update را صدا می‌زد — پس این صفحه هم فقط
 * update/set_econtract را صدا می‌زند (انحراف آگاهانه از قرارداد).
 */
#[Layout('sellers.layouts.panel')]
#[Title('پروفایل شما')]
class Profile extends Component
{
    public function render()
    {
        $seller = Seller::find(Auth::guard('seller')->id());
        $seller_info = SellerInfo::where('seller_id', $seller->id)->first();
        $categories = Category::where('category_id', null)->get();
        $econtract = SellerEcontract::find(1);
        $provinces = Province::with('cities:id,province_id,name')->select('id', 'name')->get();

        // فاز ۲۱۷ — کارت‌های بانکی فروشنده (مدیریت مستقل تب حساب بانکی، بدون چرخهٔ تایید مدیر)
        // اصلاحیه — اگر مایگریشن جدول هنوز روی این نصب اجرا نشده باشد صفحهٔ
        // پروفایل نمی‌شکند و فقط تب حساب بانکی خالی می‌ماند.
        $bankAccounts = Schema::hasTable('seller_bank_accounts')
            ? $seller->bankAccounts()
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->get()
                ->map(fn ($account) => [
                    'id' => $account->id,
                    'holder_name' => (string) $account->holder_name,
                    'card_number' => (string) $account->card_number,
                    'sheba_number' => (string) $account->sheba_number,
                    'is_default' => (bool) $account->is_default,
                    'update_url' => route('seller.profile.bank.update', $account),
                    'destroy_url' => route('seller.profile.bank.destroy', $account),
                    'default_url' => route('seller.profile.bank.default', $account),
                ])->values()->all()
            : [];

        return view('sellers.panel.profile.index', [
            'seller' => $seller,
            'seller_info' => $seller_info,
            'categories' => $categories,
            'provinces' => $provinces,
            'econtract' => $econtract,
            'bankAccounts' => $bankAccounts,
            'bankStoreUrl' => route('seller.profile.bank.store'),
            'updateUrl' => route('seller.profile.update', ['seller' => $seller]),
            'setEcontractUrl' => route('seller.profile.set_econtract'),
            'citiesUrl' => route('provinces.get-cities'),
        ]);
    }
}
