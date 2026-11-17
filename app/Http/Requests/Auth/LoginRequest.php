<?php

namespace App\Http\Requests\Auth;

use App\Services\Captcha\CaptchaManager;
use App\Services\Captcha\MathCaptchaService;
use App\Services\CaptchaService;
use App\Services\SecuritySettings;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'username' => 'required|string',
            'password' => 'required|string',
        ];

        // نقطهٔ «customer_login»: فیلد فرم بر اساس
        // درایور انتخابی (image/math/ابری)؛ راستی‌آزمایی واقعی در authenticate().
        if (SecuritySettings::captchaEnabled('customer_login')) {
            $field = CaptchaManager::fieldFor(CaptchaManager::driver('customer_login'));

            $rules[$field] = ['required', 'string', 'max:512'];
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [];

        if (SecuritySettings::captchaEnabled('customer_login')) {
            $driver = CaptchaManager::driver('customer_login');
            $field  = CaptchaManager::fieldFor($driver);

            $messages[$field.'.required'] = CaptchaManager::requiredMessage($driver);
        }

        return $messages;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate()
    {
        $this->ensureIsNotRateLimited();

        // ─── راستی‌آزمایی نقطهٔ «customer_login» ───
        // بعد از validate (حضور فیلد) و قبل از تلاش ورود؛ درایورمحور.
        if (SecuritySettings::captchaEnabled('customer_login')) {
            $captchaDriver = CaptchaManager::driver('customer_login');

            if (! CaptchaManager::verifyDriver($captchaDriver, CaptchaManager::requestValue($captchaDriver, $this))) {
                app(CaptchaService::class)->flush();
                app(MathCaptchaService::class)->flush();

                throw ValidationException::withMessages([
                    CaptchaManager::fieldFor($captchaDriver) => CaptchaManager::failureMessage($captchaDriver),
                ]);
            }
        }

        if (! Auth::attempt($this->only('username', 'password'), $this->filled('remember'))) {
            if (SecuritySettings::lockoutEnabled()) {
                RateLimiter::hit(
                    $this->throttleKey(),
                    SecuritySettings::lockoutWindowSeconds('customer')
                );
            }

            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited()
    {
        // سقف و پنجره از تب «امنیت» تنظیمات برنامه‌نویس؛ خاموش بودن کلید اصلی = بدون قفل
        $maxAttempts = SecuritySettings::lockoutEnabled()
            ? SecuritySettings::lockoutMax('customer')
            : 0;

        if ($maxAttempts > 0 && RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            event(new Lockout($this));

            $seconds = RateLimiter::availableIn($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */
    public function throttleKey()
    {
        return Str::lower($this->input('username')).'|'.$this->ip();
    }
}
