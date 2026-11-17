<?php

namespace App\Http\Controllers;

use App\Services\Captcha\MathCaptchaService;
use App\Services\CaptchaImage;
use App\Services\CaptchaService;

class CaptchaController extends Controller
{
    public function image(CaptchaService $service)
    {
        $png = (new CaptchaImage)->render(
            $service->generate()
        );

        return response($png, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    /*
     * کپچای ریاضی پیشرفته — عین الگوی image:
     * هر بار بارگذاری تصویر، عبارت تازه ساخته و هشِ پاسخش در سشن می‌رود.
     * SVG خالص است — بدون وابستگی به GD.
     */
    public function math(MathCaptchaService $service)
    {
        $svg = $service->renderSvg();

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }
}
