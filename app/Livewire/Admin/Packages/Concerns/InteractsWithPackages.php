<?php

namespace App\Livewire\Admin\Packages\Concerns;

use App\Jobs\InstallPackageJob;
use App\Livewire\Admin\Packages\PackageIndex;
use App\Models\InstalledModule;
use App\Models\PackageCache;
use App\Models\PackagePurchase;
use App\Services\PackageApiService;
use App\Support\MarketplaceSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Throwable;

/**
 * منطق مشترک نصب پکیج‌ها — پورت ۱:۱ متدهای Back\PackageController
 * (startInstall / dispatchInstall / installWithSubscription /
 *  reinstallWithExistingLicense / licenseHasTime)
 *
 * اتصالات خارجی عیناً صدا زده می‌شوند (PackageApiService)؛ همه‌ی مسیرها
 * try/catch دارند تا نبودِ شبکه پنل را نکشد .
 */
trait InteractsWithPackages
{
    /**
     * رفع «پکیج مستقل نصب نمی‌شود و هیچ پیغامی هم نمایش داده نمی‌شود»:
     * آخرین خطای جریان نصب (مثلاً 422 «این طرح فقط یک‌بار قابل خریداری است…»)
     * اینجا نگه‌داری می‌شود تا installFromDetail بتواند همان متن واقعیِ سرور
     * را داخل مودال (installError) نمایش دهد — نه فقط یک toast گذرا.
     */
    public ?string $lastInstallError = null;

    protected function api(): PackageApiService
    {
        return app(PackageApiService::class);
    }

    /**
     * آدرس callback پرداخت — در پنل جدید به کامپوننت np اشاره می‌کند،
     * اگر روت هنوز ثبت نشده باشد همان روت قدیمی پنل استفاده می‌شود.
     */
    protected function paymentCallbackUrl(): string
    {
        if (Route::has('np.packages.payment.callback')) {
            return route('np.packages.payment.callback');
        }

        return route(config('packages.payment.callback_route'));
    }

    /**
     * شروع فرآیند نصب (پورت startInstall قدیمی)
     * - پکیج رایگان: مستقیم dispatchInstall
     * - اشتراک فعال: installWithSubscription
     * - لایسنس قبلی: reinstallWithExistingLicense
     * - پکیج پولی: ایجاد purchase → ریدایرکت به درگاه
     */
    public function startInstall(string $slug, ?int $pricingPlanId = null, bool $useSubscription = false, bool $useLicense = false): void
    {
        $this->lastInstallError = null;

        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        /*
         * گیت تنظیمات بازارچه (گارد سروری، نه فقط UI):
         * کلید اصلی خاموش یا allow_install خاموش ⇒ هیچ نصب/خریدی شروع نمی‌شود.
         */
        if (! MarketplaceSettings::marketEnabled() || ! MarketplaceSettings::allowInstall()) {
            $this->dispatch('toast', message: MarketplaceSettings::installDisabledMessage(), type: 'error');

            return;
        }

        /*
         * قفل نصب همزمان: در هر لحظه فقط یک نصب (پکیج/قالب/آپدیت) مجاز است.
         * اگر همین مورد در حال نصب است یا نصب دیگری در جریان است، شروع نشود.
         */
        if ($this->isSlugInstalling($slug)) {
            $this->lastInstallError = 'نصب این مورد همچنان در جریان است؛ لطفاً تا پایان آن صبر کنید.';
            $this->dispatch('toast', message: $this->lastInstallError, type: 'error');

            return;
        }

        if ($this->hasOtherInstallationInProgress($slug)) {
            $this->lastInstallError = $this->installationLockMessage();
            $this->dispatch('toast', message: $this->lastInstallError, type: 'error');

            return;
        }

        try {
            $package = $this->api()->getPackage($slug);
            $package = $package['data'] ?? $package;
            // کلید active_versions همیشه حاضر نیست (پاسخ‌های ناقص API) → fail-safe
            $version = collect($package['active_versions'] ?? [])->first()['version'] ?? null;

            /*
             * اگر پلنی انتخاب نشده و پکیج پلن دارد → ارزان‌ترین پلن خودکار انتخاب
             *   می‌شود (مثل پیش‌فرض مودال). API خرید حتی برای پکیج‌های is_free=true
             *   هم pricing_plan_id می‌خواهد؛ بدون پلن، خطای «انتخاب طرح» می‌دهد
             *   (باگ نصب پکیج‌های رایگان — مودال قبلاً برای is_free پلن‌ها را
             *   نشان نمی‌داد و planId=null می‌فرستاد).
             */
            $activePlans = collect($package['active_pricing_plans'] ?? []);
            if ($pricingPlanId === null && $activePlans->isNotEmpty()) {
                $cheapest = $activePlans
                    ->sortBy(fn ($plan) => (int) ($plan['discount_price'] ?? $plan['price'] ?? 0))
                    ->values()
                    ->first();
                $pricingPlanId = filled($cheapest['id'] ?? null) ? (int) $cheapest['id'] : null;
            }

            $selectedPlan = collect($package['active_pricing_plans'] ?? [])
                ->firstWhere('id', $pricingPlanId);

            $isFree = false;
            if ($selectedPlan) {
                $isFree = ($selectedPlan['price'] == 0);
            } else {
                // اگر پلن پیدا نشد، از مقدار is_free پکیج استفاده کن (پورت قدیمی — نرمال‌شده برای هر شکل داده)
                $isFree = PackageIndex::normalizeIsFree($package['is_free'] ?? null);
            }

            // نصب با اشتراک فعال (بدون پرداخت)
            if ($useSubscription) {
                $this->installWithSubscription($slug);

                return;
            }

            // اگر قبلاً نصب شده، جلوگیری شود
            if (InstalledModule::where('slug', $slug)->exists()) {
                $this->dispatch('toast', message: 'این پکیج قبلاً نصب شده است.', type: 'error');

                return;
            }

            /*
             * نصب مجدد با لایسنس قبلی (خریداری‌شده — بدون پرداخت)
             *
             * فاز ۲۱۷ اصلاحیه ۲ — رفع «نمی‌توانم پکیجی را که با طرح آغازینِ رایگان
             * نصب کرده بودم، پس از حذف دوباره نصب کنم»:
             * این گیت باید «قبل» از گیت isFree اجرا شود؛ چون وقتی پلنی انتخاب
             * نشده، ارزان‌ترین پلن (مثل طرح آغازینِ رایگان) خودکار انتخاب می‌شود و
             * isFree=true می‌گردد — ترتیبِ قبلی جریان را به dispatchInstall ←
             * createPurchase می‌برد و سرور بازار با گارد «خرید تکراری» (۴۲۲
             * «شما این پکیج را با طرح آغازین در اختیار دارید…») رد می‌کرد، در حالی
             * که کاربر اعتبارِ فعال دارد و مودال هم «بدون پرداخت نصب می‌شود»
             * نشان می‌داد. با ترتیب جدید، نصب با همان لایسنسِ فعال انجام می‌شود.
             */
            if ($useLicense) {
                $this->reinstallWithExistingLicense($slug);

                return;
            }

            if ($isFree) {
                // پکیج رایگان: مستقیم به job
                $this->dispatchInstall($slug, $version, $pricingPlanId);

                return;
            }

            // پکیج پولی: ایجاد درخواست خرید با pricing_plan_id
            $callbackUrl = $this->paymentCallbackUrl();

            $purchase = $this->api()->createPurchase(
                $slug,
                $callbackUrl,
                auth('adminPanel')->id(),
                $pricingPlanId
            );

            // ذخیره رکورد خرید (پورت قدیمی)
            PackagePurchase::create([
                'admin_id' => auth('adminPanel')->id(),
                'package_slug' => $slug,
                'package_name' => $package['name'] ?? $slug,
                'version' => $version,
                'amount' => $purchase['amount'] ?? ($package['price'] ?? 0),
                'gateway' => $purchase['gateway'] ?? null,
                'transaction_id' => $purchase['transaction_id'] ?? null,
                'payment_url' => $purchase['payment_url'] ?? null,
                'status' => PackagePurchase::STATUS_PENDING,
                'meta' => $purchase,
            ]);

            if (! empty($purchase['payment_url'])) {
                $this->redirect($purchase['payment_url']);

                return;
            }

            $this->lastInstallError = 'درخواست خرید ثبت شد اما آدرس درگاه پرداخت دریافت نشد.';
            $this->dispatch('toast', message: $this->lastInstallError, type: 'error');
        } catch (RuntimeException $e) {
            $this->lastInstallError = $e->getMessage();
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        } catch (Throwable $e) {
            Log::error('Package install failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            $this->lastInstallError = 'خطا در ارتباط با سرور: '.$e->getMessage();
            $this->dispatch('toast', message: 'خطا در ارتباط با سرور.', type: 'error');
        }
    }

    /**
     * بررسی وضعیت خرید/اشتراک یک پکیج (پورت checkPurchase قدیمی)
     * خروجی برای مودال تأیید نصب پنل جدید — اولویت‌ها:
     * ۱) اشتراک فعال  ۲) خریدِ ثبت‌شده روی سرور بازار  ۳) خرید محلی با لایسنس معتبر  ۴) جریان عادی خرید
     */
    public function checkPurchaseState(string $slug): array
    {
        auth('adminPanel')->user()?->can('packages.index') ?: abort(403);

        // اولویت ۱: اشتراک فعال — از API (داده تازه، بدون کش)
        try {
            $apiPackage = $this->api()->getPackage($slug);
            $apiPackage = $apiPackage['data'] ?? $apiPackage;
            $subscription = $apiPackage['subscription'] ?? null;
            $subscriptionExpired = false;

            if ($subscription && ($subscription['is_free_with_subscription'] ?? false)) {
                $expiresAt = $subscription['subscription_expires_at'] ?? null;
                $active = true;
                if (! empty($expiresAt)) {
                    try {
                        $active = Carbon::parse($expiresAt)->isFuture();
                    } catch (Throwable $e) {
                        $active = false;
                    }
                }

                if ($active) {
                    return [
                        'mode' => 'subscription',
                        'plan_name' => $subscription['plan_name'] ?? null,
                        'expires_at' => $expiresAt,
                        'expires_at_jalali' => $this->jexpiry($expiresAt),
                        'days_remaining' => $subscription['days_remaining'] ?? null,
                    ];
                }

                $subscriptionExpired = true;
            }

            /*
             * اولویت ۲: خریدِ ثبت‌شده روی خودِ سرور بازار
             * پاسخ detail سرور برای هر پروژه is_purchased + purchased_license دارد؛
             * اگر پکیج مستقیم از سایت بازار خریداری شده باشد، فروشگاه رکورد محلی
             * ندارد و بدون این چک، مودال به‌اشتباه جریان «پرداخت و نصب» را نشان
             * می‌داد. لایسنس معتبر ⇒ همگام‌سازی رکورد محلی + نصب بدون پرداخت.
             */
            $purchasedLicense = $apiPackage['purchased_license'] ?? null;
            if (($apiPackage['is_purchased'] ?? false) && is_array($purchasedLicense) && filled($purchasedLicense['license_key'] ?? null)) {
                $licenseExpiresAt = $purchasedLicense['expires_at'] ?? null;

                if ($this->licenseHasTime($licenseExpiresAt)) {
                    $this->syncMarketplacePurchase($slug, $apiPackage, $purchasedLicense);

                    return [
                        'mode' => 'license',
                        'license_key' => $purchasedLicense['license_key'],
                        'expires_at' => $licenseExpiresAt,
                        'expires_at_jalali' => $this->jexpiry($licenseExpiresAt),
                        'days_remaining' => $purchasedLicense['days_remaining'] ?? null,
                    ];
                }

                return [
                    'mode' => 'paid',
                    'warning' => 'خرید قبلی شما برای این پکیج منقضی شده است؛ برای نصب، پلن موردنظر را انتخاب کنید.',
                ];
            }

            if ($subscriptionExpired) {
                return [
                    'mode' => 'paid',
                    'warning' => 'اشتراک شما منقضی شده است؛ برای نصب، پلن موردنظر را انتخاب کنید.',
                ];
            }
        } catch (Throwable $e) {
            // API در دسترس نیست → ادامه با بررسی خرید محلی
        }

        // اولویت ۳: خرید مستقیم قبلی (رکورد محلی)
        $purchase = PackagePurchase::query()
            ->where('package_slug', $slug)
            ->where('status', PackagePurchase::STATUS_PAID)
            ->whereNotNull('license_key')
            ->orderByDesc('paid_at')
            ->first();

        if (! $purchase) {
            return ['mode' => 'paid'];
        }

        try {
            $verify = $this->api()->verifyLicense($slug, $purchase->license_key);
        } catch (Throwable $e) {
            return [
                'mode' => 'paid',
                'warning' => 'بررسی آنلاین لایسنس ناموفق بود. لطفاً مجدد تلاش کنید.',
            ];
        }

        if (! ($verify['valid'] ?? false) || ! $this->licenseHasTime($verify['expires_at'] ?? null, $purchase->license_expires_at)) {
            return [
                'mode' => 'paid',
                'warning' => $verify['message'] ?? 'لایسنس قبلی شما منقضی شده است. برای نصب، یکی از پلن‌ها را انتخاب کنید.',
            ];
        }

        // لایسنس معتبر → نصب بدون پرداخت
        $expiresAt = $verify['expires_at'] ?? $purchase->license_expires_at;

        return [
            'mode' => 'license',
            'license_key' => $purchase->license_key,
            'expires_at' => $expiresAt,
            'expires_at_jalali' => $this->jexpiry($expiresAt),
            'days_remaining' => $expiresAt ? (int) round(now()->diffInDays(Carbon::parse($expiresAt))) : null,
        ];
    }

    /**
     * همگام‌سازی خریدِ ثبت‌شده روی سرور بازار با جدول محلی package_purchases
     * وقتی پکیج مستقیم از سایت بازار خریداری شده باشد، فروشگاه رکورد محلی ندارد؛
     * نشان «خریداری‌شده» روی کارت، دکمه «بررسی و نصب» و جریان
     * reinstallWithExistingLicense همه به این رکورد تکیه می‌کنند. رکورد همان
     * لایسنس به‌روزرسانی می‌شود تا با هر بار باز شدن مودال، رکورد تکراری ساخته نشود.
     */
    protected function syncMarketplacePurchase(string $slug, array $package, array $purchasedLicense): void
    {
        try {
            $licenseKey = (string) $purchasedLicense['license_key'];

            $existing = PackagePurchase::query()
                ->where('package_slug', $slug)
                ->where('license_key', $licenseKey)
                ->orderByDesc('paid_at')
                ->first();

            $versions = collect($package['active_versions'] ?? [])->pluck('version')->filter()->values()->all();
            if (! empty($versions)) {
                usort($versions, fn ($a, $b) => version_compare((string) $a, (string) $b));
                $version = (string) end($versions);
            } else {
                $version = (string) ($package['latest_version']['version'] ?? '');
            }

            $payload = [
                'type' => 'package',
                'admin_id' => auth('adminPanel')->id(),
                'package_slug' => $slug,
                'package_name' => $package['name'] ?? $slug,
                'version' => $version,
                'license_key' => $licenseKey,
                'license_expires_at' => $purchasedLicense['expires_at'] ?? null,
                'status' => PackagePurchase::STATUS_PAID,
                'paid_at' => $existing?->paid_at ?? now(),
            ];

            if ($existing) {
                $existing->fill($payload)->save();
            } else {
                PackagePurchase::create($payload + [
                    'meta' => ['source' => 'marketplace', 'synced_at' => now()->toDateTimeString()],
                ]);
            }
        } catch (Throwable $e) {
            // همگام‌سازی حیاتی نیست — نصب با لایسنس گرفته‌شده از API ادامه می‌یابد
            Log::warning('Marketplace purchase sync failed', ['slug' => $slug, 'error' => $e->getMessage()]);
        }
    }

    /** تاریخ جلالی برای نمایش در مودال (null امن) */
    protected function jexpiry(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return jdate(Carbon::parse($date))->format('Y/m/d');
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * نصب پکیج رایگان (پورت dispatchInstall قدیمی)
     */
    protected function dispatchInstall(string $slug, ?string $version, ?int $pricingPlanId): void
    {
        try {
            // برای پکیج رایگان، ابتدا یک درخواست "purchase" آزاد به API می‌زنیم
            $callbackUrl = $this->paymentCallbackUrl();

            try {
                $purchase = $this->api()->createPurchase($slug, $callbackUrl, auth('adminPanel')->id(), $pricingPlanId);
            } catch (RuntimeException $purchaseError) {
                /*
                 * فاز ۲۱۷ اصلاحیه ۲ — رفع «پس از حذف، نصب مجدد پکیج با طرح آغازینِ
                 * رایگان ممکن نیست»: سرور بازار، خریدِ مجددِ همان دورهٔ فعال را با
                 * ۴۲۲ رد می‌کند («شما این پکیج را با طرح آغازین در اختیار دارید…» /
                 * «این طرح فقط یک‌بار قابل خریداری است…») در حالی که کاربر اعتبار
                 * فعال دارد و باید بتواند نصب کند. در این حالت به‌جای نمایش خطا،
                 * نصب با «لایسنس فعالِ موجود» ادامه می‌یابد.
                 */
                if ($this->isDuplicatePurchaseRefusal($purchaseError->getMessage())) {
                    Log::info('Package free install refused as duplicate — retrying with existing license', [
                        'slug'    => $slug,
                        'message' => $purchaseError->getMessage(),
                    ]);

                    $this->reinstallWithExistingLicense($slug);

                    return;
                }

                throw $purchaseError;
            }

            $licenseKey = $purchase['license_key'] ?? null;

            if (! $licenseKey) {
                $callbackUrl = $this->paymentCallbackUrl();
                $purchase = $this->api()->createPurchase($slug, $callbackUrl, auth('adminPanel')->id(), $pricingPlanId);
                $licenseKey = $purchase['license_key'] ?? null;
            }

            /*
             * نیاز محصول: از لحظه‌ی درخواست نصب تا نتیجه (نصب/خطا)،
             * پکیج باید «در حال نصب» نمایش داده شود (نه «در صف»). markPending رکورد
             * را از همین لحظه در وضعیت updating می‌گذارد تا کارت و مودال پیشرفت
             * فوراً وضعیت درست را نشان دهند.
             *
             * name از کش محلی پاس می‌شود (ستون name جدول NOT NULL است؛
             * قبلاً null پاس می‌شد و درج با خطای 1364 شکست می‌خورد و نصب هرگز شروع نمی‌شد)
             */
            InstalledModule::markPending(
                $slug,
                PackageCache::where('slug', $slug)->value('name'),
                $version,
                $licenseKey
            );

            InstallPackageJob::dispatch(
                $slug,
                $licenseKey,
                auth('adminPanel')->id(),
                null, // purchaseId در callback پر می‌شود
                null  // downloadToken از داخل job گرفته می‌شود
            );

            $this->dispatch('toast', message: 'نصب پکیج آغاز شد — تا پایان، وضعیت «در حال نصب» نمایش داده می‌شود.', type: 'success');
            $this->afterInstallQueued($slug);
        } catch (\Exception $e) {
            $this->lastInstallError = $e->getMessage();
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    /**
     * نصب پکیج با اشتراک فعال (پورت installWithSubscription قدیمی)
     */
    protected function installWithSubscription(string $slug): void
    {
        try {
            $package = $this->api()->getPackage($slug);
            $package = $package['data'] ?? $package;
        } catch (RuntimeException $e) {
            $this->lastInstallError = 'بررسی اشتراک ناموفق بود: '.$e->getMessage();
            $this->dispatch('toast', message: $this->lastInstallError, type: 'error');

            return;
        }

        $subscription = $package['subscription'] ?? null;
        $eligible = false;

        if ($subscription && ($subscription['is_free_with_subscription'] ?? false)) {
            $expiresAt = $subscription['subscription_expires_at'] ?? null;
            if (empty($expiresAt)) {
                $eligible = true; // اشتراک نامحدود
            } else {
                try {
                    $eligible = Carbon::parse($expiresAt)->isFuture();
                } catch (Throwable $e) {
                    $eligible = false;
                }
            }
        }

        if (! $eligible) {
            $this->lastInstallError = 'اشتراک فعال شما این پکیج را پوشش نمی‌دهد یا منقضی شده است. برای نصب، پلن را انتخاب کنید.';
            $this->dispatch('toast', message: $this->lastInstallError, type: 'error');

            return;
        }

        $licenseKey = $subscription['license_key'] ?? null;
        $downloadToken = null;

        // لایسنس صادرنشده (فقط اشتراک) → درخواست purchase از مسیر اشتراکِ API
        if (empty($licenseKey)) {
            try {
                $callbackUrl = $this->paymentCallbackUrl();
                $purchase = $this->api()->createPurchase(
                    $slug,
                    $callbackUrl,
                    auth('adminPanel')->id(),
                    null   // pricing_plan_id لازم نیست — مسیر اشتراک قبل از چک پلن اجرا می‌شود
                );
            } catch (RuntimeException $e) {
                $this->lastInstallError = 'صدور لایسنس از اشتراک ناموفق بود: '.$e->getMessage();
                $this->dispatch('toast', message: $this->lastInstallError, type: 'error');

                return;
            }

            // اطمینان: پاسخ واقعاً از مسیر اشتراک باشد، نه شروع یک خرید پولی
            if (empty($purchase['via_subscription']) || empty($purchase['license_key'])) {
                $this->lastInstallError = 'دسترسی به این پکیج از طریق اشتراک ممکن نشد. لطفاً پلن موردنظر را خریداری کنید.';
                $this->dispatch('toast', message: $this->lastInstallError, type: 'error');

                return;
            }

            $licenseKey = $purchase['license_key'];
            $downloadToken = $purchase['download_token'] ?? null;
        }

        $version = $package['latest_version']['version']
            ?? collect($package['active_versions'] ?? [])->first()['version']
            ?? null;

        InstalledModule::markPending($slug, $package['name'] ?? null, $version, $licenseKey);

        InstallPackageJob::dispatch(
            $slug,
            $licenseKey,
            auth('adminPanel')->id(),
            null,
            $downloadToken   // اگر token گرفتیم، install مرحله verifyLicense را skip می‌کند
        );

        Log::info('Package install via subscription', [
            'slug' => $slug,
            'had_license' => ! empty($subscription['license_key']),
        ]);

        $this->dispatch('toast', message: 'نصب پکیج آغاز شد — تا پایان، وضعیت «در حال نصب» نمایش داده می‌شود.', type: 'success');
        $this->afterInstallQueued($slug);
    }

    /**
     * نصب مجدد با لایسنس قبلی (پورت reinstallWithExistingLicense قدیمی)
     * اگر رکورد محلی نبود ولی سرور بازار این پکیج را برای پروژه «خریداری‌شده»
     * اعلام کند (خرید مستقیم از سایت بازار)، لایسنس از پاسخ detail گرفته،
     * رکورد محلی همگام و نصب ادامه می‌یابد — نه «خرید معتبری یافت نشد».
     */
    protected function reinstallWithExistingLicense(string $slug): void
    {
        $purchase = PackagePurchase::query()
            ->where('package_slug', $slug)
            ->where('status', PackagePurchase::STATUS_PAID)
            ->whereNotNull('license_key')
            ->orderByDesc('paid_at')
            ->first();

        $licenseKey = $purchase?->license_key;
        $adminId = $purchase?->admin_id ?? auth('adminPanel')->id();
        $purchaseId = $purchase?->id;
        $packageName = $purchase?->package_name;

        if (empty($licenseKey)) {
            try {
                $apiPackage = $this->api()->getPackage($slug);
                $apiPackage = $apiPackage['data'] ?? $apiPackage;
                $purchasedLicense = $apiPackage['purchased_license'] ?? null;

                if (($apiPackage['is_purchased'] ?? false)
                    && is_array($purchasedLicense)
                    && filled($purchasedLicense['license_key'] ?? null)
                    && $this->licenseHasTime($purchasedLicense['expires_at'] ?? null)) {
                    $licenseKey = (string) $purchasedLicense['license_key'];
                    $packageName = $apiPackage['name'] ?? $slug;
                    $this->syncMarketplacePurchase($slug, $apiPackage, $purchasedLicense);
                    $purchaseId = PackagePurchase::query()
                        ->where('package_slug', $slug)
                        ->where('license_key', $licenseKey)
                        ->orderByDesc('paid_at')
                        ->value('id');
                }
            } catch (Throwable $e) {
                Log::warning('Marketplace license lookup failed', ['slug' => $slug, 'error' => $e->getMessage()]);
            }
        }

        if (empty($licenseKey)) {
            $this->lastInstallError = 'خرید معتبری برای این پکیج یافت نشد.';
            $this->dispatch('toast', message: $this->lastInstallError, type: 'error');

            return;
        }

        try {
            $verify = $this->api()->verifyLicense($slug, $licenseKey);
        } catch (Throwable $e) {
            $this->lastInstallError = 'بررسی لایسنس ناموفق بود: '.$e->getMessage();
            $this->dispatch('toast', message: $this->lastInstallError, type: 'error');

            return;
        }

        if (! ($verify['valid'] ?? false)) {
            $this->lastInstallError = $verify['message'] ?? 'لایسنس شما منقضی شده است. برای ادامه، یکی از پلن‌ها را انتخاب کنید.';
            $this->dispatch('toast', message: $this->lastInstallError, type: 'error');

            return;
        }

        InstalledModule::markPending($slug, $packageName, null, $licenseKey);

        InstallPackageJob::dispatch(
            $slug,
            $licenseKey,
            $adminId,
            $purchaseId,
            $verify['download_token'] ?? null
        );

        Log::info('Package reinstall with existing license', [
            'slug' => $slug,
            'purchase_id' => $purchaseId,
        ]);

        $this->dispatch('toast', message: 'نصب پکیج آغاز شد — تا پایان، وضعیت «در حال نصب» نمایش داده می‌شود.', type: 'success');
        $this->afterInstallQueued($slug);
    }

    /**
     * تشخیص ردِ «خرید تکراری» از سرور بازار — پیام‌های شناخته‌شدهٔ گاردهای
     * ضدخرید مجدد (RenewalService: «شما این پکیج را با طرح … در اختیار دارید…» /
     * طرح یک‌بارمصرف: «این طرح فقط یک‌بار قابل خریداری است…»). این ردها یعنی
     * کاربر «اعتبارِ فعال» دارد؛ نصب باید با همان لایسنس ادامه یابد نه خطا.
     */
    protected function isDuplicatePurchaseRefusal(string $message): bool
    {
        return str_contains($message, 'در اختیار دارید')
            || str_contains($message, 'فقط یک‌بار قابل خریداری')
            || str_contains($message, 'قبلاً از این طرح استفاده کرده');
    }

    /**
     * هوک پس از صف‌شدن نصب — کامپوننت لیست، مودال پیشرفت را باز می‌کند
     */
    protected function afterInstallQueued(string $slug): void
    {
        if (method_exists($this, 'refreshAfterInstall')) {
            $this->refreshAfterInstall($slug);
        }
    }

    /**
     * آپدیت ماژول هم مثل نصب: از همین لحظه «در حال نصب» نمایش داده شود
     */
    protected function markUpdatingBeforeDispatch(string $slug, ?string $version = null): void
    {
        $module = InstalledModule::where('slug', $slug)->first();

        InstalledModule::markPending($slug, $module->name ?? null, $version ?? $module->version ?? null, $module->license_key ?? null);
    }

    /**
     * آیا لایسنس هنوز زمان (اعتبار) دارد؟ (پورت licenseHasTime قدیمی)
     * خالی = لایسنس مادام‌العمر (duration_months=0) → دارای اعتبار
     */
    protected function licenseHasTime($apiExpiresAt = null, $dbExpiresAt = null): bool
    {
        $date = $apiExpiresAt ?: $dbExpiresAt;

        if (empty($date)) {
            return true; // نامحدود
        }

        try {
            return Carbon::parse($date)->isFuture();
        } catch (Throwable $e) {
            return false;
        }
    }
}
