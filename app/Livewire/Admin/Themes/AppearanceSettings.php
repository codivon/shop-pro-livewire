<?php

namespace App\Livewire\Admin\Themes;

use App\Support\PaletteEngine;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * تنظیمات ظاهری پنل مدیریت (فاز ۲۰۸)
 * ---------------------------------------------------------------------------
 * صفحهٔ مستقل در سایدبار (بخش تنظیمات ← «تنظیمات ظاهری»):
 *   • همان طراحی بخش ظاهری قالب سانیار: ۱۲ پالت آماده + شخصی‌سازی
 *     + اسلایدر گرمی/سردی + طیف ۵۰..۹۵۰ + پیش‌نمایش زنده + روشن/تیره
 *   • ذخیره در option «admin_appearance» (مستقل از زبان) و اعمال فوری
 *     روی کل پنل با بازنویسی متغیرهای --color-brand-* در layout پنل.
 *   • پیش‌فرض = زمردیِ فعلیِ پنل؛ هیچ تغییر ظاهری بدون ذخیرهٔ مدیر.
 *
 * پیش‌نمایش زنده کاملاً سمت مرورگر انجام می‌شود و پس از ذخیره، همان
 * متغیرها در همین صفحه به‌صورت inline روی <html> اعمال می‌شوند تا پنل
 * بدون رفرش رنگ بگیرد؛ منبع حقیقتِ ذخیره‌شده همیشه PaletteEngine است.
 */
#[Title('تنظیمات ظاهری')]
class AppearanceSettings extends Component
{
    /** کلید پالت انتخاب‌شده */
    public string $palette = PaletteEngine::DEFAULT_PALETTE;

    /** گرمی/سردی: −۱۰۰ (بسیار گرم) تا +۱۰۰ (بسیار سرد) */
    public int $warmth = 0;

    /** رنگ اصلی حالت شخصی‌سازی */
    public string $custom = PaletteEngine::DEFAULT_CUSTOM;

    /** حالت پیش‌فرض پنل: light | dark */
    public string $mode = 'light';

    /* فاز ۲۱۰ — رنگ فونت/سایدبار/صفحه؛ خالی = پیش‌فرض پنل (بدون بازنویسی) */

    /** رنگ فونت پنل در حالت روشن */
    public string $fontColor = '';

    /** رنگ فونت پنل در تم شب */
    public string $fontColorDark = '';

    /** رنگ سایدبار در حالت روشن */
    public string $sidebarColor = '';

    /** رنگ سایدبار در تم شب */
    public string $sidebarColorDark = '';

    /** رنگ صفحه در حالت روشن */
    public string $pageColor = '';

    /** رنگ صفحه در تم شب */
    public string $pageColorDark = '';

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
        $this->mode = $state['mode'];
        $this->fontColor = (string) ($state['font_color'] ?? '');
        $this->fontColorDark = (string) ($state['font_color_dark'] ?? '');
        $this->sidebarColor = (string) ($state['sidebar_color'] ?? '');
        $this->sidebarColorDark = (string) ($state['sidebar_color_dark'] ?? '');
        $this->pageColor = (string) ($state['page_color'] ?? '');
        $this->pageColorDark = (string) ($state['page_color_dark'] ?? '');
        $this->savedLabel = $state['label'];
        $this->saved = $state['saved'];
    }

    /**
     * همگام‌سازی وضعیت از JS (قبل از ذخیره) — یک رفت‌وبرگشت برای هر ۴ مقدار.
     */
    public function syncState($palette = null, $warmth = null, $custom = null, $mode = null, $fontColor = null, $fontColorDark = null, $sidebarColor = null, $sidebarColorDark = null, $pageColor = null, $pageColorDark = null): void
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
        if (is_string($mode) && in_array($mode, ['light', 'dark'], true)) {
            $this->mode = $mode;
        }

        /* فاز ۲۱۰ — رنگ‌های تکمیلی: خالی = پیش‌فرض پنل */
        if (is_string($fontColor)) {
            $this->fontColor = PaletteEngine::normalizeHexOrNull($fontColor) ?? '';
        }
        if (is_string($fontColorDark)) {
            $this->fontColorDark = PaletteEngine::normalizeHexOrNull($fontColorDark) ?? '';
        }
        if (is_string($sidebarColor)) {
            $this->sidebarColor = PaletteEngine::normalizeHexOrNull($sidebarColor) ?? '';
        }
        if (is_string($sidebarColorDark)) {
            $this->sidebarColorDark = PaletteEngine::normalizeHexOrNull($sidebarColorDark) ?? '';
        }
        if (is_string($pageColor)) {
            $this->pageColor = PaletteEngine::normalizeHexOrNull($pageColor) ?? '';
        }
        if (is_string($pageColorDark)) {
            $this->pageColorDark = PaletteEngine::normalizeHexOrNull($pageColorDark) ?? '';
        }
    }

    /** ذخیرهٔ پوستهٔ پنل — همان لحظه روی کل پنل اعمال می‌شود */
    public function saveSkin(): void
    {
        auth('adminPanel')->user()?->can('themes.settings') ?: abort(403);

        $previous = PaletteEngine::stateFromOption();
        $payload = PaletteEngine::resolve($this->palette, $this->warmth, $this->custom, $this->mode, [
            'font_color'         => $this->fontColor,
            'font_color_dark'    => $this->fontColorDark,
            'sidebar_color'      => $this->sidebarColor,
            'sidebar_color_dark' => $this->sidebarColorDark,
            'page_color'         => $this->pageColor,
            'page_color_dark'    => $this->pageColorDark,
        ]);

        if (PaletteEngine::isDefaultState($payload)) {
            $payload['label'] = 'زمرّد (پیش‌فرض)';
        }

        option_update(PaletteEngine::OPTION_KEY, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // لاگ اکتیویتی مطابق سبک بقیهٔ بخش‌های پنل
        $admin = auth('adminPanel')->user();
        $adminName = $admin->full_name ?? $admin->name ?? 'مدیر';

        activity()
            ->causedBy($admin)
            ->withProperties([
                'action' => 'پوستهٔ رنگی پنل مدیریت را ذخیره کرد',
                'changes' => [
                    'from' => $previous['saved'] ? $previous['label'] : 'پیش‌فرض (زمردی)',
                    'to' => [
                        'پالت' => $payload['label'],
                        'رنگ اصلی' => $payload['base'],
                        'گرمی/سردی' => $payload['warmth'],
                        'حالت پیش‌فرض' => $payload['mode'] === 'dark' ? 'تیره' : 'روشن',
                        'رنگ فونت (روشن)' => $payload['font_color'] ?? 'پیش‌فرض',
                        'رنگ فونت (تم شب)' => $payload['font_color_dark'] ?? 'پیش‌فرض',
                        'رنگ سایدبار (روشن)' => $payload['sidebar_color'] ?? 'پیش‌فرض',
                        'رنگ سایدبار (تم شب)' => $payload['sidebar_color_dark'] ?? 'پیش‌فرض',
                        'رنگ صفحه (روشن)' => $payload['page_color'] ?? 'پیش‌فرض',
                        'رنگ صفحه (تم شب)' => $payload['page_color_dark'] ?? 'پیش‌فرض',
                    ],
                ],
                'ip' => request()->ip(),
            ])
            ->log("مدیر {$adminName} پوستهٔ رنگی پنل مدیریت را ذخیره کرد: «{$payload['label']}» — رنگ اصلی {$payload['base']}");

        $this->savedLabel = $payload['label'];
        $this->saved = true;

        $this->dispatch('toast', message: 'پوستهٔ «'.$payload['label'].'» ذخیره شد و روی کل پنل اعمال گردید.', type: 'success');
    }

    /** بازگشت پوستهٔ پنل به پیش‌فرض (زمردی، بدون گرمی، حالت روشن) */
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
        $this->mode = 'light';
        $this->fontColor = '';
        $this->fontColorDark = '';
        $this->sidebarColor = '';
        $this->sidebarColorDark = '';
        $this->pageColor = '';
        $this->pageColorDark = '';
        $this->savedLabel = $default['label'];
        $this->saved = false;

        // وضعیت تازه برای JS (UI انتخاب‌ها و طیف باید ریست شود)
        $this->dispatch('appearance-reset', palette: $this->palette, warmth: $this->warmth, custom: $this->custom, mode: $this->mode, fontColor: '', fontColorDark: '', sidebarColor: '', sidebarColorDark: '', pageColor: '', pageColorDark: '', label: $this->savedLabel);

        $this->dispatch('toast', message: 'پوستهٔ پنل به حالت پیش‌فرض (زمردی) بازگشت.', type: 'info');
    }

    public function render()
    {
        // طیف و رنگ‌های زندهٔ سمت سرور برای رندر اولیه؛ پس از آن JS لحظه‌ای به‌روزرسانی می‌کند
        $preview = PaletteEngine::resolve($this->palette, $this->warmth, $this->custom, $this->mode, [
            'font_color'         => $this->fontColor,
            'font_color_dark'    => $this->fontColorDark,
            'sidebar_color'      => $this->sidebarColor,
            'sidebar_color_dark' => $this->sidebarColorDark,
            'page_color'         => $this->pageColor,
            'page_color_dark'    => $this->pageColorDark,
        ]);

        return view('admin-panel::livewire.admin.themes.appearance', [
            'palettes' => PaletteEngine::palettes(),
            'preview' => $preview,
        ])->layout('admin-panel::layout');
    }
}
