<?php

namespace Themes\SaniarTheme\src\Livewire;

use App\Models\Newsletter;
use Livewire\Component;

/**
 * SaniarTheme — فرم خبرنامهٔ فوتر (لایووایر ۴)
 * ---------------------------------------------------------------------------
 * فاز ۱۸۵ — منطق همان NewsletterController::subscribe است ولی کاملاً داخل
 * خود تم: کلاس Themes\SaniarTheme\src\Livewire\NewsletterBox + ویو
 * front::livewire.newsletter-box + نام «saniar-newsletter-box».
 *
 * مزیت: فرم در هر ناوبری SPA (تعویض فوتر) بدون بایند مجدد jQuery کار می‌کند.
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

        $this->validate();

        /* چک کردن ورودی — ایمیل یا شمارهٔ موبایل (همان قاعدهٔ کنترلر) */
        $isValid = filter_var($this->contact, FILTER_VALIDATE_EMAIL)
            || preg_match('/^09[0-9]{9}$/', $this->contact) === 1;

        if (! $isValid) {
            $this->addError('contact', 'فرمت ایمیل یا شماره موبایل صحیح نیست');

            return;
        }

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

        $this->done    = true;
        $this->contact = '';
    }

    public function render()
    {
        return view('front::livewire.newsletter-box');
    }
}
