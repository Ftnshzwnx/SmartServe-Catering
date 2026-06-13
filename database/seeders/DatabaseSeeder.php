<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Only attempt legacy migration if in local environment
        $dbExists = false;
        if (app()->environment('local')) {
            $legacyDb = 'azilinacatering';
            $host = 'localhost';
            $user = 'root';
            $pass = '';

            try {
                // Set a short timeout (2s) to prevent hanging if MySQL is offline/busy
                $pdo = new \PDO("mysql:host=$host", $user, $pass, [
                    \PDO::ATTR_TIMEOUT => 2,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]);
                $dbExists = $pdo->query("SHOW DATABASES LIKE '$legacyDb'")->rowCount() > 0;
            } catch (\Exception $e) {
                $dbExists = false;
            }
        }

        if ($dbExists) {
            $conn = new \mysqli($host, $user, $pass, $legacyDb);

            // 1. Migrate Packages
            $packages = $conn->query("SELECT * FROM package")->fetch_all(MYSQLI_ASSOC);
            foreach ($packages as $pkg) {
                DB::table('packages')->insert([
                    'id' => $pkg['package_id'],
                    'package_name' => $pkg['package_name'],
                    'price' => $pkg['price'],
                    'min_order' => $pkg['min_order'],
                    'image' => $pkg['image'],
                    'description' => $pkg['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 2. Migrate Addons
            $addons = $conn->query("SELECT * FROM package_addons")->fetch_all(MYSQLI_ASSOC);
            foreach ($addons as $addon) {
                DB::table('addons')->updateOrInsert(
                    ['addon_name' => $addon['addon_name']],
                    [
                        'price_per_pax' => $addon['price_per_pax'],
                        'active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // 3. Migrate Users (Customers)
            $users = $conn->query("SELECT * FROM users")->fetch_all(MYSQLI_ASSOC);
            foreach ($users as $u) {
                // Check if email already exists to prevent duplicate key errors
                $existing = DB::table('users')->where('email', $u['email'])->exists();
                if ($existing) continue;

                DB::table('users')->insert([
                    'id' => $u['user_id'],
                    'name' => $u['username'],
                    'full_name' => $u['full_name'],
                    'email' => $u['email'],
                    'password' => (password_get_info($u['password'])['algo'] === 0) 
                        ? Hash::make($u['password']) 
                        : $u['password'],
                    'phone' => $u['phone'],
                    'address' => $u['address'],
                    'role' => 'customer',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 4. Migrate Admins to unified users table
            $admins = $conn->query("SELECT * FROM admin")->fetch_all(MYSQLI_ASSOC);
            foreach ($admins as $ad) {
                $email = str_contains($ad['username'], '@') ? $ad['username'] : ($ad['username'] . '@smartservecatering.com');
                
                // Exclude atenshazlan admin
                if (str_contains(strtolower($ad['username']), 'atenshazlan') || str_contains(strtolower($email), 'atenshazlan')) {
                    continue;
                }

                $existing = DB::table('users')->where('email', $email)->exists();
                if ($existing) continue;

                DB::table('users')->insert([
                    'name' => $ad['username'],
                    'full_name' => 'Administrator',
                    'email' => $email,
                    'password' => (password_get_info($ad['password'])['algo'] === 0) 
                        ? Hash::make($ad['password']) 
                        : $ad['password'],
                    'role' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 5. Migrate Settings
            $settings = $conn->query("SELECT * FROM settings")->fetch_all(MYSQLI_ASSOC);
            foreach ($settings as $s) {
                DB::table('settings')->insert([
                    'setting_key' => $s['setting_key'],
                    'setting_value' => $s['setting_value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            $conn->close();
        } else {
            // Seed defaults if no legacy database
            if (DB::table('settings')->count() === 0) {
                DB::table('settings')->insert([
                    ['setting_key' => 'business_name', 'setting_value' => 'SmartServe Catering', 'created_at' => now(), 'updated_at' => now()],
                    ['setting_key' => 'contact_phone', 'setting_value' => '019-2094670', 'created_at' => now(), 'updated_at' => now()],
                    ['setting_key' => 'qr_code_path', 'setting_value' => 'admin/uploads/qr_default.png', 'created_at' => now(), 'updated_at' => now()],
                ]);
            }


            if (!DB::table('users')->where('email', 'customer@example.com')->exists()) {
                DB::table('users')->insert([
                    'name' => 'customer',
                    'full_name' => 'Customer',
                    'email' => 'customer@example.com',
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'role' => 'customer',
                    'phone' => '0123456789',
                    'address' => 'Gong Badak, Kuala Terengganu',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Seed default packages
            if (DB::table('packages')->count() === 0) {
                DB::table('packages')->insertGetId([
                    'package_name' => 'Wedding Gold',
                    'price' => 15.00,
                    'min_order' => 500,
                    'image' => 'placeholder.jpg',
                    'description' => "Nasi Minyak\nAyam Goreng Berempah\nDaging Masak Hitam\nDalca Sayur\nJelatah Harian\nAir Sirap Selasih",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (DB::table('addons')->count() === 0) {
                DB::table('addons')->insert([
                    ['addon_name' => 'Kambing Bakar', 'price_per_pax' => 5.00, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Cendol & Bubur', 'price_per_pax' => 2.50, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Kambing Golek Gergasi', 'price_per_pax' => 8.00, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Apam Balik & Roti Jala', 'price_per_pax' => 3.00, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Manggo Float Station', 'price_per_pax' => 2.50, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Teh Tarik Live Station', 'price_per_pax' => 1.50, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Satay Ayam & Daging (5 cucuk)', 'price_per_pax' => 4.00, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Coffee Bar Barista Style', 'price_per_pax' => 6.00, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Salmon Grill Station', 'price_per_pax' => 10.00, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Bubur Asyura & Manisan', 'price_per_pax' => 2.00, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Air Zam-zam & Kurma Box', 'price_per_pax' => 3.50, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Pencuci Mulut Bubur Kacang', 'price_per_pax' => 1.50, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                    ['addon_name' => 'Ayam Goreng Extra', 'price_per_pax' => 2.50, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
        }

        // Guarantee fshazwina223@gmail.com is seeded as admin
        DB::table('users')->updateOrInsert(
            ['email' => 'fshazwina223@gmail.com'],
            [
                'name' => 'admin',
                'full_name' => 'SmartServe Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('Fashaa02!'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Guarantee contact_email is seeded
        DB::table('settings')->updateOrInsert(
            ['setting_key' => 'contact_email'],
            ['setting_value' => 'fshazwina223@gmail.com', 'created_at' => now(), 'updated_at' => now()]
        );

        // 6. Seed default dishes and associate them with all packages
        $defaultDishes = [
            ['name' => 'Nasi Minyak', 'category' => 'Nasi', 'active' => true],
            ['name' => 'Nasi Briyani', 'category' => 'Nasi', 'active' => true],
            ['name' => 'Nasi Tomato', 'category' => 'Nasi', 'active' => true],
            ['name' => 'Nasi Putih', 'category' => 'Nasi', 'active' => true],
            ['name' => 'Ayam Masak Merah', 'category' => 'Ayam', 'active' => true],
            ['name' => 'Ayam Goreng Berempah', 'category' => 'Ayam', 'active' => true],
            ['name' => 'Ayam Kicap Berempah', 'category' => 'Ayam', 'active' => true],
            ['name' => 'Daging Rendang', 'category' => 'Daging', 'active' => true],
            ['name' => 'Daging Masak Hitam', 'category' => 'Daging', 'active' => true],
            ['name' => 'Daging Dendeng', 'category' => 'Daging', 'active' => true],
            ['name' => 'Dalca Sayur', 'category' => 'Sayuran', 'active' => true],
            ['name' => 'Jelatah Harian', 'category' => 'Sayuran', 'active' => true],
            ['name' => 'Acar Buah', 'category' => 'Sayuran', 'active' => true],
            ['name' => 'Puding Raja', 'category' => 'Pencuci Mulut', 'active' => true],
            ['name' => 'Bubur Som Som', 'category' => 'Pencuci Mulut', 'active' => true],
            ['name' => 'Buah-buahan Campur', 'category' => 'Pencuci Mulut', 'active' => true],
            ['name' => 'Sirap Bandung', 'category' => 'Minuman', 'active' => true],
            ['name' => 'Air Jagung', 'category' => 'Minuman', 'active' => true],
            ['name' => 'Sirap Selasih', 'category' => 'Minuman', 'active' => true],
        ];

        foreach ($defaultDishes as $dish) {
            DB::table('dishes')->updateOrInsert(
                ['name' => $dish['name']],
                [
                    'category' => $dish['category'],
                    'active' => $dish['active'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // Get all packages and assign default limits & associate seeded dishes
        $packages = \App\Models\Package::all();
        $dishes = \App\Models\Dish::all();

        foreach ($packages as $pkg) {
            if (empty($pkg->dish_limits)) {
                $pkg->dish_limits = [
                    'Nasi' => 1,
                    'Ayam' => 1,
                    'Daging' => 1,
                    'Sayuran' => 1,
                    'Pencuci Mulut' => 1,
                    'Minuman' => 1,
                ];
                $pkg->save();
            }

            // Sync all seeded dishes to this package if not already associated
            $pkg->dishes()->syncWithoutDetaching($dishes->pluck('id'));
        }

        // Seed default delivery fees if they do not exist
        $deliveryFees = [
            'delivery_fee_kuala_nerus' => '30',
            'delivery_fee_kuala_terengganu' => '50',
            'delivery_fee_marang' => '80',
            'delivery_fee_hulu_terengganu' => '100',
            'delivery_fee_setiu' => '100',
        ];

        foreach ($deliveryFees as $key => $val) {
            DB::table('settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => $val, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
