<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// ساخت مدیر پیش‌فرض برای محیط تست (admin / 12345678)
// سطح creator: صفحهٔ «تنظیمات برنامه‌نویس» با این سطح باز می‌شود
if (! DB::table('admins')->where('username', 'admin')->exists()) {
    $id = DB::table('admins')->insertGetId([
        'first_name' => 'مدیر',
        'last_name' => 'سایت',
        'username' => 'admin',
        'email' => 'admin@example.test',
        'level' => 'creator',
        'status' => 'ACTIVE',
        'password' => Hash::make('12345678'),
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    DB::table('admin_role')->insert([
        'role_id' => 1,
        'admin_id' => $id,
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ]);

    echo "Admin created: admin / 12345678\n";
} else {
    echo "Admin already exists\n";
}

// فروشندهٔ تستی برای قیمت‌های سبد چندفروشندگی
if (! DB::table('sellers')->where('slug', 'test-seller')->exists()) {
    $pdo = DB::connection()->getPdo();
    $columns = [];
    $stmt = $pdo->query("PRAGMA table_info(sellers)");

    foreach ($stmt as $col) {
        $columns[] = $col['name'];
    }

    // ستون‌های NOT NULL بدون پیش‌فرض باید مقدار بگیرند
    $data = [
        'created_at' => now()->toDateTimeString(),
        'updated_at' => now()->toDateTimeString(),
    ];

    $stmt = $pdo->query("PRAGMA table_info(sellers)");

    foreach ($stmt as $col) {
        if ($col['notnull'] && $col['dflt_value'] === null && $col['name'] !== 'id') {
            $data[$col['name']] = match (true) {
                str_contains($col['type'], 'varchar') || str_contains($col['type'], 'text') => 'فروشنده تست',
                default => 1,
            };
        }
    }

    if (in_array('title', $columns)) {
        $data['title'] = 'فروشنده تستی';
    }
    if (in_array('name', $columns)) {
        $data['name'] = 'فروشنده تستی';
    }
    if (in_array('slug', $columns)) {
        $data['slug'] = 'test-seller';
    }
    if (in_array('status_documents', $columns)) {
        // وضعیت مدارک: تاییدشده تا محصولات فروشنده قابل خرید باشند
        $data['status_documents'] = 'Accept';
    }
    if (in_array('status', $columns)) {
        $data['status'] = 'ACTIVE';
    }

    try {
        DB::table('sellers')->insert($data);
        echo "Seller created: test-seller\n";
    } catch (\Throwable $e) {
        echo "Seller create failed: ".$e->getMessage()."\n";
    }
} else {
    echo "Seller already exists\n";
}
