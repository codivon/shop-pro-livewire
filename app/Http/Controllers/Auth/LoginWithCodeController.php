<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OneTimeCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class LoginWithCodeController extends Controller
{
    /** حداکثر تلاش برای حدس کد در هر ۱۵ دقیقه (به‌ازای هر کاربر+IP) */
    public const MAX_CODE_ATTEMPTS = 5;

    public function create(Request $request)
    {
        $view = config('front.pages.login-with-code');

        if (!$view || option('login_with_code', 'off') == 'off') {
            abort(404);
        }
        return view($view);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mobile' => 'required|exists:users,username',
            'captcha' => ['required', 'captcha'],
        ], [
            /* امنیتی: شمارهٔ کاربر داخل پیام برنمی‌گردد (نه افشای بازتاب ورودی،
             * نه منبع رندر HTML در قالب‌های فرانت) */
            'mobile.exists' => 'حساب کاربری با این شماره موبایل یافت نشد؛ لطفاً شماره را بررسی یا ابتدا ثبت‌نام کنید.'
        ]);

        $user = User::where('username', $request->mobile)->first();

        // امنیتی: شمارهٔ در انتظار تأیید فقط در سشن ثبت می‌شود — مرحلهٔ دوم از
        // فرم (فیلد مخفی) هیچ چیزی دربارهٔ هویت حساب قبول نمی‌کند
        session(['otp_pending_mobile' => $user->username]);

        PasswordResetLinkController::sendCode($user);

        return response('success');
    }

    public function confirm(Request $request)
    {
        /* امنیتی (آسیب‌پذیری سابق): کد OTP باید نسبت به شمارهٔ «سشنِ» جریان
         * اعتبارسنجی شود، نه نسبت به شماره‌ای که فرم مرحلهٔ دوم (فیلد مخفی)
         * می‌فرستد — وگرنه مهاجمی که کدِ معتبرِ حساب خودش را دارد می‌تواند
         * فیلد موبایل را به شمارهٔ قربانی تغییر دهد و وارد حساب او شود. */
        $pendingMobile = (string) session('otp_pending_mobile', '');

        if ($pendingMobile === '') {
            return response('نشست شما منقضی شده است؛ لطفاً مرحلهٔ اول (دریافت کد) را دوباره انجام دهید.', 419);
        }

        $user = User::where('username', $pendingMobile)->first();

        if (!$user) {
            session()->forget('otp_pending_mobile');

            return response('نشست ورود نامعتبر است؛ لطفاً دوباره تلاش کنید.', 419);
        }

        // امنیتی: محدودیت تلاش حدس کد OTP (ضد brute-force)
        $throttleKey = 'otp-confirm:'.sha1($user->id.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_CODE_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = (int) ceil($seconds / 60);

            return response("به دلیل تلاش‌های ناموفق زیاد، این شماره موقتاً مسدود شده است. لطفاً {$minutes} دقیقه دیگر تلاش کنید.", 429);
        }

        $time = Carbon::now()->subMinutes(15);

        $validator = validator($request->all(), [
            'verify_code'     => [
                'required',
                Rule::exists('one_time_codes', 'code')->where(function ($query) use ($user, $time) {
                    $query->where('user_id', $user->id)->where('created_at', '>=', $time);
                }),
            ]
        ], [
            'verify_code.exists' => 'کد وارد شده اشتباه است'
        ]);

        if ($validator->fails()) {
            // ثبت تلاش ناموفق برای حدس کد
            RateLimiter::hit($throttleKey, 900);

            throw new \Illuminate\Validation\ValidationException($validator);
        }

        Auth::loginUsingId($user->id, true);
        OneTimeCode::where('user_id', $user->id)->delete();
        RateLimiter::clear($throttleKey);
        session()->forget('otp_pending_mobile');

        return response('success');
    }
}
