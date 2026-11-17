<?php

namespace App\Http\Requests\Auth;

use App\Rules\NotSpecialChar;
use App\Services\Captcha\CaptchaManager;
use App\Services\SecuritySettings;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
        // کپچای ثبت‌نام از تب «امنیت» تنظیمات برنامه‌نویس کنترل می‌شود
        // فاز کپچاهای چندگانه — فیلد فرم بر اساس درایور نقطهٔ «register»:
        //  image → captcha | math → captcha_answer | ابری → captcha_token
        // قانون سفارشی «captcha:register» خودش بر اساس درایورِ همان نقطه
        // راستی‌آزمایی می‌کند (AppServiceProvider).
        $captchaRules = ['nullable'];

        if (SecuritySettings::captchaEnabled('register')) {
            $field = CaptchaManager::fieldFor(CaptchaManager::driver('register'));

            $rules = [
                'first_name'    => ['required', 'string', 'max:255', new NotSpecialChar()],
                'last_name'     => ['required', 'string', 'max:255', new NotSpecialChar()],
                'username'      => ['required', 'string', 'regex:/(09)[0-9]{9}/', 'digits:11', 'unique:users'],
                'password'      => ['required', 'string', 'min:8', 'confirmed:confirmed'],
                $field          => ['required', 'string', 'max:512', 'captcha:register'],
                'referral_code' => 'nullable|exists:users,referral_code',
            ];

            return $rules;
        }

        return [
            'first_name'    => ['required', 'string', 'max:255', new NotSpecialChar()],
            'last_name'     => ['required', 'string', 'max:255', new NotSpecialChar()],
            'username'      => ['required', 'string', 'regex:/(09)[0-9]{9}/', 'digits:11', 'unique:users'],
            'password'      => ['required', 'string', 'min:8', 'confirmed:confirmed'],
            'captcha'       => $captchaRules,
            'referral_code' => 'nullable|exists:users,referral_code'
        ];
    }

    public function messages()
    {
        $messages = [
            'username.required' => 'لطفا یک شماره موبایل معتبر وارد کنید',
            'username.string'   => 'لطفا یک شماره موبایل معتبر وارد کنید',
            'username.regex'    => 'لطفا یک شماره موبایل معتبر وارد کنید',
            'username.digits'   => 'لطفا یک شماره موبایل معتبر وارد کنید',
            'username.unique'   => 'شماره موبایل وارد شده تکراری است',
        ];

        if (SecuritySettings::captchaEnabled('register')) {
            $driver = CaptchaManager::driver('register');
            $field  = CaptchaManager::fieldFor($driver);

            $messages[$field.'.required'] = CaptchaManager::requiredMessage($driver);
            $messages[$field.'.captcha']  = CaptchaManager::failureMessage($driver);
        }

        return $messages;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'username'   => convertPersianToEnglish($this->input('username'))
        ]);
    }
}
