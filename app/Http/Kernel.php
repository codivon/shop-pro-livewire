<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class, // فعال‌سازی با متغیر TRUST_PROXIES در .env (بدون مقدار = بدون اعتماد)
        //\Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \App\Http\Middleware\BlockBots::class, // ربات‌گیر — مسدودسازی IP های ثبت‌شده در تله
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\RedirectMiddleware::class,
        \App\Http\Middleware\SecurityHeaders::class, // هدرهای امنیتی استاندارد
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\ForceHttps::class,
            \App\Http\Middleware\Language::class,
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,

            \App\Http\Middleware\CheckBlockedDevice::class,
            \App\Http\Middleware\Viewer::class,
            \App\Http\Middleware\RecordAdminSession::class,

        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\EnsureApiHeaderIsSet::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \App\Http\Middleware\EnsureUserIsVerified::class,
        'notVerified' => \App\Http\Middleware\EnsureUserIsNotVerified::class,

        // my own middlewares
        // سازگاری ماژول‌ها (رفع «Call to a member function setCookie on null» +
        //   «Target class [Admin] does not exist»): پنل مدیریت قدیمی در گذار legacy-panel
        //   حذف شد و alias «Admin» (=> App\Http\Middleware\Admin::class) همراه آن از این
        //   فایل پاک شد — اما قرارداد همهٔ پکیج‌های بازار (seoAudit، ArtificialIntelligence،
        //   DigiPay، Logistics، …) این است که روت‌های ادمین‌شان با alias «Admin» ساخته
        //   می‌شود («'Admin' میدل‌ور هسته است») و PushSubscriptionController و
        //   config/file-manager.php هم از آن استفاده می‌کنند. بدون این alias، اولین
        //   بازدید صفحهٔ هر پکیج در Pipeline با «Target class [Admin] does not exist»
        //   می‌مُرد و روت‌های پکیج عملاً غیرقابل‌استفاده می‌شدند.
        //   معادل مدرنِ همان میدل‌ور = AdminPanel (گارد adminPanel + ریدایرکت np.login)؛
        //   aliasهای «Admin» و «admin» هر دو به آن نگاشت می‌شوند تا RouteServiceProvider
        //   ماژول‌ها (که با 'admin' کوچک لود می‌کند) هم سالم بماند.
        'Admin' => \App\Http\Middleware\AdminPanel::class,
        'admin' => \App\Http\Middleware\AdminPanel::class,
        'bot.trap' => \App\Http\Middleware\BlockBots::class,
        'Seller' => \App\Http\Middleware\Seller::class,
        'CheckCreator' => \App\Http\Middleware\CheckCreator::class,
        'CheckUserExists' => \App\Http\Middleware\CheckUserExists::class,
        'CheckUserNotExists' => \App\Http\Middleware\CheckUserNotExists::class,
        'EnsureForceChange' => \App\Http\Middleware\EnsureForceChange::class,
        'CheckPasswordChange' => \App\Http\Middleware\CheckPasswordChange::class,
    ];
}
