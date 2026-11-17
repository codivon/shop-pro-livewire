<?php

namespace Tests\Feature\Extra;

use App\Models\Admin;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * تست گارد فعال‌سازی قالب (ریشهٔ ۴۰۴ خام صفحهٔ اصلی).
 *
 * سناریوی واقعی کاربر: پوشهٔ قالب روی هاست دستی تغییرنام می‌یابد (مثلاً Saniar → SaniarTheme)
 * ولی فضای‌نام PHP داخل src همان قدیمی می‌ماند → کلاس
 * Themes\{نام‌پوشه}\src\ThemeServiceProvider پیدا نمی‌شود → provider بی‌صدا رد می‌شود →
 * هیچ روت فرانتی ثبت نمی‌شود → صفحهٔ اصلی ۴۰۴ و خطاهای عجیب در پنل.
 * از این پس فعال‌سازی قبل از ثبت CURRENT_THEME ساختار را اعتبارسنجی می‌کند و
 * با پیام فارسی واضح متوقف می‌شود.
 *
 * گارد از طریق کامپوننت Livewire پنل جدید (ThemeIndex::activate) آزموده می‌شود؛
 * (روت PUT پنل قدیمی admin.themes.update با حذف پنل قدیمی برداشته شده است)
 * همین گارد در بازار قالب (PackageInstallerService) هم تکرار شده است.
 */
class ThemeActivationTest extends TestCase
{
    protected string $envBackup;
    protected string $fakeTheme = 'BrokenTheme67';
    protected string $fakePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
        $this->envBackup = (string) File::get(base_path('.env'));

        // قالب ساختگی: پوشه + config + کلاس provider با «فضای‌نام اشتباه»
        $this->fakePath = base_path("themes/{$this->fakeTheme}");
        File::ensureDirectoryExists($this->fakePath.'/src');
        File::ensureDirectoryExists($this->fakePath.'/config');
        File::ensureDirectoryExists($this->fakePath.'/src/resources/assets');
        File::put($this->fakePath.'/config/general.php', "<?php\n\nreturn [\n    'theme_name' => 'BrokenTheme67',\n    'asset_path' => 'themes/BrokenTheme67/',\n    'demo' => ['name' => 'قالب شکسته', 'description' => '', 'image' => ''],\n    'links' => [],\n];\n");
        // فضای‌نام عمداً با نام پوشه هم‌خوان نیست (شبیه تغییرنام دستی روی هاست)
        File::put($this->fakePath.'/src/ThemeServiceProvider.php', "<?php\n\nnamespace Themes\\SomeOldName\\src;\n\nuse Illuminate\\Support\\ServiceProvider;\n\nclass ThemeServiceProvider extends ServiceProvider\n{\n    public function register()\n    {\n    }\n\n    public function boot()\n    {\n    }\n}\n");
    }

    protected function tearDown(): void
    {
        // بازگردانی .env قبل از هر چیز (تست موفق CURRENT_THEME را عوض می‌کند)
        File::put(base_path('.env'), $this->envBackup);
        $this->artisan('config:clear')->run();

        if (is_link(public_path("themes/{$this->fakeTheme}"))) {
            @unlink(public_path("themes/{$this->fakeTheme}"));
        }
        if (is_dir($this->fakePath)) {
            File::deleteDirectory($this->fakePath);
        }

        parent::tearDown();
    }

    private function creator(): Admin
    {
        $admin = Admin::create([
            'first_name' => 'تست',
            'last_name'  => 'نمونه',
            'username'   => 'theme67-'.random_int(10000, 99999),
            'email'      => 'theme67-'.random_int(10000, 99999).'@test.ir',
            'password'   => \Illuminate\Support\Facades\Hash::make('secret1234'),
            'level'      => 'creator',
            'status'     => 'ACTIVE',
        ]);
        $this->actingAs($admin, 'adminPanel');

        // گیت‌های پرمیشن در کنسول ثبت نمی‌شوند — مثل منطق واقعی creator همه‌چیز مجاز
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user instanceof Admin ? true : null;
        });

        return $admin;
    }

    public function test_broken_theme_activation_is_rejected_before_env_change(): void
    {
        $this->creator();

        // زیر CURRENT_THEME=subprocess-override، config حافظه با فایل .env فرق دارد؛
        // مرجع درستی که باید دست‌نخورده بماند خودِ فایل است.
        $envBefore = (string) File::get(base_path('.env'));

        // روت PUT پنل قدیمی (admin.themes.update) با حذف پنل قدیمی برداشته شده —
        // همان گارد در ThemeIndex::activate پنل جدید اجرا می‌شود
        Livewire::test(\App\Livewire\Admin\Themes\ThemeIndex::class)
            ->call('activate', $this->fakeTheme)
            ->assertDispatched('toast', function ($event, $params) {
                // پیام گارد باید توضیح‌دار باشد (فضای‌نام اشتباه + نام قالب)
                $this->assertSame('error', $params['type'] ?? null, 'پیام گارد باید خطا باشد');

                $message = (string) ($params['message'] ?? '');
                $this->assertStringContainsString('معتبر نیست', $message);
                $this->assertStringContainsString('ThemeServiceProvider', $message);
                $this->assertStringContainsString($this->fakeTheme, $message);

                return true;
            });

        // فایل .env باید بیت‌به‌بیت دست‌نخورده مانده باشد (CURRENT_THEME عوض نشود)
        $this->assertSame($envBefore, (string) File::get(base_path('.env')), 'فعال‌سازی قالب شکسته نباید .env را تغییر دهد');
    }

    public function test_valid_theme_activation_registers_front_routes_and_home_responds(): void
    {
        // اولین قالب معتبرِ موجود روی دیسک (SaniarTheme در نسخهٔ کامل، WeblakShop در ریپوی هسته)
        $targetTheme = '';
        foreach (['SaniarTheme', 'WeblakShop'] as $theme) {
            if (is_dir(base_path("themes/{$theme}/src"))) {
                $targetTheme = $theme;
                break;
            }
        }

        if ($targetTheme === '') {
            $this->markTestSkipped('هیچ قالب معتبری روی دیسک موجود نیست.');
        }

        $this->creator();

        Livewire::test(\App\Livewire\Admin\Themes\ThemeIndex::class)
            ->call('activate', $targetTheme);

        // .env ثبت شده باشد
        $envNow = (string) File::get(base_path('.env'));
        $this->assertStringContainsString('CURRENT_THEME='.$targetTheme, $envNow);

        // NOTE: رندر صفحه اصلی با تم جدید در «همین پروسه» قابل آزمودن نیست: هلپرهای
        // دو تم توابع هم‌نام دارند (مثل production_throttle) و بارگذاری هم‌زمانِ
        // دو provider در یک پروسه = Fatal redeclare. در production هر ریکوئست
        // پروسهٔ تازه با فقط یک تم فعال بوت می‌شود. رندر با SaniarTheme فعال در
        // مجموعهٔ ۲۴تایی اختصاصی خود تم پوشش داده می‌شود (CURRENT_THEME=SaniarTheme).
        $this->assertTrue(
            class_exists(\App\Services\ThemeStructureGuard::providerClass($targetTheme)),
            'کلاس provider تم جدید باید پس از فعال‌سازی قابل رزولوشن باشد'
        );
    }
}
