<?php

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\Dish;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed settings because the application expects settings to be loaded
        \DB::table('settings')->insert([
            ['setting_key' => 'business_name', 'setting_value' => 'SmartServe Catering', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'deposit_percentage', 'setting_value' => '30', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'contact_phone', 'setting_value' => '012-3456789', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'contact_email', 'setting_value' => 'hello@smartservecatering.com', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'contact_address', 'setting_value' => 'Kuala Lumpur, Malaysia', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_guest_cannot_download_quotation()
    {
        $response = $this->get(route('menu.quotation', [
            'package_id' => 1,
            'quantity' => 100
        ]));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_customer_can_download_quotation_pdf()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        
        $package = Package::create([
            'package_name' => 'Wedding Silver',
            'price' => 12.00,
            'min_order' => 100,
            'description' => 'Dishes list',
            'dish_limits' => [
                'Nasi' => 1,
            ]
        ]);

        $dish = Dish::create(['name' => 'Nasi Hujan Panas', 'category' => 'Nasi', 'active' => true]);
        $addon = Addon::create(['addon_name' => 'Kambing Golek', 'price_per_pax' => 5.00, 'active' => true]);

        $response = $this->actingAs($customer)->get(route('menu.quotation', [
            'package_id' => $package->id,
            'quantity' => 150,
            'addons' => $addon->id,
            'dishes' => $dish->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_quotation_fails_if_quantity_below_min_order()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        
        $package = Package::create([
            'package_name' => 'Wedding Silver',
            'price' => 12.00,
            'min_order' => 100,
            'description' => 'Dishes list',
            'dish_limits' => [
                'Nasi' => 1,
            ]
        ]);

        $response = $this->actingAs($customer)->get(route('menu.quotation', [
            'package_id' => $package->id,
            'quantity' => 50, // Below min_order of 100
        ]));

        $response->assertStatus(400);
    }
}
