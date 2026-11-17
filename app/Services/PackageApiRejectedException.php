<?php

namespace App\Services;

use RuntimeException;

/**
 * رد قطعی سمت سرور بازار — 4xx کسب‌وکاری (توکن غلط، دامنهٔ غیرمجاز، …).
 * سرور در دسترس بود ولی این درخواست را رد کرد ⇒ پکیج باید بسته شود.
 */
class PackageApiRejectedException extends RuntimeException
{
    /** کد وضعیت HTTP پاسخ رد */
    public readonly int $status;

    /** بدنهٔ JSON پاسخ (در صورت قابل‌تجزیه بودن) */
    public readonly ?array $body;

    public function __construct(string $message, int $status = 400, ?array $body = null)
    {
        parent::__construct($message, $status);

        $this->status = $status;
        $this->body   = $body;
    }
}
