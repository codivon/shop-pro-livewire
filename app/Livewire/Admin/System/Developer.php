<?php

namespace App\Livewire\Admin\System;

use App\Jobs\ProcessUpdateJob;
use App\Http\Controllers\Back\AiRelayController;
use App\Models\BotTrap;
use App\Services\LicenseEnvelopeVerifier;
use App\Services\SecuritySettings;
use App\Support\MarketplaceSettings;
use App\Support\MultiVendorSettings;
use App\Support\PulseSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * تنظیمات توسعه‌دهنده — پورت ۱:۱ Back\DeveloperController
 * (showSettings/updateSettings[shared_hosting|general] / downApplication / upApplication /
 *  webpushNotification / clearCache / showUpdater / checkUpdate / updateApplication /
 *  updaterAfter / updateStatus / resetUpdate)
 *
 * گیت: قدیمی روت‌ها را با میدل‌ور CheckCreator می‌بست (فقط level=creator) و هیچ permission
 * «developer.*» در دیتابیس وجود ندارد → همین شرط creator اینجا اعمال شده است.
 *
 * انحراف مستندشده در clearCache: قدیمی optimize:clear را صدا می‌زد؛ طبق اسپک فقط
 * cache:clear + config:clear + view:clear اجرا می‌شود (route:cache با Livewire ناسازگار است).
 */
#[Title('تنظیمات توسعه‌دهنده')]
class Developer extends Component
{
    /** آدرس پنل بروزرسانی (پورت constructor قدیمی) : منتقل‌شده به update.opashop.ir : بازگشت به update.weblak.ir */
    protected string $panelUrl = 'https://update.weblak.ir/api/v1/check-update';

    /* ---------------- کرون جاب ---------------- */

    public bool $sharedHostingCronEnabled = false;

    /** زمان‌بند در ۲ دقیقه‌ی اخیر اجرا شده است؟ (پورت showSettings) */
    public bool $scheduleRun = false;

    public string $scheduleLastRun = '';

    /* ---------------- حالت بروزرسانی (down/up) ---------------- */

    public string $maintenanceSecret = '';

    public string $info_maintenance_mode_title = '';

    public string $info_maintenance_mode_description = '';

    /* ---------------- تنظیمات عمومی ---------------- */

    public string $updaterToken = '';

    public bool $appDebugMode = false;

    /**
     * محیط برنامه (APP_ENV) — از پنل تغییر می‌کند تا تنظیمِ سرعت
     * (production = کش‌ها فعال، خطاها مخفی) به SSH/ویرایش دستی .env نیاز نداشته باشد.
     */
    public string $appEnv = 'production';

    /**
     * کلید اصلی شروع Pulse (PULSE_ENABLED) — recoreدرهای پایش سرعت.
     * (تفاوت با pulseAdminAccess: آن سوییچ «دسترسی مدیران به داشبورد /pulse» است؛
     * این سوییچ «خودِ ثبت متریک‌ها» است که سربار کمی روی هر درخواست دارد.)
     */
    public bool $pulseEnabled = false;

    /** داشبورد Pulse برای مدیران پنل (گیت viewPulse در AuthServiceProvider) */
    public bool $pulseAdminAccess = true;

    public bool $debugbarEnabled = false;

    public bool $enableOldUpdates = true;

    /** در قدیمی فیلدی در فرم نبود ولی هر بار «false» ذخیره می‌شد؛ اینجا مقدار فعلی حفظ می‌شود */
    public bool $enableHelpVideos = false;

    /* ---------------- بروزرسانی نرم‌افزار ---------------- */

    public string $versionInstalled = '1.0.0';

    public bool $isNewVersionAvailable = false;

    public string $versionAvailable = '';

    public ?string $updateDescription = null;

    public ?string $updateErrors = null;

    public bool $checking = false;

    /* ---------------- امنیت ---------------- */

    public bool $securityCaptchaEnabled = true;

    public bool $securityCaptchaAdminLogin = true;

    public bool $securityCaptchaRegister = true;

    public bool $securityCaptchaCustomerLogin = false;

    public bool $securityCaptchaSellerLogin = false;

    public bool $securityCaptchaComments = false;

    /* درایور هر نقطهٔ کپچا (image | math | hcaptcha | turnstile) */
    public string $captchaDriverAdminLogin = 'image';

    public string $captchaDriverRegister = 'image';

    public string $captchaDriverCustomerLogin = 'image';

    public string $captchaDriverSellerLogin = 'image';

    public string $captchaDriverComments = 'image';

    /* کلیدهای کپچای ابری */
    public string $captchaHcaptchaSiteKey = '';

    public string $captchaHcaptchaSecret = '';

    public string $captchaTurnstileSiteKey = '';

    public string $captchaTurnstileSecret = '';

    /** پیچیدگی کپچای ریاضی: تعداد عملوند (۲ تا ۴) */
    public int $captchaMathOperands = 3;

    public int $securityTimeTrapSeconds = 3;

    public bool $securityHoneypotEnabled = true;

    public bool $securityLockoutEnabled = true;

    public int $securityLockoutAdminMax = 5;

    public int $securityLockoutAdminMinutes = 15;

    public int $securityLockoutCustomerMax = 5;

    public int $securityLockoutCustomerMinutes = 1;

    public int $securityLockoutCommentsMax = 5;

    public int $securityLockoutCommentsMinutes = 10;

    public bool $securityHeadersEnabled = true;

    public bool $securityHstsEnabled = false;

    public bool $securityBotTrapEnabled = true;

    public bool $adminTwoFactorEnabled = false;

    public string $adminTwoFactorChannel = 'email';

    /* ---------------- بازارچه ---------------- */

    public bool $marketEnabled = true;

    public bool $marketAllowInstall = true;

    public bool $marketAllowUninstall = true;

    public bool $marketShowTemplates = true;

    public bool $marketAutoCheckUpdates = false;

    public int $marketItemsPerPage = MarketplaceSettings::DEFAULT_PER_PAGE;

    public string $marketNoticeText = '';

    /* ---------------- چندفروشندگی ---------------- */

    /** کلید اصلی — همان multi_vendor_system_status قدیمی (قرارداد 'true'/'false') */
    public bool $vendorEnabled = false;

    /** تسویهٔ خودکار فروشندگان پس از پرداخت سفارش */
    public bool $vendorAutoSettlement = true;

    /** اعتبار کمیسیون اختصاصی هر فروشنده */
    public bool $vendorSellerOverride = true;

    /** پرداخت دسته‌جمعی تسویه در صفحهٔ «درخواستی‌ها» */
    public bool $vendorBulkSettlement = true;

    /** کارت آمار تسویه در داشبورد فروشنده */
    public bool $vendorDashboardStats = true;

    /** صورت‌حساب تسویهٔ فروشنده */
    public bool $vendorSettlementStatement = true;

    /** گزارش فروش فروشنده */
    public bool $vendorSellerAnalytics = true;

    /** سیگنال‌های تقاضا */
    public bool $vendorDemandSignals = true;

    /** دسترسی به نظرات محصولات */
    public bool $vendorReviewsAccess = true;

    /** مرکز موجودی */
    public bool $vendorInventoryCenter = true;

    /** خروجی اکسل محصولات فروشنده */
    public bool $vendorProductsExport = true;

    /** لیدربورد مقایسهٔ فروشندگان */
    public bool $vendorLeaderboard = true;

    /** کمیسیون پیش‌فرض سراسری ٪ — '' یعنی بدون پیش‌فرض (رفتار قبل: ۰) */
    public string $vendorDefaultCommission = '';

    /** پنل مدرن فروشندگان */
    public bool $vendorModernPanel = true;

    /** صفحهٔ فرود پیشرفتهٔ فروشندگان */
    public bool $vendorLandingPage = true;

    /** احراز هویت مالکیت موبایل فروشنده با فینوتک */
    public bool $vendorKycMobileVerify = false;

    /** ربات‌گیر ورود فروشنده با رمز عبور */
    public bool $vendorLoginCaptcha = true;

    /** انتخابگر آیکون پنل‌ها (قانون طلایی) */
    public bool $adminIconPicker = true;

    /** شناسهٔ کلاینت فینوتک */
    public string $vendorFinnotechClientId = '';

    /** رمز کلاینت فینوتک */
    public string $vendorFinnotechClientSecret = '';

    /** حداقل مبلغ درخواست برداشت فروشنده (تومان) — ۰ یعنی بدون حد اضافه */
    public string $vendorMinWithdraw = '0';

    /** مایگریشن‌های اجرا نشده (فایل موجود، رکورد/جدول موجود نیست) */
    public array $pendingMigrations = [];

    /* ---------------- رلهٔ سراسری هوش مصنوعی ---------------- */

    /** کلید دسترسی رله (RELAY_ACCESS_KEY) — همهٔ سرویس‌ها با یک کلید */
    public string $aiRelayAccessKey = '';

    /** دامنهٔ نصب رله — مثل https://relay.example.com (برای نمایش آدرس‌های آماده) */
    public string $aiRelayDomain = '';

    /** IPهای مجاز — با کاما/خط جدید */
    public string $aiRelayAllowedIps = '';

    /** هدرهای عمومی به همهٔ سرویس‌ها — هر خط «Name: value» */
    public string $aiRelayHeaders = '';

    /** سرویس‌های رله — [{key,title,base,auth,base_url,api_key,headers,enabled,configured_key}] */
    public array $aiRelayProviders = [];

    /* ================= کلید عمومی راستی‌آزمایی لایسنس ================= */

    /** مقدار کلید عمومی — خطِ تک‌خطی .env با دنبالهٔ \n، یا PEM چندخطی، یا Base64 خام؛ خالی = استفاده از env/کلید جاسازی‌شده */
    public string $licensePublicKey = '';

    /** وضعیت منابع کلید عمومی — خروجی LicenseEnvelopeVerifier::candidateSources() */
    public array $licenseKeySources = [];

    /** تعداد کلیدهای عمومی مورد اعتماد فعلی (برای لاگ/نمایش) */
    public int $licenseTrustedKeyCount = 0;


    public function mount(): void
    {
        $this->ensureCreator();

        // بررسی مایگریشن‌های جدید (فقط برای نمایش بخش سبز)
        $this->pendingMigrations = $this->detectPendingMigrations();

        // پورت showSettings
        $schedule_last_work = option('schedule_run');
        $this->scheduleLastRun = (string) ($schedule_last_work ?: '');
        if ($schedule_last_work) {
            if (! is_object($schedule_last_work)) {
                $schedule_last_work = Carbon::createFromDate($schedule_last_work);
            }

            $diff = $schedule_last_work->diffInMinutes(now());
            $this->scheduleRun = ($diff <= 2);
        }

        $this->sharedHostingCronEnabled = option('shared_hosting_cron_enabled', 'off') === 'on';

        // حالت بروزرسانی
        $this->maintenanceSecret = str_random(15);
        $this->info_maintenance_mode_title = (string) option('info_maintenance_mode_title', 'در حال بروزرسانی هستیم!');
        $this->info_maintenance_mode_description = (string) option('info_maintenance_mode_description', 'در حال بروزرسانی وبسایت هستیم لطفا دقایقی بعد مراجعه کنید.<br>از صبر و شکیبایی شما متشکریم.');

        // تنظیمات عمومی
        $this->updaterToken = (string) option('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN');
        $this->appDebugMode = config('app.debug') == true;

        // محیط و کلید اصلی Pulse (هر دو در env زندگی می‌کنند)
        $this->appEnv = app()->environment();
        $this->pulseEnabled = (bool) config('pulse.enabled');

        $this->debugbarEnabled = config('debugbar.enabled') == true;
        $this->enableOldUpdates = option('enable_old_updates', 'true') == 'true';
        $this->enableHelpVideos = option('enable_help_videos', 'true') == 'true';

        // دسترسی مدیران به Pulse
        $this->pulseAdminAccess = PulseSettings::adminAccess();

        // آپدیت‌کننده (پورت showUpdater — بدون اتصال شبکه در mount)
        $this->versionInstalled = $this->getVersion();
        $this->versionAvailable = $this->versionInstalled;
        $this->isNewVersionAvailable = false;
        $this->updateErrors = null;

        // امنیت
        $this->securityCaptchaEnabled = SecuritySettings::captchaMasterEnabled();
        $this->securityCaptchaAdminLogin = SecuritySettings::captchaEnabled('admin_login');
        $this->securityCaptchaRegister = SecuritySettings::captchaEnabled('register');
        $this->securityCaptchaCustomerLogin = SecuritySettings::captchaEnabled('customer_login');
        $this->securityCaptchaSellerLogin = SecuritySettings::captchaEnabled('seller_login');
        $this->securityCaptchaComments = SecuritySettings::captchaEnabled('comments');

        // درایور هر نقطه
        $this->captchaDriverAdminLogin = SecuritySettings::captchaDriver('admin_login');
        $this->captchaDriverRegister = SecuritySettings::captchaDriver('register');
        $this->captchaDriverCustomerLogin = SecuritySettings::captchaDriver('customer_login');
        $this->captchaDriverSellerLogin = SecuritySettings::captchaDriver('seller_login');
        $this->captchaDriverComments = SecuritySettings::captchaDriver('comments');

        // کلیدهای کپچای ابری + پیچیدگی ریاضی
        $this->captchaHcaptchaSiteKey = SecuritySettings::hcaptchaSiteKey();
        $this->captchaHcaptchaSecret = SecuritySettings::hcaptchaSecret();
        $this->captchaTurnstileSiteKey = SecuritySettings::turnstileSiteKey();
        $this->captchaTurnstileSecret = SecuritySettings::turnstileSecret();
        $this->captchaMathOperands = SecuritySettings::mathOperands();
        $this->securityTimeTrapSeconds = SecuritySettings::timeTrapSeconds();
        $this->securityHoneypotEnabled = SecuritySettings::honeypotEnabled();
        $this->securityLockoutEnabled = SecuritySettings::lockoutEnabled();
        $this->securityLockoutAdminMax = SecuritySettings::lockoutMax('admin');
        $this->securityLockoutAdminMinutes = SecuritySettings::lockoutMinutes('admin');
        $this->securityLockoutCustomerMax = SecuritySettings::lockoutMax('customer');
        $this->securityLockoutCustomerMinutes = SecuritySettings::lockoutMinutes('customer');
        $this->securityLockoutCommentsMax = SecuritySettings::lockoutMax('comments');
        $this->securityLockoutCommentsMinutes = SecuritySettings::lockoutMinutes('comments');
        $this->securityHeadersEnabled = SecuritySettings::headersEnabled();
        $this->securityHstsEnabled = SecuritySettings::hstsEnabled();
        $this->securityBotTrapEnabled = SecuritySettings::botTrapEnabled();
        $this->adminTwoFactorEnabled = SecuritySettings::twoFactorEnabled();
        $this->adminTwoFactorChannel = SecuritySettings::twoFactorChannel();

        // بازارچه
        $this->marketEnabled = MarketplaceSettings::marketEnabled();
        $this->marketAllowInstall = MarketplaceSettings::allowInstall();
        $this->marketAllowUninstall = MarketplaceSettings::allowUninstall();
        $this->marketShowTemplates = MarketplaceSettings::showTemplates();
        $this->marketAutoCheckUpdates = MarketplaceSettings::autoCheckUpdates();
        $this->marketItemsPerPage = MarketplaceSettings::itemsPerPage();
        $this->marketNoticeText = MarketplaceSettings::noticeText();

        // چندفروشندگی
        $this->vendorEnabled = MultiVendorSettings::enabled();
        $this->vendorAutoSettlement = MultiVendorSettings::autoSettlement();
        $this->vendorSellerOverride = MultiVendorSettings::sellerOverrideEnabled();
        $this->vendorBulkSettlement = MultiVendorSettings::bulkSettlementEnabled();
        $this->vendorDashboardStats = MultiVendorSettings::dashboardStatsEnabled();
        $this->vendorSettlementStatement = MultiVendorSettings::settlementStatementEnabled();
        $this->vendorSellerAnalytics = MultiVendorSettings::analyticsEnabled();
        $this->vendorDemandSignals = MultiVendorSettings::demandSignalsEnabled();
        $this->vendorReviewsAccess = MultiVendorSettings::reviewsAccessEnabled();
        $this->vendorInventoryCenter = MultiVendorSettings::inventoryCenterEnabled();
        $this->vendorProductsExport = MultiVendorSettings::productsExportEnabled();
        $this->vendorLeaderboard = MultiVendorSettings::leaderboardEnabled();
        $this->vendorModernPanel = MultiVendorSettings::modernPanelEnabled();
        $this->vendorLandingPage = MultiVendorSettings::landingPageEnabled();
        $this->vendorKycMobileVerify = MultiVendorSettings::kycMobileVerifyEnabled();
        $this->vendorLoginCaptcha = option(MultiVendorSettings::KEY_LOGIN_CAPTCHA, '1') === '1';
        $this->adminIconPicker = option('admin_icon_picker', '1') === '1';
        $this->vendorFinnotechClientId = trim((string) option('finnotech_client_id', ''));
        $this->vendorFinnotechClientSecret = trim((string) option('finnotech_client_secret', ''));
        $this->vendorDefaultCommission = (string) (MultiVendorSettings::defaultCommission() ?? '');
        $this->vendorMinWithdraw = (string) MultiVendorSettings::minWithdrawAmount();

        // رلهٔ سراسری هوش مصنوعی
        $this->loadAiRelay();

        // کلید عمومی راستی‌آزمایی لایسنس
        $this->loadLicenseKeyStatus();
    }

    /* ================= رلهٔ سراسری هوش مصنوعی ================= */

    /** بارگذاری تنظیمات رله از options + پیش‌فرض‌های منطقی از config/ai-relay.php */
    protected function loadAiRelay(): void
    {
        $this->aiRelayAccessKey = trim((string) option(AiRelayController::OPT_ACCESS_KEY, ''));
        $this->aiRelayDomain = trim((string) option(AiRelayController::OPT_DOMAIN, ''));
        $this->aiRelayAllowedIps = (string) option(AiRelayController::OPT_ALLOWED_IPS, '');
        $this->aiRelayHeaders = (string) option(AiRelayController::OPT_HEADERS, '');

        // کلید پیشنهادی — فقط برای نمایش؛ با ذخیره ثبت می‌شود
        if ($this->aiRelayAccessKey === '') {
            $this->aiRelayAccessKey = 'rl-'.Str::random(40);
        }

        $saved = collect(AiRelayController::savedProviders())->keyBy('key');

        $this->aiRelayProviders = [];

        foreach (config('ai-relay.providers', []) as $key => $def) {
            $row = $saved->get($key, []);

            $this->aiRelayProviders[] = [
                'key' => $key,
                'title' => (string) $def['title'],
                'base' => (string) $def['base'],
                'auth' => (string) $def['auth'],
                'base_url' => (string) ($row['base_url'] ?? $def['base']),
                'api_key' => (string) ($row['api_key'] ?? ''),
                // دفاعی — headers همیشه رشتهٔ متنی است؛ اگر جایی آرایه ذخیره شده باشد، خالی در نظر گرفته می‌شود
                'headers' => is_array($row['headers'] ?? null) ? '' : (string) ($row['headers'] ?? ''),
                'enabled' => (bool) ($row['enabled'] ?? false),
                // آیا کلید API در تنظیمات پکیج هوش مصنوعی ذخیره شده؟ (برای پیش‌پرکردن)
                'package_key' => $this->aiPackageKeyFor($key),
            ];
        }
    }

    /** کلید API ذخیره‌شدهٔ سرویس در پکیج هوش مصنوعی — برای دکمهٔ «برداشت از پکیج» */
    protected function aiPackageKeyFor(string $key): string
    {
        try {
            $model = '\Modules\ArtificialIntelligence\Models\AiSetting';

            if (! class_exists($model)) {
                return '';
            }

            return trim((string) $model::get($key.'_api_key', ''));
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** کلید API یک سرویس را از پکیج هوش مصنوعی برمی‌دارد به فیلد رله */
    public function pullAiRelayKey(string $key): void
    {
        foreach ($this->aiRelayProviders as $i => $row) {
            if ($row['key'] === $key && ($row['package_key'] ?? '') !== '') {
                $this->aiRelayProviders[$i]['api_key'] = $row['package_key'];
                $this->dispatch('toast', message: 'کلید '.$row['title'].' از پکیج هوش مصنوعی برداشت شد — فراموش نکنید ذخیره کنید.', type: 'info');

                return;
            }
        }
    }

    /** تولید کلید دسترسی جدید */
    public function generateAiRelayKey(): void
    {
        $this->aiRelayAccessKey = 'rl-'.Str::random(40);
    }

    /** انتخاب/لغو همهٔ سرویس‌ها */
    public function toggleAllAiRelayProviders(bool $on): void
    {
        foreach ($this->aiRelayProviders as $i => $row) {
            $this->aiRelayProviders[$i]['enabled'] = $on;
        }
    }

    public function saveAiRelay(): void
    {
        $this->ensureCreator();

        $this->validate([
            'aiRelayAccessKey' => ['required', 'string', 'min:16', 'max:128'],
            'aiRelayDomain' => ['nullable', 'string', 'max:200', 'regex:/^https?:\/\/[a-zA-Z0-9.\-]+(:\d+)?$/u'],
            'aiRelayAllowedIps' => ['nullable', 'string', 'max:2000'],
            'aiRelayHeaders' => ['nullable', 'string', 'max:2000'],
            'aiRelayProviders.*.base_url' => ['nullable', 'string', 'max:300'],
            'aiRelayProviders.*.api_key' => ['nullable', 'string', 'max:300'],
        ], [
            'aiRelayAccessKey.required' => 'کلید دسترسی رله لازم است.',
            'aiRelayAccessKey.min' => 'کلید دسترسی رله باید حداقل ۱۶ نویسه باشد.',
            'aiRelayDomain.regex' => 'دامنهٔ رله باید یک آدرس معتبر باشد (مثل https://relay.example.com).',
        ]);

        option_update(AiRelayController::OPT_ACCESS_KEY, trim($this->aiRelayAccessKey));
        option_update(AiRelayController::OPT_DOMAIN, rtrim(trim($this->aiRelayDomain), '/'));
        option_update(AiRelayController::OPT_ALLOWED_IPS, (string) $this->aiRelayAllowedIps);
        option_update(AiRelayController::OPT_HEADERS, (string) $this->aiRelayHeaders);

        $list = [];

        foreach ($this->aiRelayProviders as $row) {
            $list[] = [
                'key' => (string) $row['key'],
                'base_url' => rtrim(trim((string) $row['base_url']), '/'),
                'api_key' => trim((string) $row['api_key']),
                'headers' => (string) $row['headers'],
                'enabled' => (bool) $row['enabled'],
            ];
        }

        option_update(AiRelayController::OPT_PROVIDERS, (string) json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // بازخوانی — تا وضعیت ذخیره‌شده و پیش‌نمایش آدرس‌ها تازه شود
        $this->loadAiRelay();
        $this->aiRelayAccessKey = trim($this->aiRelayAccessKey);

        $this->dispatch('toast', message: 'تنظیمات رلهٔ هوش مصنوعی ذخیره شد — حالا می‌توانید relay.php را دانلود کنید.', type: 'success');
    }

    /** شمار سرویس‌های فعال رله (برای نشان تب) */
    public function getAiRelayActiveCountProperty(): int
    {
        return count(array_filter($this->aiRelayProviders, fn (array $row) => $row['enabled']));
    }


    /* ================= گیت ================= */

    private function ensureCreator(): void
    {
        $user = auth('adminPanel')->user();

        abort_unless($user && $user->level === 'creator', 403);
    }

    /* ================= کلید عمومی راستی‌آزمایی لایسنس ================= */

    /** بارگذاری وضعیت منابع کلید عمومی از LicenseEnvelopeVerifier (منبع حقیقت) */
    protected function loadLicenseKeyStatus(): void
    {
        $verifier = new LicenseEnvelopeVerifier;

        $this->licensePublicKey = $verifier->settingsPublicKey();
        $this->licenseKeySources = $verifier->candidateSources();
        $this->licenseTrustedKeyCount = $verifier->trustedKeyCount();
    }

    /**
     * ذخیرهٔ کلید عمومی راستی‌آزمایی لایسنس در تنظیمات پنل (دیتابیس — نه .env).
     *
     * سه فرمت پذیرفته است: خطِ کاملِ «PACKAGE_LICENSE_PUBLIC_KEY="..."» (کپی از صفحهٔ
     * پرداخت موفق یا پنل آپدیت‌کننده)، PEM چندخطی و Base64 خام؛ هر سه نرمال‌سازی و
     * با openssl اعتبارسنجی می‌شوند تا کلید خراب هرگز ذخیره نشود (fail-closed است —
     * کلید ناهم‌خوان یعنی بسته‌شدن پکیج‌ها).
     *
     * مزیت نسبت به .env: نوشتن در دیتابیس است، کش پیکربندی را نمی‌خواهد و بلافاصله
     * (همان درخواست بعدی) در راستی‌آزمایی امضاها استفاده می‌شود.
     */
    public function saveLicenseKey(): void
    {
        $this->ensureCreator();

        $value = trim((string) $this->licensePublicKey);

        // اگر مدیر «کل خطِ .env» را چسبانده باشد، پیشوند و کوتیشن‌ها جدا می‌شود
        if (preg_match('/^PACKAGE_LICENSE_PUBLIC_KEY\s*=\s*(.+)$/s', $value, $m)) {
            $value = trim($m[1]);
        }

        $value = trim($value, "\"'");

        if ($value !== '') {
            $verifier = new LicenseEnvelopeVerifier;

            if (! $verifier->isValidPublicKey($value)) {
                $this->addError('licensePublicKey', 'مقدار واردشده کلید عمومی معتبری نیست. خطِ تک‌خطی «PACKAGE_LICENSE_PUBLIC_KEY="..."» را از صفحهٔ نتیجهٔ پرداخت یا پنل آپدیت‌کننده کامل کپی کنید (کپیِ ناقص متن = شکست پکیج‌ها).');

                return;
            }
        }

        option_update('packages_license_public_key', $value);

        $this->licensePublicKey = $value;
        $this->loadLicenseKeyStatus();

        $this->dispatch('toast', message: $value === ''
            ? 'کلید عمومی از تنظیمات پنل پاک شد — راستی‌آزمایی به .env و کلید جاسازی‌شده در کد برمی‌گردد.'
            : 'کلید عمومی راستی‌آزمایی ذخیره شد و از همین لحظه در تأیید امضاهای سرور آپدیت‌کننده استفاده می‌شود.', type: 'success');
    }

    /* ================= ذخیره‌ها (پورت updateSettings) ================= */

    public function saveSharedHosting(): void
    {
        $this->ensureCreator();

        $cronEnabled = $this->sharedHostingCronEnabled ? 'on' : 'off';
        option_update('shared_hosting_cron_enabled', $cronEnabled);

        $this->dispatch('toast', message: 'تنظیمات صف (هاست اشتراکی) ذخیره شد.', type: 'success');
    }

    public function saveGeneral(): void
    {
        $this->ensureCreator();

        $this->validate([
            'appEnv' => ['required', 'in:production,staging,local'],
        ], [
            'appEnv.in' => 'محیط برنامه فقط می‌تواند production، staging یا local باشد.',
        ], [
            'appEnv' => 'محیط برنامه',
        ]);

        /*
         * همهٔ envها با cacheConfig=false نوشته می‌شوند و در پایان «یک‌بار»
         * کش کانفیگ بازسازی می‌شود. قبلاً هر change_env یک config:cache کامل (بوتِ یک
         * Application دوم) اجرا می‌کرد و در پایان هم config:clear کل کش را می‌کُشت —
         * یعنی پروداکشن بعد از «اولین ذخیرهٔ همین صفحه» تا ابد بدون کش کانفیگ و کند
         * کار می‌کرد (یکی از ریشه‌های پنهانِ افت سرعت پنل).
         */

        // APP_DEBUG در env
        change_env('APP_DEBUG', $this->appDebugMode ? 'true' : 'false', false);

        // محیط برنامه در env (تغییر از پنل، بدون SSH)
        change_env('APP_ENV', $this->appEnv, false);

        // کلید اصلی Pulse در env (در «سنجه‌های سیستمی» پنل لازم است)
        change_env('PULSE_ENABLED', $this->pulseEnabled ? 'true' : 'false', false);

        // enable_help_videos در option
        $helpVideos = $this->enableHelpVideos ? 'true' : 'false';
        option_update('enable_help_videos', $helpVideos);

        // توکن آپدیت‌کننده: option + env (پورت دقیق — هر دو)
        option_update('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', $this->updaterToken);
        change_env('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', $this->updaterToken, false);

        // DEBUGBAR_ENABLED در env
        $debugbar = $this->debugbarEnabled ? 'true' : 'false';
        change_env('DEBUGBAR_ENABLED', $debugbar, false);

        // enable_old_updates در option
        $oldUpdates = $this->enableOldUpdates ? 'true' : 'false';
        option_update('enable_old_updates', $oldUpdates);

        // سوییچ دسترسی مدیران به داشبورد Pulse (/pulse)
        PulseSettings::setAdminAccess($this->pulseAdminAccess);

        // بازسازی «یک‌بارهٔ» کش کانفیگ پس از همهٔ نوشتن‌های env:
        // production ⇒ کش بماند (سرعت)؛ بقیهٔ محیط‌ها ⇒ بدون کش تا تغییرها فوراً در dev دیده شود.
        // دفاعی: شکست بازسازی هرگز خودِ «ذخیره» را ۵۰۰ نکند.
        // بازسازی به «بعد از ارسال پاسخ» موکول شد (defer_config_rebuild):
        // Artisan::call('config:cache') وسط چرخهٔ Livewire یک Application دوم را بوت
        // و Facadeها را به اپی با .env تازه جابه‌جا می‌کرد ⇒ config('app.debug') بین دو
        // بررسیِ متوالی لایووایر برعکس می‌شد ⇒ «Undefined variable $start» و ۵۰۰ شدن
        // ذخیره (و در نتیجه نرسیدن هیچ توست موفق/ناموفق به مرورگر).
        defer_config_rebuild($this->appEnv);

        $this->dispatch('toast', message: 'تنظیمات توسعه‌دهنده ذخیره شد.', type: 'success');
    }

    /* ================= امنیت ================= */

    public function saveSecurity(): void
    {
        $this->ensureCreator();

        $this->validate([
            'securityTimeTrapSeconds' => ['required', 'integer', 'between:0,300'],
            'securityLockoutAdminMax' => ['required', 'integer', 'between:1,100'],
            'securityLockoutAdminMinutes' => ['required', 'integer', 'between:1,1440'],
            'securityLockoutCustomerMax' => ['required', 'integer', 'between:1,100'],
            'securityLockoutCustomerMinutes' => ['required', 'integer', 'between:1,1440'],
            'securityLockoutCommentsMax' => ['required', 'integer', 'between:1,100'],
            'securityLockoutCommentsMinutes' => ['required', 'integer', 'between:1,1440'],
            'captchaMathOperands' => ['required', 'integer', 'between:2,4'],
            'captchaDriverAdminLogin'    => ['required', 'in:'.implode(',', \App\Services\Captcha\CaptchaManager::DRIVERS)],
            'captchaDriverRegister'      => ['required', 'in:'.implode(',', \App\Services\Captcha\CaptchaManager::DRIVERS)],
            'captchaDriverCustomerLogin' => ['required', 'in:'.implode(',', \App\Services\Captcha\CaptchaManager::DRIVERS)],
            'captchaDriverSellerLogin'   => ['required', 'in:'.implode(',', \App\Services\Captcha\CaptchaManager::DRIVERS)],
            'captchaDriverComments'      => ['required', 'in:'.implode(',', \App\Services\Captcha\CaptchaManager::DRIVERS)],
            'adminTwoFactorChannel' => ['required', 'in:email,sms,both'],
        ], [
            'securityTimeTrapSeconds.between' => 'تلهٔ زمان باید بین ۰ تا ۳۰۰ ثانیه باشد.',
            'securityLockoutAdminMax.between' => 'سقف تلاش مدیر باید بین ۱ تا ۱۰۰ باشد.',
            'securityLockoutAdminMinutes.between' => 'پنجرهٔ قفل مدیر باید بین ۱ تا ۱۴۴۰ دقیقه باشد.',
            'securityLockoutCustomerMax.between' => 'سقف تلاش مشتری باید بین ۱ تا ۱۰۰ باشد.',
            'securityLockoutCustomerMinutes.between' => 'پنجرهٔ قفل مشتری باید بین ۱ تا ۱۴۴۰ دقیقه باشد.',
            'securityLockoutCommentsMax.between' => 'سقف تلاش دیدگاه‌ها باید بین ۱ تا ۱۰۰ باشد.',
            'securityLockoutCommentsMinutes.between' => 'پنجرهٔ قفل دیدگاه‌ها باید بین ۱ تا ۱۴۴۰ دقیقه باشد.',
            'adminTwoFactorChannel.in' => 'کانال ارسال کد نامعتبر است.',
        ]);

        // کپچا
        option_update('security_captcha_enabled', $this->securityCaptchaEnabled ? 'on' : 'off');
        option_update('security_captcha_admin_login', $this->securityCaptchaAdminLogin ? 'on' : 'off');
        option_update('security_captcha_register', $this->securityCaptchaRegister ? 'on' : 'off');
        option_update('security_captcha_customer_login', $this->securityCaptchaCustomerLogin ? 'on' : 'off');
        option_update('security_captcha_seller_login', $this->securityCaptchaSellerLogin ? 'on' : 'off');
        option_update('security_captcha_comments', $this->securityCaptchaComments ? 'on' : 'off');
        option_update('security_time_trap_seconds', (string) $this->securityTimeTrapSeconds);

        // درایور هر نقطهٔ کپچا — هر نقطه فقط یک درایور
        option_update('security_captcha_driver_admin_login', $this->captchaDriverAdminLogin);
        option_update('security_captcha_driver_register', $this->captchaDriverRegister);
        option_update('security_captcha_driver_customer_login', $this->captchaDriverCustomerLogin);
        option_update('security_captcha_driver_seller_login', $this->captchaDriverSellerLogin);
        option_update('security_captcha_driver_comments', $this->captchaDriverComments);

        // کلیدهای کپچای ابری (hCaptcha / Cloudflare Turnstile)
        option_update('captcha_hcaptcha_site_key', trim($this->captchaHcaptchaSiteKey));
        option_update('captcha_hcaptcha_secret', trim($this->captchaHcaptchaSecret));
        option_update('captcha_turnstile_site_key', trim($this->captchaTurnstileSiteKey));
        option_update('captcha_turnstile_secret', trim($this->captchaTurnstileSecret));

        // پیچیدگی کپچای ریاضی
        option_update('captcha_math_operands', (string) min(4, max(2, $this->captchaMathOperands)));

        // هانی‌پات
        option_update('security_honeypot_enabled', $this->securityHoneypotEnabled ? 'on' : 'off');

        // قفل تلاش‌ها
        option_update('security_lockout_enabled', $this->securityLockoutEnabled ? 'on' : 'off');
        option_update('security_lockout_admin_max', (string) $this->securityLockoutAdminMax);
        option_update('security_lockout_admin_minutes', (string) $this->securityLockoutAdminMinutes);
        option_update('security_lockout_customer_max', (string) $this->securityLockoutCustomerMax);
        option_update('security_lockout_customer_minutes', (string) $this->securityLockoutCustomerMinutes);
        option_update('security_lockout_comments_max', (string) $this->securityLockoutCommentsMax);
        option_update('security_lockout_comments_minutes', (string) $this->securityLockoutCommentsMinutes);

        // هدرهای امنیتی
        option_update('security_headers_enabled', $this->securityHeadersEnabled ? 'on' : 'off');
        option_update('security_hsts_enabled', $this->securityHstsEnabled ? 'on' : 'off');

        // تلهٔ ربات — کلید اصلی مسدودسازی آی‌پی
        option_update('security_bot_trap_enabled', $this->securityBotTrapEnabled ? 'on' : 'off');

        // ورود دومرحله‌ای مدیر
        option_update('admin_2fa_enabled', $this->adminTwoFactorEnabled ? 'on' : 'off');
        option_update('admin_2fa_channel', $this->adminTwoFactorChannel);

        $this->dispatch('toast', message: 'تنظیمات امنیتی ذخیره شد.', type: 'success');
    }

    /* ================= بازارچه ================= */

    public function saveMarketplace(): void
    {
        $this->ensureCreator();

        $this->validate([
            'marketItemsPerPage' => ['required', 'integer', 'between:1,'.MarketplaceSettings::MAX_PER_PAGE],
            'marketNoticeText' => ['nullable', 'string', 'max:500'],
        ], [
            'marketItemsPerPage.between' => 'تعداد آیتم هر صفحه باید بین ۱ تا '.MarketplaceSettings::MAX_PER_PAGE.' باشد.',
            'marketNoticeText.max' => 'متن بنر بازارچه حداکثر ۵۰۰ کاراکتر است.',
        ]);

        // کلید اصلی — گیت منو + صفحات packages/*
        option_update(MarketplaceSettings::KEY_ENABLED, $this->marketEnabled ? '1' : '0');
        option_update('packages_market_allow_install', $this->marketAllowInstall ? '1' : '0');
        option_update('packages_market_allow_uninstall', $this->marketAllowUninstall ? '1' : '0');
        option_update('packages_market_show_templates', $this->marketShowTemplates ? '1' : '0');
        option_update('packages_market_auto_check_updates', $this->marketAutoCheckUpdates ? '1' : '0');
        option_update('packages_market_items_per_page', (string) $this->marketItemsPerPage);
        option_update('packages_market_notice_text', trim((string) $this->marketNoticeText));

        $this->dispatch('toast', message: 'تنظیمات بازارچه ذخیره شد.', type: 'success');
    }

    /* ================= چندفروشندگی ================= */

    public function saveMultiVendor(): void
    {
        $this->ensureCreator();

        $this->validate([
            'vendorDefaultCommission' => ['nullable', 'integer', 'between:0,100'],
            'vendorMinWithdraw' => ['nullable', 'integer', 'between:0,50000000'],
            'vendorFinnotechClientId' => ['nullable', 'string', 'max:191'],
            'vendorFinnotechClientSecret' => ['nullable', 'string', 'max:191'],
        ], [
            'vendorDefaultCommission.integer' => 'کمیسیون پیش‌فرض باید عدد باشد.',
            'vendorDefaultCommission.between' => 'کمیسیون پیش‌فرض باید بین ۰ تا ۱۰۰ درصد باشد.',
            'vendorMinWithdraw.integer' => 'حداقل مبلغ برداشت باید عدد باشد.',
            'vendorMinWithdraw.between' => 'حداقل مبلغ برداشت باید بین ۰ تا ۵۰٬۰۰۰٬۰۰۰ تومان باشد.',
        ]);

        // کلید اصلی — همان کلید قدیمی با قرارداد 'true'/'false'
        // (میدل‌ور Seller، سایدبار و Product.php با == 'true' می‌خوانند)
        MultiVendorSettings::setEnabled($this->vendorEnabled);

        option_update('multi_vendor_auto_settlement', $this->vendorAutoSettlement ? '1' : '0');
        option_update('multi_vendor_seller_commission_override', $this->vendorSellerOverride ? '1' : '0');
        option_update('multi_vendor_bulk_settlement', $this->vendorBulkSettlement ? '1' : '0');
        option_update('multi_vendor_dashboard_stats', $this->vendorDashboardStats ? '1' : '0');
        option_update('multi_vendor_settlement_statement', $this->vendorSettlementStatement ? '1' : '0');
        option_update('seller_analytics_enabled', $this->vendorSellerAnalytics ? '1' : '0');
        option_update('seller_demand_signals', $this->vendorDemandSignals ? '1' : '0');
        option_update('seller_reviews_access', $this->vendorReviewsAccess ? '1' : '0');
        option_update('seller_inventory_center', $this->vendorInventoryCenter ? '1' : '0');
        option_update('seller_products_export', $this->vendorProductsExport ? '1' : '0');
        option_update('multi_vendor_leaderboard', $this->vendorLeaderboard ? '1' : '0');
        option_update(MultiVendorSettings::KEY_MODERN_PANEL, $this->vendorModernPanel ? '1' : '0');
        option_update(MultiVendorSettings::KEY_LANDING_PAGE, $this->vendorLandingPage ? '1' : '0');
        option_update(MultiVendorSettings::KEY_KYC_MOBILE, $this->vendorKycMobileVerify ? '1' : '0');
        // ربات‌گیر ورود فروشنده با رمز عبور
        option_update(MultiVendorSettings::KEY_LOGIN_CAPTCHA, $this->vendorLoginCaptcha ? '1' : '0');
        // انتخابگر آیکون پنل‌ها
        option_update('admin_icon_picker', $this->adminIconPicker ? '1' : '0');
        option_update('finnotech_client_id', trim((string) $this->vendorFinnotechClientId));
        option_update('finnotech_client_secret', trim((string) $this->vendorFinnotechClientSecret));
        option_update('multi_vendor_default_commission', trim((string) $this->vendorDefaultCommission) === '' ? '' : (string) max(0, min(100, (int) $this->vendorDefaultCommission)));
        option_update('multi_vendor_withdraw_min_amount', (string) max(0, (int) $this->vendorMinWithdraw));

        MultiVendorSettings::flushCache();

        $this->dispatch('toast', message: 'تنظیمات چندفروشندگی ذخیره شد.', type: 'success');
    }

    /* ================= تلهٔ ربات ================= */

    /**
     * لیست تله‌های فعال (۲۴ ساعت اخیر) برای نمایش در تنظیمات برنامه‌نویس.
     * حذف دستی رکورد از دیتابیس تا ۱۰ دقیقه اثر ندارد (کش)؛ رفع مسدودی از همین‌جا کش را هم پاک می‌کند.
     */
    public function getActiveTrapsProperty(): array
    {
        try {
            return BotTrap::query()
                ->where('last_hit_at', '>=', now()->subHours(BotTrap::BLOCK_TTL_HOURS))
                ->orderByDesc('last_hit_at')
                ->limit(50)
                ->get(['id', 'ip_address', 'trap_path', 'hit_count', 'last_hit_at'])
                ->toArray();
        } catch (\Throwable) {
            return []; // قبل از نصب یا هر خطای دیتابیس — صفحه نباید بشکند
        }
    }

    /** رفع مسدودی یک آی‌پی — حذف رکورد + پاک‌کردن کش (فوراً اثر می‌کند) */
    public function unblockIp(int $trapId): void
    {
        $this->ensureCreator();

        $trap = BotTrap::query()->find($trapId);

        if ($trap) {
            $ip = $trap->ip_address;
            $trap->delete();
            BotTrap::flushCache();

            $this->dispatch('toast', message: 'مسدودی آی‌پی '.$ip.' برداشته شد (کش هم پاک شد) — فوراً اثر می‌کند.', type: 'success');
        }
    }

    /** رفع مسدودی همهٔ آی‌پی‌ها — پاک‌سازی کامل تله‌ها + کش */
    public function unblockAllIps(): void
    {
        $this->ensureCreator();

        $count = BotTrap::query()->count();
        BotTrap::query()->delete();
        BotTrap::flushCache();

        $this->dispatch('toast', message: 'همهٔ مسدودی‌ها برداشته شد ('.$count.' رکورد) — کش هم پاک شد.', type: 'success');
    }

    /* ================= حالت بروزرسانی (پورت downApplication/upApplication) ================= */

    public function downApplication(): void
    {
        $this->ensureCreator();

        $this->validate([
            'maintenanceSecret' => 'required|string',
        ], [
            'maintenanceSecret.required' => 'فیلد Secret الزامی است.',
        ], [
            'maintenanceSecret' => 'secret',
        ]);

        option_update('info_maintenance_mode_title', $this->info_maintenance_mode_title);
        option_update('info_maintenance_mode_description', $this->info_maintenance_mode_description);

        $secret = $this->maintenanceSecret;
        Artisan::call("down --render='errors::503' --secret='{$secret}'");

        $this->dispatch('toast', message: 'حالت بروزرسانی فعال شد. ورود از طریق آدرس مخفی: /'.$secret, type: 'warning');
    }

    public function upApplication(): void
    {
        $this->ensureCreator();

        Artisan::call('up');

        $this->dispatch('toast', message: 'حالت بروزرسانی غیرفعال شد.', type: 'success');
    }

    /* ================= وب‌پوش (پورت webpushNotification) ================= */

    public function webpushNotification(): void
    {
        $this->ensureCreator();

        Artisan::call('webpush:vapid');

        $this->dispatch('toast', message: 'کلیدهای وب پوش تنظیم شدند.', type: 'success');
    }

    /* ================= مایگریشن‌های جدید ================= */

    /**
     * فایل‌های مایگریشن موجود در database/migrations که در جدول migrations ثبت
     * نشده‌اند. مبنای تشخیص جدول migrations است و برای دقت بیشتر، مایگریشن‌های
     * «ساخت جدول» که جدولشان از قبل وجود دارد (رکورد جاافتاده ولی ساختار موجود)
     * کنار گذاشته می‌شوند تا اجرای مجدد به خطای «جدول موجود» نخورد.
     */
    private function detectPendingMigrations(): array
    {
        // نصب تازه — جدول migrations هنوز ساخته نشده؛ مایگریشن‌ها در نصب اجرا می‌شوند
        if (! Schema::hasTable('migrations')) {
            return [];
        }

        $ran = DB::table('migrations')->pluck('migration')->all();

        $pending = [];

        foreach (glob(database_path('migrations/*.php')) ?: [] as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $ran, true)) {
                continue;
            }

            $content = (string) File::get($file);

            preg_match_all("/Schema::create\\(\\s*['\"]([^'\"]+)['\"]/", $content, $creates);
            $creates = array_values(array_unique($creates[1] ?? []));

            // فقط جدول‌هایی که واقعاً در دیتابیس نیستند
            $missingTables = array_values(array_filter($creates, fn ($table) => ! Schema::hasTable($table)));

            // مایگریشنِ ساختِ جدول که همهٔ جدول‌هایش موجود است ⇒ عملاً اجرا شده
            // ولی رکوردش ثبت نشده؛ اجرای مجددش فقط خطا می‌دهد → نمایش نده
            if ($creates !== [] && $missingTables === []) {
                continue;
            }

            preg_match_all("/Schema::table\\(\\s*['\"]([^'\"]+)['\"]/", $content, $alters);
            $alters = array_values(array_unique($alters[1] ?? []));

            $pending[] = [
                'migration' => $name,
                'creates' => $missingTables,
                'alters' => $alters,
            ];
        }

        return $pending;
    }

    /** ایجاد جداول/فیلدهای جدید — اجرای مایگریشن‌های معلق */
    public function runPendingMigrations(): void
    {
        $this->ensureCreator();

        try {
            Artisan::call('migrate', ['--force' => true]);

            $this->pendingMigrations = $this->detectPendingMigrations();

            $this->dispatch('toast', message: 'جداول و فیلدهای جدید با موفقیت ایجاد شدند.', type: 'success');
        } catch (\Throwable $exception) {
            report($exception);

            $this->dispatch('toast', message: 'اجرای مایگریشن‌ها ناموفق بود: '.Str::limit($exception->getMessage(), 140), type: 'error');
        }
    }

    /* ================= پاک‌کردن کش (پورت clearCache) ================= */

    public function clearCache(): void
    {
        $this->ensureCreator();

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            $this->dispatch('toast', message: 'کش برنامه با موفقیت پاک‌سازی شد.', type: 'success');
        } catch (\Throwable $exception) {
            report($exception);

            $this->dispatch('toast', message: 'پاک‌سازی کش ناموفق بود.', type: 'error');
        }
    }

    /* ================= آپدیت‌کننده (پورت updater) ================= */

    protected function updateCode(): ?string
    {
        return env('SELF_UPDATER_HTTP_PRIVATE_ACCESS_TOKEN', config('self-update.updater_token'));
    }

    /** بررسی نسخه جدید (پورت checkUpdate + بخش showUpdater) */
    public function checkUpdate(): void
    {
        $this->ensureCreator();

        $this->checking = true;

        $currentVersion = $this->getVersion();

        try {
            $response = Http::timeout(30)->get($this->panelUrl, [
                'token' => $this->updateCode(),
                'version' => $currentVersion,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $this->isNewVersionAvailable = (bool) ($data['update_available'] ?? false);
                $this->versionAvailable = (string) ($data['version'] ?? $currentVersion);
                $this->updateDescription = $data['description'] ?? null;
                $this->updateErrors = isset($data['error']) ? (string) $data['error'] : null;
            } else {
                $this->updateErrors = 'خطا در ارتباط با سرور بروزرسانی (کد '.$response->status().')';
            }
        } catch (\Throwable $e) {
            // پورت رفتار قدیمی: خطای اتصال با ترجمه/پیام نمایش داده می‌شود
            $message = $e->getMessage();
            $translated = __('errors.'.$message);

            $this->updateErrors = 'خطا رخ داده: '.(
                $translated != 'errors.'.$message ? $translated : $message
            );
            $this->isNewVersionAvailable = false;
        } finally {
            $this->checking = false;
        }
    }

    /** شروع بروزرسانی (پورت updateApplication — قفل اتمیک + صف) */
    public function updateApplication(): void
    {
        $this->ensureCreator();

        $lock = Cache::lock('update_in_progress', 1200); // 20 دقیقه

        if (! $lock->get()) {
            $this->dispatch('toast', message: 'آپدیت دیگری در حال اجراست. لطفاً صبر کنید.', type: 'error');

            return;
        }

        try {
            $currentVersion = $this->getVersion();

            if (Cache::get('update_status') === 'success') {
                $this->dispatch('toast', message: 'آپدیت قبلاً با موفقیت انجام شده است.', type: 'error');

                return;
            }

            $response = Http::timeout(30)->get($this->panelUrl, [
                'token' => $this->updateCode(),
                'version' => $currentVersion,
            ]);

            if (! $response->successful() || ! $response->json('update_available')) {
                $this->dispatch('toast', message: 'نسخه جدیدی یافت نشد یا دسترسی غیرمجاز است.', type: 'error');

                return;
            }

            $newVersion = (string) $response->json('version');

            Cache::put('update_processing', true, now()->addHours(2));
            Cache::put('update_status', 'queued', now()->addHours(2));
            Cache::put('update_progress', 0, now()->addHours(2));
            Cache::put('update_step', 'در حال قرار گرفتن در صف...', now()->addHours(2));
            Cache::put('update_version', $newVersion, now()->addHours(2));
            Cache::forget('update_error');
            Cache::forget('update_error_details');

            dispatch(new ProcessUpdateJob($this->panelUrl, $this->updateCode(), $currentVersion));

            $lock->release();

            $this->dispatch('toast', message: 'درخواست بروزرسانی در صف قرار گرفت.', type: 'success');
        } catch (\Throwable $e) {
            $lock->release();
            $this->dispatch('toast', message: 'خطا در ارسال درخواست: '.$e->getMessage(), type: 'error');
        }
    }

    /** وضعیت لحظه‌ای بروزرسانی برای wire:poll (پورت updateStatus) */
    public function getUpdateStatusProperty(): array
    {
        $isProcessing = Cache::get('update_processing', false);
        $progress = Cache::get('update_progress', 0);
        $status = Cache::get('update_status');
        $error = Cache::get('update_error');
        $version = Cache::get('update_version');
        $step = Cache::get('update_step');
        $jobId = Cache::get('update_job_id');

        if ($status === 'success') {
            return [
                'status' => 'success',
                'version' => $version,
                'message' => 'بروزرسانی با موفقیت انجام شد',
                'step' => 'کامل شد',
                'progress' => 100,
            ];
        }

        if ($status === 'error') {
            return [
                'status' => 'error',
                'message' => $error ?? 'خطای ناشناخته',
                'details' => Cache::get('update_error_details'),
                'step' => 'خطا',
                'progress' => $progress,
            ];
        }

        if ($isProcessing) {
            return [
                'status' => 'processing',
                'progress' => $progress,
                'message' => $step ?? 'در حال بروزرسانی...',
                'step' => $step ?? 'در حال بروزرسانی...',
                'job_id' => $jobId,
            ];
        }

        if (Cache::get('update_queued', false)) {
            return [
                'status' => 'waiting',
                'message' => 'در انتظار شروع بروزرسانی...',
                'step' => 'در انتظار شروع',
                'progress' => 0,
                'job_id' => $jobId,
            ];
        }

        return ['status' => 'idle'];
    }

    /** دستورات پس از بروزرسانی (پورت updaterAfter) */
    public function updaterAfter(): void
    {
        $this->ensureCreator();

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');

            $this->dispatch('toast', message: 'دستورات پس از بروزرسانی با موفقیت اجرا شدند.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: 'خطا در اجرای دستورات: '.$e->getMessage(), type: 'error');
        }
    }

    /** ریست وضعیت بروزرسانی (پورت resetUpdate) */
    public function resetUpdate(): void
    {
        $this->ensureCreator();

        foreach ([
            'update_processing', 'update_queued', 'update_job_dispatched', 'update_status',
            'update_progress', 'update_step', 'update_version', 'update_error',
            'update_error_details', 'update_job_id',
        ] as $key) {
            Cache::forget($key);
        }

        $this->dispatch('toast', message: 'وضعیت بروزرسانی با موفقیت ریست شد.', type: 'success');
    }

    /* ================= متدهای کمکی (پورت) ================= */

    private function getVersion(): string
    {
        $versionFile = storage_path('app/version.json');
        if (File::exists($versionFile)) {
            $content = json_decode(File::get($versionFile), true);

            return $content['version'] ?? config('app.version', '1.0.0');
        }

        return config('app.version', '1.0.0');
    }

    /** آیا سایت در حالت بروزرسانی (down) است؟ */
    public function getMaintenanceIsDownProperty(): bool
    {
        return is_file(storage_path('framework/down'));
    }

    public function render()
    {
        return view('admin-panel::livewire.admin.system.developer')
            ->layout('admin-panel::layout');
    }
}
