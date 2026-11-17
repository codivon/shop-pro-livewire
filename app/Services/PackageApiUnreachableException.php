<?php

namespace App\Services;

use RuntimeException;
use Throwable;

/**
 * سخت‌سازی امنیتی لایسنس — استثناهای نوع‌دار برای تمایز دقیق دو حالت:
 *
 *  ۱) PackageApiUnreachableException — سرور «در دسترس نیست» (قطع شبکه، تایم‌اوت، 5xx)
 *     ⇒ کلاینت باید وارد «مهلت» (grace) شود، نه بستن فوری.
 *
 *  ۲) PackageApiRejectedException — سرور «دسترس‌پذیر بود و رد کرد» (4xx کسب‌وکاری:
 *     توکن غلط، دامنهٔ غیرمجاز، …) ⇒ رد قطعی؛ پکیج باید بسته شود.
 *
 * قبلاً هر دو حالت RuntimeException یکسان بودند و گارد نمی‌توانست تصمیم درست بگیرد.
 */
class PackageApiUnreachableException extends RuntimeException
{
    public function __construct(string $message = 'ارتباط با سرور پکیج‌ها برقرار نشد.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
