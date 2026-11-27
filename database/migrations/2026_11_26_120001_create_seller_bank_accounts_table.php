<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * فاز ۲۱۷ — جدول کارت‌های بانکی فروشندگان (تسویه).
 *
 * تا الان فقط یک شبا (sellers_info.shaba_number) وجود داشت؛ از این فاز
 * فروشنده در پنل خودش چند کارت بانکی (نام صاحب حساب + شماره کارت + شبا)
 * اضافه/ویرایش/حذف می‌کند (حداقل یک کارت باید بماند) و کارتِ پیش‌فرضِ
 * تسویه همیشه به‌صورت همگام در sellers_info.shaba_number هم نوشته می‌شود
 * تا جریان‌های فعلی تسویه/نمایش بدون تغییر کار کنند.
 *
 * داده‌های قبلی: برای هر فروشندهٔ دارای شبا، یک کارت پیش‌فرض از روی
 * sellers_info (شبا + نام صاحب حساب از نام/نام خانوادگی یا نام فروشگاه)
 * ساخته می‌شود؛ شماره کارت این کارت‌های قدیمی خالی است و فروشنده باید
 * از تب حساب بانکی پنل تکمیلش کند.
 */
return new class extends Migration
{
    public function up(): void
    {
        // اصلاحیه — idempotent: روی بعضی نصب‌ها جدول از قبل ساخته شده ولی رکوردش
        // در جدول migrations ثبت نشده است؛ در این حالت Schema::create با خطای
        // «Base table or view already exists (1050)» شکست می‌خورد و بقیهٔ
        // مایگریشن‌های معلق هم اجرا نمی‌شدند. حالا اگر جدول موجود باشد ساخت
        // جدول میان‌پر می‌شود و فقط تکمیل داده‌ها بررسی می‌شود.
        $freshTable = ! Schema::hasTable('seller_bank_accounts');

        if ($freshTable) {
            Schema::create('seller_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id');
                $table->string('holder_name');
                $table->string('card_number', 16)->nullable();
                $table->string('sheba_number', 26);
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->index(['seller_id', 'is_default']);
                $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
            });
        }

        // مهاجرت داده‌های قبلی: شبای sellers_info → اولین کارت پیش‌فرض هر فروشنده.
        // idempotent — فقط فروشندگانی که هنوز هیچ کارتی ندارند پر می‌شوند تا
        // اجرای مجدد مایگریشن هرگز کارت تکراری نسازد.
        if (Schema::hasTable('sellers_info')) {
            $rows = DB::table('sellers_info')
                ->whereNotNull('shaba_number')
                ->where('shaba_number', '!=', '')
                ->get(['seller_id', 'first_name', 'last_name', 'business_name', 'shaba_number']);

            foreach ($rows as $row) {
                if (DB::table('seller_bank_accounts')->where('seller_id', $row->seller_id)->exists()) {
                    continue;
                }

                $holder = trim(((string) $row->first_name).' '.((string) $row->last_name));

                if ($holder === '') {
                    $holder = (string) ($row->business_name ?: 'صاحب حساب');
                }

                $sheba = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', en_digits((string) $row->shaba_number)));

                if (preg_match('/^[0-9]{24}$/', $sheba)) {
                    $sheba = 'IR'.$sheba;
                }

                DB::table('seller_bank_accounts')->insert([
                    'seller_id' => $row->seller_id,
                    'holder_name' => $holder,
                    'card_number' => null,
                    'sheba_number' => $sheba,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_bank_accounts');
    }
};
