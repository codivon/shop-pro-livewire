<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

/*
 * فاز ۱۸۶ — اجرای «پیش‌نیازهای اعلان‌شدهٔ خودِ تم» هنگام فعال‌سازی/نصب/اجزای دستی.
 *
 * مسئله: تم‌ها نیازهای متفاوتی دارند؛ تم SPA سانیار به Livewire 4 نیاز دارد،
 * بعضی تم‌ها باید npm build بگیرند، همهٔ تم‌ها کش‌ها و لینک دارایی‌ها می‌خواهند.
 * قبلاً فقط «لینک دارایی + پاک‌سازی کش» انجام می‌شد؛ نتیجه: روی فروشگاهی که
 * Livewire نداشت، تم سانیار بی‌صدا غیر SPA می‌ماند.
 *
 * راه‌حل (Theme-Driven): هر تم فایل config/setup.php خودش را می‌تواند داشته باشد
 * و نیازهایش را اعلان می‌کند. تمی که این فایل را ندارد، فقط گام‌های پیش‌فرض
 * (دارایی‌ها + کش) را می‌گیرد — رفتار تم‌های قدیمی مثل WeblakShop دست‌نخورده می‌ماند.
 *
 * قرارداد config/setup.php:
 *
 *   return [
 *       // برای نمایش بج در کارت تم (اختیاری)
 *       'summary' => ['livewire/livewire'],
 *
 *       // نصب پکیج‌های composer در صورت نبود (با probe برای تشخیص)
 *       'composer' => [
 *           'packages' => [
 *               ['package' => 'livewire/livewire', 'probe' => 'Livewire\Livewire', 'reason' => '...'],
 *           ],
 *       ],
 *
 *       // چک‌های سبک (بدون تلاش نصب)
 *       'checks' => [
 *           ['type' => 'extension', 'extension' => 'mbstring', 'label' => '...'],
 *           ['type' => 'class_exists', 'class' => '...', 'label' => '...', 'hint' => '...'],
 *       ],
 *
 *       // دستورات artisan (ترتیب حفظ می‌شود)
 *       'artisan' => ['view:clear' => 'پاک‌سازی کش ویوها', ...],
 *
 *       // انتشار دارایی‌ها با تگ provider تم
 *       'publish' => ['{نام‌تم}' => 'انتشار دارایی‌ها'],
 *
 *       // بیلد npm (فقط تم‌هایی که دارایی کامپایل می‌خواهند)
 *       'npm' => ['path' => 'src', 'script' => 'build', 'install' => 'ci'],
 *   ];
 *
 * همهٔ گام‌ها «غیرمسمّد» (non-blocking) هستند؛ خروجی هر گام گزارش می‌شود تا ادمین
 * در پنل نتیجهٔ هر مورد را ببیند. امنیت: نام تم قبل از فراخوانی توسط
 * is_safe_extension_name اعتبارسنجی می‌شود (مسیرها فقط basename را می‌پذیرند).
 */
class ThemeSetupRunner
{
    /** وضعیت‌های مجاز گام */
    public const STATUS_OK    = 'ok';
    public const STATUS_WARN  = 'warn';
    public const STATUS_FAIL  = 'fail';
    public const STATUS_SKIP  = 'skip';
    public const STATUS_INFO  = 'info';

    /** طول برش خروجی دستورات برای نمایش در گزارش */
    protected const OUTPUT_LIMIT = 700;

    /**
     * اجرای کامل آماده‌سازی یک تم.
     *
     * @param  string $theme   نام پوشهٔ تم (basename — بدون مسیر)
     * @param  string $context activate|install|manual (فقط برای پیام‌ها)
     * @return array<int, array{label: string, status: string, message: string}>
     */
    public static function run(string $theme, string $context = 'activate'): array
    {
        $theme = basename(trim($theme, '/\\'));

        if (! is_safe_extension_name($theme)) {
            return [['label' => 'اعتبارسنجی', 'status' => self::STATUS_FAIL, 'message' => 'نام قالب معتبر نیست.']];
        }

        $steps = [];

        $contextLabels = [
            'activate' => 'فعال‌سازی قالب',
            'install'  => 'نصب قالب',
            'manual'   => 'اجرای دستی آماده‌سازی',
        ];

        $steps[] = [
            'label'   => 'شروع',
            'status'  => self::STATUS_INFO,
            'message' => 'اجرای آماده‌سازی قالب «'.$theme.'» ('.($contextLabels[$context] ?? $context).')',
        ];

        /* ─── گام‌های پیش‌فرض هر تم (بدون نیاز به setup.php) ─── */

        self::stepAssets($theme, $steps);

        $config = self::config($theme);

        if (empty($config)) {
            // تم بدون setup.php → فقط کش‌ها و تمام (رفتار تم‌های قدیمی حفظ می‌شود)
            self::stepCacheClears($steps);
            $steps[] = [
                'label'   => 'پیش‌نیازهای اختصاصی',
                'status'  => self::STATUS_SKIP,
                'message' => 'این قالب فایل config/setup.php ندارد؛ گام اضافه‌ای لازم نیست.',
            ];

            return $steps;
        }

        /* ─── گام‌های اعلان‌شدهٔ خود تم ─── */

        // بیلد npm — قبل از composer تا اگر پکیج‌های npm و composer هر دو لازم بود، هر دو اجرا شوند
        if (! empty($config['npm']) && is_array($config['npm'])) {
            self::stepNpm($theme, $config['npm'], $steps);
        }

        // پکیج‌های composer (با probe و تلاش نصب خودکار)
        foreach ($config['composer']['packages'] ?? [] as $package) {
            self::stepComposerPackage($package, $steps);
        }

        // چک‌های سبک
        foreach ($config['checks'] ?? [] as $check) {
            self::stepCheck($check, $steps);
        }

        // دستورات artisan اعلان‌شده — هم آرایهٔ عددی ['view:clear'] و هم key => label پشتیبانی می‌شود
        foreach ($config['artisan'] ?? [] as $command => $label) {
            $isKeyValid = is_string($command) && preg_match('/^[a-z0-9\-:]+$/', $command) === 1;

            $command = $isKeyValid ? $command : (string) $label;
            $label   = is_string($label) ? $label : $command;

            self::stepArtisanCommand($command, $label, $steps);
        }

        // vendor:publish با تگ اعلان‌شده — هم عددی و هم key => label پشتیبانی می‌شود
        foreach ($config['publish'] ?? [] as $tag => $label) {
            $isKeyValid = is_string($tag) && preg_match('/^[A-Za-z0-9\-\._]+$/', $tag) === 1;

            $tag = $isKeyValid ? $tag : (string) $label;
            $label = is_string($label) ? $label : 'انتشار دارایی‌های «'.$tag.'»';

            self::stepPublish($tag, $label, $steps);
        }

        /* ─── پاک‌سازی کش‌ها در پایان (پس از هرگونه composer/npm/publish) ─── */

        self::stepCacheClears($steps);

        $steps[] = [
            'label'   => 'پایان',
            'status'  => self::STATUS_INFO,
            'message' => 'اگر پکیجی در این مرحله نصب شد، یک بار صفحهٔ پنل را رفرش کنید تا کامل اعمال شود.',
        ];

        return $steps;
    }

    /* ---------------- خواندن config/setup.php ---------------- */

    /** مسیر فایل setup تم */
    public static function configPath(string $theme): string
    {
        return base_path('themes/'.basename(trim($theme, '/\\')).'/config/setup.php');
    }

    /** آیا تم، setup.php دارد؟ */
    public static function hasConfig(string $theme): bool
    {
        return is_file(self::configPath($theme));
    }

    /** خواندن امن setup.php (فایل معیوب یا بدون return → آرایهٔ خالی) */
    public static function config(string $theme): array
    {
        try {
            $config = customConfig(self::configPath($theme));
        } catch (Throwable $e) {
            report($e);

            return [];
        }

        return is_array($config) ? $config : [];
    }

    /**
     * خلاصهٔ نیازهای تم برای نمایش بج در کارت تم.
     *
     * @return string[] مثل ['livewire/livewire']
     */
    public static function summary(string $theme): array
    {
        $config = self::config($theme);

        if (empty($config)) {
            return [];
        }

        $items = [];

        if (is_array($config['summary'] ?? null)) {
            foreach ($config['summary'] as $item) {
                if (is_string($item) && $item !== '') {
                    $items[] = $item;
                }
            }
        }

        foreach ($config['composer']['packages'] ?? [] as $package) {
            $name = is_array($package) ? ($package['package'] ?? null) : $package;

            if (is_string($name) && $name !== '' && ! in_array($name, $items)) {
                $items[] = $name;
            }
        }

        if (! empty($config['npm']) && ! in_array('npm build', $items)) {
            $items[] = 'npm build';
        }

        return $items;
    }

    /* ---------------- گام‌ها ---------------- */

    /**
     * لینک/کپی دارایی‌های تم (themes/{تم}/src/resources/assets → public/themes/{تم})
     * idempotent — زنجیرهٔ symlink→exec→کپی خود ThemeAssetLinker.
     */
    protected static function stepAssets(string $theme, array &$steps): void
    {
        $target = base_path("themes/{$theme}/src/resources/assets");
        $link   = public_path("themes/{$theme}");

        if (! is_dir($target)) {
            $steps[] = [
                'label'   => 'دارایی‌های تم',
                'status'  => self::STATUS_SKIP,
                'message' => 'پوشهٔ src/resources/assets در این قالب وجود ندارد.',
            ];

            return;
        }

        try {
            // فاز ۱۸۹ — تشخیص قبل از اجرا برای گزارش دقیق‌تر:
            // پوشهٔ public موجود ولی تهی = علت ۴۰۴ شدن دارایی‌ها؛ لینکر الان بازسازی می‌کند
            $wasEmptyPublicDir = is_dir($link) && ThemeAssetLinker::dirIsEmpty($link);

            $mode = ThemeAssetLinker::linkOrCopy($target, $link);

            $messages = [
                'symlink'      => 'لینک نمادین دارایی‌ها ساخته شد (public/themes/'.$theme.').',
                'exec'         => 'لینک نمادین با دستور سیستم ساخته شد (public/themes/'.$theme.').',
                'existing'     => 'لینک نمادین دارایی‌ها از قبل برقرار است.',
                'existing-dir' => 'دارایی‌ها قبلاً در public منتشر شده‌اند.',
                'copy'         => 'symlink روی این هاست در دسترس نبود؛ دارایی‌ها کپی شدند (public/themes/'.$theme.').',
            ];

            // فاز ۱۹۰ — پیام وضعیتِ «پوشهٔ خالی که بازسازی شد» به‌جای پیام گمراه‌کنندهٔ قبلی
            if ($wasEmptyPublicDir && in_array($mode, ['symlink', 'exec', 'copy'], true)) {
                $messages[$mode] .= ' (پوشهٔ قبلی خالی بود و بازسازی شد — ریشهٔ ۴۰۴ شدن دارایی‌ها)';
            } elseif ($mode === 'copy') {
                // فاز ۱۹۰ — شمار فایل‌های منتشرشده برای تشخیص سریع کپی خراب
                $copiedCount = 0;

                try {
                    foreach (File::allFiles($link) as $f) {
                        if ($f->getFilename() !== ThemeAssetLinker::COPY_MARKER) {
                            $copiedCount++;
                        }
                    }
                } catch (Throwable) {
                }

                $messages['copy'] = $copiedCount > 0
                    ? 'symlink روی این هاست در دسترس نبود؛ دارایی‌ها کپی شدند (public/themes/'.$theme.' — '.$copiedCount.' فایل).'
                    : 'symlink روی این هاست در دسترس نبود و کپی هم ناموفق بود (public/themes/'.$theme.' خالی ماند).';
            } elseif ($mode === 'existing-dir') {
                // تعداد فایل‌های موجود برای شفافیت ادمین
                $fileCount = 0;
                try {
                    foreach (File::allFiles($link) as $f) {
                        $fileCount++;
                    }
                } catch (Throwable) {
                }

                $messages['existing-dir'] = $fileCount > 0
                    ? "دارایی‌ها قبلاً در public منتشر شده‌اند ({$fileCount} فایل)."
                    : 'دارایی‌ها قبلاً در public منتشر شده‌اند.';
            }

            $steps[] = [
                'label'   => 'دارایی‌های تم',
                'status'  => $mode === 'copy' ? self::STATUS_WARN : self::STATUS_OK,
                'message' => $messages[$mode] ?? ('وضعیت دارایی‌ها: '.$mode),
            ];
        } catch (Throwable $e) {
            report($e);
            $steps[] = [
                'label'   => 'دارایی‌های تم',
                'status'  => self::STATUS_FAIL,
                'message' => 'خطا در لینک دارایی‌ها: '.$e->getMessage(),
            ];
        }
    }

    /**
     * اطمینان از نصب پکیج composer با probe (چک کلاس).
     * probe از قبل پاس شد → skip با پیام «از قبل نصب است».
     * نبود → تلاش composer require (در صورت در دسترس بودن composer)؛
     * در غیر این صورت warn با دستور دقیق برای ادمین.
     *
     * @param  array{package?: string, probe?: string, reason?: string}|string $package
     */
    protected static function stepComposerPackage(string|array $package, array &$steps): void
    {
        $packageName = is_array($package) ? ($package['package'] ?? null) : $package;
        $probe       = is_array($package) ? ($package['probe'] ?? null) : null;
        $reason      = is_array($package) ? ($package['reason'] ?? null) : null;

        if (! is_string($packageName) || $packageName === '' || str_contains($packageName, '..') || str_contains($packageName, ' ')) {
            $steps[] = [
                'label'   => 'پکیج composer',
                'status'  => self::STATUS_FAIL,
                'message' => 'نام پکیج نامعتبر است.',
            ];

            return;
        }

        $probeClass = is_string($probe) && $probe !== '' ? $probe : null;

        // تشخیص نصب‌بودن: probe کلاس، وگرنه چک vendor وگرنه چک composer.lock
        $alreadyInstalled = false;

        if ($probeClass !== null && class_exists($probeClass)) {
            $alreadyInstalled = true;
        } elseif ($probeClass === null) {
            $alreadyInstalled = self::isPackageInLock($packageName);
        }

        if ($alreadyInstalled) {
            $steps[] = [
                'label'   => $packageName,
                'status'  => self::STATUS_OK,
                'message' => 'از قبل نصب است.'.($reason !== null ? ' — '.$reason : ''),
            ];

            return;
        }

        // نصب خودکار
        $composer = self::findComposer();

        if ($composer === null) {
            $steps[] = [
                'label'   => $packageName,
                'status'  => self::STATUS_WARN,
                'message' => 'نیازمندی قالب: «'.$packageName.'» نصب نیست و composer روی هاست در دسترس نیست.'
                    .' لطفاً روی سرور اجرا کنید: composer require '.$packageName,
            ];

            return;
        }

        $output = self::runShellCommand(
            $composer.' require '.self::shellArg($packageName).' --no-interaction --no-progress 2>&1'
        );

        // پس از require، کش کشف پکیج‌ها نوسازی شود تا ServiceProvider پکیج جدید ثبت شود
        try {
            Artisan::call('package:discover');
        } catch (Throwable $e) {
            report($e);
        }

        $installedNow = $probeClass !== null
            ? false // کلاس تازه در همین پروسه با autoloader قدیمی رزول نمی‌شود؛ پیام زیر کافی است
            : self::isPackageInLock($packageName);

        if ($output['code'] === 0) {
            $steps[] = [
                'label'   => $packageName,
                'status'  => self::STATUS_OK,
                'message' => 'نصب شد'.($installedNow ? '' : ' (برای اعمال کامل، یک بار صفحهٔ پنل را رفرش کنید)').($reason !== null ? ' — '.$reason : ''),
            ];
        } else {
            $steps[] = [
                'label'   => $packageName,
                'status'  => self::STATUS_WARN,
                'message' => 'نصب خودکار «'.$packageName.'» ناموفق بود. روی سرور اجرا کنید: composer require '.$packageName
                    .' | خروجی: '.self::clip($output['output']),
            ];
        }
    }

    /** چک‌های سبک اعلان‌شدهٔ تم */
    protected static function stepCheck(array $check, array &$steps): void
    {
        $type  = $check['type'] ?? '';
        $label = $check['label'] ?? $type;
        $hint  = $check['hint'] ?? null;

        switch ($type) {
            case 'extension':
                $extension = (string) ($check['extension'] ?? '');

                if ($extension === '') {
                    return;
                }

                if (extension_loaded($extension)) {
                    $steps[] = ['label' => $label, 'status' => self::STATUS_OK, 'message' => 'اکستنشن «'.$extension.'» فعال است.'];
                } else {
                    $steps[] = [
                        'label'   => $label,
                        'status'  => self::STATUS_WARN,
                        'message' => 'اکستنشن PHP «'.$extension.'» فعال نیست؛ در پنل هاست فعال کنید.'
                            .($hint !== null ? ' — '.$hint : ''),
                    ];
                }
                break;

            case 'class_exists':
                $class = (string) ($check['class'] ?? '');

                if ($class === '') {
                    return;
                }

                if (class_exists($class)) {
                    $steps[] = ['label' => $label, 'status' => self::STATUS_OK, 'message' => 'کلاس «'.$class.'» در دسترس است.'];
                } else {
                    $steps[] = [
                        'label'   => $label,
                        'status'  => self::STATUS_WARN,
                        'message' => 'کلاس «'.$class.'» پیدا نشد.'
                            .($hint !== null ? ' — '.$hint : ''),
                    ];
                }
                break;

            case 'file_exists':
                $path = (string) ($check['path'] ?? '');

                if ($path === '') {
                    return;
                }

                $resolved = str_starts_with($path, '/') ? $path : base_path($path);
                $exists   = str_contains($resolved, '..') ? false : file_exists($resolved);

                $steps[] = [
                    'label'   => $label,
                    'status'  => $exists ? self::STATUS_OK : self::STATUS_WARN,
                    'message' => $exists
                        ? 'فایل «'.$path.'» موجود است.'
                        : 'فایل «'.$path.'» پیدا نشد.'.($hint !== null ? ' — '.$hint : ''),
                ];
                break;

            default:
                // نوع ناشناخته → نادیده (سازگاری رو به جلو برای تم‌ها)
        }
    }

    /** اجرای یک دستور artisan با ثبت خروجی */
    protected static function stepArtisanCommand(string $command, string $label, array &$steps): void
    {
        // گارد: فقط الگوی «command:sub» یا «command» مجاز است (بدون آرگومان خام — امن)
        if ($command === '' || ! preg_match('/^[a-z0-9\-:]+$/', $command)) {
            if ($command !== '') {
                $steps[] = [
                    'label'   => $label,
                    'status'  => self::STATUS_FAIL,
                    'message' => 'دستور artisan نامعتبر است: '.$command,
                ];
            }

            return;
        }

        try {
            Artisan::call($command);
            $output = trim(Artisan::output());

            $steps[] = [
                'label'   => $label,
                'status'  => self::STATUS_OK,
                'message' => 'دستور «'.$command.'» اجرا شد'.($output !== '' ? ': '.self::clip($output) : ''),
            ];
        } catch (Throwable $e) {
            report($e);
            $steps[] = [
                'label'   => $label,
                'status'  => self::STATUS_WARN,
                'message' => 'دستور «'.$command.'» خطا داد: '.$e->getMessage(),
            ];
        }
    }

    /** vendor:publish با تگ تم */
    protected static function stepPublish(string $tag, string $label, array &$steps): void
    {
        if (! preg_match('/^[A-Za-z0-9\-\._]+$/', $tag)) {
            $steps[] = ['label' => $label, 'status' => self::STATUS_FAIL, 'message' => 'تگ انتشار نامعتبر است: '.$tag];

            return;
        }

        try {
            Artisan::call('vendor:publish', ['--tag' => $tag, '--force' => true]);
            $output = trim(Artisan::output());

            $steps[] = [
                'label'   => $label,
                'status'  => self::STATUS_OK,
                'message' => 'vendor:publish تگ «'.$tag.'» اجرا شد'.($output !== '' ? ': '.self::clip($output) : ''),
            ];
        } catch (Throwable $e) {
            report($e);
            $steps[] = ['label' => $label, 'status' => self::STATUS_WARN, 'message' => 'vendor:publish خطا داد: '.$e->getMessage()];
        }
    }

    /**
     * بیلد npm تم (فقط برای تم‌هایی که اعلان کرده‌اند).
     * با گارد exec/escapeshellarg — روی هاست‌های بدون شل، warn دقیق می‌دهد نه کرش.
     */
    protected static function stepNpm(string $theme, array $npm, array &$steps): void
    {
        $relPath = trim((string) ($npm['path'] ?? 'src'), '/');
        $script  = (string) ($npm['script'] ?? 'build');
        $install = (string) ($npm['install'] ?? 'ci');
        $dir     = base_path("themes/{$theme}".($relPath !== '' ? '/'.$relPath : ''));

        $packageJson = $dir.'/package.json';

        if (! is_file($packageJson)) {
            $steps[] = [
                'label'   => 'بیلد npm',
                'status'  => self::STATUS_SKIP,
                'message' => 'package.json در themes/'.$theme.'/'.$relPath.' پیدا نشد؛ بیلد لازم نیست.',
            ];

            return;
        }

        $scripts = json_decode((string) file_get_contents($packageJson), true)['scripts'] ?? [];

        if (! is_array($scripts) || ! isset($scripts[$script])) {
            $steps[] = [
                'label'   => 'بیلد npm',
                'status'  => self::STATUS_SKIP,
                'message' => 'اسکریپت «'.$script.'» در package.json تعریف نشده؛ بیلد لازم نیست.',
            ];

            return;
        }

        if (! function_exists('exec') || ! function_exists('escapeshellarg')) {
            $steps[] = [
                'label'   => 'بیلد npm',
                'status'  => self::STATUS_WARN,
                'message' => 'اجرای شل روی این هاست غیرفعال است؛ بیلد را دستی اجرا کنید: cd themes/'.$theme.'/'.$relPath.' && npm '.$install.' && npm run '.$script,
            ];

            return;
        }

        $npm = self::findBinary(['npm', '/usr/local/bin/npm', '/usr/bin/npm']);

        if ($npm === null) {
            $steps[] = [
                'label'   => 'بیلد npm',
                'status'  => self::STATUS_WARN,
                'message' => 'npm/Node.js روی سرور در دسترس نیست؛ اگر دارایی‌های کامپایل‌شده در بستهٔ تم موجودند، نیازی به بیلد نیست وگرنه Node را نصب کنید.',
            ];

            return;
        }

        // نصب وابستگی‌ها (اگر node_modules نیست) و سپس بیلد
        @set_time_limit(300);

        if (! is_dir($dir.'/node_modules')) {
            $installOutput = self::runShellCommand('cd '.self::shellArg($dir).' && '.self::shellArg($npm).' '.self::shellArg($install).' 2>&1');

            if ($installOutput['code'] !== 0) {
                $steps[] = [
                    'label'   => 'بیلد npm',
                    'status'  => self::STATUS_WARN,
                    'message' => 'نصب وابستگی‌های npm ناموفق بود: '.self::clip($installOutput['output']),
                ];

                return;
            }
        }

        $buildOutput = self::runShellCommand('cd '.self::shellArg($dir).' && '.self::shellArg($npm).' run '.self::shellArg($script).' 2>&1');

        $steps[] = [
            'label'   => 'بیلد npm',
            'status'  => $buildOutput['code'] === 0 ? self::STATUS_OK : self::STATUS_WARN,
            'message' => $buildOutput['code'] === 0
                ? 'بیلد «npm run '.$script.'» با موفقیت انجام شد.'
                : 'بیلد npm ناموفق بود: '.self::clip($buildOutput['output']),
        ];
    }

    /** پاک‌سازی کش‌ها — گام پایانی همهٔ حالت‌ها */
    protected static function stepCacheClears(array &$steps): void
    {
        foreach ([
            'view:clear'   => 'پاک‌سازی کش ویوها',
            'config:clear' => 'پاک‌سازی کش کانفیگ',
            'route:clear'  => 'پاک‌سازی کش روت‌ها',
        ] as $command => $label) {
            self::stepArtisanCommand($command, $label, $steps);
        }
    }

    /* ---------------- ابزارهای کمکی ---------------- */

    /** آیا پکیج در composer.lock نصب شده است؟ (فallback بدون probe کلاس) */
    protected static function isPackageInLock(string $packageName): bool
    {
        try {
            $lockFile = base_path('composer.lock');

            if (! is_file($lockFile)) {
                return false;
            }

            $lock = json_decode((string) file_get_contents($lockFile), true);

            foreach (['packages', 'packages-dev'] as $section) {
                foreach ($lock[$section] ?? [] as $item) {
                    if (($item['name'] ?? null) === $packageName) {
                        return true;
                    }
                }
            }
        } catch (Throwable $e) {
            report($e);
        }

        return false;
    }

    /** یافتن باینری composer (null = در دسترس نیست) */
    protected static function findComposer(): ?string
    {
        if (! function_exists('exec') || ! function_exists('escapeshellarg')) {
            return null;
        }

        $candidates = ['composer', '/usr/local/bin/composer', '/usr/bin/composer', '/opt/composer/composer'];

        $phar = base_path('composer.phar');

        if (is_file($phar)) {
            $candidates[] = PHP_BINARY.' '.$phar;
        }

        foreach ($candidates as $candidate) {
            $output = self::runShellCommand(self::shellArg($candidate).' --version 2>&1');

            if ($output['code'] === 0 && str_contains($output['output'], 'Composer')) {
                return $candidate;
            }
        }

        return null;
    }

    /** یافتن باینری از بین مسیرهای نامزد */
    protected static function findBinary(array $candidates): ?string
    {
        if (! function_exists('exec') || ! function_exists('escapeshellarg')) {
            return null;
        }

        foreach ($candidates as $candidate) {
            $output = self::runShellCommand(self::shellArg($candidate).' --version 2>&1');

            if ($output['code'] === 0) {
                return $candidate;
            }
        }

        return null;
    }

    /** یافتن npm (فقط برای پیام) */
    protected static function findNpm(): ?string
    {
        return self::findBinary(['npm', '/usr/local/bin/npm', '/usr/bin/npm']);
    }

    /**
     * اجرای امن دستور شل با گارد کامل — روی هاست‌های بدون exec همیشه وضعیت مشخص برمی‌گرداند.
     *
     * @return array{code: int, output: string}
     */
    protected static function runShellCommand(string $command): array
    {
        if (! function_exists('exec')) {
            return ['code' => -1, 'output' => 'exec روی این هاست غیرفعال است.'];
        }

        @set_time_limit(300);

        $lines = [];
        $code  = -1;

        @exec($command, $lines, $code);

        return ['code' => $code, 'output' => implode("\n", $lines)];
    }

    /** escape امن برای شل (با گارد) */
    protected static function shellArg(string $value): string
    {
        if (function_exists('escapeshellarg')) {
            return escapeshellarg($value);
        }

        return "'".str_replace("'", "'\\''", $value)."'";
    }

    /** برش خروجی طولانی برای نمایش در گزارش */
    protected static function clip(string $text): string
    {
        $text = trim($text);

        if (mb_strlen($text) <= self::OUTPUT_LIMIT) {
            return $text;
        }

        return mb_substr($text, 0, self::OUTPUT_LIMIT).' …';
    }

    /** لاگ تجمیعی نتیجهٔ اجرا (برای دیباگ) */
    public static function logResults(string $theme, array $steps, string $context): void
    {
        try {
            Log::info('ThemeSetupRunner', [
                'theme'   => $theme,
                'context' => $context,
                'steps'   => array_map(
                    static fn (array $step) => $step['status'].' | '.$step['label'].' | '.$step['message'],
                    $steps
                ),
            ]);
        } catch (Throwable) {
            // لاگ هرگز آماده‌سازی را نبندد
        }
    }
}
