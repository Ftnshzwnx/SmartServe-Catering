<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = \DB::connection();
        if ($connection->getDriverName() === 'sqlite') {
            $pdo = $connection->getPdo();
            $pdo->sqliteCreateFunction('YEAR', function ($date) {
                return $date ? date('Y', strtotime($date)) : null;
            });
            $pdo->sqliteCreateFunction('MONTH', function ($date) {
                return $date ? date('n', strtotime($date)) : null;
            });
            $pdo->sqliteCreateFunction('DAY', function ($date) {
                return $date ? date('j', strtotime($date)) : null;
            });
        }

        // Seed settings because the application expects settings to be loaded
        \DB::table('settings')->insert([
            ['setting_key' => 'business_name', 'setting_value' => 'SmartServe Catering', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'deposit_percentage', 'setting_value' => '30', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'contact_phone', 'setting_value' => '012-3456789', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'contact_email', 'setting_value' => 'hello@smartservecatering.com', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'contact_address', 'setting_value' => 'Kuala Lumpur, Malaysia', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_guest_cannot_access_reports_page(): void
    {
        $response = $this->get('/admin/reports');

        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_reports_page(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get('/admin/reports');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_reports_page_when_no_data_exists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/reports');

        $response->assertOk();
    }

    public function test_admin_can_access_reports_page_when_data_exists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        $package = Package::create([
            'package_name' => 'Premium Buffet',
            'price' => 25.00,
            'min_order' => 50,
            'description' => 'Test Description',
            'dish_limits' => ['Main' => 2],
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'package_name' => $package->package_name,
            'delivery_address' => '123 Test St',
            'delivery_zone' => 'Zone A',
            'delivery_fee' => 50.00,
            'total_price' => 1300.00,
            'status' => 'Completed',
            'delivery_date' => now()->format('Y-m-d'),
            'delivery_time' => '12:00:00',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'package_id' => $package->id,
            'quantity' => 50,
            'price' => 25.00,
            'subtotal' => 1250.00,
            'selected_dishes' => ['Dish A', 'Dish B'],
            'selected_addons' => ['Extra Ice (+RM2.00)'],
            'addon_cost' => 50.00,
        ]);

        $response = $this->actingAs($admin)->get('/admin/reports');

        $response->assertOk();
    }
}
