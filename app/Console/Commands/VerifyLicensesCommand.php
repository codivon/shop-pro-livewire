<?php

namespace App\Console\Commands;

use App\Models\InstalledModule;
use App\Services\LicenseVerifier;
use App\Services\ModuleIntegrityService;
use App\Services\PackageApiService;
use Illuminate\Console\Command;
use Throwable;

/**
 * سخت‌سازی امنیتی کامند verify با «اجراکنندهٔ واقعی» بسته‌شدن.
 *
 * تفاوت با نسخهٔ قدیم:
 *   • قدیم فقط گزارش می‌داد (و اصلاً در Schedule ثبت نشده بود!) — هیچ پکیجی بسته نمی‌شد.
 *   • جدید: قضاوت از گارد fail-closed می‌آید (پاکت امضاشده + یکپارچگی فایل‌ها +
 *     مهلت ۲۴ ساعته) و وضعیت license_state در دیتابیس «قطعی» می‌شود.
 *   • فلگ --ensure-manifests: provision مانیفست برای نصب‌های قدیمیِ بدون module.lock.
 *   • زمان‌بندی روزانه در Console\Kernel ثبت شده است.
 *
 * بازگشایی خودکار: اگر لایسنس تمدید شده باشد، چک بعدی پاکت معتبر تازه می‌گیرد
 * و وضعیت به‌طور خودکار به active برمی‌گردد — نیازی به کامند جداگانه نیست.
 */
class VerifyLicensesCommand extends Command
{
    protected $signature = 'packages:verify-licenses
                            {--ensure-manifests : provision مانیفست برای پکیج‌های بدون module.lock}
                            {--notify : ارسال نوتیفیکیشن برای لایسنس‌های منقضی}';

    protected $description = 'بررسی fail-closed لایسنس همهٔ ماژول‌های نصب‌شده (بستن/بازگشایی خودکار + یکپارچگی فایل‌ها)';

    public function handle(LicenseVerifier $verifier, PackageApiService $api, ModuleIntegrityService $integrity): int
    {
        $this->info('شروع بررسی لایسنس‌ها (fail-closed)...');

        if ($this->option('ensure-manifests')) {
            $this->ensureManifests($api, $integrity);
        }

        $results = $verifier->verifyAll();

        if (empty($results)) {
            $this->info('هیچ ماژول لایسنس‌داری یافت نشد.');

            return self::SUCCESS;
        }

        $closed = [];
        $grace  = [];

        foreach ($results as $slug => $res) {
            $state = $res['state'] ?? 'closed';

            if ($state === 'active') {
                $this->line("  OK  {$slug} - معتبر");
            } elseif ($state === 'grace') {
                $this->warn("  GRACE  {$slug} - سرور در دسترس نیست؛ تا {$res['grace_until']} مهلت دارد");
                $grace[] = $slug;
            } else {
                $this->error("  CLOSED  {$slug} - {$res['message']}");
                $closed[] = $slug;
            }
        }

        if ($this->option('notify') && !empty($closed)) {
            $this->warn('لایسنس‌های بسته‌شده: ' . implode(', ', $closed));
        }

        return self::SUCCESS;
    }

    /**
     * provision مانیفست برای نصب‌های قدیمی — جلوی بسته‌شدن ناخواسته در روز ارتقا.
     */
    private function ensureManifests(PackageApiService $api, ModuleIntegrityService $integrity): void
    {
        $modules = InstalledModule::whereNotNull('license_key')->get();
        $done    = 0;

        foreach ($modules as $module) {
            try {
                if ($integrity->hasManifest($module->name)) {
                    continue;
                }

                $signed = $api->fetchManifest($module->slug, $module->version);

                if ($integrity->storeManifest($module->name, $signed)) {
                    $module->forceFill(['integrity_verified_at' => now()])->save();
                    $done++;
                    $this->line("  manifest => {$module->slug} (v{$module->version})");
                }
            } catch (Throwable $e) {
                $this->warn("  manifest skip {$module->slug}: " . $e->getMessage());
            }
        }

        $this->info("مانیفست provision‌شده: {$done}");
    }
}
