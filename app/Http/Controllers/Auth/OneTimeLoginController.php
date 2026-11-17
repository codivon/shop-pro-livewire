<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OneTimeCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OneTimeLoginController extends Controller
{
    /** حداکثر تلاش برای حدس کد OTP در هر ۱۵ دقیقه (به‌ازای هر کاربر+IP) */
    public const MAX_CODE_ATTEMPTS = 5;

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|exists:users,username'
        ]);

        if ($validator->fails()) {
            abort(404);
        }

        $user = User::where('username', $request->mobile)->first();
        $verify_code = OneTimeCode::where('user_id', $user->id)->latest()->first();

        if (!$verify_code) {
            return redirect()->route('password.request');
        }

        // امنیتی: شمارهٔ در انتظار تأیید فقط در سشن — مرحلهٔ POST از فرم ملاک نیست
        session(['otp_pending_mobile' => $user->username]);

        $resend_time = $verify_code->created_at->addSeconds(120)->timestamp;

        if (session()->has('forget_password_link') and session('forget_password_link') == $request->mobile) {
            $view = config('front.pages.change-password', 'front::auth.change-password');
        } else {
            $view = config('front.pages.one-time-login', 'front.pages.one-time-login');
        }

        return view($view, compact('resend_time', 'user'));
    }

    public function store(Request $request)
    {
        /* امنیتی (آسیب‌پذیری سابق): کد OTP نسبت به شمارهٔ «سشنِ» جریان
         * اعتبارسنجی می‌شود، نه شمارهٔ فرم — تغییر فیلد مخفی موبایل هیچ
         * اثری روی هویت حساب هدف ندارد. */
        $pendingMobile = (string) session('otp_pending_mobile', '');

        if ($pendingMobile === '') {
            return response('نشست شما منقضی شده است؛ لطفاً درخواست کد را دوباره انجام دهید.', 419);
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

        $user->update([
            'force_to_password_change' => true,
        ]);

        Auth::loginUsingId($user->id, true);
        OneTimeCode::where('user_id', $user->id)->delete();
        RateLimiter::clear($throttleKey);
        session()->forget('otp_pending_mobile');

        return response('success');
    }
}
