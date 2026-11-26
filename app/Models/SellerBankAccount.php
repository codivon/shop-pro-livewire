<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * فاز ۲۱۷ — کارت بانکی فروشنده (تسویه).
 *
 * هر فروشنده می‌تواند چند کارت بانکی (نام صاحب حساب + شماره کارت + شبا)
 * داشته باشد؛ حداقل یک کارت باید همیشه باقی بماند و کارت پیش‌فرض
 * (is_default) مقصد تسویه است که به‌صورت همگام در sellers_info.shaba_number
 * هم نوشته می‌شود تا جریان‌های فعلی تسویه/نمایش بدون تغییر کار کنند.
 */
class SellerBankAccount extends Model
{
    protected $fillable = [
        'seller_id',
        'holder_name',
        'card_number',
        'sheba_number',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }
}
