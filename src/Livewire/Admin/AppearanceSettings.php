<?php

namespace Themes\SaniarTheme\src\Livewire\Admin;

use Livewire\Component;
use Themes\SaniarTheme\src\Support\PaletteEngine;

/**
 * SaniarTheme — تنظیمات ظاهری قالب (فاز ۲۰۸)
 * ---------------------------------------------------------------------------
 * کارتِ کاملاً اختصاصیِ قالب در صفحهٔ «تنظیمات قالب» پنل مدیریت:
 *   • ۱۲ پالت آماده + شخصی‌سازی (کد HEX دلخواه)
 *   • اسلایدر گرمی/سردی رنگ (چرخش Hue به سمت گرم/سرد + کم‌رنگ/پررنگ)
 *   • تولید طیف ۵۰..۹۵۰ با پیش‌نمایش زنده
 *   • حالت پیش‌فرض روشن/تیرهٔ سایت
 *   • ذخیرهٔ پوسته در option «saniar_appearance» (مستقل از زبان) —
 *     فرانت همان لحظه با تزریق متغیرهای CSS در master.blade.php
 *     کل تم را با رنگ‌های جدید می‌بیند.
 *
 * پیش‌نمایش زنده کاملاً سمت مرورگر (JS داخل ویو) انجام می‌شود و
 * شبکه فقط هنگام ذخیره/بازگشت به پیش‌فرض درگیر است؛ منبع حقیقتِ
 * ذخیره‌شده همیشه PaletteEngine (PHP) است.
 *
 * این کامپوننت داخل فرمِ ThemeSettings رندر می‌شود؛ همهٔ دکمه‌هایش
 * type="button" هستند تا فرم والد submit نشود.
 */
class AppearanceSettings extends Component
{
    /** کلید پالت انتخاب‌شده (رندر سرور برای انتخابِ اولیه) */
    public string $palette = PaletteEngine::DEFAULT_PALETTE;

    /** گرمی/سردی: −۱۰۰ (بسیار گرم) تا +۱۰۰ (بسیار سرد) */
    public int $warmth = 0;

    /** رنگ اصلی حالت شخصی‌سازی */
    public string $custom = PaletteEngine::DEFAULT_CUSTOM;

    /** حالت پیش‌فرض سایت: light | dark */
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
        $this->mode = $state['mode'];
        $this->savedLabel = $state['label'];
        $this->saved = $state['saved'];
    }

    /**
     * همگام‌سازی وضعیت از JS (قبل از ذخیره) — یک رفت‌وبرگشت برای هر ۴ مقدار.
     */
    public function syncState($palette = null, $warmth = null, $custom = null, $mode = null): void
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
    }

    /** ذخیرهٔ پوسته — همان لحظه روی فرانت اعمال می‌شود */
    public function saveSkin(): void
    {
        auth('adminPanel')->user()?->can('themes.settings') ?: abort(403);

        $previous = PaletteEngine::stateFromOption();
        $payload = PaletteEngine::resolve($this->palette, $this->warmth, $this->custom, $this->mode);

        if (PaletteEngine::isDefaultState($payload)) {
            $payload['label'] = 'گرادیان اینستاگرامی (پیش‌فرض)';
        }

        option_update(PaletteEngine::OPTION_KEY, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // لاگ اکتیویتی مطابق سبک بقیهٔ بخش‌های پنل
        $admin = auth('adminPanel')->user();
        $adminName = $admin->full_name ?? $admin->name ?? 'مدیر';

        activity()
            ->causedBy($admin)
            ->withProperties([
                'action' => 'پوستهٔ رنگی قالب را ذخیره کرد',
                'changes' => [
                    'from' => $previous['saved'] ? $previous['label'] : 'پیش‌فرض',
                    'to' => [
                        'پالت' => $payload['label'],
                        'رنگ اصلی' => $payload['base'],
                        'گرمی/سردی' => $payload['warmth'],
                        'حالت پیش‌فرض' => $payload['mode'] === 'dark' ? 'تیره' : 'روشن',
                    ],
                ],
                'ip' => request()->ip(),
            ])
            ->log("مدیر {$adminName} پوستهٔ رنگی قالب را ذخیره کرد: «{$payload['label']}» — رنگ اصلی {$payload['base']}");

        $this->savedLabel = $payload['label'];
        $this->saved = true;

        $this->dispatch('toast', message: 'پوستهٔ «'.$payload['label'].'» ذخیره شد و روی کل سایت اعمال گردید.', type: 'success');
    }

    /** بازگشت به پیش‌فرض (گرادیان اینستاگرامی، بدون گرمی، حالت روشن) */
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
                'action' => 'پوستهٔ رنگی قالب به پیش‌فرض بازگشت',
                'changes' => [
                    'from' => $previous['saved'] ? $previous['label'] : 'پیش‌فرض',
                    'to' => 'گرادیان اینستاگرامی (پیش‌فرض)',
                ],
                'ip' => request()->ip(),
            ])
            ->log("مدیر {$adminName} پوستهٔ رنگی قالب را به پیش‌فرض بازگرداند.");

        $default = PaletteEngine::defaultPayload();

        $this->palette = PaletteEngine::DEFAULT_PALETTE;
        $this->warmth = 0;
        $this->custom = PaletteEngine::DEFAULT_CUSTOM;
        $this->mode = 'light';
        $this->savedLabel = $default['label'];
        $this->saved = false;

        // وضعیت تازه برای JS (UI انتخاب‌ها و طیف باید ریست شود)
        $this->dispatch('appearance-reset', palette: $this->palette, warmth: $this->warmth, custom: $this->custom, mode: $this->mode, label: $this->savedLabel);

        $this->dispatch('toast', message: 'پوستهٔ قالب به حالت پیش‌فرض بازگشت.', type: 'info');
    }

    public function render()
    {
        // طیف و رنگ‌های زندهٔ سمت سرور برای رندر اولیه؛ پس از آن JS لحظه‌ای به‌روزرسانی می‌کند
        $preview = PaletteEngine::resolve($this->palette, $this->warmth, $this->custom, $this->mode);

        return view('front::livewire.admin.appearance', [
            'palettes' => PaletteEngine::palettes(),
            'preview' => $preview,
        ]);
    }
}
