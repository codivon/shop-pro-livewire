<?php

namespace App\Livewire\Admin\Themes;

use App\Support\PaletteEngine;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * تنظیمات ظاهری پنل مدیریت (فاز ۲۱۱ — طراحی تازه)
 * ---------------------------------------------------------------------------
 * صفحهٔ مستقل در سایدبار (بخش تنظیمات ← «تنظیمات ظاهری»):
 *   • پالت‌های آماده = بسته‌های رنگیِ هماهنگِ چندنقشی: با هر انتخاب،
 *     دکمه‌ها/آیکن‌ها (رنگ اصلی)، سایدبار و صفحه — هرکدام با رنگ مخصوص
 *     خودشان — در حالت روشن و تم شب هماهنگ تغییر می‌کنند؛ نه همه‌چیز
 *     یک‌رنگ، و متن‌ها/دکمه‌ها در هر دو حالت خوانا می‌مانند.
 *   • شخصی‌سازی: وقتی پالت «شخصی‌سازی» انتخاب شود، رنگ دکمه‌ها، متن،
 *     سایدبار و صفحه جداگانه قابل تغییر است؛ نسخهٔ تم‌شبِ هر رنگ
 *     خودکار و هماهنگ ساخته می‌شود (مشتق‌سازی در PaletteEngine).
 *   • اسلایدر گرمی/سردی: کل بستهٔ رنگی (رنگ اصلی + سایدبار + صفحه)
 *     با هم گرم‌تر یا سردتر می‌شود.
 *   • حالت پیش‌فرضِ روشن/تیرهٔ پنل از جای دیگر تنظیم می‌شود (کوکی کاربر /
 *     دکمهٔ ماه) و اینجا فقط «سازگار» است — پیش‌نمایش دوحالته.
 *
 * ذخیره در option «admin_appearance» (مستقل از زبان). پیش‌نمایش زنده
 * کاملاً سمت مرورگر با ریاضیِ قرینهٔ PaletteEngine انجام می‌شود؛ پس از
 * ذخیره، متغیرها inline روی <html> اعمال می‌شوند تا کل پنل بدون رفرش
 * رنگ بگیرد. منبع حقیقتِ ذخیره‌شده همیشه PaletteEngine است.
 */
#[Title('تنظیمات ظاهری')]
class AppearanceSettings extends Component
{
    /** کلید پالت انتخاب‌شده */
    public string $palette = PaletteEngine::DEFAULT_PALETTE;

    /** گرمی/سردی: −۱۰۰ (بسیار گرم) تا +۱۰۰ (بسیار سرد) */
    public int $warmth = 0;

    /** رنگ اصلی حالت شخصی‌سازی (دکمه‌ها و تأکیدها) */
    public string $custom = PaletteEngine::DEFAULT_CUSTOM;

    /* نقش‌های شخصی‌سازی — خالی = پیش‌فرض پنل (بدون بازنویسی) */

    /** رنگ متن پنل (شخصی‌سازی) */
    public string $customFont = '';

    /** رنگ سایدبار (شخصی‌سازی) */
    public string $customSidebar = '';

    /** رنگ صفحه (شخصی‌سازی) */
    public string $customPage = '';

    /**
     * حالت پیش‌فرضِ پنل (light | dark) — از فاز ۲۱۰ حفظ شده تا پوسته‌های
     * قدیمی خراب نشوند؛ در UI جدید تنظیم نمی‌شود (از جای دیگر: کوکی کاربر).
     */
    public string $mode = 'light';

    /** برچسب وضعیت ذخیره‌شده (نشان «ذخیره‌سازی: …») */
    public string $savedLabel = '';

    /** آیا پوسته‌ای ذخیره شده است؟ */
    public bool $saved = false;

    public function mount(): void
    {
        auth('adminPanel')->user()?->can('themes.settings') ?: abort(403);

        $state = PaletteEngine::stateFromOption();

        $this->palette = $state['palette'];
        $this->warmth = (int) $state['warmth'];
        $this->custom = $state['custom'];
        $this->customFont = (string) ($state['custom_font'] ?? '');
        $this->customSidebar = (string) ($state['custom_sidebar'] ?? '');
        $this->customPage = (string) ($state['custom_page'] ?? '');
        $this->mode = $state['mode'];
        $this->savedLabel = $state['label'];
        $this->saved = $state['saved'];
    }

    /**
     * همگام‌سازی وضعیت از JS (قبل از ذخیره) — یک رفت‌وبرگشت برای همهٔ مقادیر.
     */
    public function syncState($palette = null, $warmth = null, $custom = null, $customFont = null, $customSidebar = null, $customPage = null): void
    {
        if (is_string($palette) && $palette !== '') {
            $this->palette = $palette;
        }
        if ($warmth !== null && $warmth !== '') {
            $this->warmth = max(-100, min(100, (int) $warmth));
        }
        if (is_string($custom) && $custom !== '') {
            $this->custom = PaletteEngine::normalizeHex($custom, $this->custom);
        }
        if (is_string($customFont)) {
            $this->customFont = PaletteEngine::normalizeHexOrNull($customFont) ?? '';
        }
        if (is_string($customSidebar)) {
            $this->customSidebar = PaletteEngine::normalizeHexOrNull($customSidebar) ?? '';
        }
        if (is_string($customPage)) {
            $this->customPage = PaletteEngine::normalizeHexOrNull($customPage) ?? '';
        }
    }

    /** ورودی‌های نقش‌های شخصی‌سازی برای resolve */
    private function customExtras(): array
    {
        return [
            'font_color'    => $this->customFont,
            'sidebar_color' => $this->customSidebar,
            'page_color'    => $this->customPage,
        ];
    }

    /** ذخیرهٔ پوستهٔ پنل — همان لحظه روی کل پنل اعمال می‌شود */
    public function saveSkin(): void
    {
        auth('adminPanel')->user()?->can('themes.settings') ?: abort(403);

        $previous = PaletteEngine::stateFromOption();
        $payload = PaletteEngine::resolve($this->palette, $this->warmth, $this->custom, $this->mode, $this->customExtras());

        if (PaletteEngine::isDefaultState($payload)) {
            $payload['label'] = 'زمرّد (پیش‌فرض)';
        }

        option_update(PaletteEngine::OPTION_KEY, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // لاگ اکتیویتی مطابق سبک بقیهٔ بخش‌های پنل
        $admin = auth('adminPanel')->user();
        $adminName = $admin->full_name ?? $admin->name ?? 'مدیر';

        $changes = [
            'پالت' => $payload['label'],
            'رنگ اصلی (دکمه‌ها و آیکن‌ها)' => $payload['base'],
            'گرمی/سردی' => $payload['warmth'],
            'رنگ سایدبار (روشن)' => $payload['sidebar_color'] ?? 'پیش‌فرض',
            'رنگ سایدبار (تم شب)' => $payload['sidebar_color_dark'] ?? 'پیش‌فرض',
            'رنگ صفحه (روشن)' => $payload['page_color'] ?? 'پیش‌فرض',
            'رنگ صفحه (تم شب)' => $payload['page_color_dark'] ?? 'پیش‌فرض',
            'رنگ متن (روشن)' => $payload['font_color'] ?? 'پیش‌فرض',
            'رنگ متن (تم شب)' => $payload['font_color_dark'] ?? 'پیش‌فرض',
        ];

        activity()
            ->causedBy($admin)
            ->withProperties([
                'action' => 'پوستهٔ رنگی پنل مدیریت را ذخیره کرد',
                'changes' => [
                    'from' => $previous['saved'] ? $previous['label'] : 'پیش‌فرض (زمردی)',
                    'to' => $changes,
                ],
                'ip' => request()->ip(),
            ])
            ->log("مدیر {$adminName} پوستهٔ رنگی پنل مدیریت را ذخیره کرد: «{$payload['label']}» — رنگ اصلی {$payload['base']}");

        $this->savedLabel = $payload['label'];
        $this->saved = true;

        $this->dispatch('toast', message: 'پوستهٔ «'.$payload['label'].'» ذخیره شد و روی کل پنل اعمال گردید.', type: 'success');
    }

    /** بازگشت پوستهٔ پنل به پیش‌فرض (زمردی، بدون گرمی) */
    public function resetSkin(): void
    {
        auth('adminPanel')->user()?->can('themes.settings') ?: abort(403);

        $previous = PaletteEngine::stateFromOption();

        option_update(PaletteEngine::OPTION_KEY, '');

        $admin = auth('adminPanel')->user();
        $adminName = $admin->full_name ?? $admin->name ?? 'مدیر';

        activity()
            ->causedBy($admin)
            ->withProperties([
                'action' => 'پوستهٔ رنگی پنل مدیریت به پیش‌فرض بازگشت',
                'changes' => [
                    'from' => $previous['saved'] ? $previous['label'] : 'پیش‌فرض (زمردی)',
                    'to' => 'زمرّد (پیش‌فرض)',
                ],
                'ip' => request()->ip(),
            ])
            ->log("مدیر {$adminName} پوستهٔ رنگی پنل مدیریت را به پیش‌فرض بازگرداند.");

        $default = PaletteEngine::defaultPayload();

        $this->palette = PaletteEngine::DEFAULT_PALETTE;
        $this->warmth = 0;
        $this->custom = PaletteEngine::DEFAULT_CUSTOM;
        $this->customFont = '';
        $this->customSidebar = '';
        $this->customPage = '';
        $this->mode = 'light';
        $this->savedLabel = $default['label'];
        $this->saved = false;

        // وضعیت تازه برای JS (UI انتخاب‌ها و پیش‌نمایش باید ریست شود)
        $this->dispatch('appearance-reset', palette: $this->palette, warmth: $this->warmth, custom: $this->custom, customFont: '', customSidebar: '', customPage: '', label: $this->savedLabel);

        $this->dispatch('toast', message: 'پوستهٔ پنل به حالت پیش‌فرض (زمردی) بازگشت.', type: 'info');
    }

    public function render()
    {
        // پیش‌نمایش زندهٔ سمت سرور برای رندر اولیه؛ پس از آن JS لحظه‌ای به‌روزرسانی می‌کند
        $preview = PaletteEngine::resolve($this->palette, $this->warmth, $this->custom, $this->mode, $this->customExtras());

        return view('admin-panel::livewire.admin.themes.appearance', [
            'palettes' => PaletteEngine::palettes(),
            'preview' => $preview,
        ])->layout('admin-panel::layout');
    }
}
