<?php

namespace Themes\SaniarTheme\src\Livewire;

use App\Models\Newsletter;
use Livewire\Component;
use Illuminate\Validation\ValidationException;

/**
 * SaniarTheme — فرم خبرنامهٔ فوتر (لایووایر ۴)
 * ---------------------------------------------------------------------------
 * فاز ۱۸۵ — منطق همان NewsletterController::subscribe است ولی کاملاً داخل
 * خود تم: کلاس Themes\SaniarTheme\src\Livewire\NewsletterBox + ویو
 * front::livewire.newsletter-box + نام «saniar-newsletter-box».
 *
 * مزیت: فرم در هر ناوبری SPA (تعویض فوتر) بدون بایند مجدد jQuery کار می‌کند.
 *
 * فاز ۲۰۱ — بازخورد با کاستوم‌تست تم:
 *   • موفقیت → رویداد مرورگری «newsletter-subscribed» → showCustomToast
 *     («عضویت شما در خبرنامه با موفقیت ثبت شد»)
 *   • خطا (اعتبارسنجی/فرمت) → رویداد «newsletter-error» با پیام واضح
 *     (به‌جای سکوت یا پیام داخلی تنها)
 *   • متن «در حال ثبت…» دکمه دیگر با span های wire:loading عوض نمی‌شود
 *     (مورف‌شدن DOM باعث «گیرکردن» متن می‌شد) — ویو با CSS و :disabled
 *     جابه‌جایی متن را انجام می‌دهد که با پایان هر درخواست قطعاً بازمی‌گردد.
 */
class NewsletterBox extends Component
{
    /** ایمیل یا شمارهٔ موبایل */
    public string $contact = '';

    public bool $done = false;

    protected function rules(): array
    {
        return [
            'contact' => 'required|string|max:255|unique:newsletters,contact',
        ];
    }

    protected function messages(): array
    {
        return [
            'contact.required' => 'لطفا ایمیل یا شماره موبایل خود را وارد کنید',
            'contact.unique'   => 'این ایمیل یا شماره قبلاً ثبت شده است',
        ];
    }

    public function updatedContact(): void
    {
        $this->resetValidation('contact');
        $this->done = false;
    }

    public function submit()
    {
        $this->done = false;

        /* اعتبارسنجی با کنترل دستی خطا — تا رویداد «newsletter-error»
           با پیام درست dispatch شود و کاربر کاستوم‌تست ببیند */
        try {
            $this->validate();
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first() ?: 'لطفا ایمیل یا شماره موبایل خود را وارد کنید';

            /* باگ خطا را دستی روی فیلد بگذار تا is-invalid و متن خطا بماند */
            $this->addError('contact', $first);
            $this->dispatch('newsletter-error', message: $first);

            return;
        }

        /* چک کردن ورودی — ایمیل یا شمارهٔ موبایل (همان قاعدهٔ کنترلر) */
        $isValid = filter_var($this->contact, FILTER_VALIDATE_EMAIL)
            || preg_match('/^09[0-9]{9}$/', $this->contact) === 1;

        if (! $isValid) {
            $message = 'فرمت ایمیل یا شماره موبایل صحیح نیست';

            $this->addError('contact', $message);
            $this->dispatch('newsletter-error', message: $message);

            return;
        }

        try {
            $userAgent = request()->userAgent();

            Newsletter::create([
                'contact'      => $this->contact,
                'ip_address'   => request()->ip(),
                'user_agent'   => $userAgent,
                'device_type'  => Newsletter::getDeviceType($userAgent),
                'browser'      => Newsletter::getBrowser($userAgent),
                'os'           => Newsletter::getOS($userAgent),
                'referrer'     => request()->server('HTTP_REFERER'),
                'landing_page' => url()->previous(),
                'is_active'    => true,
            ]);
        } catch (\Throwable $e) {
            report($e);

            $message = 'خطایی رخ داده است؛ لطفاً دوباره تلاش کنید';

            $this->addError('contact', $message);
            $this->dispatch('newsletter-error', message: $message);

            return;
        }

        $this->done    = true;
        $this->contact = '';

        /* پیام موفقیت با کاستوم‌تست تم نمایش داده می‌شود (ویو: @script) */
        $this->dispatch('newsletter-subscribed');
    }

    public function render()
    {
        return view('front::livewire.newsletter-box');
    }
}
