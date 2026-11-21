<?php

use App\Http\Controllers\Back\PackagePaymentController;
use App\Http\Controllers\Back\ProvinceController;
use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

require __DIR__ . '/auth.php';
Route::get('province/get-cities', [ProvinceController::class, 'getCities'])->name('provinces.get-cities');

/*
| نصب‌کننده: اجرای مرحله‌به‌مرحله‌ی دیتابیس با AJAX (رفع صفحه‌ی سیاه/Timeout روی هاست)
| محافظت با همان middleware «install» پکیج (پس از نصب، 403 می‌دهد).
*/
Route::middleware('install')->group(function () {
    Route::get('install/migrate', [\App\Http\Controllers\Install\MigrateController::class, 'index'])->name('install.migrate');
    Route::get('install/migrate-step', [\App\Http\Controllers\Install\MigrateController::class, 'step'])->name('install.migrate-step');
});

/*
| ثبت‌نام اولین مدیر (صفحه‌ی جدید هم‌سبک با پنل جدید)
| پس از اتمام نصب (جداول + سیدر)، دکمه‌ی صفحه‌ی «نصب کامل شد» مدیر را به همین
| مسیر هدایت می‌کند. با ساخت اولین مدیر، این صفحه به‌صورت خودکار 404 می‌شود.
*/
Route::get('setup', \App\Livewire\Admin\Auth\Register::class)
    ->name('register')
    ->middleware(['CheckUserNotExists']);
Route::get('register', fn () => redirect()->route('register'))->name('register.legacy');

// Push Subscriptions
Route::post('subscriptions', [PushSubscriptionController::class, 'update']);
Route::post('subscriptions/delete', [PushSubscriptionController::class, 'destroy']);

// Manifest file (optional if VAPID is used)
// از Closure به کنترلر منتقل شد تا route:cache در پروداکشن ممکن شود
Route::get('manifest.json', [
    \App\Http\Controllers\SystemUtilityController::class,
    'manifest',
]);


/*
|--------------------------------------------------------------------------
| ربات‌گیر (Bot Trap)
|--------------------------------------------------------------------------
| مسیرهای طعمه: اسکنرهای خودکار و ربات‌های مخرب همیشه این مسیرها را پیمایش
| می‌کنند؛ هیچ کاربر واقعی‌ای به آن‌ها سر نمی‌زند. هر ضربه = مسدودسازی IP.
| این مسیرها در robots.txt هم Disallow شده‌اند (ربات‌های مطیع می‌آیند و تله می‌شوند).
*/
Route::match(['get', 'post'], 'bot-trap/hit', [\App\Http\Controllers\BotTrapController::class, 'catch'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

foreach ([
    'wp-login.php', 'wp-admin', 'wp-admin/', 'wp-content', 'wp-includes', 'wordpress',
    'xmlrpc.php', 'phpmyadmin', 'pma', 'adminer.php', 'wp-config.php', 'wp-settings.php',
    '.env', '.git/config', 'phpinfo.php', 'info.php', 'shell.php', 'eval-stdin.php',
    'config.json', 'composer.json.bak', '.aws/credentials', 'cgi-bin', 'telescope',
    'vendor/phpunit/phpunit/src/Util/PHP/eval-stdin.php', 'actuator/gateway/logout',
] as $trapPath) {
    Route::match(['get', 'post'], $trapPath, [\App\Http\Controllers\BotTrapController::class, 'catch']);
}

// به‌روزرسانی خودکار csrf برای اسکریپت‌های داخلی — دیگر توکن به بیرون درز نمی‌کند
// از Closure به کنترلر منتقل شد تا route:cache در پروداکشن ممکن شود
Route::get('refresh-csrf', [\App\Http\Controllers\SystemUtilityController::class, 'refreshCsrf'])
    ->name('csrf')->middleware('throttle:30,1');

// فاز ۱۹۴ — پینگ زنده‌نگه‌داشتن نشست (ضد «لاگ‌اوت خودکار بعد از چند دقیقه بی‌اکشنی»):
// هر درخواستِ دارای سشن، last activity را تازه می‌کند؛ توکن CSRF تازه هم برمی‌گردد
Route::get('session/ping', [\App\Http\Controllers\SystemUtilityController::class, 'sessionPing'])
    ->name('session.ping')->middleware('throttle:10,1');

Route::get('/captcha/image', [\App\Http\Controllers\CaptchaController::class, 'image'])
    ->name('captcha.image')
    ->middleware('throttle:120,1'); // ضد درخواست‌های انبوه

// کپچای ریاضی پیشرفته — عین الگوی captcha.image
Route::get('/captcha/math', [\App\Http\Controllers\CaptchaController::class, 'math'])
    ->name('captcha.math')
    ->middleware('throttle:120,1');

/*Route::post('/bale/webhook', [BaleWebhookController::class, 'handle'])
    ->name('bale.webhook');*/
Route::get('packages/payment/callback', [PackagePaymentController::class, 'callback'])->name('admin.packages.payment.callback');


