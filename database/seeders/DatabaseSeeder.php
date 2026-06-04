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
        // Connect to legacy database if available
        $legacyDb = 'azilinacatering';
        $host = 'localhost';
        $user = 'root';
        $pass = '';

        try {
            $pdo = new \PDO("mysql:host=$host", $user, $pass);
            $dbExists = $pdo->query("SHOW DATABASES LIKE '$legacyDb'")->rowCount() > 0;
        } catch (\Exception $e) {
            $dbExists = false;
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
                DB::table('package_addons')->insert([
                    'id' => $addon['addon_id'],
                    'package_id' => $addon['package_id'],
                    'addon_name' => $addon['addon_name'],
                    'price_per_pax' => $addon['price_per_pax'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
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
            DB::table('settings')->insert([
                ['setting_key' => 'business_name', 'setting_value' => 'SmartServe Catering', 'created_at' => now(), 'updated_at' => now()],
                ['setting_key' => 'qr_code_path', 'setting_value' => 'admin/uploads/qr_default.png', 'created_at' => now(), 'updated_at' => now()],
            ]);
            
            DB::table('users')->insert([
                'name' => 'admin',
                'full_name' => 'SmartServe Admin',
                'email' => 'admin@smartservecatering.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->insert([
                'name' => 'customer',
                'full_name' => 'John Doe',
                'email' => 'customer@example.com',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'phone' => '0123456789',
                'address' => 'Gong Badak, Kuala Terengganu',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Seed default packages
            $pkgId1 = DB::table('packages')->insertGetId([
                'package_name' => 'Wedding Gold',
                'price' => 15.00,
                'min_order' => 500,
                'image' => 'placeholder.jpg',
                'description' => "Nasi Minyak\nAyam Goreng Berempah\nDaging Masak Hitam\nDalca Sayur\nJelatah Harian\nAir Sirap Selasih",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('package_addons')->insert([
                ['package_id' => $pkgId1, 'addon_name' => 'Kambing Bakar', 'price_per_pax' => 5.00, 'created_at' => now(), 'updated_at' => now()],
                ['package_id' => $pkgId1, 'addon_name' => 'Cendol & Bubur', 'price_per_pax' => 2.50, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}
