<?php

namespace App\Support;

/* ============================================================
   PaletteEngine (فاز ۲۱۱) — موتور پوستهٔ رنگی «پنل مدیریت»
   ------------------------------------------------------------
   طراحی کاملاً تازه نسبت به فاز ۲۰۸/۲۱۰:
   هر پالتِ آماده یک «بستهٔ رنگیِ هماهنگِ چندنقشی» است — نه یک
   رنگِ واحد. هر پالت این نقش‌ها را با هم تغییر می‌دهد:
     • رنگ اصلی (دکمه‌ها، لینک‌ها، آیکن‌ها، حالت فعال) ← طیف brand-50..950
     • رنگ سایدبار — نسخهٔ روشن و تم شب (تیِنِ هم‌خانواده با رنگ اصلی)
     • رنگ صفحه — نسخهٔ روشن و تم شب (کمی عمیق‌تر از سایدبار)
     • رنگ متن — در پالت‌های آماده همان پیش‌فرضِ پنل می‌ماند تا
       خوانایی همیشه تضمین شود؛ فقط در «شخصی‌سازی» قابل تغییر است.
   ⇒ همهٔ بخش‌های پنل رنگ می‌گیرند، اما «همه‌چیز یک‌رنگ» نمی‌شود؛
   سایدبار/صفحه تِینِ لطیفِ همان خانوادهٔ رنگی‌اند و متن‌ها و
   دکمه‌ها در روشن و تیره به‌صورت خودکار خوانا می‌مانند.

   حالت پیش‌فرضِ روشن/تیرهٔ پنل از جای دیگر (کوکی کاربر / دکمهٔ ماه)
   تنظیم می‌شود و اینجا فقط «سازگار» است: هر پالت همیشه نسخهٔ
   روشن و تیرهٔ هماهنگ دارد و متنِ سایدبار با کنتراست YIQ از خودِ
   رنگِ سایدبار مشتق می‌شود.

   شخصی‌سازی (پالت «custom»): مدیر رنگ دکمه‌ها/متن/سایدبار/صفحه را
   یک‌بار انتخاب می‌کند؛ نسخهٔ تم‌شبِ هر رنگ به‌صورت خودکار و هماهنگ
   (هیو بر اساس رنگ اصلی، روشنایی متناسب) ساخته می‌شود:
     • سایدبار روشن‌تر از YIQ۱۴۰ ⇒ در شب به تیرهٔ هم‌خانواده می‌رود
     • صفحهٔ روشن‌تر از YIQ۱۲۰ ⇒ در شب به تیرهٔ هم‌خانواده می‌رود
     • متنِ تیره‌تر از YIQ۱۵۰ ⇒ در شب به روشنِ هم‌خانواده می‌رود
   • رنگی که خودش تیره است (مثل سایدبار شب‌رنگ) در تم شب دست‌نخورده
     می‌ماند — چون همان حالت شب‌پسند است.

   تفاوت با نسخهٔ قالب (Themes\SaniarTheme\src\Support):
   • کلید option: «admin_appearance» (مستقل از زبان — در
     non_language_options ثبت شده) و پیش‌فرضِ پوسته = زمردیِ
     فعلیِ پنل (هیچ تغییر ظاهری بدون انتخاب مدیر رخ نمی‌دهد).
   • طیف حول رنگ اصلی با «پلهٔ ۶۰۰ = رنگ اصلی» ساخته می‌شود چون
     رنگِ برندِ پنل، پلهٔ brand-600 است (دکمه‌ها/سایدبار/تیترها).
   • پالت «زمرّد» طیفِ ثابتِ تیلویند دارد (fixed_scale) تا ظاهر فعلی
     پنل پیکسل‌به‌پیکسل حفظ شود (brand-600=#059669، brand-500=#10b981، …).
   • خروجی (payload) در layout پنل به‌صورت بازنویسی متغیرهای
     --color-brand-* + متغیرهای سایدبار/صفحه/متن تزریق می‌شود؛
     کلیدهای payload با فاز ۲۱۰ یکسان‌اند (layoutها دست‌نخورده).
   ============================================================ */

class PaletteEngine
{
    /** کلید option ذخیرهٔ پوستهٔ پنل (non_language_options — مستقل از زبان) */
    public const OPTION_KEY = 'admin_appearance';

    /** پالت پیش‌فرض پنل = زمردیِ فعلی (تا با استقرارِ این نسخه ظاهر عوض نشود) */
    public const DEFAULT_PALETTE = 'emerald';

    /** رنگ اصلی پیش‌فرض حالت شخصی‌سازی */
    public const DEFAULT_CUSTOM = '#D62976';

    /* پیش‌فرض‌های نقش‌های پنل (= مقادیر فعلی پنل؛ خالی بودن هرکدام در
       payloadِ شخصی‌سازی یعنی «همان پیش‌فرض پنل، بدون بازنویسی») */

    /** رنگ متن پنل در حالت روشن (‎--color-ink در admin.css) */
    public const DEFAULT_FONT_COLOR = '#1A2333';

    /** رنگ متن پنل در تم شب (‎--color-night-ink در admin.css) */
    public const DEFAULT_FONT_COLOR_DARK = '#E5EAF2';

    /** رنگ سایدبار در حالت روشن (bg-surface) */
    public const DEFAULT_SIDEBAR_COLOR = '#FFFFFF';

    /** رنگ سایدبار در تم شب (dark:bg-night-2) */
    public const DEFAULT_SIDEBAR_COLOR_DARK = '#111827';

    /** رنگ صفحه در حالت روشن (bg-surface-2 بدنه) */
    public const DEFAULT_PAGE_COLOR = '#F6F7F8';

    /** رنگ صفحه در تم شب (dark:bg-night بدنه) */
    public const DEFAULT_PAGE_COLOR_DARK = '#0D1219';

    /* آستانه‌های مشتق‌سازی خودکار نسخهٔ تم‌شب (شخصی‌سازی) —
       قرینهٔ دقیق در JS صفحهٔ تنظیمات پیاده شده است */

    /** سایدبار روشن‌تر از این YIQ ⇒ در تم شب تیرهٔ هم‌خانواده می‌شود */
    public const SIDE_LIGHT_YIQ = 140.0;

    /** صفحهٔ روشن‌تر از این YIQ ⇒ در تم شب تیرهٔ هم‌خانواده می‌شود */
    public const PAGE_LIGHT_YIQ = 120.0;

    /** متن تیره‌تر از این YIQ ⇒ در تم شب روشنِ هم‌خانواده می‌شود */
    public const FONT_DARK_YIQ = 150.0;

    /** روشنایی/اشباع تیرهٔ هم‌خانوادهٔ سایدبار (تم شب) */
    public const SIDE_DARK_S = 30.0;
    public const SIDE_DARK_L = 10.5;

    /** روشنایی/اشباع تیرهٔ هم‌خانوادهٔ صفحه (تم شب) */
    public const PAGE_DARK_S = 26.0;
    public const PAGE_DARK_L = 7.0;

    /** روشنایی/اشباع روشنِ هم‌خانوادهٔ متن (تم شب) */
    public const FONT_DARK_S = 24.0;
    public const FONT_DARK_L = 88.0;

    /**
     * پالت‌های آماده — هر پالت یک بستهٔ رنگیِ هماهنگِ چندنقشی است.
     * «roles» = رنگ سایدبار/صفحه در روشن و تم شب (تیِنِ هم‌خانوادهٔ رنگ اصلی).
     * ترتیب آرایه = چیدمان گرید RTL (اولین = راست‌ترین کارت).
     */
    public static function palettes(): array
    {
        return [
            [
                'key'    => 'emerald',
                'title'  => 'زمرّد',
                'desc'   => 'سبزِ رویش — پیش‌فرض پنل',
                'base'   => '#059669',
                'roles'  => [
                    'side_light' => '#FFFFFF',
                    'side_dark'  => '#111827',
                    'page_light' => '#F6F7F8',
                    'page_dark'  => '#0D1219',
                ],
                // طیف ثابت تیلویند زمردی — ظاهرِ فعلی پنل، پیکسل‌به‌پیکسل حفظ می‌شود
                'fixed_scale' => [
                    50  => '#ECFDF5',
                    100 => '#D1FAE5',
                    200 => '#A7F3D0',
                    300 => '#6EE7B7',
                    400 => '#34D399',
                    500 => '#10B981',
                    600 => '#059669',
                    700 => '#047857',
                    800 => '#065F46',
                    900 => '#064E3B',
                    950 => '#022C22',
                ],
                'default' => true,
            ],
            [
                'key'    => 'sapphire',
                'title'  => 'سافایر',
                'desc'   => 'آبیِ اعتماد و تکنولوژی',
                'base'   => '#275FE0',
                'roles'  => [
                    'side_light' => '#F4F7FE',
                    'side_dark'  => '#101828',
                    'page_light' => '#EEF3FB',
                    'page_dark'  => '#0B1220',
                ],
            ],
            [
                'key'    => 'midnight',
                'title'  => 'نیلی شب',
                'desc'   => 'نیلیِ عمیق و شبانه',
                'base'   => '#4F46E5',
                'roles'  => [
                    'side_light' => '#F4F5FD',
                    'side_dark'  => '#14142B',
                    'page_light' => '#EEF0FA',
                    'page_dark'  => '#0F0F20',
                ],
            ],
            [
                'key'    => 'royal-purple',
                'title'  => 'بنفش سلطنتی',
                'desc'   => 'شیک، مدرن و لاکچری',
                'base'   => '#9333EA',
                'roles'  => [
                    'side_light' => '#F9F5FE',
                    'side_dark'  => '#181029',
                    'page_light' => '#F3EDFB',
                    'page_dark'  => '#120C1C',
                ],
            ],
            [
                'key'    => 'fuchsia',
                'title'  => 'ارغوانی',
                'desc'   => 'درخشش لوکس و ماندگار',
                'base'   => '#C026D3',
                'roles'  => [
                    'side_light' => '#FCF3FD',
                    'side_dark'  => '#1F1226',
                    'page_light' => '#F8EDFA',
                    'page_dark'  => '#170E1D',
                ],
            ],
            [
                'key'    => 'coral',
                'title'  => 'مرجانی',
                'desc'   => 'سرخِ گلیِ ملایم و شاد',
                'base'   => '#F43F5E',
                'roles'  => [
                    'side_light' => '#FEF3F6',
                    'side_dark'  => '#241118',
                    'page_light' => '#FCEDF1',
                    'page_dark'  => '#180E13',
                ],
            ],
            [
                'key'    => 'crimson',
                'title'  => 'سرخ گلگون',
                'desc'   => 'قرمزِ پرانرژی و جدی',
                'base'   => '#DC2626',
                'roles'  => [
                    'side_light' => '#FEF3F2',
                    'side_dark'  => '#241211',
                    'page_light' => '#FCEDEB',
                    'page_dark'  => '#180D0C',
                ],
            ],
            [
                'key'    => 'fiery-sunset',
                'title'  => 'غروب آتشین',
                'desc'   => 'نارنجیِ گرم و پرانرژی',
                'base'   => '#EA580C',
                'roles'  => [
                    'side_light' => '#FEF6EF',
                    'side_dark'  => '#241408',
                    'page_light' => '#FCEFE5',
                    'page_dark'  => '#180F08',
                ],
            ],
            [
                'key'    => 'amber',
                'title'  => 'کهربای زعفرانی',
                'desc'   => 'طلاییِ گرم و درخشان',
                'base'   => '#D97706',
                'roles'  => [
                    'side_light' => '#FDF9EC',
                    'side_dark'  => '#221B09',
                    'page_light' => '#FAF4E1',
                    'page_dark'  => '#181206',
                ],
            ],
            [
                'key'    => 'calm-ocean',
                'title'  => 'اقیانوس آرام',
                'desc'   => 'فیروزه‌ایِ عمیق و آرامش‌بخش',
                'base'   => '#0891B2',
                'roles'  => [
                    'side_light' => '#F0F9FC',
                    'side_dark'  => '#0A1D26',
                    'page_light' => '#E9F4F8',
                    'page_dark'  => '#08161D',
                ],
            ],
            [
                'key'    => 'caspian',
                'title'  => 'فیروزهٔ خزر',
                'desc'   => 'ترکیبِ سبز و آبیِ شاداب',
                'base'   => '#0D9488',
                'roles'  => [
                    'side_light' => '#F0FAF8',
                    'side_dark'  => '#0A1F1B',
                    'page_light' => '#E9F5F2',
                    'page_dark'  => '#081814',
                ],
            ],
            [
                'key'    => 'graphite',
                'title'  => 'گرافیت',
                'desc'   => 'خاکستریِ حرفه‌ای و بی‌طرف',
                'base'   => '#475569',
                'roles'  => [
                    'side_light' => '#F5F7F9',
                    'side_dark'  => '#15181D',
                    'page_light' => '#F0F2F5',
                    'page_dark'  => '#0F1114',
                ],
            ],
            [
                'key'    => 'custom',
                'title'  => 'شخصی‌سازی',
                'desc'   => 'رنگ‌ها را خودت بساز',
                'base'   => self::DEFAULT_CUSTOM,
                'custom' => true,
            ],
        ];
    }

    /** پالت‌ها به‌صورت کلیددار برای جست‌وجوی سریع */
    public static function indexed(): array
    {
        $indexed = [];
        foreach (self::palettes() as $palette) {
            $indexed[$palette['key']] = $palette;
        }

        return $indexed;
    }

    /* ========================================================
       ریاضی رنگ — HEX ↔ HSL ↔ YIQ
       ======================================================== */

    public static function normalizeHex(?string $value, string $fallback = self::DEFAULT_CUSTOM): string
    {
        $value = strtoupper(trim((string) $value));
        if (preg_match('/^#?([0-9A-F]{6})$/', $value, $m)) {
            return '#'.$m[1];
        }

        return strtoupper($fallback);
    }

    /**
     * نرمال‌سازی HEX با اجازهٔ خالی بودن.
     * خالی/نامعتبر = null یعنی «پیش‌فرض پنل» (بدون بازنویسی متغیر).
     */
    public static function normalizeHexOrNull(?string $value): ?string
    {
        $value = strtoupper(trim((string) $value));
        if (preg_match('/^#?([0-9A-F]{6})$/', $value, $m)) {
            return '#'.$m[1];
        }

        return null;
    }

    /** درخشندگی YIQ (معیار کنتراست) */
    public static function yiq(string $hex): float
    {
        [$r, $g, $b] = self::hexToRgb($hex);

        return (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    }

    /**
     * رنگ متنِ خوانا روی پس‌زمینهٔ داده‌شده (معیار YIQ).
     * برای سایدبارِ رنگی: متن تیره روی رنگ روشن و متن روشن روی رنگ تیره.
     */
    public static function contrastInk(string $hex): string
    {
        return self::yiq($hex) >= 150 ? self::DEFAULT_FONT_COLOR : '#F2F5FA';
    }

    /** HEX -> "R, G, B" (for rgba() shadows in the injected style) */
    public static function hexToRgbString(string $hex): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);

        return $r . ', ' . $g . ', ' . $b;
    }

    /** HEX -> HSL: h(0-360), s(0-100), l(0-100) */
    public static function hexToHsl(string $hex): array
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = 0.0;
            $s = 0.0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
            if ($max == $r) {
                $h = (($g - $b) / $d) + ($g < $b ? 6 : 0);
            } elseif ($max == $g) {
                $h = (($b - $r) / $d) + 2;
            } else {
                $h = (($r - $g) / $d) + 4;
            }
            $h *= 60;
        }

        return [$h, $s * 100, $l * 100];
    }

    /** HSL → HEX (#RRGGBB) */
    public static function hslToHex(float $h, float $s, float $l): string
    {
        [$r, $g, $b] = self::hslToRgb($h, $s, $l);

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    public static function hexToRgb(string $hex): array
    {
        $hex = ltrim(self::normalizeHex($hex), '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function hslToRgb(float $h, float $s, float $l): array
    {
        $h = fmod(fmod($h, 360) + 360, 360) / 360;
        $s = max(0.0, min(100.0, $s)) / 100;
        $l = max(0.0, min(100.0, $l)) / 100;

        if ($s == 0.0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = self::hueToRgb($p, $q, $h + 1 / 3);
            $g = self::hueToRgb($p, $q, $h);
            $b = self::hueToRgb($p, $q, $h - 1 / 3);
        }

        return [
            (int) round($r * 255),
            (int) round($g * 255),
            (int) round($b * 255),
        ];
    }

    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    private static function wrapHue(float $h): float
    {
        return fmod(fmod($h, 360) + 360, 360);
    }

    /** کوتاه‌ترین چرخش Hue از $from به $to (−۱۸۰..۱۸۰) */
    private static function shortestHueDelta(float $from, float $to): float
    {
        $delta = fmod($to - $from, 360);
        if ($delta > 180) {
            $delta -= 360;
        }
        if ($delta < -180) {
            $delta += 360;
        }

        return $delta;
    }

    /**
     * اعمال گرمی/سردی روی یک رنگ.
     * warmth منفی = گرم‌تر (به سمت نارنجی/سرخ + کمی پررنگ‌تر)
     * warmth مثبت = سردتر (به سمت آبی + کمی کم‌رنگ‌تر)
     * خروجی: [رنگِ شیفت‌شده، مقدار چرخش Hue بر حسب درجه]
     */
    public static function warmthRotation(string $hex, int $warmth): array
    {
        if ($warmth === 0) {
            return [self::normalizeHex($hex), 0.0];
        }

        [$h, $s, $l] = self::hexToHsl($hex);
        $t = abs($warmth) / 100;
        $target = $warmth < 0 ? 28.0 : 215.0;
        $delta = self::shortestHueDelta($h, $target);
        $rotation = $delta * $t * 0.55;

        $s2 = $warmth < 0
            ? min(100.0, $s + 8.0 * $t)
            : max(8.0, $s - 6.0 * $t);

        return [self::hslToHex(self::wrapHue($h + $rotation), $s2, $l), $rotation];
    }

    /** چرخش Hue یک رنگ به میزان مشخص (برای رنگ‌های گرادیان و نقش‌ها) */
    public static function rotateHue(string $hex, float $rotation): string
    {
        if ($rotation == 0.0) {
            return self::normalizeHex($hex);
        }

        [$h, $s, $l] = self::hexToHsl($hex);

        return self::hslToHex(self::wrapHue($h + $rotation), $s, $l);
    }

    /** رنگِ هم‌خانواده با هیوی داده‌شده (برای مشتق‌سازی خودکار نسخهٔ تم‌شب) */
    private static function tinted(float $hue, float $saturation, float $lightness): string
    {
        return self::hslToHex(self::wrapHue($hue), $saturation, $lightness);
    }

    /**
     * طیف ۵۰..۹۵۰ حول رنگ اصلی — رنگ اصلی دقیقاً در پلهٔ ۶۰۰ می‌نشیند
     * (رنگ برندِ پنل brand-600 است: دکمهٔ اصلی/لینک‌ها/آیکن‌ها).
     */
    public static function scale(string $baseHex): array
    {
        [$h, $s, $l] = self::hexToHsl($baseHex);
        $anchor = max(38.0, min(60.0, $l));

        $lightness = [
            50  => min(98.0, $anchor + 49),
            100 => min(95.0, $anchor + 43),
            200 => min(91.0, $anchor + 36),
            300 => min(85.0, $anchor + 27),
            400 => min(76.0, $anchor + 19),
            500 => min(68.0, $anchor + 10),
            600 => $anchor,
            700 => max(30.0, $anchor - 8),
            800 => max(24.0, $anchor - 15),
            900 => max(18.0, $anchor - 22),
            950 => max(12.0, $anchor - 29),
        ];

        $saturation = [
            50  => $s * 0.40,
            100 => $s * 0.48,
            200 => $s * 0.62,
            300 => $s * 0.78,
            400 => $s * 0.92,
            500 => min(100.0, $s * 1.02),
            600 => $s,
            700 => min(100.0, $s * 1.04),
            800 => $s,
            900 => $s * 0.94,
            950 => $s * 0.88,
        ];

        $out = [];
        foreach ($lightness as $step => $light) {
            $out[$step] = self::hslToHex(
                self::wrapHue($h),
                max(12.0, min(100.0, $saturation[$step])),
                max(8.0, min(98.0, $light))
            );
        }

        return $out;
    }

    /* ========================================================
       نقش‌های رنگی — سایدبار/صفحه/متن
       ======================================================== */

    /**
     * محاسبهٔ نقش‌های رنگی (سایدبار/صفحه/متن × روشن/تم شب).
     * پالت‌های آماده: نقش‌ها از تعریف پالت + چرخش گرمی/سردی.
     * شخصی‌سازی: نقش‌ها از ورودی مدیر + مشتق‌سازی خودکار نسخهٔ تم‌شب.
     * خروجی: [side_light, side_dark, page_light, page_dark, font_light, font_dark]
     *         null = پیش‌فرض پنل (بدون بازنویسی متغیر).
     */
    private static function resolveRoles(array $palette, string $paletteKey, string $baseHex, float $rotation, ?array $extras): array
    {
        if ($paletteKey === 'custom') {
            $font = self::normalizeHexOrNull($extras['font_color'] ?? null);
            $side = self::normalizeHexOrNull($extras['sidebar_color'] ?? null);
            $page = self::normalizeHexOrNull($extras['page_color'] ?? null);

            // هیوی مشترک = هیوی رنگ اصلی — همهٔ مشتق‌ها هم‌خانواده می‌مانند
            $hue = self::hexToHsl($baseHex)[0];

            $sideDark = null;
            if ($side !== null) {
                // سایدبارِ روشن در شب به تیرهٔ هم‌خانواده می‌رود؛ سایدبارِ تیره دست‌نخورده می‌ماند
                $sideDark = self::yiq($side) >= self::SIDE_LIGHT_YIQ
                    ? self::tinted($hue, self::SIDE_DARK_S, self::SIDE_DARK_L)
                    : $side;
            }

            $pageDark = null;
            if ($page !== null) {
                $pageDark = self::yiq($page) >= self::PAGE_LIGHT_YIQ
                    ? self::tinted($hue, self::PAGE_DARK_S, self::PAGE_DARK_L)
                    : $page;
            }

            $fontDark = null;
            if ($font !== null) {
                // متنِ تیره در شب به روشنِ هم‌خانواده می‌رود تا خوانا بماند
                $fontDark = self::yiq($font) < self::FONT_DARK_YIQ
                    ? self::tinted($hue, self::FONT_DARK_S, self::FONT_DARK_L)
                    : $font;
            }

            return [$side, $sideDark, $page, $pageDark, $font, $fontDark];
        }

        $roles = $palette['roles'];

        return [
            self::rotateHue($roles['side_light'], $rotation),
            self::rotateHue($roles['side_dark'], $rotation),
            self::rotateHue($roles['page_light'], $rotation),
            self::rotateHue($roles['page_dark'], $rotation),
            null, // فونت پالت‌های آماده = پیش‌فرض پنل (خوانایی تضمینی)
            null,
        ];
    }

    /* ========================================================
       ساخت payload نهایی + خواندن/ذخیرهٔ option
       ======================================================== */

    /**
     * محاسبهٔ کامل وضعیت پوسته از ورودی‌های خام.
     * خروجی = همان JSON ذخیره‌شونده در option (layout پنل فقط مصرف‌کننده است).
     * کلیدهای خروجی با فاز ۲۱۰ یکسان‌اند تا layoutها بدون تغییر کار کنند.
     */
    public static function resolve(string $paletteKey, int $warmth, ?string $customHex = null, ?string $mode = 'light', ?array $extras = null): array
    {
        $palettes = self::indexed();
        $paletteKey = isset($palettes[$paletteKey]) ? $paletteKey : self::DEFAULT_PALETTE;
        $palette = $palettes[$paletteKey];

        $warmth = max(-100, min(100, (int) $warmth));
        $custom = self::normalizeHex($customHex, self::DEFAULT_CUSTOM);

        $rawBase = $paletteKey === 'custom' ? $custom : $palette['base'];
        [$base, $rotation] = self::warmthRotation($rawBase, $warmth);

        // پالت با طیف ثابت (زمردیِ فعلی) فقط وقتی گرمی/سردی صفر است، طیف دقیق تیلویند را می‌دهد؛
        // با چرخش گرمی/سردی، طیف مثل بقیهٔ پالت‌ها حول رنگِ شیفت‌شده ساخته می‌شود.
        if ($warmth === 0 && ! empty($palette['fixed_scale']) && is_array($palette['fixed_scale'])) {
            $scale = $palette['fixed_scale'];
        } else {
            $scale = self::scale($base);
        }

        $stops = array_map(
            fn (string $color) => self::rotateHue($color, $rotation),
            $palette['gradient'] ?? [$scale[400], $scale[600]]
        );

        // برچسب خوانا برای نشان «ذخیره‌سازی» و لاگ اکتیویتی
        $label = $paletteKey === 'custom'
            ? 'شخصی‌سازی ('.$custom.')'
            : $palette['title'];
        if ($warmth !== 0) {
            $label .= $warmth < 0 ? ' — گرم‌تر' : ' — سردتر';
        }

        [$sideLight, $sideDark, $pageLight, $pageDark, $fontLight, $fontDark] =
            self::resolveRoles($palette, $paletteKey, $base, $rotation, $extras);

        return [
            'v'              => 3,
            'palette'        => $paletteKey,
            'warmth'         => $warmth,
            'custom'         => $custom,
            'mode'           => $mode === 'dark' ? 'dark' : 'light',
            'label'          => $label,
            'base'           => $scale[600],
            'hover'          => $scale[700],
            'dark_base'      => $scale[400],
            'dark_hover'     => $scale[300],
            'scale'          => $scale,
            'gradient'       => array_values($stops),
            'gradient_angle' => (int) ($palette['gradient_angle'] ?? 135),

            /* نقش‌های رنگی — جدا برای روشن و تم شب.
               null = «پیش‌فرض پنل» و در تزریق CSS رد می‌شود. */
            'font_color'         => $fontLight,
            'font_color_dark'    => $fontDark,
            'sidebar_color'      => $sideLight,
            'sidebar_color_dark' => $sideDark,
            'page_color'         => $pageLight,
            'page_color_dark'    => $pageDark,
        ];
    }

    /** آیا وضعیت فعلی همان پیش‌فرض است؟ (برای پسوند «(پیش‌فرض)» روی برچسب) */
    public static function isDefaultState(array $payload): bool
    {
        return ($payload['palette'] ?? '') === self::DEFAULT_PALETTE
            && (int) ($payload['warmth'] ?? 0) === 0;
    }

    /** پوشهٔ پیش‌فرض پنل (زمردیِ فعلی — وقتی چیزی ذخیره نشده یا مقدار خراب است) */
    public static function defaultPayload(): array
    {
        $payload = self::resolve(self::DEFAULT_PALETTE, 0, self::DEFAULT_CUSTOM, 'light');
        $payload['label'] = 'زمرّد (پیش‌فرض)';

        return $payload;
    }

    /**
     * وضعیت فعلی پوسته از option — برای mount صفحهٔ پنل و تزریق CSS لایوت.
     * payload همیشه تازه محاسبه می‌شود (ورودی‌های ذخیره‌شده قطعی‌اند).
     */
    public static function stateFromOption(): array
    {
        $raw = '';
        try {
            $raw = (string) option(self::OPTION_KEY, '');
        } catch (\Throwable $e) {
            $raw = '';
        }

        $data = json_decode($raw, true);
        if (
            ! is_array($data)
            || empty($data['palette'])
            || ! is_string($data['palette'])
        ) {
            return [
                'palette'        => self::DEFAULT_PALETTE,
                'warmth'         => 0,
                'custom'         => self::DEFAULT_CUSTOM,
                'mode'           => 'light',
                'custom_font'    => '',
                'custom_sidebar' => '',
                'custom_page'    => '',
                'label'          => 'زمرّد (پیش‌فرض)',
                'saved'          => false,
                'payload'        => self::defaultPayload(),
            ];
        }

        // ورودی‌های شخصی‌سازی فقط برای پالت «custom» معنا دارند
        $isCustom = $data['palette'] === 'custom';

        $payload = self::resolve(
            (string) $data['palette'],
            (int) ($data['warmth'] ?? 0),
            isset($data['custom']) ? (string) $data['custom'] : self::DEFAULT_CUSTOM,
            ($data['mode'] ?? 'light') === 'dark' ? 'dark' : 'light',
            $isCustom ? [
                'font_color'    => isset($data['font_color']) ? (string) $data['font_color'] : '',
                'sidebar_color' => isset($data['sidebar_color']) ? (string) $data['sidebar_color'] : '',
                'page_color'    => isset($data['page_color']) ? (string) $data['page_color'] : '',
            ] : null
        );

        if (self::isDefaultState($payload)) {
            $payload['label'] = 'زمرّد (پیش‌فرض)';
        }

        return [
            'palette'        => $payload['palette'],
            'warmth'         => $payload['warmth'],
            'custom'         => $payload['custom'],
            'mode'           => $payload['mode'],
            'custom_font'    => $isCustom ? (string) ($payload['font_color'] ?? '') : '',
            'custom_sidebar' => $isCustom ? (string) ($payload['sidebar_color'] ?? '') : '',
            'custom_page'    => $isCustom ? (string) ($payload['page_color'] ?? '') : '',
            'label'          => $payload['label'],
            'saved'          => true,
            'payload'        => $payload,
        ];
    }
}
