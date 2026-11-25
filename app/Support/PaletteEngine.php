<?php

namespace App\Support;

/* ============================================================
   PaletteEngine (فاز ۲۰۸) — موتور پوستهٔ رنگی «پنل مدیریت»
   ------------------------------------------------------------
   همان زبانِ طراحیِ بخش «ظاهر و رنگ‌بندی» قالب سانیار، این‌بار
   برای خودِ پنل: ۱۲ پالت آماده + شخصی‌سازی + اسلایدر گرمی/سردی
   + طیف ۵۰..۹۵۰ + حالت پیش‌فرض روشن/تیره.

   تفاوت با نسخهٔ قالب (Themes\SaniarTheme\src\Support):
   • کلید option: «admin_appearance» (مستقل از زبان — در
     non_language_options ثبت شده) و پیش‌فرضِ پوسته = زمردیِ
     فعلیِ پنل (هیچ تغییر ظاهری بدون انتخاب مدیر رخ نمی‌دهد).
   • طیف حول رنگ اصلی با «پلهٔ ۶۰۰ = رنگ اصلی» ساخته می‌شود چون
     رنگِ برندِ پنل، پلهٔ brand-600 است (دکمه‌ها/سایدبار/تیترها).
   • پالت «زمرّد» طیفِ ثابتِ تیلویندِ زمردی دارد (fixed_scale) تا
     پنلِ بدون پوسته ذخیره‌نشده، پیکسل‌به‌پیکسل ظاهر فعلی را حفظ کند.
   • خروجی (payload) در layout پنل به‌صورت بازنویسی متغیرهای
     --color-brand-* تزریق می‌شود؛ پنل با Tailwind 4 از همین
     متغیرها استفاده می‌کند ⇒ کل پنل یک‌جا رنگ می‌گیرد.
   ============================================================ */

class PaletteEngine
{
    /** کلید option ذخیرهٔ پوستهٔ پنل (non_language_options — مستقل از زبان) */
    public const OPTION_KEY = 'admin_appearance';

    /** پالت پیش‌فرض پنل = زمردیِ فعلی (تا با استقرارِ این نسخه ظاهر عوض نشود) */
    public const DEFAULT_PALETTE = 'emerald';

    /** رنگ اصلی پیش‌فرض حالت شخصی‌سازی */
    public const DEFAULT_CUSTOM = '#D62976';

    /**
     * پالت‌های آماده — ترتیب آرایه = چیدمان گرید RTL (اولین = راست‌ترین کارت)
     * مثل بخش قالب؛ فقط پیش‌فرضِ انتخاب‌شدهٔ اولیه، زمردی است.
     */
    public static function palettes(): array
    {
        return [
            [
                'key'            => 'instagram',
                'title'          => 'گرادیان اینستاگرامی',
                'desc'           => 'اینستا - مدرن - جذاب',
                'base'           => '#D62976',
                'swatch'         => ['#F58529', '#DD2A7B', '#8134AF'],
                'gradient'       => ['#F58529', '#DD2A7B', '#8134AF', '#515BD4'],
                'gradient_angle' => 45,
            ],
            [
                'key'    => 'sapphire',
                'title'  => 'آبی سافایر',
                'desc'   => 'رنگ تکنولوژی، امنیت و اعتماد',
                'base'   => '#275FE0',
                'swatch' => ['#60A5FA', '#1D4ED8'],
            ],
            [
                'key'    => 'royal-purple',
                'title'  => 'بنفش سلطنتی',
                'desc'   => 'شیک و مدرن و لاکچری',
                'base'   => '#9333EA',
                'swatch' => ['#C084FC', '#7E22CE'],
            ],
            [
                'key'         => 'emerald',
                'title'       => 'زمرّد',
                'desc'        => 'سبز رویش و امید',
                'base'        => '#059669',
                'swatch'      => ['#34D399', '#047857'],
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
                'default'     => true,
            ],
            [
                'key'    => 'calm-ocean',
                'title'  => 'اقیانوس آرام',
                'desc'   => 'فیروزه‌ای عمیق و آرامش‌بخش',
                'base'   => '#0891B2',
                'swatch' => ['#22D3EE', '#0E7490'],
            ],
            [
                'key'    => 'midnight',
                'title'  => 'نیلی شب',
                'desc'   => 'نیلی عمیق و شبانه',
                'base'   => '#4F46E5',
                'swatch' => ['#818CF8', '#4338CA'],
            ],
            [
                'key'    => 'fiery-sunset',
                'title'  => 'غروب آتشین',
                'desc'   => 'نارنجی آتشین و پرانرژی',
                'base'   => '#EA580C',
                'swatch' => ['#FB923C', '#DC2626'],
            ],
            [
                'key'    => 'amber',
                'title'  => 'کهربای گرم',
                'desc'   => 'طلایی گرم و درخشان',
                'base'   => '#D97706',
                'swatch' => ['#F59E0B', '#B45309'],
            ],
            [
                'key'    => 'crimson',
                'title'  => 'سرخ گلگون',
                'desc'   => 'قرمز غلیظ و پرانرژی',
                'base'   => '#DC2626',
                'swatch' => ['#F87171', '#B91C1C'],
            ],
            [
                'key'    => 'coral',
                'title'  => 'مرجانی',
                'desc'   => 'سرخ گلی ملایم و شاد',
                'base'   => '#F43F5E',
                'swatch' => ['#FB7185', '#E11D48'],
            ],
            [
                'key'    => 'fuchsia',
                'title'  => 'ارغوانی تون',
                'desc'   => 'درخششی ماندگار و لوکس',
                'base'   => '#C026D3',
                'swatch' => ['#E879F9', '#A21CAF'],
            ],
            [
                'key'    => 'caspian',
                'title'  => 'فیروزه خزر',
                'desc'   => 'ترکیبی از سبز و آبی و شاداب',
                'base'   => '#0D9488',
                'swatch' => ['#2DD4BF', '#0F766E'],
            ],
            [
                'key'    => 'custom',
                'title'  => 'شخصی‌سازی',
                'desc'   => 'رنگ‌های خودت را بساز',
                'base'   => self::DEFAULT_CUSTOM,
                'swatch' => ['#8B5CF6', '#EC4899', '#F97316', '#FACC15', '#10B981', '#3B82F6'],
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
       ریاضی رنگ — HEX ↔ HSL
       ======================================================== */

    public static function normalizeHex(?string $value, string $fallback = self::DEFAULT_CUSTOM): string
    {
        $value = strtoupper(trim((string) $value));
        if (preg_match('/^#?([0-9A-F]{6})$/', $value, $m)) {
            return '#'.$m[1];
        }

        return strtoupper($fallback);
    }

    /** HEX → [h(۰..۳۶۰), s(۰..۱۰۰), l(۰..۱۰۰)] */
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

    /** چرخش Hue یک رنگ به میزان مشخص (برای رنگ‌های گرادیان) */
    public static function rotateHue(string $hex, float $rotation): string
    {
        if ($rotation == 0.0) {
            return self::normalizeHex($hex);
        }

        [$h, $s, $l] = self::hexToHsl($hex);

        return self::hslToHex(self::wrapHue($h + $rotation), $s, $l);
    }

    /**
     * طیف ۵۰..۹۵۰ حول رنگ اصلی — رنگ اصلی دقیقاً در پلهٔ ۶۰۰ می‌نشیند
     * (رنگ برندِ پنل brand-600 است: دکمهٔ اصلی/سایدبار/تیترها).
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
       ساخت payload نهایی + خواندن/ذخیرهٔ option
       ======================================================== */

    /**
     * محاسبهٔ کامل وضعیت پوسته از ورودی‌های خام.
     * خروجی = همان JSON ذخیره‌شونده در option (layout پنل فقط مصرف‌کننده است).
     */
    public static function resolve(string $paletteKey, int $warmth, ?string $customHex = null, ?string $mode = 'light'): array
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

        return [
            'v'              => 1,
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
        ];
    }

    /** آیا وضعیت فعلی همان پیش‌فرض است؟ (برای پسوند «(پیش‌فرض)» روی برچسب) */
    public static function isDefaultState(array $payload): bool
    {
        return ($payload['palette'] ?? '') === self::DEFAULT_PALETTE
            && (int) ($payload['warmth'] ?? 0) === 0
            && ($payload['mode'] ?? 'light') === 'light';
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
                'palette' => self::DEFAULT_PALETTE,
                'warmth'  => 0,
                'custom'  => self::DEFAULT_CUSTOM,
                'mode'    => 'light',
                'label'   => 'زمرّد (پیش‌فرض)',
                'saved'   => false,
                'payload' => self::defaultPayload(),
            ];
        }

        $payload = self::resolve(
            (string) $data['palette'],
            (int) ($data['warmth'] ?? 0),
            isset($data['custom']) ? (string) $data['custom'] : self::DEFAULT_CUSTOM,
            ($data['mode'] ?? 'light') === 'dark' ? 'dark' : 'light'
        );

        if (self::isDefaultState($payload)) {
            $payload['label'] = 'زمرّد (پیش‌فرض)';
        }

        return [
            'palette' => $payload['palette'],
            'warmth'  => $payload['warmth'],
            'custom'  => $payload['custom'],
            'mode'    => $payload['mode'],
            'label'   => $payload['label'],
            'saved'   => true,
            'payload' => $payload,
        ];
    }

    /** استایل گرادیان کارت پالت (نمونهٔ رنگی روی هر کارت) */
    public static function swatchStyle(array $palette): string
    {
        $stops = implode(', ', $palette['swatch']);

        return 'linear-gradient(135deg, '.$stops.')';
    }
}
