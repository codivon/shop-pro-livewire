<?php

namespace App\Services\Captcha;

use App\Services\SecuritySettings;
use Illuminate\Support\Facades\Session;

/*
 * کپچای ریاضی پیشرفته — درایور «math»
 * ==================================================================
 * برخلاف کپچاهای معمول «۲ + ۳ = ?»، عبارت چندعملیاتی با پرانتز تولید
 * می‌شود — مثل «(۷ + ۴) × ۵ − ۸» — و به‌صورت SVG با ارقام فارسی، نویز،
 * چرخش و جابه‌جایی عمودی نمایش داده می‌شود (بدون وابستگی به GD؛ SVG
 * خالص است و روی هر میزبان با هر فونتی رندر می‌شود).
 *
 * امنیت: فقط هشِ پاسخ در سشن نگه داشته می‌شود (هرگز پاسخ خام)، پاسخ
 * یک‌بارمصرف است، انقضای ۵ دقیقه دارد و سقف تلاش دارد — عینِ قرارداد
 * CaptchaService تصویری. ورودی با ارقام فارسی/عربی/انگلیسی پذیرفته
 * می‌شود.
 */
class MathCaptchaService
{
    private const TTL          = 300; // ۵ دقیقه اعتبار
    private const MAX_ATTEMPTS = 4;   // حداکثر تلاش با یک عبارت

    public const SESSION_KEY = 'captcha_math';

    /** فارسی/عربی → انگلیسی برای نرمال‌سازی پاسخ کاربر */
    private const DIGIT_MAP = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /** انگلیسی → فارسی برای نمایش عبارت */
    private const PERSIAN_MAP = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ];

    private const NEON = [
        [96, 165, 250], [52, 211, 153], [244, 114, 182],
        [251, 191, 36], [167, 139, 250], [56, 189, 248],
    ];

    /**
     * تولید عبارت جدید + ذخیرهٔ هش پاسخ در سشن + بازگرداندن SVG آمادهٔ نمایش.
     * الگوی «تصویر = چالش» دقیقاً مثل CaptchaController::image است.
     */
    public function renderSvg(): string
    {
        [$expression, $answer] = $this->build();

        Session::put(self::SESSION_KEY, [
            'hash'     => $this->hash((string) $answer),
            'expires'  => now()->addSeconds(self::TTL)->getTimestamp(),
            'attempts' => 0,
        ]);

        return $this->svg($expression);
    }

    public function verify(?string $value): bool
    {
        $data = Session::get(self::SESSION_KEY);

        if (! is_array($data) || ! isset($data['hash'], $data['expires'])) {
            return false;
        }

        if (now()->getTimestamp() > $data['expires']) {
            $this->flush();

            return false;
        }

        // شمارش تلاش — ضد brute-force
        $data['attempts'] = ($data['attempts'] ?? 0) + 1;
        Session::put(self::SESSION_KEY, $data);

        if ($data['attempts'] > self::MAX_ATTEMPTS) {
            $this->flush();

            return false;
        }

        $ok = hash_equals($data['hash'], $this->hash($this->normalize($value)));

        if ($ok) {
            $this->flush(); // یک‌بارمصرف
        }

        return $ok;
    }

    public function flush(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /* ================= ساخت عبارت پیچیده ================= */

    /**
     * عبارت چندعملیاتی با پرانتز (۲ تا ۴ عملوند).
     *
     * قیدها برای «قابل حل برای انسان، سخت برای اسکریپت»:
     *  • تفریق فقط وقتی سمت چپ بزرگ‌تر است (پاسخ منفی/صفر نداریم)
     *  • ضرب فقط وقتی هر دو سمت کوچک‌اند (پاسخِ ذهنی‌شدنی)
     *  • پرانتز فقط دور زیرعبارت‌های مرکب — اولویت عملگرها همیشه درست می‌ماند
     *
     * @return array{0: string, 1: int} [عبارت با ارقام انگلیسی، پاسخ صحیح]
     */
    private function build(): array
    {
        $operands = SecuritySettings::mathOperands();

        for ($try = 0; $try < 50; $try++) {
            [$text, $value] = $this->buildNode($operands);

            if ($value >= 0 && $value <= 9999) {
                return [$text, $value];
            }
        }

        // fallback قطعی — همیشه سازگار
        $a = random_int(11, 39);
        $b = random_int(2, 9);
        $c = random_int(2, 7);

        return ["({$a} + {$b}) × {$c}", ($a + $b) * $c];
    }

    /**
     * گرهٔ بازگشتی عبارت.
     *
     * @return array{0: string, 1: int, 2: bool} [متن، مقدار، آیا مرکب است (برای پرانتز)]
     */
    private function buildNode(int $n): array
    {
        if ($n <= 1) {
            $v = random_int(2, 12);

            return [(string) $v, $v, false];
        }

        $leftCount  = random_int(1, $n - 1);
        $rightCount = $n - $leftCount;

        [$lt, $lv, $lc] = $this->buildNode($leftCount);
        [$rt, $rv, $rc] = $this->buildNode($rightCount);

        // انتخاب عملگر با قیدها
        $ops = ['+'];
        if ($lv > $rv) {
            $ops[] = '-'; // پاسخ همیشه ≥ ۱
        }
        if ($lv <= 12 && $rv <= 9 && $lv * $rv <= 108) {
            $ops[] = '×';
        }
        $op = $ops[random_int(0, count($ops) - 1)];

        $value = match ($op) {
            '+' => $lv + $rv,
            '-' => $lv - $rv,
            default => $lv * $rv,
        };

        // پرانتز فقط دور زیرعبارتِ مرکب
        $ltOut = $lc ? "({$lt})" : $lt;
        $rtOut = $rc ? "({$rt})" : $rt;

        return ["{$ltOut} {$op} {$rtOut}", $value, true];
    }

    /* ================= نرمال‌سازی و هش ================= */

    /** فارسی/عربی → انگلیسی + حذف هر جزء غیرعددی */
    private function normalize(?string $value): string
    {
        $value = strtr(trim((string) $value), self::DIGIT_MAP);

        return preg_replace('/[^0-9]/', '', $value) ?? '';
    }

    private function hash(?string $answer): string
    {
        // نمک با کلید اپ — حتی با افشای سشن، برگرداندن پاسخ ممکن نیست
        return hash('sha256', config('app.key').'|'.trim((string) $answer));
    }

    /* ================= رندر SVG ================= */

    /**
     * SVG با پس‌زمینهٔ تیره، نویز خط/دایرهٔ نئونی، ارقام فارسی،
     * چرخش و جابه‌جایی عمودی هر گلیف.
     */
    private function svg(string $expression): string
    {
        $w    = 320;
        $h    = 76;
        $text = strtr($expression, self::PERSIAN_MAP);

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $count = max(1, count($chars));
        $slot  = ($w - 48) / $count;

        $glyphs = '';
        foreach ($chars as $i => $ch) {
            $p     = self::NEON[random_int(0, count(self::NEON) - 1)];
            $color = sprintf('#%02x%02x%02x', $p[0], $p[1], $p[2]);
            $x     = 24 + $slot * $i + random_int(-2, 3);
            $y     = (int) ($h / 2) + random_int(-6, 6);
            $angle = random_int(-16, 16);
            $size  = ($ch === '(' || $ch === ')') ? 30 : random_int(30, 36);

            $glyphs .= sprintf(
                '<text x="%.1f" y="%d" text-anchor="middle" transform="rotate(%d %.1f %d)" font-size="%d" font-weight="700" fill="%s" fill-opacity="0.92">%s</text>',
                $x,
                $y,
                $angle,
                $x,
                $y,
                $size,
                $color,
                htmlspecialchars($ch, ENT_QUOTES, 'UTF-8')
            );
        }

        // نویز: قوس/خط نئونی + نقطه
        $noise = '';
        for ($i = 0; $i < 4; $i++) {
            $p       = self::NEON[random_int(0, count(self::NEON) - 1)];
            $color   = sprintf('#%02x%02x%02x', $p[0], $p[1], $p[2]);
            $x1      = random_int(-15, $w + 15);
            $y1      = random_int(-10, $h + 10);
            $x2      = random_int(-15, $w + 15);
            $y2      = random_int(-10, $h + 10);
            $opacity = random_int(18, 40) / 100;
            $width   = random_int(1, 2);

            $noise .= ($i % 2 === 0)
                ? sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-opacity="%.2f" stroke-width="%d"/>', $x1, $y1, $x2, $y2, $color, $opacity, $width)
                : sprintf('<circle cx="%d" cy="%d" r="%d" fill="none" stroke="%s" stroke-opacity="%.2f" stroke-width="%d"/>', random_int(0, $w), random_int(0, $h), random_int(8, 26), $color, $opacity, $width);
        }
        for ($i = 0; $i < 26; $i++) {
            $g     = random_int(90, 190);
            $noise .= sprintf('<circle cx="%d" cy="%d" r="1.2" fill="#%02x%02x%02x" fill-opacity="%.2f"/>', random_int(0, $w), random_int(0, $h), $g, $g, $g, random_int(25, 55) / 100);
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}" role="img" aria-label="کپچای ریاضی">
  <defs>
    <linearGradient id="capMathBg" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#0f172a"/>
      <stop offset="1" stop-color="#1e293b"/>
    </linearGradient>
  </defs>
  <rect width="{$w}" height="{$h}" fill="url(#capMathBg)" rx="12"/>
  {$noise}
  <g font-family="Vazirmatn, Vazir, IRANSans, Tahoma, Arial, sans-serif">{$glyphs}</g>
</svg>
SVG;
    }
}
