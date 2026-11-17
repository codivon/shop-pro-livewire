<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سخت‌سازی امنیتی لایسنس ماژول‌ها (Fail-Closed)
 *
 *  installed_modules ستون‌های جدید:
 *   • license_state         — active | grace | closed | not_installed
 *   • license_reason        — کد دلیل بسته‌شدن (expired/tampered/domain_mismatch/...)
 *   • license_envelope      — پاکت لایسنس امضاشدهٔ سرور بازار (منبع حقیقت اعتبار)
 *   • grace_started_at      — لنگر شروع دورهٔ مهلت هنگام قطع سرور
 *   • last_api_check_at     — آخرین چک آنلاین موفق (زمان‌سنج چک دوره‌ای)
 *   • integrity_verified_at — مُهر provision مانیفست؛ حذف module.lock بعد از این
 *                             مُهر = دستکاری = بستن پکیج
 *
 *  جدول module_license_events — گزارش رویدادهای گارد برای پایش ادمین.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installed_modules', function (Blueprint $table) {
            if (! Schema::hasColumn('installed_modules', 'license_state')) {
                $table->string('license_state', 20)->default('active')->after('integrity_hash');
            }

            if (! Schema::hasColumn('installed_modules', 'license_reason')) {
                $table->string('license_reason', 64)->nullable()->after('license_state');
            }

            if (! Schema::hasColumn('installed_modules', 'license_envelope')) {
                $table->text('license_envelope')->nullable()->after('license_reason');
            }

            if (! Schema::hasColumn('installed_modules', 'grace_started_at')) {
                $table->timestamp('grace_started_at')->nullable()->after('license_envelope');
            }

            if (! Schema::hasColumn('installed_modules', 'last_api_check_at')) {
                $table->timestamp('last_api_check_at')->nullable()->after('grace_started_at');
            }

            if (! Schema::hasColumn('installed_modules', 'integrity_verified_at')) {
                $table->timestamp('integrity_verified_at')->nullable()->after('last_api_check_at');
            }
        });

        if (! Schema::hasTable('module_license_events')) {
            Schema::create('module_license_events', function (Blueprint $table) {
                $table->id();
                $table->string('module_slug', 100)->index();
                $table->string('event', 40)->index();
                $table->json('details')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('installed_modules', function (Blueprint $table) {
            foreach (['license_state', 'license_reason', 'license_envelope', 'grace_started_at', 'last_api_check_at', 'integrity_verified_at'] as $column) {
                if (Schema::hasColumn('installed_modules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasTable('module_license_events')) {
            Schema::dropIfExists('module_license_events');
        }
    }
};
