<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* فاز ۲۰۲ پیوست — فیلدهای «نام شما» و «ایمیل» برای دیدگاه مهمان
   تک‌صفحهٔ مقاله: مهمان‌ها نام و ایمیل خود را وارد می‌کنند و این
   اطلاعات کنار دیدگاه ذخیره می‌شود (پنل مدیریت هم آن را نشان می‌دهد). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            if (!Schema::hasColumn('comments', 'name')) {
                $table->string('name')->nullable()->after('body');
            }
            if (!Schema::hasColumn('comments', 'email')) {
                $table->string('email')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            if (Schema::hasColumn('comments', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('comments', 'name')) {
                $table->dropColumn('name');
            }
        });
    }
};
