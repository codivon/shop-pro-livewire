<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * سیدر حجمی برای محیط توسعه/تست.
 *
 * یک فشار داده‌ای واقع‌گرایانه می‌سازد تا اندازه‌گیری سرعت پنل روی
 * دیتابیس خالی بی‌معنا نباشد: کاربر، محصول، قیمت، سفارش، آیتم سفارش،
 * تراکنش، دیدگاه، نقد و علاقه‌مندی.
 *
 * اجرا: SEED_USERS=1000 SEED_PRODUCTS=10000 php artisan db:seed --class=MassDataSeeder
 * اندازه‌ها با متغیر محیطی SEED_USERS / SEED_PRODUCTS / SEED_ORDERS قابل تنظیم‌اند.
 */
class MassDataSeeder extends Seeder
{
    /** کلیدهای سازنده‌ی عنوان‌های نمونه محصول */
    private array $adjectives = ['ویژه', 'مینی', 'حرفه‌ای', 'نسخه ۲۰۲۶', 'پرطرفدار', 'اقتصادی', 'لوکس', 'بسته ۳ عددی'];
    private array $nouns = ['هدفون', 'ماوس گیمینگ', 'کیبورد مکانیکال', 'مانیتور ۲۷ اینچ', 'پاوربانک', 'کارت گرافیک', 'پایه گوشی', 'فن لپ‌تاپ', 'کابل شارژ', 'اسپیکر بلوتوث', 'تبلت', 'ساعت هوشمند', 'گوشی موبایل', 'دوربین ورزشی', 'پرینتر لیزری', 'روتر بی‌سیم', 'هارد اکسترنال', 'فلش مموری'];
    private array $brands = ['اپل', 'سامسونگ', 'شیائومی', 'ایسوس', 'ال‌جی', 'لنوو', 'سونی', 'ایسر', 'الیت', 'جنرال'];

    private array $firstNames = ['علی', 'محمد', 'رضا', 'حسین', 'امیر', 'مهدی', 'سارا', 'فاطمه', 'زهرا', 'مریم', 'نگار', 'الهام', 'سینا', 'کامران', 'آرش', 'پویا', 'نیما', 'سانیار', 'سهراب', 'بهنام'];
    private array $lastNames = ['احمدی', 'محمدی', 'رضایی', 'کریمی', 'موسوی', 'حسینی', 'صادقی', 'نوری', 'عباسی', 'قاسمی', 'شریفی', 'طاهری', 'امینی', 'فرهادی', 'کاظمی', 'یزدانی'];

    private array $commentBodies = [
        'کیفیت ساخت خوبیه، بسته‌بندی هم سالم رسید. پیشنهاد می‌کنم.',
        'نسبت به قیمتش ارزش خرید داره ولی ارسالش یک روز دیرتر شد.',
        'بعد از دو ماه استفاده هنوز مثل روز اول کار می‌کنه.',
        'متأسفانه رنگی که فرستاده بودن با عکس فرق داشت، ولی مشکل حل شد.',
        'دقیقاً همون چیزی بود که توضیحات نوشته بود. ممنون از فروشنده.',
        'از بین نمونه‌های مشابه، این یکی منطقی‌ترین انتخابه.',
        'پشتیبانی فروشنده سریع جواب داد و راهنمایی کامل کرد.',
        'قیمتش نسبت به ماه قبل بهتر شده، موقع خرید بگیریدش.',
    ];
    private array $reviewBodies = [
        'برای استفاده روزمره عالیه. اگر جایگزین قیمتش هم پیدا کنید، قطعاً می‌ارزد.',
        'انتظار بیشتری داشتم؛ کیفیت بدنه متوسطه ولی عملکرد قابل قبوله.',
        'بعد از سه هفته استفاده، هیچ ایرادی ندیدم و راضی‌ام.',
        'ارزش خرید بالا؛ با این بودجه انتخاب درستی بود.',
    ];

    public function run()
    {
        // اندازه‌ها با متغیر محیطی قابل تنظیم‌اند: SEED_USERS / SEED_PRODUCTS / SEED_ORDERS
        $users = (int) env('SEED_USERS', 1000);
        $products = (int) env('SEED_PRODUCTS', 10000);
        $orders = (int) env('SEED_ORDERS', 3000);

        DB::statement('PRAGMA foreign_keys = OFF');

        $this->seedUsers($users);
        $this->seedProducts($products);
        $this->seedOrders($orders);
        $this->seedComments();
        $this->seedReviews();
        $this->seedFavorites();
        $this->seedWallets();

        DB::statement('PRAGMA foreign_keys = ON');
    }

    /** یک هش مشترک برای همه — در داده تست هدف فقط داشتن مقدار است */
    private function passwordHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make('12345678');
    }

    private function seedUsers(int $count): void
    {
        $exists = DB::table('users')->count();

        if ($exists >= $count) {
            $this->command->info("کاربران از قبل موجودند ({$exists}) — رد شد.");

            return;
        }

        $hash = $this->passwordHash();
        $now = now()->toDateTimeString();
        $rows = [];

        for ($i = $exists + 1; $i <= $count; $i++) {
            $first = $this->firstNames[$i % count($this->firstNames)];
            $last = $this->lastNames[$i % count($this->lastNames)];

            $rows[] = [
                'first_name' => $first,
                'last_name' => $last,
                'username' => 'user'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'email' => 'user'.str_pad((string) $i, 5, '0', STR_PAD_LEFT).'@example.test',
                'mobile' => '0912'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'level' => 'user',
                'password' => $hash,
                'status' => 1,
                'newsletter' => $i % 3 === 0 ? 1 : 0,
                'notification' => 1,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'verified_at' => $i % 10 !== 0 ? $now : null,
                'force_to_password_change' => 0,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        $this->command->info('کاربران: '.DB::table('users')->count());
    }

    private function seedProducts(int $count): void
    {
        $exists = DB::table('products')->count();

        if ($exists >= $count) {
            $this->command->info("محصولات از قبل موجودند ({$exists}) — رد شد.");

            return;
        }

        $adminId = DB::table('admins')->value('id');
        $categoryIds = DB::table('categories')->pluck('id')->all();
        $now = now()->toDateTimeString();
        $rows = [];

        for ($i = $exists + 1; $i <= $count; $i++) {
            $noun = $this->nouns[$i % count($this->nouns)];
            $adj = $i % 3 === 0 ? ' '.$this->adjectives[$i % count($this->adjectives)] : '';
            $title = $noun.' '.$this->brands[$i % count($this->brands)].$adj;

            $rows[] = [
                'product_id' => 'PRD-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'title' => $title,
                'title_en' => 'Product '.$i,
                'type' => 'physical',
                'admin_id' => $adminId,
                'category_id' => $categoryIds[$i % count($categoryIds)],
                'slug' => 'product-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'weight' => random_int(100, 5000),
                'special' => $i % 15 === 0 ? 1 : 0,
                'description' => str_repeat('توضیحات کامل محصول برای شبیه‌سازی حجم متن واقعی. ', 8),
                'short_description' => 'شبیه‌سازی توضیح کوتاه برای تست.',
                'sell' => ($i * 7) % 400,
                'view' => ($i * 13) % 9000,
                'shipping_nature' => ['small', 'medium', 'large'][$i % 3],
                'status' => $i % 50 === 0 ? 'Waiting' : 'Accept',
                'price_type' => $i % 6 === 0 ? 'multiple-price' : 'single-price',
                'published' => 1,
                'unit' => 'تعداد',
                'rating' => $i % 7,
                'lang' => 'fa',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('products')->insert($chunk);
        }

        // قیمت‌ها: هر محصول یک قیمت اصلی، هر شش‌تا یکی یک واریانت دوم
        $priceRows = [];
        $sellerId = DB::table('sellers')->value('id');
        $warehouseId = DB::table('warehouses')->value('id');

        foreach ($rows as $index => $row) {
            $number = $exists + $index + 1;
            $base = 500_000 + ($number * 37_000) % 40_000_000;
            $discount = $number % 4 === 0 ? random_int(5, 25) : 0;

            $priceRows[] = [
                'price' => $base,
                'seller_id' => $sellerId,
                'product_id' => $number,
                'discount' => $discount,
                'discount_price' => $discount > 0 ? (int) round($base * (100 - $discount) / 100) : null,
                'regular_price' => $base,
                'stock' => random_int(3, 500),
                'cart_max' => 5,
                'cart_min' => 1,
                'published' => 1,
                'ordering' => 0,
                'warehouse_id' => $warehouseId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($number % 6 === 0) {
                $priceRows[] = [
                    'price' => (int) ($base * 1.15),
                    'seller_id' => $sellerId,
                    'product_id' => $number,
                    'discount' => 0,
                    'discount_price' => null,
                    'regular_price' => (int) ($base * 1.15),
                    'stock' => random_int(0, 200),
                    'cart_max' => 5,
                    'cart_min' => 1,
                    'published' => 1,
                    'ordering' => 1,
                    'warehouse_id' => $warehouseId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($priceRows, 500) as $chunk) {
            DB::table('prices')->insert($chunk);
        }

        // اتصال دسته‌ها به محصول‌ها (لیست‌کردن دسته در فروشگاه از همین جدول واسط می‌خواند)
        $pivotRows = [];

        foreach ($rows as $index => $row) {
            $number = $exists + $index + 1;

            $pivotRows[] = [
                'category_id' => $row['category_id'],
                'product_id' => $number,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($pivotRows, 500) as $chunk) {
            DB::table('category_product')->insert($chunk);
        }

        $this->command->info('محصولات: '.DB::table('products')->count().' / قیمت‌ها: '.DB::table('prices')->count().' / اتصال دسته: '.DB::table('category_product')->count());
    }

    private function seedOrders(int $count): void
    {
        $exists = DB::table('orders')->count();

        if ($exists >= $count) {
            $this->command->info("سفارشات از قبل موجودند ({$exists}) — رد شد.");

            return;
        }

        $users = DB::table('users')->select('id', 'first_name', 'last_name', 'mobile')->limit(800)->get();
        $cities = DB::table('cities')->select('id', 'province_id')->inRandomOrder()->limit(200)->get();
        $sellerId = DB::table('sellers')->value('id');
        $products = DB::table('products')
            ->join('prices', 'prices.product_id', '=', 'products.id')
            ->where('prices.published', 1)
            ->select('products.id', 'products.title', 'prices.price', 'prices.discount', 'prices.discount_price', 'prices.id as price_id')
            ->inRandomOrder()
            ->limit(3000)
            ->get();

        if ($products->isEmpty() || $users->isEmpty() || $cities->isEmpty()) {
            $this->command->warn('داده مرجع کافی نیست — سفارش ثبت نشد.');

            return;
        }

        $statuses = ['paid', 'delivered', 'sent', 'unpaid', 'canceled'];
        $weights = [30, 25, 15, 20, 10]; // احتمال هر وضعیت
        $shippingStatuses = ['w-pending', 'w-gathering', 'gathered', 'w-post', 'posted', 'delivered'];
        $now = now();
        $orderRows = [];
        $itemRows = [];
        $transactionRows = [];

        for ($i = $exists + 1; $i <= $count; $i++) {
            $user = $users[$i % $users->count()];
            $city = $cities[$i % $cities->count()];
            $status = $statuses[$this->weightedPick($weights)];

            $items = [];
            $itemCount = 1 + ($i % 3);
            $total = 0;

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products[($i * 3 + $j * 7) % $products->count()];
                $quantity = 1 + ($j % 2);
                $unit = $product->discount_price ?? ($product->discount > 0
                    ? (int) round($product->price * (100 - $product->discount) / 100)
                    : $product->price);
                $total += $unit * $quantity;

                $items[] = [
                    'product' => $product,
                    'unit' => $unit,
                    'quantity' => $quantity,
                ];
            }

            $createdAt = $now->copy()->subDays(($i % 90))->subHours($i % 24);
            $orderRows[] = [
                'user_id' => $user->id,
                'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'مشتری تست',
                'mobile' => $user->mobile ?? '09120000000',
                'province_id' => $city->province_id,
                'city_id' => $city->id,
                'postal_code' => (string) (1000000000 + $i * 137),
                'address' => 'خیابان نمونه، کوچه '.($i % 40 + 1).', پلاک '.($i % 120 + 1),
                'shipping_cost' => 45_000,
                'price' => $total,
                'status' => $status,
                'shipping_status' => $shippingStatuses[$i % count($shippingStatuses)],
                'tracking_code' => $status !== 'unpaid' ? 'TRK'.str_pad((string) $i, 10, '0', STR_PAD_LEFT) : null,
                'gateway' => 'payir',
                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => $createdAt->toDateTimeString(),
            ];

            foreach ($items as $item) {
                $itemRows[] = [
                    'order_id' => $i,
                    'seller_id' => $sellerId,
                    'product_id' => $item['product']->id,
                    'price_id' => $item['product']->price_id,
                    'title' => $item['product']->title,
                    'price' => $item['unit'],
                    'real_price' => $item['unit'],
                    'quantity' => $item['quantity'],
                    'shipping_status' => $shippingStatuses[$i % count($shippingStatuses)],
                    'tracking_code' => $status !== 'unpaid' ? 'TRK'.str_pad((string) $i, 10, '0', STR_PAD_LEFT) : null,
                    'created_at' => $createdAt->toDateTimeString(),
                    'updated_at' => $createdAt->toDateTimeString(),
                ];
            }

            if ($status !== 'unpaid') {
                $transactionRows[] = [
                    'status' => $status === 'canceled' ? 3 : 10,
                    'amount' => $total + 45_000,
                    'transId' => 'TEST-'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                    'factorNumber' => 'ORD-'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                    'mobile' => $user->mobile ?? '09120000000',
                    'description' => 'پرداخت سفارش تستی',
                    'token' => 'tok'.str_pad((string) $i, 20, '0', STR_PAD_LEFT),
                    'transactionable_id' => $i,
                    'transactionable_type' => 'App\Models\Order',
                    'user_id' => $user->id,
                    'created_at' => $createdAt->toDateTimeString(),
                    'updated_at' => $createdAt->toDateTimeString(),
                ];
            }
        }

        foreach (array_chunk($orderRows, 500) as $chunk) {
            DB::table('orders')->insert($chunk);
        }

        foreach (array_chunk($itemRows, 500) as $chunk) {
            DB::table('order_items')->insert($chunk);
        }

        foreach (array_chunk($transactionRows, 500) as $chunk) {
            DB::table('transactions')->insert($chunk);
        }

        $this->command->info('سفارشات: '.DB::table('orders')->count().' / آیتم‌ها: '.DB::table('order_items')->count().' / تراکنش‌ها: '.DB::table('transactions')->count());
    }

    private function seedComments(): void
    {
        if (DB::table('comments')->count() >= 5000) {
            $this->command->info('دیدگاه‌ها موجودند — رد شد.');

            return;
        }

        $users = DB::table('users')->select('id')->limit(1000)->get();
        $productIds = DB::table('products')->select('id')->limit(5000)->get();
        $rows = [];
        $now = now()->toDateTimeString();

        for ($i = 1; $i <= 5000; $i++) {
            $rows[] = [
                'user_id' => $users[$i % $users->count()]->id,
                'body' => $this->commentBodies[$i % count($this->commentBodies)],
                'commentable_id' => $productIds[$i % $productIds->count()]->id,
                'commentable_type' => 'App\Models\Product',
                'status' => $i % 100 < 75 ? 'approved' : 'pending',
                'likes_count' => $i % 9,
                'dislikes_count' => $i % 4,
                'ip_address' => '10.0.'.($i % 250).'.'.($i % 250),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('comments')->insert($chunk);
        }

        // شمارندهٔ دیدگاه محصول‌ها به‌روز می‌شود تا صفحهٔ محصول واقعی باشد
        DB::statement("
            UPDATE products SET reviews_count = (
                SELECT COUNT(*) FROM comments
                WHERE comments.commentable_id = products.id
                  AND comments.commentable_type = 'App\Models\Product'
                  AND comments.status = 'approved'
            ) WHERE id IN (SELECT DISTINCT commentable_id FROM comments WHERE commentable_type = 'App\Models\Product')
        ");

        $this->command->info('دیدگاه‌ها: '.DB::table('comments')->count());
    }

    private function seedReviews(): void
    {
        if (DB::table('reviews')->count() >= 3000) {
            $this->command->info('نقد و بررسی‌ها موجودند — رد شد.');

            return;
        }

        $users = DB::table('users')->select('id')->limit(1000)->get();
        $productIds = DB::table('products')->select('id')->limit(5000)->get();
        $rows = [];
        $now = now()->toDateTimeString();

        for ($i = 1; $i <= 3000; $i++) {
            $rows[] = [
                'user_id' => $users[$i % $users->count()]->id,
                'product_id' => $productIds[$i % $productIds->count()]->id,
                'title' => 'نقد تستی شماره '.$i,
                'body' => $this->reviewBodies[$i % count($this->reviewBodies)],
                'rating' => 3 + ($i % 3),
                'suggest' => ['yes', 'no', 'dontknow'][$i % 3],
                'status' => $i % 3 === 0 ? 'pending' : 'approved',
                'likes_count' => $i % 12,
                'dislikes_count' => $i % 5,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('reviews')->insert($chunk);
        }

        $this->command->info('نقد و بررسی‌ها: '.DB::table('reviews')->count());
    }

    private function seedFavorites(): void
    {
        if (DB::table('favorites')->count() >= 2000) {
            $this->command->info('علاقه‌مندی‌ها موجودند — رد شد.');

            return;
        }

        $users = DB::table('users')->select('id')->limit(1000)->get();
        $productIds = DB::table('products')->select('id')->limit(5000)->get();
        $rows = [];
        $now = now()->toDateTimeString();

        for ($i = 1; $i <= 2000; $i++) {
            $rows[] = [
                'user_id' => $users[$i % $users->count()]->id,
                'product_id' => $productIds[($i * 3) % $productIds->count()]->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('favorites')->insert($chunk);
        }

        $this->command->info('علاقه‌مندی‌ها: '.DB::table('favorites')->count());
    }

    private function seedWallets(): void
    {
        if (DB::table('wallets')->count() >= 1000) {
            $this->command->info('کیف پول‌ها موجودند — رد شد.');

            return;
        }

        $rows = [];
        $now = now()->toDateTimeString();

        foreach (DB::table('users')->select('id')->limit(1000)->get() as $user) {
            $rows[] = [
                'user_id' => $user->id,
                'balance' => random_int(0, 2_500_000),
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('wallets')->insert($chunk);
        }

        $this->command->info('کیف پول‌ها: '.DB::table('wallets')->count());
    }

    /** انتخاب اندیس وزن‌دار برای شبیه‌سازی توزیع واقعی وضعیت سفارش */
    private function weightedPick(array $weights): int
    {
        $total = array_sum($weights);
        $rand = random_int(1, $total);
        $acc = 0;

        foreach ($weights as $index => $weight) {
            $acc += $weight;

            if ($rand <= $acc) {
                return $index;
            }
        }

        return 0;
    }
}
