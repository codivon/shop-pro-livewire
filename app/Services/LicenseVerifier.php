<?php

namespace App\Services;

use App\Models\InstalledModule;
use RuntimeException;

/**
 * سخت‌سازی امنیتی بازنویسی بر پایهٔ ModuleLicenseGuard (fail-closed).
 *
 * تفاوت با نسخهٔ قدیم:
 *   • قدیم: روی خطای API به تاریخ محلی برمی‌گشت (fail-open) و هیچ‌وقت چیزی را
 *     نمی‌بست — ریشهٔ حفرهٔ گزارش‌شده («پکیج‌ها با قطع سرور کار می‌کنند»).
 *   • جدید: منبع حقیقت = پاکت لایسنس امضاشدهٔ سرور؛ قطع سرور ⇒ مهلت ۲۴ ساعته
 *     (از خود پاکت) و سپس بسته‌شدن؛ دستکاری دیتابیس/فایل‌ها هم کشف و بسته می‌شود.
 */
class LicenseVerifier
{
    public function __construct(
        private ModuleLicenseGuard $guard
    ) {}

    /**
     * بررسی اعتبار لایسنس یک ماژول نصب‌شده (گیت fail-closed کامل).
     *
     * @return array{
     *   valid: bool,
     *   state: string,
     *   reason: ?string,
     *   message: ?string,
     *   expires_at: ?string,
     *   grace_until: ?string,
     *   offline: bool,
     * }
     */
    public function verify(InstalledModule $installed): array
    {
        $status = $this->guard->status($installed->slug);

        return [
            'valid'       => $status['ok'],
            'state'       => $status['state'],
            'reason'      => $status['reason'],
            'message'     => $status['message'],
            'expires_at'  => $status['expires_at'],
            'grace_until' => $status['grace_until'],
            // grace یعنی سرور در دسترس نبوده ولی هنوز در مهلت است
            'offline'     => $status['state'] === ModuleLicenseGuard::STATE_GRACE,
        ];
    }

    /**
     * بررسی گروهی همه ماژول‌های نصب‌شده (کرون روزانه + فراخوانی‌های پنل).
     *
     * نکتهٔ مهم: قضاوت هر ماژول «قطعی» است — ماژول‌های ناپاک/منقضی/دستکاری‌شده
     * در همین فراخوانی بسته می‌شوند (license_state=closed در دیتابیس).
     *
     * @return array<string, array>
     */
    public function verifyAll(): array
    {
        $results = [];
        $modules = InstalledModule::whereNotNull('license_key')->get();

        foreach ($modules as $module) {
            try {
                $results[$module->slug] = $this->verify($module);
            } catch (RuntimeException $e) {
                $results[$module->slug] = [
                    'valid'   => false,
                    'state'   => ModuleLicenseGuard::STATE_CLOSED,
                    'reason'  => 'verifier_error',
                    'message' => $e->getMessage(),
                    'expires_at'  => null,
                    'grace_until' => null,
                    'offline'     => false,
                ];
            }
        }

        return $results;
    }
}
