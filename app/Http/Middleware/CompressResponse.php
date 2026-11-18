<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * فشرده‌سازی پاسخ‌های متنی به‌صورت لایهٔ اطمینان (safety-net)
 * --------------------------------------------------------------------------
 * پنل مدیریت جدید صفحات سنگینی دارد (HTML چندصدکیلوبایتی) و آپدیت‌های
 * Livewire هم «کل HTML رندرشدهٔ کامپوننت» را در پاسخ JSON برمی‌گردانند.
 * روی هاست‌هایی که mod_deflate/brotli فعال ندارند (مشکل رایج هاست اشتراکی)،
 * همین بایت‌ها به‌صورت خام منتقل می‌شوند و تعامل‌های ساده (تیک، ذخیره، ناوبری)
 * چند ثانیه‌ای حس می‌شوند.
 *
 * این میدلور فقط وقتی دست به پاسخ می‌زند که:
 *   ۱) سرور قبلاً فشرده نکرده باشد (بودن Content-Encoding)؛
 *   ۲) کلاینت gzip را قبول کند؛
 *   ۳) پاسخ از نوع فایل/جریان نباشد (BinaryFile/Streamed/SSE)؛
 *   ۴) محتوا متنی باشد (HTML/JSON/SVG/JS/CSS/…) و بزرگ‌تر از آستانه.
 * با mod_deflate فعال، این میدلور عملاً بی‌اثر می‌شود (شرط ۱) و هزینه‌ای هم ندارد.
 * غیرفعال‌سازی با .env: RESPONSE_COMPRESSION=false
 */
class CompressResponse
{
    /** پاسخ‌های کوچک‌تر از این مقدار فشرده نمی‌شوند (هزینهٔ gzip > صرفهٔ انتقال) */
    protected const MIN_SIZE = 1024;

    protected const COMPRESSIBLE = [
        'text/html', 'application/json', 'text/plain', 'text/css',
        'text/javascript', 'application/javascript', 'application/x-javascript',
        'image/svg+xml', 'text/xml', 'application/xml', 'application/rss+xml',
        'text/less', 'text/scss', 'text/vtt', 'application/vnd.ms-fontobject',
        'font/opentype', 'font/ttf', 'font/x-woff', 'application/x-font-ttf',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! config('general.compress_responses', true)) {
            return $response;
        }

        // پاسخ‌های جریانی/فایلی هرگز لمس نمی‌شوند (آپلود/دانلود/SSE)
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        if ($response->headers->has('Content-Encoding')
            || ! $response->headers->has('Content-Type')
            || $response->headers->get('Content-Type') === 'text/event-stream'
        ) {
            return $response;
        }

        $contentType = strtok($response->headers->get('Content-Type'), ';');

        if (! in_array($contentType, self::COMPRESSIBLE, true)) {
            return $response;
        }

        $acceptEncoding = (string) $request->headers->get('Accept-Encoding');

        if (! str_contains($acceptEncoding, 'gzip')) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || strlen($content) < self::MIN_SIZE) {
            return $response;
        }

        $compressed = @gzencode($content, 5);

        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));

        // کش‌های میانی باید بر اساس Accept-Encoding تفکیک کنند
        if (! $response->headers->has('Vary')) {
            $response->headers->set('Vary', 'Accept-Encoding');
        }

        return $response;
    }
}
