<?php

namespace App\Support;

use Illuminate\Support\Facades\View;

/**
 * تشخیص «پنل فعال»
 * --------------------------------------------------------------------------
 * تا هر ماژول ActivePanel خودش را داشت (Modules\X\Support\ActivePanel)
 * و فقط ویوهایی که خودشان @extends شرطی داشتند دو-پنلی می‌شدند. مشکل عملی:
 * نسخه‌های «قدیمی» پکیج‌ها که روی سرورهای کاربر نصب است هیچ شرطی ندارند و همیشه
 * back.layouts.master (کروم قدیمی) را رندر می‌کنند.
 *
 * حالا «میزبان» تصمیم می‌گیرد: هر دو لایه‌ی میزبان (back.layouts.master و
 * admin-panel::layouts.module) قبل از انتخاب chrome از همین کلاس می‌پرسند؛
 * بنابراین حتی پکیج‌های قدیمی هم داخل کروم پنل فعال رندر می‌شوند و برای
 * پروژه‌های آینده هم هیچ قراردادی روی پکیج تحمیل نمی‌شود.
 *
 * پس از حذف کامل پنل قدیمی: همیشه کروم پنل جدید.
 */
class ActivePanel
{
    /**
     * پنل جدید (Livewire) — پس از حذف کامل پنل قدیمی، همیشه فعال است.
     * (قبلاً با ?ap=old / آدرس admin-old / کوکی ap_panel قابل تغییر بود)
     */
    public static function useNewPanel(): bool
    {
        return true;
    }

    /** کروم پنل جدید برای صفحات کلاسیک وجود دارد؟ (پروژه‌های آینده ممکن است نداشته باشند) */
    public static function hasNewChrome(): bool
    {
        return View::exists('admin-panel::layouts.module-new');
    }

    /**
     * لایه‌ی «واقعی»ای که باید @extends شود؛ صفحات کلاسیک (پکیج‌ها + صفحات قدیمی
     * خود میزبان) از این استفاده می‌کنند تا فقط «یک» نقطه تصمیم وجود داشته باشد.
     * همیشه به لایه‌ی واقعی اشاره می‌کند (نه wrapper) تا ریکورشن رخ ندهد.
     */
    public static function layoutToExtend(): string
    {
        if (self::hasNewChrome()) {
            return 'admin-panel::layouts.module-new';
        }

        return 'admin-panel::layouts.module';
    }
}
