<?php

namespace App\Services;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * JalaliDate — تبدیل تاریخ میلادی به شمسی (جلالی) بدون وابستگی به بستهٔ خارجی
 * =============================================================================
 *
 * کاربرد: پیام‌های لایسنس (دورهٔ مهلت/انقضا) و هر جای هسته که تاریخ باید
 * شمسی با ارقام فارسی نمایش داده شود (نیاز محصول — ۱۴۰۴).
 *
 * مسیر اصلی: IntlDateFormatter با تقویم persian (دقیقِ نجی — هم‌الگو با
 * morilog/jalali)؛ اگر intl روی هاست فعال نبود، الگوریتم حسابی ۳۳ سالهٔ
 * جاسازی‌شده (خطای حداکثر یک روز در سال‌های خاص) فالبک می‌شود.
 * =============================================================================
 */
class JalaliDate
{
    /**
     * «۱۴۰۵/۰۸/۲۵ ساعت ۱۹:۵۹» — برای پیام‌های کاربر (مهلت/انقضا).
     * ورودی: Carbon|string|null — null/خالی/خراب ⇒ رشتهٔ ورودی دست‌نخورده یا ''.
     */
    public static function format(mixed $when): string
    {
        try {
            if ($when === null || $when === '') {
                return '';
            }

            $dt = $when instanceof DateTimeInterface ? Carbon::instance($when) : Carbon::parse((string) $when);

            [$jy, $jm, $jd] = self::fromCarbon($dt);

            return self::fa(sprintf(
                '%04d/%02d/%02d ساعت %02d:%02d',
                $jy,
                $jm,
                $jd,
                (int) $dt->format('H'),
                (int) $dt->format('i')
            ));
        } catch (Throwable) {
            return is_string($when) ? $when : '';
        }
    }

    /**
     * «1405/08/25 - 19:59:02» — سازگار با فرمت لاگ‌های پکیج‌ها.
     */
    public static function dateTime(CarbonInterface $dt, bool $persianDigits = false): string
    {
        [$jy, $jm, $jd] = self::fromCarbon($dt);

        $out = sprintf('%04d/%02d/%02d - %s', $jy, $jm, $jd, $dt->format('H:i:s'));

        return $persianDigits ? self::fa($out) : $out;
    }

    /**
     * «1405/08/25»
     */
    public static function date(CarbonInterface $dt, bool $persianDigits = false): string
    {
        [$jy, $jm, $jd] = self::fromCarbon($dt);

        $out = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);

        return $persianDigits ? self::fa($out) : $out;
    }

    /**
     * تبدیل سال/ماه/روز میلادی به [سال، ماه، روز] جلالی (الگوریتم حسابی).
     *
     * @return int[] [jy, jm, jd]
     */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $gDaysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $jDaysInMonth = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $gDayNo = 365 * $gy2
            + intdiv($gy2 + 3, 4)
            - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400)
            + $gd
            + array_sum(array_slice($gDaysInMonth, 0, $gm - 1));

        $jDayNo = $gDayNo - 79;
        $jNp = intdiv($jDayNo, 12053);
        $jDayNo %= 12053;
        $jy = 979 + 33 * $jNp + 4 * intdiv($jDayNo, 1461);
        $jDayNo %= 1461;

        if ($jDayNo >= 366) {
            $jy += intdiv($jDayNo - 366, 365);
            $jDayNo = ($jDayNo - 366) % 365;
        }

        $jm = 0;

        for ($i = 0; $i < 12 && $jDayNo >= $jDaysInMonth[$i]; $i++) {
            $jDayNo -= $jDaysInMonth[$i];
            $jm++;
        }

        return [$jy, $jm + 1, $jDayNo + 1];
    }

    /**
     * از Carbon — اول IntlDateFormatter (تقویم persian نجی)؛ فالبک حسابی.
     *
     * @return int[] [jy, jm, jd]
     */
    private static function fromCarbon(CarbonInterface $dt): array
    {
        try {
            if (class_exists('\IntlDateFormatter')) {
                $fmt = new \IntlDateFormatter(
                    'en_US@calendar=persian',
                    \IntlDateFormatter::NONE,
                    \IntlDateFormatter::NONE,
                    $dt->getTimezone()->getName(),
                    \IntlDateFormatter::TRADITIONAL,
                    'yyyy-MM-dd'
                );

                $parsed = $fmt->format($dt->getTimestamp());

                if ($parsed !== false && preg_match('/^(-?\d+)-(\d{1,2})-(\d{1,2})$/', $parsed, $m)) {
                    return [(int) $m[1], (int) $m[2], (int) $m[3]];
                }
            }
        } catch (Throwable) {
            // intl نبود/خطا داد ⇒ الگوریتم حسابی
        }

        return self::toJalali((int) $dt->format('Y'), (int) $dt->format('n'), (int) $dt->format('j'));
    }

    /**
     * ارقام لاتین → فارسی
     */
    public static function fa(string $text): string
    {
        return strtr($text, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }
}
