<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * بزرگ‌کردن ستون description جدول packages_cache (TEXT → MEDIUMTEXT)
 *
 * خطای واقعی production (لاگ ۱۴۰۲/۰۸/۲۵ — ۲۰۲۶-۱۱-۱۵):
 *   SQLSTATE[22001]: String data, right truncated: 1406
 *   Data too long for column 'description' at row 1
 *
 * ریشهٔ ماجرا (زنجیرهٔ کامل):
 *   ۱) سرور بازار (opshop-updater) برای پکیج «هوش مصنوعی» یک توضیحِ HTML کامل
 *      (صفحهٔ معرفی/پرزنتی + CSS داخلی — ده‌ها کیلوبایت) در فیلد description پاسخ
 *      API برمی‌گرداند.
 *   ۲) ستون description از نسخه‌های اولیه از نوع TEXT است (سقف ۶۴KB) و این حجم را
 *      نمی‌پذیرد → خطای ۱۴۰۶.
 *   ۳) چون syncCache کل عملیات را داخل یک تراکنش انجام می‌داد، شکستِ همین یک رکورد
 *      باعث رول‌بکِ کامل کش پکیج‌ها می‌شد (کش کهنه می‌ماند و هیچ پکیجی بروز نمی‌شد).
 *   ۴) لاگرِ Laravel هنگام شکست کوئری، کل SQL + bindingها را در لاگ می‌نویسد → سند
 *      HTML چندخطی (bindingِ description) داخل laravel.log می‌افتد و لاگ چند
 *      مگابایتی می‌شود — پاسخ سؤال «چرا کد HTML داخل فایل لاگ است؟».
 *
 * این مایگریشن سقف ستون را به ۱۶MB می‌رساند؛ هم‌زمان در همین کامیت syncCache هر دو
 * فراخوانی‌گر (PackageIndex لایووایر + PackageController بک‌اند) به مدل «هر پکیج
 * جداگانه + لاگِ استریلِ بدون SQL» تغییر می‌کند تا یک رکورد خراب، کل کش را
 * رول‌بک نکند و لاگ هم منفجر نشود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages_cache', function (Blueprint $table) {
            $table->mediumText('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('packages_cache', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }
};
