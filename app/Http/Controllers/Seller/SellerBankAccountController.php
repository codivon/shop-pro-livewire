<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerBankAccount;
use App\Models\SellerInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/*
 * فاز ۲۱۷ — مدیریت کارت‌های بانکی فروشنده در پنل (تب «حساب بانکی» پروفایل).
 *
 * تا الان شبا فقط یک فیلد در فرم کلاسیک پروفایل بود و با کل پروفایل وارد
 * چرخهٔ تایید مدیر (status_work = EditProfile) می‌شد؛ از این فاز فروشنده
 * کارت‌هایش را مستقل و فوری مدیریت می‌کند:
 *
 * - افزودن کارت (نام صاحب حساب + شماره کارت ۱۶ رقمی با Luhn + شبای IR+۲۴رقم با MOD-97)
 * - ویرایش کارت (برای تکمیل شمارهٔ کارت کارت‌های قدیمیِ مهاجرت‌شده هم استفاده می‌شود)
 * - حذف کارت — با شرط «حداقل یک کارت باید باقی بماند»
 * - انتخاب کارت پیش‌فرض تسویه
 *
 * کارت پیش‌فرض همیشه به‌صورت همگام در sellers_info.shaba_number نوشته می‌شود
 * تا جریان‌های فعلی تسویه/نمایش بدون تغییر کار کنند.
 */
class SellerBankAccountController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $sellerId = Auth::guard('seller')->id();

        $data = $this->validated($request);

        $isFirst = SellerBankAccount::where('seller_id', $sellerId)->count() === 0;

        $account = SellerBankAccount::create(array_merge($data, [
            'seller_id' => $sellerId,
            'is_default' => $isFirst,
        ]));

        if ($isFirst) {
            $this->syncDefaultSheba($sellerId);
        }

        return response()->json([
            'status' => 'success',
            'message' => $isFirst
                ? 'کارت بانکی با موفقیت ثبت شد و به‌عنوان کارت پیش‌فرض تسویه انتخاب شد.'
                : 'کارت بانکی با موفقیت اضافه شد.',
        ]);
    }

    public function update(Request $request, SellerBankAccount $bank_account): JsonResponse
    {
        $this->authorizeAccount($bank_account);

        $data = $this->validated($request);

        $bank_account->update($data);

        if ($bank_account->is_default) {
            $this->syncDefaultSheba($bank_account->seller_id);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'کارت بانکی با موفقیت به‌روزرسانی شد.',
        ]);
    }

    public function destroy(SellerBankAccount $bank_account): JsonResponse
    {
        $this->authorizeAccount($bank_account);

        $remaining = SellerBankAccount::where('seller_id', $bank_account->seller_id)
            ->where('id', '!=', $bank_account->id)
            ->count();

        if ($remaining === 0) {
            // شرط «حداقل یک کارت» — آخرین کارت قابل حذف نیست
            return response()->json([
                'status' => 'error',
                'message' => 'حداقل یک کارت بانکی باید در پروفایل شما باقی بماند؛ ابتدا کارت دیگری اضافه کنید.',
            ], 422);
        }

        $wasDefault = $bank_account->is_default;
        $sellerId = $bank_account->seller_id;

        $bank_account->delete();

        if ($wasDefault) {
            $next = SellerBankAccount::where('seller_id', $sellerId)->orderBy('id')->first();

            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        $this->syncDefaultSheba($sellerId);

        return response()->json([
            'status' => 'success',
            'message' => 'کارت بانکی حذف شد.',
        ]);
    }

    public function setDefault(SellerBankAccount $bank_account): JsonResponse
    {
        $this->authorizeAccount($bank_account);

        SellerBankAccount::where('seller_id', $bank_account->seller_id)
            ->where('id', '!=', $bank_account->id)
            ->update(['is_default' => false]);

        $bank_account->update(['is_default' => true]);

        $this->syncDefaultSheba($bank_account->seller_id);

        return response()->json([
            'status' => 'success',
            'message' => 'تسویه‌های بعدی به این کارت انجام می‌شود.',
        ]);
    }

    /* ================= هلپرها ================= */

    /** فروشنده فقط به کارت‌های خودش دسترسی دارد */
    private function authorizeAccount(SellerBankAccount $bank_account): void
    {
        if ((int) $bank_account->seller_id !== (int) Auth::guard('seller')->id()) {
            abort(404);
        }
    }

    /** نرمال‌سازی + اعتبارسنجی فیلدهای کارت بانکی */
    private function validated(Request $request): array
    {
        $request->merge([
            'holder_name' => trim((string) $request->input('holder_name')),
            'card_number' => preg_replace('/[^0-9]/', '', en_digits((string) $request->input('card_number'))),
            'sheba_number' => $this->normalizeSheba((string) $request->input('sheba_number')),
        ]);

        return $this->validate($request, [
            'holder_name' => 'required|string|max:120',
            'card_number' => 'required|ir_bank_card_number',
            'sheba_number' => 'required|ir_iban',
        ], [
            'holder_name.required' => 'نام صاحب حساب را وارد کنید',
            'holder_name.max' => 'نام صاحب حساب حداکثر ۱۲۰ نویسه است',
            'card_number.required' => 'شماره کارت را وارد کنید',
            'card_number.ir_bank_card_number' => 'شماره کارت وارد شده معتبر نیست؛ کارت بانکی ۱۶ رقمی را بررسی کنید',
            'sheba_number.required' => 'شماره شبا را وارد کنید',
            'sheba_number.ir_iban' => 'شماره شبا وارد شده معتبر نیست؛ شبای ۲۶ رقمی (IR + ۲۴ رقم) را مطابق اطلاعات بانکی بررسی کنید',
        ]);
    }

    /** نرمال‌سازی شبای واردشده: حذف فاصله/خط تیره + افزودن خودکار پیشوند IR */
    private function normalizeSheba(string $value): string
    {
        $sheba = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', en_digits($value)));

        if (preg_match('/^[0-9]{24}$/', $sheba)) {
            $sheba = 'IR'.$sheba;
        }

        return $sheba;
    }

    /**
     * همگام‌سازی شبای کارت پیش‌فرض با sellers_info.shaba_number تا
     * جریان‌های فعلی تسویه/نمایش (خروجی اکسل، پنل ادمین و…) بدون تغییر کار کنند.
     */
    private function syncDefaultSheba(int $sellerId): void
    {
        $default = SellerBankAccount::where('seller_id', $sellerId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        SellerInfo::where('seller_id', $sellerId)->update([
            'shaba_number' => $default?->sheba_number ?: '',
        ]);
    }
}
