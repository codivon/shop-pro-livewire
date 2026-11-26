<?php

namespace App\Support;

/* ============================================================
   PaletteEngine (فاز ۲۱۲) — موتور پوستهٔ رنگی «پنل مدیریت»
   ------------------------------------------------------------
   نسخهٔ ۴ — بازطراحی کامل پالت‌ها بر اساس بازخورد مدیر:
   پالت‌های قبلی (فاز ۲۱۱) فقط «تِینِ لطیفِ» نزدیک به سفید داشتند
   و تغییرشان روی پنل محسوس نبود. در این نسخه هر پالتِ آماده
   «کل توکن‌های پنل» را با هم عوض می‌کند — در روشن و تم شب:
     • brand-50..950            → دکمه‌ها، لینک‌ها، آیکن‌ها، حالت فعال
     • surface / surface-2 / 3  → کارت‌ها، رنگ صفحه، هاورها
     • line                     → همهٔ خط‌ها و حاشیه‌ها
     • ink / ink-2 / ink-3      → متن‌ها (اصلی/متوسط/کم‌رنگ)
     • night / night-1..3       → صفحه و کارت‌ها و هاورها در تم شب
     • night-line / night-ink-2 → خط‌ها و متن‌ها در تم شب
     • سایدبار = رنگ عمیقِ مدرن (در روشن هم تیرهٔ شیک؛ متنِ آن
       خودکار با کنتراست YIQ روشن می‌شود) + نسخهٔ مخصوص تم شب.
   ⇒ با انتخاب هر پالت، «کل پنل» — سایدبار، صفحه، کارت‌ها،
     خط‌ها، متن‌ها، دکمه‌ها و آیکن‌ها — یک‌جا و هماهنگ رنگ می‌گیرد؛
     اما همه‌چیز یک‌رنگ نمی‌شود و متن‌ها/دکمه‌ها در روشن و تیره
     به‌صورت خودکار خوانا می‌مانند (همهٔ زوج‌های کنتراست با اسکریپت
     طراحی، قبل از انتشار تایید شده‌اند).

   پالت «زمرّد» = پیش‌فرض پنل — توکن‌ها عیناً مقادیر فعلی admin.css
   است تا بدون انتخاب مدیر، هیچ تغییری در ظاهر پنل رخ ندهد.

   گرمی/سردی: هیوِ «کل بسته» (طیف + توکن‌ها + سایدبار + صفحه)
   با هم و هماهنگ می‌چرخد — گرم به سمت ۲۸° و سرد به سمت ۲۱۵°.

   شخصی‌سازی (پالت custom): مدیر رنگ دکمه‌ها/متن/سایدبار/صفحه را
   جدا انتخاب می‌کند؛ توکن‌های باقی (خط‌ها، هاورها، متن متوسط و
   کم‌رنگ) و نسخهٔ تم‌شبِ همهٔ نقش‌ها خودکار و هماهنگ ساخته می‌شود:
     • سایدبار روشن‌تر از YIQ۱۴۰ ⇒ در شب تیرهٔ هم‌خانواده
     • صفحهٔ روشن‌تر از YIQ۱۲۰ ⇒ در شب تیرهٔ هم‌خانواده
     • متنِ تیره‌تر از YIQ۱۵۰ ⇒ در شب روشنِ هم‌خانواده
   • رنگی که خودش تیره است در تم شب دست‌نخورده می‌ماند.

   خروجی resolve() همان JSON ذخیره‌شونده در option است؛ کلیدهای
   فاز ۲۱۰/۲۱۱ همگی حفظ شده‌اند (سازگاری لاگ‌ها) و کلید تازهٔ
   «tokens» برای تزریق کامل توکن‌ها در لایوت‌ها اضافه شده است.
   ============================================================ */

class PaletteEngine
{
    /** کلید option ذخیرهٔ پوستهٔ پنل (non_language_options — مستقل از زبان) */
    public const OPTION_KEY = 'admin_appearance';

    /** پالت پیش‌فرض پنل = زمردیِ فعلی (تا با استقرار ظاهر عوض نشود) */
    public const DEFAULT_PALETTE = 'emerald';

    /** رنگ اصلی پیش‌فرض حالت شخصی‌سازی */
    public const DEFAULT_CUSTOM = '#D62976';

    /* پیش‌فرض‌های نقش‌های پنل (= مقادیر فعلی پنل؛ برای شخصی‌سازی و
       «پیش‌فرض» بودن پالت زمردی) */

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

    /** رنگ صفحه در حالت روشن — نسخهٔ قدیمی (سازگاری) */
    public const DEFAULT_PAGE_COLOR_DARK = '#0D1219';

    /* دستورهای مشتق‌سازی توکن‌ها از هیؤ هر پالت (فاز ۲۱۲) —
       قرینهٔ دقیق در JS صفحهٔ تنظیمات پیاده شده است */

    /** کارت‌ها/سطوح روشن — همیشه سفید تمیز روی صفحهٔ رنگی */
    public const LIGHT_SURFACE = '#FFFFFF';

    /** هاور (surface-3) و خط‌ها (line) و متن‌ها در حالت روشن */
    public const SURFACE3_S = 42.0;
    public const SURFACE3_L = 94.0;
    public const LINE_S     = 34.0;
    public const LINE_L     = 90.0;
    public const INK_S      = 32.0;
    public const INK_L      = 15.0;
    public const INK2_S     = 20.0;
    public const INK2_L     = 38.0;
    public const INK3_S     = 16.0;
    public const INK3_L     = 50.0;

    /** توکن‌های تم شب (کمی رنگ‌گرفته، نه خاکستریِ خام) */
    public const NIGHT_S       = 42.0;
    public const NIGHT_L       = 5.5;
    public const NIGHT1_S      = 38.0;
    public const NIGHT1_L      = 7.5;
    public const NIGHT2_S      = 33.0;
    public const NIGHT2_L      = 10.5;
    public const NIGHT3_S      = 29.0;
    public const NIGHT3_L      = 14.5;
    public const NIGHTLINE_S   = 26.0;
    public const NIGHTLINE_L   = 21.5;
    public const NIGHTINK_S    = 46.0;
    public const NIGHTINK_L    = 92.0;
    public const NIGHTINK2_S   = 22.0;
    public const NIGHTINK2_L   = 66.0;

    /** سایدبار در تم شب — عمیق‌تر از کارت‌ها تا جدا دیده شود */
    public const SIDE_NIGHT_S  = 36.0;
    public const SIDE_NIGHT_L  = 8.0;

    /* آستانه‌های مشتق‌سازی خودکار نسخهٔ تم‌شب (شخصی‌سازی) */

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
     * پالت‌های آماده — هر پالت یک بستهٔ رنگیِ کامل و مدرن است.
     *   base        = رنگ اصلی (پلهٔ ۶۰۰ طیف؛ دکمه‌ها/لینک‌ها/آیکن‌ها)
     *   scale       = طیف ثابتِ دست‌تنظیم‌شده (زیبا و حرفه‌ای)
     *   side_light  = سایدبار در حالت روشن — رنگ عمیقِ مدرن
     *   page_light  = رنگ صفحه در حالت روشن — شستوی رنگیِ لطیف
     *   توکن‌های دیگر (خط‌ها/متن‌ها/تم شب) از هیؤ رنگ اصلی مشتق می‌شوند.
     * ترتیب آرایه = چیدمان گرید RTL (اولین = راست‌ترین کارت).
     */
    public static function palettes(): array
    {
        return [
            [
                'key'       => 'emerald',
                'title'     => 'زمرّد',
                'desc'      => 'سبزِ رویش — پیش‌فرض پنل',
                'base'      => '#059669',
                'side_light' => '#FFFFFF',
                'page_light' => '#F6F7F8',
                // طیف ثابت تیلویند زمردی — ظاهرِ فعلی پنل، پیکسل‌به‌پیکسل حفظ می‌شود
                'scale' => [
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
                // توکن‌های ثابت = عیناً مقادیر فعلی admin.css (پیش‌فرض پنل)
                'fixed_tokens' => [
                    'light' => [
                        'surface'  => '#FFFFFF',
                        'surface_2' =>  '#F6F7F8',
                        'surface_3' =>  '#EEF0F2',
                        'line'     => '#E5E8EC',
                        'ink'      => '#1A2333',
                        'ink_2'       =>  '#5B6472',
                        'ink_3'       =>  '#8B93A1',
                    ],
                    'dark' => [
                        'night'     => '#0D1219',
                        'night_1'    =>  '#10161F',
                        'night_2'    =>  '#111827',
                        'night_3'    =>  '#161F2E',
                        'night_line' =>  '#223047',
                        'night_ink'  =>  '#E5EAF2',
                        'night_ink_2' =>  '#9AA7BA',
                    ],
                    'side_night' => '#111827',
                ],
                'default' => true,
            ],
            [
                'key'        => 'indigo',
                'title'      => 'ایندیگو',
                'desc'       => 'نیلیِ مدرن و استودیویی',
                'base'       => '#4F46E5',
                'side_light' => '#1E1B4B',
                'page_light' => '#EEF1FC',
                'scale' => [
                    50  => '#EEF2FF',
                    100 => '#E0E7FF',
                    200 => '#C7D2FE',
                    300 => '#A5B4FC',
                    400 => '#818CF8',
                    500 => '#6366F1',
                    600 => '#4F46E5',
                    700 => '#4338CA',
                    800 => '#3730A3',
                    900 => '#312E81',
                    950 => '#1E1B4B',
                ],
            ],
            [
                'key'        => 'ocean',
                'title'      => 'اقیانوس',
                'desc'       => 'آبیِ عمیق و اعتماد',
                'base'       => '#2563EB',
                'side_light' => '#172554',
                'page_light' => '#EBF3FC',
                'scale' => [
                    50  => '#EFF6FF',
                    100 => '#DBEAFE',
                    200 => '#BFDBFE',
                    300 => '#93C5FD',
                    400 => '#60A5FA',
                    500 => '#3B82F6',
                    600 => '#2563EB',
                    700 => '#1D4ED8',
                    800 => '#1E40AF',
                    900 => '#1E3A8A',
                    950 => '#172554',
                ],
            ],
            [
                'key'        => 'midnight',
                'title'      => 'نیمه‌شب',
                'desc'       => 'آسیش‌آبیِ آرام و روشن',
                'base'       => '#0369A1',
                'side_light' => '#082F49',
                'page_light' => '#EAF4FB',
                'scale' => [
                    50  => '#F0F9FF',
                    100 => '#E0F2FE',
                    200 => '#BAE6FD',
                    300 => '#7DD3FC',
                    400 => '#38BDF8',
                    500 => '#0EA5E9',
                    600 => '#0369A1',
                    700 => '#075985',
                    800 => '#0C4A6E',
                    900 => '#0A3A5C',
                    950 => '#082F49',
                ],
            ],
            [
                'key'        => 'teal',
                'title'      => 'فیروزه',
                'desc'       => 'سبزآبیِ تازه و شاداب',
                'base'       => '#0F766E',
                'side_light' => '#042F2E',
                'page_light' => '#E9F6F5',
                'scale' => [
                    50  => '#F0FDFA',
                    100 => '#CCFBF1',
                    200 => '#99F6E4',
                    300 => '#5EEAD4',
                    400 => '#2DD4BF',
                    500 => '#14B8A6',
                    600 => '#0F766E',
                    700 => '#115E59',
                    800 => '#134E4A',
                    900 => '#0F423E',
                    950 => '#042F2E',
                ],
            ],
            [
                'key'        => 'forest',
                'title'      => 'جنگل',
                'desc'       => 'سبزِ طبیعی و بالنده',
                'base'       => '#15803D',
                'side_light' => '#052E16',
                'page_light' => '#EBF6ED',
                'scale' => [
                    50  => '#F0FDF4',
                    100 => '#DCFCE7',
                    200 => '#BBF7D0',
                    300 => '#86EFAC',
                    400 => '#4ADE80',
                    500 => '#22C55E',
                    600 => '#15803D',
                    700 => '#166534',
                    800 => '#14532D',
                    900 => '#11451F',
                    950 => '#052E16',
                ],
            ],
            [
                'key'        => 'violet',
                'title'      => 'بنفش',
                'desc'       => 'بنفشِ رویایی و خلاق',
                'base'       => '#7C3AED',
                'side_light' => '#2E1065',
                'page_light' => '#F2EFFC',
                'scale' => [
                    50  => '#F5F3FF',
                    100 => '#EDE9FE',
                    200 => '#DDD6FE',
                    300 => '#C4B5FD',
                    400 => '#A78BFA',
                    500 => '#8B5CF6',
                    600 => '#7C3AED',
                    700 => '#6D28D9',
                    800 => '#5B21B6',
                    900 => '#4C1D95',
                    950 => '#2E1065',
                ],
            ],
            [
                'key'        => 'plum',
                'title'      => 'ارغوان',
                'desc'       => 'بنفشِ مجلل و لوکس',
                'base'       => '#9333EA',
                'side_light' => '#3B0764',
                'page_light' => '#F7EFFA',
                'scale' => [
                    50  => '#FAF5FF',
                    100 => '#F3E8FF',
                    200 => '#E9D5FF',
                    300 => '#D8B4FE',
                    400 => '#C084FC',
                    500 => '#A855F7',
                    600 => '#9333EA',
                    700 => '#7E22CE',
                    800 => '#6B21A8',
                    900 => '#581C87',
                    950 => '#3B0764',
                ],
            ],
            [
                'key'        => 'rose',
                'title'      => 'سرخ لاکچری',
                'desc'       => 'سرخِ شیک و پرشور',
                'base'       => '#E11D48',
                'side_light' => '#4C0519',
                'page_light' => '#FCEFF2',
                'scale' => [
                    50  => '#FFF1F2',
                    100 => '#FFE4E6',
                    200 => '#FECDD3',
                    300 => '#FDA4AF',
                    400 => '#FB7185',
                    500 => '#F43F5E',
                    600 => '#E11D48',
                    700 => '#BE123C',
                    800 => '#9F1239',
                    900 => '#881337',
                    950 => '#4C0519',
                ],
            ],
            [
                'key'        => 'sunset',
                'title'      => 'غروب آتشین',
                'desc'       => 'نارنجیِ گرم و پرانرژی',
                'base'       => '#C2410C',
                'side_light' => '#431407',
                'page_light' => '#FBF2E9',
                'scale' => [
                    50  => '#FFF7ED',
                    100 => '#FFEDD5',
                    200 => '#FED7AA',
                    300 => '#FDBA74',
                    400 => '#FB923C',
                    500 => '#F97316',
                    600 => '#C2410C',
                    700 => '#9A3412',
                    800 => '#7C2D12',
                    900 => '#6A2610',
                    950 => '#431407',
                ],
            ],
            [
                'key'        => 'golden',
                'title'      => 'کهربا',
                'desc'       => 'طلاییِ گرم و درخشان',
                'base'       => '#B45309',
                'side_light' => '#451A03',
                'page_light' => '#FAF3E7',
                'scale' => [
                    50  => '#FFFBEB',
                    100 => '#FEF3C7',
                    200 => '#FDE68A',
                    300 => '#FCD34D',
                    400 => '#FBBF24',
                    500 => '#F59E0B',
                    600 => '#B45309',
                    700 => '#92400E',
                    800 => '#78350F',
                    900 => '#652D0C',
                    950 => '#451A03',
                ],
            ],
            [
                'key'        => 'graphite',
                'title'      => 'گرافیت',
                'desc'       => 'خاکستریِ مینیمال و حرفه‌ای',
                'base'       => '#475569',
                'side_light' => '#0F172A',
                'page_light' => '#F1F3F7',
                'scale' => [
                    50  => '#F8FAFC',
                    100 => '#F1F5F9',
                    200 => '#E2E8F0',
                    300 => '#CBD5E1',
                    400 => '#94A3B8',
                    500 => '#64748B',
                    600 => '#475569',
                    700 => '#334155',
                    800 => '#1E293B',
                    900 => '#0F172A',
                    950 => '#020617',
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
       ریاضی رنگ — HEX ↔ HSL ↔ YIQ ↔ MIX
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

    /** مخلوط دو رنگ RGB (t = سهم رنگ دوم) — برای مشتق‌های نرم */
    public static function mix(string $hexA, string $hexB, float $t): string
    {
        [$r1, $g1, $b1] = self::hexToRgb($hexA);
        [$r2, $g2, $b2] = self::hexToRgb($hexB);

        return sprintf('#%02X%02X%02X',
            (int) round($r1 + ($r2 - $r1) * $t),
            (int) round($g1 + ($g2 - $g1) * $t),
            (int) round($b1 + ($b2 - $b1) * $t)
        );
    }

    /** رنگِ هم‌خانواده با هیوی داده‌شده (برای مشتق‌سازی توکن‌ها) */
    private static function tinted(float $hue, float $saturation, float $lightness): string
    {
        return self::hslToHex(self::wrapHue($hue), $saturation, $lightness);
    }

    /**
     * طیف ۵۰..۹۵۰ حول رنگ اصلی — رنگ اصلی دقیقاً در پلهٔ ۶۰۰ می‌نشیند
     * (رنگ برندِ پنل brand-600 است: دکمهٔ اصلی/لینک‌ها/آیکن‌ها).
     * فقط برای حالت شخصی‌سازی استفاده می‌شود؛ پالت‌های آماده طیف
     * دست‌تنظیم‌شدهٔ خودشان را دارند.
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
       مشتق‌سازی توکن‌های کامل پنل از هیؤ پالت (فاز ۲۱۲)
       ======================================================== */

    /**
     * توکن‌های کامل روشن/شب از هیؤ داده‌شده + لنگرهای پالت.
     * قرینهٔ دقیق همین دستورها در JS صفحهٔ تنظیمات پیاده شده است.
     *
     * $hue          = هیؤ مؤثر (بعد از چرخش گرمی/سردی)
     * $pageLight    = لنگر رنگ صفحه (روشن)
     * $fontLight    = لنگر رنگ متن روشن (null = از هیو مشتق شود)
     */
    private static function deriveTokens(float $hue, string $pageLight, ?string $fontLight = null): array
    {
        $ink = $fontLight !== null
            ? $fontLight
            : self::tinted($hue, self::INK_S, self::INK_L);

        // متنِ متوسط و کم‌رنگ از خودِ متنِ اصلی مشتق می‌شود تا هم‌خانواده بمانند
        [$ih, $is, $il] = self::hexToHsl($ink);
        $ink2 = self::tinted($ih, min(60.0, $is * 0.6), max(20.0, min(70.0, $il * 2.4 + 8)));
        $ink3 = self::tinted($ih, min(55.0, $is * 0.5), max(30.0, min(78.0, $il * 3.0 + 14)));

        return [
            'light' => [
                'surface'  => self::LIGHT_SURFACE,
                'surface_2' =>  $pageLight,
                'surface_3' =>  self::tinted($hue, self::SURFACE3_S, self::SURFACE3_L),
                'line'     => self::tinted($hue, self::LINE_S, self::LINE_L),
                'ink'      => $ink,
                'ink_2'       =>  $ink2,
                'ink_3'       =>  $ink3,
            ],
            'dark' => [
                'night'     => self::tinted($hue, self::NIGHT_S, self::NIGHT_L),
                'night_1'    =>  self::tinted($hue, self::NIGHT1_S, self::NIGHT1_L),
                'night_2'    =>  self::tinted($hue, self::NIGHT2_S, self::NIGHT2_L),
                'night_3'    =>  self::tinted($hue, self::NIGHT3_S, self::NIGHT3_L),
                'night_line' =>  self::tinted($hue, self::NIGHTLINE_S, self::NIGHTLINE_L),
                'night_ink'  =>  self::tinted($hue, self::NIGHTINK_S, self::NIGHTINK_L),
                'night_ink_2' =>  self::tinted($hue, self::NIGHTINK2_S, self::NIGHTINK2_L),
            ],
            'side_night' => self::tinted($hue, self::SIDE_NIGHT_S, self::SIDE_NIGHT_L),
        ];
    }

    /* ========================================================
       نقش‌های رنگی — سایدبار/صفحه/متن
       ======================================================== */

    /**
     * نقش‌های حالت شخصی‌سازی از ورودی مدیر + مشتق‌سازی خودکار نسخهٔ تم‌شب.
     * خروجی: [side, sideDark, page, pageDark, font, fontDark]
     *         null = پیش‌فرض پنل (بدون بازنویسی متغیر).
     */
    private static function resolveCustomRoles(string $baseHex, ?array $extras): array
    {
        $font = self::normalizeHexOrNull($extras['font_color'] ?? null);
        $side = self::normalizeHexOrNull($extras['sidebar_color'] ?? null);
        $page = self::normalizeHexOrNull($extras['page_color'] ?? null);

        // هیوی مشترک = هیوی رنگ اصلی — همهٔ مشتق‌ها هم‌خانواده می‌مانند
        $hue = self::hexToHsl($baseHex)[0];

        // سایدبارِ روشن در شب به تیرهٔ هم‌خانواده می‌رود؛ سایدبارِ تیره دست‌نخورده می‌ماند
        $sideDark = null;
        if ($side !== null) {
            $sideDark = self::yiq($side) >= self::SIDE_LIGHT_YIQ
                ? self::tinted($hue, self::SIDE_DARK_S, self::SIDE_DARK_L)
                : $side;
        }

        // صفحهٔ روشن در شب به تیرهٔ هم‌خانواده می‌رود
        $pageDark = null;
        if ($page !== null) {
            $pageDark = self::yiq($page) >= self::PAGE_LIGHT_YIQ
                ? self::tinted($hue, self::PAGE_DARK_S, self::PAGE_DARK_L)
                : $page;
        }

        // متنِ تیره در شب به روشنِ هم‌خانواده می‌رود تا خوانا بماند
        $fontDark = null;
        if ($font !== null) {
            $fontDark = self::yiq($font) < self::FONT_DARK_YIQ
                ? self::tinted($hue, self::FONT_DARK_S, self::FONT_DARK_L)
                : $font;
        }

        return [$side, $sideDark, $page, $pageDark, $font, $fontDark];
    }

    /* ========================================================
       ساخت payload نهایی + خواندن/ذخیرهٔ option
       ======================================================== */

    /**
     * محاسبهٔ کامل وضعیت پوسته از ورودی‌های خام.
     * خروجی = همان JSON ذخیره‌شونده در option (layout پنل فقط مصرف‌کننده است).
     * کلیدهای فاز ۲۱۰/۲۱۱ حفظ شده‌اند + کلید تازهٔ «tokens».
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

        $hue = self::hexToHsl($base)[0];

        /* طیف برند — پالت‌های آماده طیف ثابتِ دست‌تنظیم دارند؛ با گرمی/سردی
           همهٔ پله‌ها با هم می‌چرخند. شخصی‌سازی طیف حول رنگ اصلی می‌سازد. */
        if ($paletteKey === 'custom') {
            $scale = self::scale($base);
        } else {
            $scale = [];
            foreach ($palette['scale'] as $step => $stepHex) {
                $scale[$step] = self::rotateHue($stepHex, $rotation);
            }
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

        /* ==================================================
           توکن‌های کامل پنل + نقش‌های سه‌گانه (سازگاری فاز ۲۱۰)
           ================================================== */
        if ($paletteKey === 'custom') {
            // ── شخصی‌سازی: لنگرها = انتخاب مدیر یا پیش‌فرض پنل ──
            [$side, $sideDark, $page, $pageDark, $font, $fontDark] =
                self::resolveCustomRoles($base, $extras);

            // توکن‌های روشن: صفحه و متنِ انتخابی مدیر (یا پیش‌فرض پنل) +
            // خط‌ها و هاورها از هیؤ رنگ اصلی (هم‌خانوادهٔ برند)
            $ink = $font ?? self::DEFAULT_FONT_COLOR;
            [$ih, $is, $il] = self::hexToHsl($ink);

            $tokens = [
                'light' => [
                    'surface'  => self::LIGHT_SURFACE,
                    'surface_2' =>  $page ?? self::DEFAULT_PAGE_COLOR,
                    'surface_3' =>  self::tinted($hue, self::SURFACE3_S, self::SURFACE3_L),
                    'line'     => self::tinted($hue, self::LINE_S, self::LINE_L),
                    'ink'      => $ink,
                    'ink_2'       =>  self::tinted($ih, min(60.0, $is * 0.6), max(20.0, min(70.0, $il * 2.4 + 8))),
                    'ink_3'       =>  self::tinted($ih, min(55.0, $is * 0.5), max(30.0, min(78.0, $il * 3.0 + 14))),
                ],
                'dark' => [
                    'night'     => $pageDark ?? self::tinted($hue, self::NIGHT_S, self::NIGHT_L),
                    'night_1'    =>  self::tinted($hue, self::NIGHT1_S, self::NIGHT1_L),
                    'night_2'    =>  self::tinted($hue, self::NIGHT2_S, self::NIGHT2_L),
                    'night_3'    =>  self::tinted($hue, self::NIGHT3_S, self::NIGHT3_L),
                    'night_line' =>  self::tinted($hue, self::NIGHTLINE_S, self::NIGHTLINE_L),
                    'night_ink'  =>  $fontDark ?? self::tinted($hue, self::NIGHTINK_S, self::NIGHTINK_L),
                    'night_ink_2' =>  self::tinted($hue, self::NIGHTINK2_S, self::NIGHTINK2_L),
                ],
            ];

            $payloadSide      = $side;
            $payloadSideDark  = $sideDark;
        } else {
            // ── پالت‌های آماده: توکن‌های دست‌تنظیم (زمردی) یا مشتق از هیو ──
            if (isset($palette['fixed_tokens']) && $rotation == 0.0) {
                // پیش‌فرضِ بدون گرمی/سردی = عیناً مقادیر فعلی پنل (پیکسل‌به‌پیکسل)
                $tokens = [
                    'light' => $palette['fixed_tokens']['light'],
                    'dark'  => $palette['fixed_tokens']['dark'],
                ];
                $sideDark = $palette['fixed_tokens']['side_night'];
            } elseif (isset($palette['fixed_tokens'])) {
                // زمردی با گرمی/سردی — کل توکن‌های ثابت با هم می‌چرخند
                $tokens = [
                    'light' => array_map(fn (string $c) => self::rotateHue($c, $rotation), $palette['fixed_tokens']['light']),
                    'dark'  => array_map(fn (string $c) => self::rotateHue($c, $rotation), $palette['fixed_tokens']['dark']),
                ];
                $sideDark = self::rotateHue($palette['fixed_tokens']['side_night'], $rotation);
            } else {
                // توکن‌ها از هیؤ مؤثر (بعد از چرخش گرمی/سردی) مشتق می‌شوند —
                // پس نسخهٔ شبِ سایدبار هم بدون چرخشِ دوباره از همین‌جا می‌آید
                $tokens = self::deriveTokens($hue, self::rotateHue($palette['page_light'], $rotation));
                $sideDark = $tokens['side_night'];
            }

            $side = self::rotateHue($palette['side_light'], $rotation);

            $payloadSide      = $side;
            $payloadSideDark  = $sideDark;
            $font             = null;
            $fontDark         = null;
        }

        return [
            'v'              => 4,
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

            /* نقش‌های رنگی — سازگاری با کلیدهای فاز ۲۱۰/۲۱۱ (لاگ اکتیویتی).
               شخصی‌سازی: null یعنی «پیش‌فرض پنل». */
            'font_color'         => $paletteKey === 'custom' ? ($font ?? null) : null,
            'font_color_dark'    => $paletteKey === 'custom' ? ($fontDark ?? null) : null,
            'sidebar_color'      => $payloadSide,
            'sidebar_color_dark' => $payloadSideDark,
            'page_color'         => $tokens['light']['surface_2'],
            'page_color_dark'    => $tokens['dark']['night'],

            /* فاز ۲۱۲ — توکن‌های کامل پنل (روشن + شب) */
            'tokens' => [
                'light' => $tokens['light'],
                'dark'  => $tokens['dark'],
            ],

            /* متنِ خوانای سایدبار (مشتق از خود رنگ سایدبار با کنتراست YIQ)
               شخصی‌سازی بدون انتخابِ سایدبار = null یعنی متنِ پیش‌فرض پنل */
            'side_ink'      => $payloadSide !== null ? self::contrastInk($payloadSide) : null,
            'side_ink_dark' => $payloadSideDark !== null ? self::contrastInk($payloadSideDark) : null,
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
