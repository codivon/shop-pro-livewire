<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * همگام‌سازی خودکار دارایی‌های تم وقتی روی هاست «کپی واقعی» است.
 *
 * ریشهٔ باگ: ThemeAssetLinker روی هاست‌هایی که symlink و exec غیرفعال دارند
 * دارایی‌های تم را «کپی واقعی» می‌کند:
 *   themes/X/src/resources/assets → public/themes/X
 * بعد از هر git pull منبع (src) به‌روز می‌شود ولی کپیِ public کهنه می‌ماند —
 * یعنی رفع‌های JS/CSS فرانت هرگز به سایت
 * نمی‌رسیدند و کاربر «تغییرات را نمی‌دید».
 *
 * راه‌حل: در هر بوت (با کش اسکن) اگر پوشهٔ public «کپی خودمان» باشد (مارکر
 * .shoplink-copy)، بیشترین mtime منبع با مُهرِ داخل مارکر مقایسه می‌شود؛
 * منبعِ جدیدتر → پاک و از نو کپی. پس از `git pull` + `optimize:clear` (که کش را
 * خالی می‌کند) اولین بازدید، دارایی‌ها را خودکار تازه می‌کند — بدون shop:link دستی.
 *
 * امنیت: اگر پوشهٔ public مارکر ما را ندارد (symlink سالم یا پوشهٔ دست‌ساز ادمین)
 * هیچ دستی زده نمی‌شود.
 */
class ThemeAssetSync
{
    /** کلید کشِ نتیجهٔ اسکن maxMtime (اسکن هر نیم‌ساعت یک‌بار کافی است) */
    public const SCAN_CACHE_KEY = 'theme.assets.scan.';

    public const CACHE_TTL = 1800; // ۳۰ دقیقه

    /**
     * @param  string  $theme  نام پوشهٔ تم (مثل WeblakShop)
     * @param  bool|null  $force  نوسازی اجباری برای تست/کامند
     * @param  string|null  $srcOverride  مسیر منبع جایگزین (تست‌پذیری)
     * @param  string|null  $linkOverride  مسیر لینک جایگزین (تست‌پذیری)
     * @param  string|null  $version  نسخهٔ تم (فاز ۲۱۴ اصلاحیه ۲) — اگر با مُهرِ
     *   داخل مارکر فرق داشته باشد، بدون توجه به mtime/کش، دارایی‌ها اجباری
     *   بازسازی می‌شوند (ترمیم «کپیِ کهنه» بعد از هر انتشار تم حتی وقتی
     *   mtime فایل‌ها به‌درستی رشد نکرده باشند).
     * @return array{status: string, stamp?: int}
     *
     * status ها: no-source | not-our-copy | fresh | refreshed | rebuilt | link-failed
     */
    public static function syncIfStale(string $theme, ?bool $force = null, ?string $srcOverride = null, ?string $linkOverride = null, ?string $version = null): array
    {
        $src = $srcOverride ?: base_path("themes/{$theme}/src/resources/assets");
        $link = $linkOverride ?: public_path("themes/{$theme}");

        if (! is_dir($src)) {
            return ['status' => 'no-source'];
        }

        /* فاز ۱۸۹ — خودترمیمی: اگر لینک/کپی در public «اصلاً» وجود ندارد،
         * قبلاً بی‌صدا 'no-link' برمی‌گشت و دارایی‌های تم برای همیشه ۴۰۴ می‌ماندند
         * (مثلاً بعد از حذف دستی پوشهٔ public/themes/X یا دیپلوی که پوشهٔ public را
         * پاک می‌کند). الان همان‌جا لینک/کپی از نو ساخته می‌شود — idempotent است. */
        if (! is_dir($link)) {
            try {
                ThemeAssetLinker::linkOrCopy($src, $link);
                self::stampVersion(rtrim($link, '/').'/'.ThemeAssetLinker::COPY_MARKER, $version);

                return ['status' => 'rebuilt', 'stamp' => self::maxMtime($src)];
            } catch (Throwable $e) {
                report($e);

                return ['status' => 'link-failed'];
            }
        }

        $marker = rtrim($link, '/').'/'.ThemeAssetLinker::COPY_MARKER;

        // پوشهٔ بدون مارکر → symlink سالم یا پوشهٔ دست‌ساز ادمین
        if (! is_file($marker)) {
            /* فاز ۱۸۹ — پوشهٔ بدون مارکر ولی «خالی» (هیچ فایلی ندارد) → بازسازی.
             * این دقیقاً وضعیتی بود که دارایی‌های تم سانیار ۴۰۴ می‌دادند: پوشهٔ
             * public/themes/X موجود ولی تهی؛ نه لینکر دست می‌زد نه sync. پوشهٔ
             * دست‌سازِ دارای محتوا مثل قبل دست‌نخورده می‌ماند. */
            if (ThemeAssetLinker::dirIsEmpty($link)) {
                try {
                    ThemeAssetLinker::linkOrCopy($src, $link);
                    self::stampVersion($marker, $version);

                    return ['status' => 'rebuilt', 'stamp' => self::maxMtime($src)];
                } catch (Throwable $e) {
                    report($e);

                    return ['status' => 'link-failed'];
                }
            }

            return ['status' => 'not-our-copy'];
        }

        // مارکرهای متنی قدیمی به‌صورت ۰ خوانده می‌شوند → یک‌بار نوسازی خودکار
        $rawMarker = (string) @file_get_contents($marker);
        $stamp = (int) $rawMarker;

        /* فاز ۲۱۴ اصلاحیه ۲ — نسخهٔ تم داخل مارکر (بخش سوم بعد از «mtime|تعداد»)؛
           فرمت‌های قدیمی فقط «mtime|تعداد» دارند → storedVersion = null →
           اولین بوت بعد از آپدیت master/تم، بازسازی اجباری یک‌بار انجام می‌شود
           و بعد از آن مُهر نسخه ذخیره شده و مقایسه‌ها سبک می‌مانند. */
        $markerParts = explode('|', $rawMarker);
        $storedVersion = (isset($markerParts[2]) && $markerParts[2] !== '') ? $markerParts[2] : null;
        $versionChanged = ($version !== null && $version !== $storedVersion);

        /* فاز ۱۹۰ — وضعیت «مارکرِ بی‌محتوا»:
         * فایل .shoplink-copy هست ولی هیچ فایل دارایی دیگری کنارش کپی نشده
         * (کپیِ بی‌صدای تهی روی هاست‌های اشتراکی). بدون این گارد، چون مُهر mtime
         * در مارکر واقعی بود، مقایسهٔ maxMtime همیشه «fresh» برمی‌گرداند و
         * دارایی‌های تم برای همیشه ۴۰۴ می‌ماندند — دقیقاً گزارش کاربر:
         * «فقط یک فایل .shoplink-copy در public ساخته می‌شود». */
        if (! ThemeAssetLinker::hasRealAssets($link)) {
            try {
                ThemeAssetLinker::linkOrCopy($src, $link);
                self::stampVersion($marker, $version);

                return ['status' => 'rebuilt', 'stamp' => self::maxMtime($src)];
            } catch (Throwable $e) {
                report($e);

                return ['status' => 'link-failed'];
            }
        }

        /* فاز ۲۱۴ اصلاحیه ۲ — تغییر نسخهٔ تم ⇒ بدون توجه به mtime و کشِ اسکن،
           بازسازی اجباری. مسیر سبکِ «fresh» فقط وقتی است که نسخه هم یکسان باشد. */
        if ($versionChanged) {
            // بازسازی در ادامهٔ متد انجام می‌شود (بدون خواندن کش maxMtime)
        } else {
            $maxMtime = Cache::remember(self::SCAN_CACHE_KEY.$theme, self::CACHE_TTL, fn () => self::maxMtime($src));

            if ($maxMtime <= $stamp && ! $force) {
                return ['status' => 'fresh'];
            }
        }

        // کپی کهنه است → پاک و از نو بساز (ThemeAssetLinker خودش مُهر جدید را می‌نویسد)
        // گارد: اگر روزی لینکِ ارتقایافته به symlink بود، فقط خودِ لینک حذف شود نه
        // محتوای منبع (deleteDirectory روی symlink محتوای هدف را پاک می‌کند!)
        if (is_link($link)) {
            @unlink($link);
        } else {
            File::deleteDirectory($link);
        }

        /* فاز ۱۹۰ — کپی تأییدشده حالا در شکست استثنا می‌دهد؛ اینجا در بوتِ تم هستیم،
         * اجازه ندهیم استثنا به فرانت برسد و کل صفحه بخوابد. */
        try {
            ThemeAssetLinker::linkOrCopy($src, $link);
            self::stampVersion($marker, $version);
        } catch (Throwable $e) {
            report($e);

            return ['status' => 'link-failed'];
        }

        return ['status' => 'refreshed', 'stamp' => self::maxMtime($src)];
    }

    /**
     * فاز ۲۱۴ اصلاحیه ۲ — درج نسخهٔ تم در مارکر «mtime|تعداد|نسخه».
     * بی‌صدا و بی‌خطر: اگر نسخه‌ای پاس نشده باشد، مارکر دست نمی‌خورد
     * تا فرمت قدیمی «mtime|تعداد» حفظ شود.
     */
    private static function stampVersion(string $marker, ?string $version): void
    {
        if ($version === null || ! is_file($marker)) {
            return;
        }

        try {
            $parts = explode('|', (string) @file_get_contents($marker));
            $parts[2] = $version;
            @file_put_contents($marker, implode('|', array_slice($parts, 0, 3)));
        } catch (Throwable) {
            // نوشتن مارکر حیاتی نیست — خطا بی‌صدا نادیده می‌شود
        }
    }

    /** بیشترین mtime فایل‌های یک پوشه به‌صورت بازگشتی (بدون استثنا) */
    public static function maxMtime(string $dir): int
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
}
