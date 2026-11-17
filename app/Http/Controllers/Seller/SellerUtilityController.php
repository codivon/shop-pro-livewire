<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\Order;
use App\Models\Tag;
use Illuminate\Http\Request;

/**
 * اندپوینت‌های ابزاری مشترک پنل فروشنده
 * --------------------------------------------------------------------------
 * پس از حذف پنل مدیریت قدیمی، اندپوینت‌های admin.get-labels / admin.get-tags /
 * admin.orders.set-tracking-code که صفحات پنل فروشنده (sellers-old) استفاده
 * می‌کردند، حذف شدند. این کلاس همان منطق را در فضای نام پنل فروشنده ارائه
 * می‌کند (seller.get-labels / seller.get-tags / seller.orders.set-tracking-code).
 */
class SellerUtilityController extends Controller
{
    /** پیشنهاد برچسب‌ها (autocomplete فرم محصول — پورت MainController::getLabels) */
    public function getLabels(Request $request)
    {
        $labels = Label::detectLang()->where('title', 'like', '%' . $request->term . '%')
            ->latest()
            ->take(5)
            ->pluck('title')
            ->toArray();

        return response()->json($labels);
    }

    /** پیشنهاد تگ‌ها (autocomplete فرم محصول — پورت MainController::get_tags) */
    public function getTags(Request $request)
    {
        $tags = Tag::detectLang()->where('name', 'like', '%' . $request->term . '%')
            ->latest()
            ->take(5)
            ->pluck('name')
            ->toArray();

        return response()->json($tags);
    }

    /**
     * ثبت کد رهگیری سفارش توسط فروشنده (پورت OrderController::set_tracking_code)
     * با گارد مالکیت: سفارش باید آیتمی متعلق به همین فروشنده داشته باشد.
     */
    public function setTrackingCode(Request $request, Order $order)
    {
        abort_unless($order->items()->where('seller_id', sellerID())->exists(), 403);

        $sellerName = seller_info()->business_name ?? 'فروشنده';
        $oldTrackingCode = $order->tracking_code;
        $newTrackingCode = $request->code;

        $order->tracking_code = $request->code;
        $order->save();

        activity()
            ->performedOn($order)
            ->causedBy(auth()->user())
            ->event('updated')
            ->withProperties([
                'action' => 'set_tracking_code',
                'order_id' => $order->id,
                'old_tracking_code' => $oldTrackingCode,
                'new_tracking_code' => $newTrackingCode,
                'ip' => request()->ip()
            ])
            ->log("فروشنده {$sellerName} کد رهگیری سفارش شماره {$order->id} را از «{$oldTrackingCode}» به «{$newTrackingCode}» تغییر داد");

        return response('success');
    }
}
