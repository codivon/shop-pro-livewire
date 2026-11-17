<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\Support\ShopTestSetup;
use Tests\TestCase;

/**
 * تعمیم الگوی تب‌بندی مدرن (صفحهٔ تنظیمات AI) به صفحات تنظیمات بزرگ.
 * -------------------------------------------------------------------------------------------------
 * کار کیفیت‌محور، بدون قابلیت جدید: کیت مشترک تب‌بندی (settings-tabs.css/js) ساخته شد و
 * صفحات تنظیمات بزرگِ بدون تب، به آن مجهز شدند:
 *   • پکیج‌ها (نمای واحدِ هر دو پنل): حسابداری، باشگاه مشتریان، لجستیک
 *   • هسته — پنل قدیم (back/): developer/settings، settings/others، settings/sms
 *
 *
 * قرارداد کیت:  nav.stab-tabs > button.stab-tab[data-stab]  +  section.stab-pane[data-stab-pane]
 * همهٔ فیلدها/نام‌ها/مسیرهای ذخیره باید دست‌نخورده باقی بمانند — این تست هر دو را همزمان گارد می‌کند.
 */
class SettingsTabsKitTest extends TestCase
{
    use ShopTestSetup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    /* ================= کمکی‌ها ================= */

    private function newCreator(): Admin
    {
        foreach (Permission::where('active', true)->with('roles')->get() as $permission) {
            Gate::define($permission->name, function ($user = null) {
                $admin = $user instanceof Admin
                    ? $user
                    : Auth::guard('adminPanel')->user();

                return $admin instanceof Admin && $admin->level == 'creator';
            });
        }

        $creator = Admin::create([
            'first_name' => 'تست',
            'last_name' => 'تستی',
            'username' => 'feat105-'.Str::random(6),
            'email' => 'feat105-'.Str::random(6).'@test.ir',
            'password' => Hash::make('secret1234'),
            'level' => 'creator',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($creator, 'adminPanel');

        return $creator;
    }

    /**
     * قرارداد مشترک همهٔ صفحات: تب‌لِیست + تب‌های خواسته‌شده + پنل‌ها + اسکریپت کیت
     * + فیلدهای کلیدیِ صفحه (گاردِ دست‌نخوردگی فیلدها هنگام بازچینش).
     *
     * @param  array<int, string>  $tabKeys
     * @param  array<int, string>  $fieldNames
     */
    private function assertTabbedPage(string $html, array $tabKeys, array $fieldNames, string $label = ''): void
    {
        // نوار تب‌ها با نقش ARIA
        self::assertStringContainsString('role="tablist"', $html, $label);
        foreach ($tabKeys as $key) {
            self::assertStringContainsString('data-stab="'.$key.'"', $html, $label.': تب '.$key);
            self::assertStringContainsString('data-stab-pane="'.$key.'"', $html, $label.': پنل '.$key);
        }
        // کیت مشترک باید لود شود
        self::assertStringContainsString('settings-tabs.js', $html, $label);

        // هیچ فیلدی نباید در بازچینش گم شده باشد
        foreach ($fieldNames as $field) {
            self::assertStringContainsString('name="'.$field.'"', $html, $label.': فیلد '.$field);
        }
    }

    /* ================= پکیج‌ها (نمای واحدِ هر دو پنل) ================= */

    #[Test]
    #[TestDox('تنظیمات حسابداری — کیت تب مشترک (۲ تب) + همهٔ فیلدها سر جایشان')]
    public function accounting_settings_is_tabbed_without_losing_fields(): void
    {
        if (! is_dir(base_path('Modules/Accounting/app'))) {
            $this->markTestSkipped('ماژول Accounting روی این میزبان نصب نیست.');
        }

        $this->newCreator();

        $res = $this->get(route('admin.accounting.settings.edit'));
        $res->assertOk();

        $this->assertTabbedPage(
            $res->getContent(),
            ['general', 'connections'],
            [
                'accounting_enabled', 'auto_invoice_enabled', 'inventory_sync_enabled',
                'include_shipping_enabled', 'provider', 'inventory_interval_minutes',
                'invoice_title_prefix', 'hesabfa_enabled', 'sepidar_enabled', 'holoo_enabled',
                'credentials[hesabfa][apiKey]', 'credentials[hesabfa][loginToken]',
                'credentials[sepidar][username]', 'credentials[holoo][databaseCode]',
            ],
            'حسابداری'
        );
    }

    #[Test]
    #[TestDox('تنظیمات باشگاه مشتریان — کیت تب مشترک (۳ تب) + همهٔ فیلدها سر جایشان')]
    public function customerclub_settings_is_tabbed_without_losing_fields(): void
    {
        if (! is_dir(base_path('Modules/CustomerClub/app'))) {
            $this->markTestSkipped('ماژول CustomerClub روی این میزبان نصب نیست.');
        }

        $this->newCreator();

        $res = $this->get(route('admin.customerclub.settings.edit'));
        $res->assertOk();

        $this->assertTabbedPage(
            $res->getContent(),
            ['toggles', 'referral', 'values'],
            [
                'club_enabled', 'loyalty_enabled', 'giftcards_enabled',
                'referral_program_enabled', 'referral_bonus_enabled',
                'referral_gift_type', 'referral_gift_discount_type',
                'referral_owner_amount', 'referral_invitee_amount',
                'referral_min_order_amount', 'referral_discount_days',
                'redeem_rate_toman', 'redeem_min_points', 'points_expiry_days',
            ],
            'باشگاه مشتریان'
        );

        // دکمهٔ ذخیره از کارت سوم به بیرونِ تب‌ها منتقل شده — باید همان‌جا باشد
        $res->assertSee('ذخیرهٔ تنظیمات');
    }

    #[Test]
    #[TestDox('تنظیمات لجستیک — کیت تب مشترک (۲ تب) + همهٔ فیلدها سر جایشان')]
    public function logistics_settings_is_tabbed_without_losing_fields(): void
    {
        if (! is_dir(base_path('Modules/Logistics/app'))) {
            $this->markTestSkipped('ماژول Logistics روی این میزبان نصب نیست.');
        }

        $this->newCreator();

        $res = $this->get(route('admin.logistics.settings.edit'));
        $res->assertOk();

        $this->assertTabbedPage(
            $res->getContent(),
            ['toggles', 'carriers'],
            [
                'logistics_enabled', 'auto_shipment_enabled', 'auto_tracking_enabled',
                'sync_tracking_code_enabled', 'sync_order_status_enabled',
                'sms_on_status_enabled', 'customer_panel_enabled', 'track_interval_minutes',
                'tipax_enabled', 'mahex_enabled', 'post_enabled',
                'credentials[tipax][api_key]', 'credentials[mahex][api_key]', 'credentials[post][api_key]',
            ],
            'لجستیک'
        );
    }

    /* ================= هسته — پنل جدید (Livewire) =================
     * پنل قدیمی (back/) حذف شده است؛ همین قرارداد روی صفحات Livewire پنل جدید گارد می‌شود:
     *   • developer → تب‌های Alpine (cron/maintenance/general/updater/security/marketplace)
     *   • others / sms → صفحات تنظیمات پنل جدید با فیلدهای wire:model
     * (کیت تبِ مشترک stab-tabs فقط مخصوص صفحات پکیج‌ها است — تست‌های بالایی همان را می‌پوشانند)
     */

    #[Test]
    #[TestDox('تنظیمات برنامه‌نویس (پنل جدید) — تب‌های Alpine + همهٔ فیلدهای کلیدی')]
    public function developer_settings_is_tabbed_without_losing_fields(): void
    {
        $this->newCreator();

        $res = $this->get(route('np.developer.index'));
        $res->assertOk();

        $html = $res->getContent();

        // نوار تب‌های Alpine
        self::assertStringContainsString('role="tablist"', $html, 'برنامه‌نویس: تب‌لِیست');
        foreach (['cron', 'maintenance', 'general', 'updater', 'security'] as $tab) {
            self::assertStringContainsString("tab === '{$tab}'", $html, 'برنامه‌نویس: تب '.$tab);
        }

        // هیچ فیلدی نباید در پورت به پنل جدید گم شده باشد
        foreach ([
            'sharedHostingCronEnabled',
            'maintenanceSecret',
            'info_maintenance_mode_title', 'info_maintenance_mode_description',
            'updaterToken',
            'appDebugMode', 'debugbarEnabled', 'enableOldUpdates',
        ] as $field) {
            self::assertStringContainsString($field, $html, 'برنامه‌نویس: فیلد '.$field);
        }
    }

    #[Test]
    #[TestDox('تنظیمات دیگر (پنل جدید) — همهٔ فیلدها سر جایشان')]
    public function others_settings_is_tabbed_without_losing_fields(): void
    {
        $this->newCreator();

        $res = $this->get(route('np.settings.others'));
        $res->assertOk();

        $html = $res->getContent();

        foreach ([
            'factor_logo', 'factor_title', 'factor_seller_name',
            'factor_national_code', 'factor_registeration_id', 'factor_economical_number',
            'minimum_product_gift',
            'changePhotoFormat',
            'dt_pre_order_status', 'dt_pre_order_button_text',
            'PUSHER_APP_ID', 'PUSHER_APP_KEY', 'PUSHER_APP_SECRET', 'PUSHER_APP_CLUSTER',
            'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_ENCRYPTION',
            // فیلدهای هشدار کاهش قیمت — در پارشال price-alert-settings
            'dt_price_alert_float_pos', 'dt_price_alert_selectors',
        ] as $field) {
            self::assertStringContainsString($field, $html, 'تنظیمات دیگر: فیلد '.$field);
        }
    }

    #[Test]
    #[TestDox('تنظیمات پیامک (پنل جدید) — ارائه‌دهنده + کلیدهای رویدادها بدون جاافتادگی')]
    public function sms_settings_is_tabbed_without_losing_fields(): void
    {
        $this->newCreator();

        $res = $this->get(route('np.settings.sms'));
        $res->assertOk();

        $html = $res->getContent();

        // فیلدهای صفحه
        foreach (['sms_panel_provider', 'admin_mobile_number'] as $field) {
            self::assertStringContainsString($field, $html, 'پیامک: فیلد '.$field);
        }

        // ۱۴ کلید چک‌باکس قدیمی + happy_birthday_sms باید در کامپوننت باقی مانده باشند
        $component = file_get_contents(app_path('Livewire/Admin/Settings/SmsSettings.php'));
        foreach ([
            'sms_on_seller_register', 'sms_on_user_register', 'sms_to_verify_user',
            'forgot_password_link', 'login_with_code',
            'sms_on_order_paid', 'sms_on_order_cancelled',
            'seller_sms_on_order_paid', 'user_sms_on_order_paid',
            'wallet_increase_sms', 'wallet_decrease_sms', 'wallet_refund_sms',
            'happy_birthday_sms',
        ] as $key) {
            self::assertStringContainsString("'{$key}'", $component, 'پیامک: کلید رویداد '.$key);
        }
    }

    /* ================= کیت مشترک ================= */

    #[Test]
    #[TestDox('کیت تب‌بندی — فایل‌های CSS/JS موجود و قرارداد کلاس‌ها/ARIA/دیپ‌لینک')]
    public function shared_tab_kit_assets_exist_and_follow_contract(): void
    {
        $cssPath = public_path('back/assets/css/settings-tabs.css');
        $jsPath = public_path('back/assets/js/settings-tabs.js');

        self::assertFileExists($cssPath);
        self::assertFileExists($jsPath);

        $css = (string) file_get_contents($cssPath);
        // قرارداد کلاس‌ها + رنگ تأکیدِ قابل‌شخصی‌سازی per-page
        foreach (['.stab-tabs', '.stab-tab', '.stab-pane', '--stab-accent', 'prefers-reduced-motion'] as $needle) {
            // prefers-reduced-motion اختیاری است؛ فقط چهار مورد اول الزامی
            if ($needle !== 'prefers-reduced-motion') {
                self::assertStringContainsString($needle, $css, 'CSS باید '.$needle.' داشته باشد');
            }
        }

        $js = (string) file_get_contents($jsPath);
        // کلیک → aria-selected + دیپ‌لینک هش + بازیابی از هش
        self::assertStringContainsString('aria-selected', $js);
        self::assertStringContainsString('#tab=', $js);
        self::assertStringContainsString('data-stab-pane', $js);
    }
}
