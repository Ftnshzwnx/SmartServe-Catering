<?php

namespace Tests\Feature;

use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryZoneTest extends TestCase
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

    public function test_guest_cannot_modify_delivery_zones(): void
    {
        $response = $this->post('/admin/delivery-zones', [
            'name' => 'Kemaman',
            'fee' => 150.00
        ]);
        $response->assertRedirect(route('login'));

        $zone = DeliveryZone::create(['name' => 'Dungun', 'fee' => 120.00]);
        $response = $this->put('/admin/delivery-zones/' . $zone->id, [
            'name' => 'Dungun Updated',
            'fee' => 130.00
        ]);
        $response->assertRedirect(route('login'));

        $response = $this->delete('/admin/delivery-zones/' . $zone->id);
        $response->assertRedirect(route('login'));
    }

    public function test_customer_cannot_modify_delivery_zones(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->post('/admin/delivery-zones', [
            'name' => 'Kemaman',
            'fee' => 150.00
        ]);
        $response->assertStatus(403);

        $zone = DeliveryZone::create(['name' => 'Dungun', 'fee' => 120.00]);
        $response = $this->actingAs($customer)->put('/admin/delivery-zones/' . $zone->id, [
            'name' => 'Dungun Updated',
            'fee' => 130.00
        ]);
        $response->assertStatus(403);

        $response = $this->actingAs($customer)->delete('/admin/delivery-zones/' . $zone->id);
        $response->assertStatus(403);
    }

    public function test_admin_can_add_delivery_zone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/delivery-zones', [
            'name' => 'Dungun',
            'fee' => 120.00
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('delivery_zones', [
            'name' => 'Dungun',
            'fee' => 120.00
        ]);
    }

    public function test_admin_can_update_delivery_zone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $zone = DeliveryZone::where('name', 'Marang')->first();

        $response = $this->actingAs($admin)->put('/admin/delivery-zones/' . $zone->id, [
            'name' => 'Marang Updated',
            'fee' => 95.00
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('delivery_zones', [
            'id' => $zone->id,
            'name' => 'Marang Updated',
            'fee' => 95.00
        ]);
    }

    public function test_admin_can_delete_delivery_zone(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $zone = DeliveryZone::where('name', 'Setiu')->first();

        $response = $this->actingAs($admin)->delete('/admin/delivery-zones/' . $zone->id);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('delivery_zones', [
            'id' => $zone->id
        ]);
    }
}
