<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

/**
 * ساخت لینک دارایی‌های قالب (themes/X/src/resources/assets → public/themes/X)
 * با زنجیرهٔ فال‌بک کامل و idempotent:
 *
 *   ۱) symlink سالم از قبل موجود؟ → هیچ کاری لازم نیست
 *   ۲) تابع بومی symlink() (اگر روی هاست disable نشده باشد)
 *   ۳) exec با ln -s / mklink (فقط اگر تابع exec واقعاً موجود باشد!)
 *   ۴) کپی واقعی محتوا + فایل مارکر (تضمینی — روی هر هستی کار می‌کند)
 *
 * ریشهٔ باگ : File::link لاراول روی لینوکس وقتی symlink غیرفعال باشد
 * بی‌دقت exec('ln -s …') می‌زند؛ روی هاست‌هایی که exec هم در disable_functions است
 * (هاست اشتراکی سخت‌گیرانه) خطای مهلک:
 *   «Call to undefined function Illuminate\Filesystem\exec()»
 * رخ می‌دهد و دکمهٔ «تنظیم به عنوان قالب» در پنل با ۵۰۰ می‌میرد.
 */
class ThemeAssetLinker
{
    /**
     * مارکر فایل داخل پوشه‌های کپی‌شده توسط این سرویس؛
     * برای تشخیص «کپی قدیمی خودمان» (نوسازی/ارتقای symlink) از پوشهٔ دست‌ساز ناشناس.
     */
    public const COPY_MARKER = '.shoplink-copy';

    /**
     * لینک یا کپی — همیشه بی‌خطر برای فراخوانی چندباره.
     *
     * پارامترهای allowSymlink/allowExec فقط برای تست‌پذیری‌اند؛
     * پیش‌فرض از قابلیت‌های واقعی PHP کشف می‌شوند (function_exists).
     *
     * @param  string      $target  مسیر هدف (پوشهٔ assets تم)
     * @param  string      $link    مسیر لینک در public
     * @param  bool|null   $allowSymlink
     * @param  bool|null   $allowExec
     * @return string حالت نهایی: symlink|exec|existing|existing-dir|copy
     *
     * @throws RuntimeException اگر هدف وجود نداشته باشد
     */
    public static function linkOrCopy(string $target, string $link, ?bool $allowSymlink = null, ?bool $allowExec = null): string
    {
        $allowSymlink = $allowSymlink ?? function_exists('symlink');
        $allowExec    = $allowExec    ?? function_exists('exec');

        if (!is_dir($target) && !is_file($target)) {
            throw new RuntimeException("هدف لینک وجود ندارد: {$target}");
        }

        // ۰) symlink سالم از قبل برقرار است → idempotent
        if (self::linkValid($link)) {
            return 'existing';
        }

        // symlink شکسته → پاک شود تا جایگزین ممکن باشد
        if (is_link($link)) {
            @unlink($link);
        }

        // مسیر به‌صورت پوشه/فایل واقعیِ موجود است:
        //  - کپی قدیمی خودمان (دارای مارکر) → نوسازی: پاک و از نو بساز
        //    (به‌روزرسانی تم + ارتقای خودکار کپی→symlink وقتی هاست اجازه داد)
        //  - پوشهٔ بدون مارکر ولی «بدون هیچ فایلی» → بازسازی (فاز ۱۸۹: پوشهٔ
        //    خالیِ رهاش‌شده دقیقاً همان علت ۴۰۴ شدن دارایی‌ها بود — لینکر قبلاً
        //    از ترس پاک‌کردن فایل دست‌ساز ادمین، پوشهٔ خالی را هم دست‌نخورده
        //    می‌گذاشت و پیام گمراه‌کنندهٔ «از قبل منتشر شده‌اند» می‌داد)
        //  - پوشه/فایل ناشناس با محتوا → دست نمی‌زنیم (از بین نبردن فایل دست‌ساز ادمین)
        if (file_exists($link)) {
            $isOurStaleCopy = is_dir($link) && is_file(rtrim($link, '/').'/'.self::COPY_MARKER);

            if ($isOurStaleCopy) {
                File::deleteDirectory($link);
            } elseif (is_dir($link) && self::dirIsEmpty($link)) {
                // فاز ۱۸۹ — پوشهٔ خالیِ بدون مارکر: بازسازی کامل (لینک/کپی تازه)
                File::deleteDirectory($link);
            } else {
                return 'existing-dir';
            }
        }

        File::ensureDirectoryExists(dirname($link));

        // ۱) تابع بومی symlink — روی هاست‌های اشتراکی ممکن است disable باشد؛
        //    در آن حالت PHP فقط warning می‌دهد و false برمی‌گرداند نه استثنا
        if ($allowSymlink) {
            if (@symlink($target, $link) && self::linkValid($link)) {
                return 'symlink';
            }
            if (is_link($link)) {
                @unlink($link); // لینک نیمه‌ساخته/شکسته
            }
        }

        // ۲) دستور سیستم‌عامل — فقط با گارد function_exists
        if ($allowExec) {
            if (windows_os()) {
                $mode = is_dir($target) ? 'J' : 'H';
                @exec("mklink /{$mode} ".escapeshellarg($link).' '.escapeshellarg($target).' 2>&1', $out, $code);
            } else {
                @exec('ln -s '.escapeshellarg($target).' '.escapeshellarg($link).' 2>&1', $out, $code);
            }

            if (self::linkValid($link)) {
                return 'exec';
            }
            if (is_link($link)) {
                @unlink($link); // ln شکسته
            }
        }

        // ۳) فال‌بک تضمینی: کپی واقعی محتوا + مارکر مالکیت
        if (!file_exists($link)) {
            File::makeDirectory($link, 0755, true, true);
        }

        if (is_dir($target)) {
            /*
             * فاز ۱۹۰ — کپیِ «تأییدشده» با جابه‌جایی اتمیک:
             * File::copyDirectory لاراول در شکستِ هر فایل فقط false برمی‌گرداند (بی‌صدا!)
             * و قبلاً همین کپیِ بی‌صدای تهی باعث می‌شد فقط مارکر در public ساخته شود
             * و دارایی‌های تم برای همیشه ۴۰۴ بمانند. حالا کپی در پوشهٔ موقت انجام و
             * شمار فایل‌ها با منبع مقایسه می‌شود؛ سپس با rename به مقصد می‌رود —
             * مقصد هرگز «نیمه‌کاره/تهی» نمی‌ماند. در شکست کامل → RuntimeException
             * با پیام علت (در گزارش «اجرای پیش‌نیازها» و لاگ‌ها قابل مشاهده است).
             */
            self::copyDirectoryVerified($target, $link);
        } else {
            File::copy($target, $link);
        }

        return 'copy';
    }

    /**
     * کپی تأییدشدهٔ محتوا (فاز ۱۹۰) — کپی در پوشهٔ موقت + جابه‌جایی اتمیک.
     *
     * چرا لازم است؟ File::copyDirectory لاراول روی شکست هر فایل «فقط false»
     * برمی‌گرداند: نه استثنا می‌دهد نه پیام. روی هاست‌های اشتراکی (سهمیهٔ دیسک،
     * دسترسی پوشه، open_basedir و…) نتیجه «پوشهٔ مقصدِ تهی» می‌شد و چون
     * مقدار بازگشتی نادیده گرفته می‌شد، همه فکر می‌کردند کپی موفق بوده است.
     *
     * ساختار سه‌مرحله‌ای:
     *   ۱) کپی استاندارد لاراول در «پوشهٔ موقت» کنار مقصد
     *   ۲) اگر ناقص بود → پاک‌سازی موقت و کپیِ دستی تک‌تک فایل‌ها با ثبت نخستین
     *      خطای واقعی (error_get_last) — علت به ادمین نمایش داده می‌شود
     *   ۳) نوشتن مارکر مالکیت داخل موقت و سپس rename اتمیک به مقصد —
     *      مقصد در هیچ لحظه‌ای «تهی/نیمه‌کاره» نمی‌ماند (۴۰۴ نداریم)
     *
     * مارکر حالا «مُهر زمانی + شمار فایل» است: "<maxMtime>|<fileCount>"؛
     * بخش اول همان معیار کهنگی ThemeAssetSync است و خواندن با (int) سازگار می‌ماند.
     *
     * @param  string  $source       پوشهٔ منبع (assets تم)
     * @param  string  $destination  پوشهٔ مقصد در public
     * @return int تعداد فایل‌های کپی‌شدهٔ نهایی
     *
     * @throws RuntimeException منبع خالی / ناخوانا / کپی ناقص با ذکر علت واقعی
     */
    public static function copyDirectoryVerified(string $source, string $destination): int
    {
        /* فاز ۱۹۰ — شمار فقط «دارایی واقعی»:
         * مارکر .shoplink-copy ممکن است به‌اشتباه داخل خود منبع هم نشسته باشد
         * (دقیقاً اتفاقی که برای تم سانیار افتاد و در ریپو کامیت شد!) —
         * شمارِ کل فایل‌ها آن را «دارایی» می‌گرفت و کپیِ تک‌فایله «موفق» جلوه می‌کرد. */
        try {
            $sourceFiles    = File::allFiles($source);
            $realSourceFiles = array_values(array_filter(
                $sourceFiles,
                fn ($f) => $f->getFilename() !== self::COPY_MARKER
            ));
            $sourceCount = count($realSourceFiles);
        } catch (Throwable $e) {
            throw new RuntimeException("خواندن پوشهٔ منبع دارایی‌ها ناموفق بود: {$source} — {$e->getMessage()}");
        }

        if ($sourceCount === 0) {
            $strayMarker = count($sourceFiles) > 0
                ? ' (فقط مارکر .shoplink-copy در منبع هست — دارایی واقعی وجود ندارد)'
                : '';

            throw new RuntimeException(
                "پوشهٔ منبع دارایی‌ها خالی است: {$source}{$strayMarker} — هیچ فایلی برای انتشار در public وجود ندارد؛".
                ' مطمئن شوید قالب کامل آپلود/pull شده است (پوشهٔ src/resources/assets باید پر باشد).'
            );
        }

        $destination = rtrim($destination, '/');
        $parent      = dirname($destination);
        File::ensureDirectoryExists($parent);

        // پاک‌سازی پوشه‌های موقتِ رهاش‌شدهٔ اجراهای قبلی (کرش/تایم‌اوت) — حفظ سهمیهٔ دیسک هاست
        foreach (@glob($parent.'/'.basename($destination).'.tmp-copy-*') ?: [] as $stale) {
            if (is_dir($stale) && (@filemtime($stale) ?: 0) < (time() - 3600)) {
                File::deleteDirectory($stale);
            }
        }

        $tmp = $destination.'.tmp-copy-'.substr(md5(uniqid('', true)), 0, 8);

        // ۱) کپی استاندارد لاراول در پوشهٔ موقت
        $standardOk = false;

        try {
            $standardOk = File::copyDirectory($source, $tmp);
        } catch (Throwable) {
            $standardOk = false; // ادامه با کپی دستی
        }

        // مارکرِ سرگردان منبع در شمارش مقصد هم به‌حساب نمی‌آید
        $copied = $standardOk
            ? count(array_filter(File::allFiles($tmp), fn ($f) => $f->getFilename() !== self::COPY_MARKER))
            : -1;

        if ($copied < $sourceCount) {
            // ۲) کپی دستی تک‌تک فایل‌ها + ثبت نخستین خطای واقعی
            File::deleteDirectory($tmp);
            File::ensureDirectoryExists($tmp);

            $sourceReal  = realpath($source) ?: rtrim($source, '/');
            $prefix      = rtrim($sourceReal, '/');
            $firstError  = null;
            $copied      = 0;

            try {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($sourceReal, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file) {
                    if (! $file->isFile()) {
                        continue;
                    }

                    // فاز ۱۹۰ — مارکرِ سرگردان در منبع هرگز به public منتقل نمی‌شود
                    if ($file->getFilename() === self::COPY_MARKER) {
                        continue;
                    }

                    $from = $file->getPathname();
                    $rel  = ltrim(str_replace('\\', '/', substr($from, strlen($prefix))), '/');
                    $to   = $tmp.'/'.$rel;

                    try {
                        File::ensureDirectoryExists(dirname($to));
                    } catch (Throwable $e) {
                        $firstError ??= 'ساخت پوشهٔ مقصد ناموفق: '.$e->getMessage();

                        continue;
                    }

                    @clearstatcache();

                    if (@copy($from, $to)) {
                        $copied++;

                        continue;
                    }

                    $last = error_get_last();
                    $firstError ??= ($last['message'] ?? 'خطای ناشناخته در کپی فایل');
                }
            } catch (Throwable $e) {
                $firstError ??= $e->getMessage();
            }

            if ($copied < $sourceCount) {
                File::deleteDirectory($tmp);

                throw new RuntimeException(sprintf(
                    'کپی دارایی‌ها ناقص ماند: %d از %d فایل کپی شد. علت: %s (منبع: %s)',
                    $copied,
                    $sourceCount,
                    $firstError ?? 'نامشخص',
                    $source
                ));
            }
        }

        // مارکر مالکیت + مُهر داخل پوشهٔ موقت — قبل از جابه‌جایی، تا وضعیت مقصد همیشه کامل باشد
        @file_put_contents($tmp.'/'.self::COPY_MARKER, self::maxMtimeOf($source).'|'.$copied);

        // ۳) جابه‌جایی اتمیک موقت → مقصد (مقصدِ قبلی پاک و rename می‌شود)
        if (! File::moveDirectory($tmp, $destination, true)) {
            File::deleteDirectory($tmp);

            throw new RuntimeException("جابه‌جایی کپی موقت به مقصد ناموفق بود: {$destination}");
        }

        return $copied;
    }

    /** شمار فایل‌های یک پوشه به‌صورت بازگشتی — خطاها را با -1 بی‌صدا می‌بلعد */
    public static function countFiles(string $dir): int
    {
        try {
            return count(File::allFiles($dir));
        } catch (Throwable) {
            return -1;
        }
    }

    /**
     * آیا در پوشه، «غیر از مارکر کپی» هیچ فایل دارایی واقعی هست؟ (فاز ۱۹۰)
     *
     * وضعیت «مارکرِ بی‌محتوا» = کپیِ خراب (File::copyDirectory فقط false برگردانده
     * ولی مارکر نوشته شده). lazy است — با اولین فایل واقعی true برمی‌گرداند.
     * در هر بوتِ تم صدا زده می‌شود؛ کپی سالم → فایل اول را فوراً می‌بیند.
     * پوشهٔ ناخوانا → true (محتاطانه: فرض کن سالم است، دست نزن).
     */
    public static function hasRealAssets(string $dir): bool
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() !== self::COPY_MARKER) {
                    return true; // حداقل یک فایل دارایی واقعی هست
                }
            }
        } catch (Throwable) {
            return true; // ناخوانا → محتاطانه: فرض کن سالم است، دست نزن
        }

        return false; // فقط مارکر (یا هیچ‌چیز) — کپی خراب است
    }

    /** بیشترین mtime فایل‌های یک پوشه (بدون استثنا) — نسخهٔ محلی بدون وابستگی به ThemeAssetSync */
    public static function maxMtimeOf(string $dir): int
    {
        $max = 0;

        try {
            foreach (File::allFiles($dir) as $file) {
                $mtime = $file->getMTime();

                if ($mtime > $max) {
                    $max = $mtime;
                }
            }
        } catch (Throwable) {
            // مسیر ناخوانا/دسترسی — بی‌خطر
        }

        return $max;
    }

    /**
     * آیا پوشه «بدون هیچ فایلی» است؟ (فقط پوشه‌های خالی تودرتو مجاز است)
     * پوشهٔ ناخوانا/دسترسی‌ناقص → false (دست نمی‌زنیم — محتاطانه)
     * public شده تا ThemeAssetSync هم برای خودترمیمیِ پوشهٔ خالی از آن استفاده کند.
     *
     * پیاده‌سازی lazy است: با اولین فایل false برمی‌گردد (اسکن کامل نمی‌شود) —
     * چون در هر بوتِ تم برای وضعیت «پوشهٔ بدون مارکر» صدا زده می‌شود.
     */
    public static function dirIsEmpty(string $dir): bool
    {
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    return false; // حداقل یک فایل واقعی وجود دارد — کافی است، ادامه نمی‌دهیم
                }
            }
        } catch (Throwable) {
            return false; // ناخوانا → محتاطانه: دست نزن
        }

        return true;
    }

    /** آیا مسیر به‌عنوان symlink سالم وجود دارد؟ (هدف موجود است) */
    protected static function linkValid(string $link): bool
    {
        return is_link($link) && @readlink($link) !== false && file_exists($link);
    }
}
