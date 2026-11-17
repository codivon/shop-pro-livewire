<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * چرخش خودکار لاگ — ضد «سوزاندن منابع سرور» روی هاست اشتراکی
 * ==================================================================
 *
 * مشکل: storage/logs/laravel.log با سیلاب خطاها بی‌سقف بزرگ می‌شود؛ نوتیفایر
 * و لاگ‌خوانِ پنل مجبورند سرِ فایل‌های چندده‌مگابایتی بچرخند و دیسک هاست
 * اشتراکی پر می‌شود (قبلاً همین لاگ چند صد مگابایت رشد کرده بود).
 *
 * راه‌حل: هر تیکِ زمان‌بند (با دروازهٔ کش هر ۱۰ دقیقه) فقط filesize می‌خواند؛
 * اگر لاگ از سقف گذشت، محتوایش فشرده در storage/logs/archive نگه داشته می‌شود
 * (حداکثر N آرشیو آخر، بقیه حذف) و خود فایل truncate می‌شود تا handler بازِ
 * Monolog (در دیمون صف) به همان inode معتبر بنویسد و چیزی گم نشود.
 *
 * چرا daily channel نه؟ گردش‌کار مدیر لاگِ تکی (laravel.log) در پنل است؛ این
 * چرخش همان فایل را حفظ می‌کند و فقط سقف می‌گذارد.
 */
class LogRotate
{
    /** سقف حجم laravel.log — پس از گذر از این، آرشیو و truncate */
    public const MAX_BYTES = 26214400; // 25 MB

    /** تعداد آرشیوهایی که نگه داشته می‌شود (بقیه حذف) */
    public const KEEP_ARCHIVES = 10;

    /** فاصلهٔ بین دو بررسی حجم (دقیقه) — بررسیِ خودش باید تقریباً مجانی باشد */
    public const CHECK_INTERVAL_MINUTES = 10;

    public function runIfDue(): void
    {
        try {
            if (Cache::has('logs.rotate.check')) {
                return;
            }

            Cache::put('logs.rotate.check', 1, now()->addMinutes(self::CHECK_INTERVAL_MINUTES));
        } catch (Throwable) {
            // کش در دسترس نیست — بررسی ادامه پیدا می‌کند (هر تیک) ولی هرگز نمی‌شکند
        }

        try {
            $path = storage_path('logs/laravel.log');

            if (! is_file($path)) {
                return;
            }

            clearstatcache(true, $path);

            $size = filesize($path);

            if ($size === false || $size < self::MAX_BYTES) {
                return;
            }

            $archiveDir = storage_path('logs/archive');

            if (! is_dir($archiveDir) && ! @mkdir($archiveDir, 0755, true) && ! is_dir($archiveDir)) {
                return;
            }

            $content = (string) file_get_contents($path);

            if ($content === '') {
                return;
            }

            $target = $archiveDir.'/laravel-'.now()->format('Ymd-His').'.log.gz';

            if (@file_put_contents($target, (string) gzencode($content, 6)) !== false) {
                // truncate (نه unlink) — handler بازِ دیمون صف به همان inode می‌نویسد
                @file_put_contents($path, '['.now()->toDateTimeString().'] production.INFO: LogRotate — لاگ '.number_format($size / 1048576, 1).' مگابایتی آرشیو شد در '.$target."\n");
            }

            // نگهداری فقط آخرین N آرشیو
            $archives = glob($archiveDir.'/laravel-*.log.gz') ?: [];
            sort($archives);

            while (count($archives) > self::KEEP_ARCHIVES) {
                @unlink((string) array_shift($archives));
            }
        } catch (Throwable) {
            // چرخش لاگ هرگز نباید هیچ درخواست یا تیک زمان‌بندی را بشکند
        }
    }
}
