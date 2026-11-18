<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Support\IconRegistry;
use Illuminate\Http\JsonResponse;

/**
 * دادهٔ انتخابگر آیکون.
 *
 * قبلاً کل نقشهٔ آیکون‌ها (نزدیک یک‌چهارم مگابایت) داخل خود صفحهٔ منوها
 * و فرم‌ها به‌صورت JSON اینلاین رندر می‌شد و حجم HTML و اولین رندر را
 * سنگین می‌کرد. حالا این داده فقط وقتی پیکر واقعاً باز شود یک‌بار
 * واکشی می‌شود و در مرورگر برای کل روز کش است.
 */
class IconDataController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $lucide = [];

        foreach (IconRegistry::lucide() as $name => $inner) {
            $lucide[] = ['name' => $name, 'inner' => $inner];
        }

        return response()
            ->json([
                'fa' => array_keys(IconRegistry::fa()),
                'mdi' => array_keys(IconRegistry::mdi()),
                'lucide' => $lucide,
            ])
            // محتوای این پاسخ فقط با تغییر نسخه عوض می‌شود؛ مرورگر یک روز کش می‌کند
            ->header('Cache-Control', 'public, max-age=86400')
            // ETag نسخه‌محور: با هر انتشار مقدار عوض می‌شود و کش باطل می‌شود
            ->setEtag(md5(app()->version().'|icons'));
    }
}
