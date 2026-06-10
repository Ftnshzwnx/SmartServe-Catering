<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Dish;
use App\Models\Package;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomProposalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed settings if they are used by the application
        \App\Models\Setting::updateOrCreate(
            ['setting_key' => 'deposit_percentage'],
            ['setting_value' => '30']
        );
    }

    public function test_customer_can_submit_custom_proposal_request(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $dish1 = Dish::create(['name' => 'Nasi Minyak', 'category' => 'Nasi', 'active' => true]);
        $dish2 = Dish::create(['name' => 'Ayam Goreng Berempah', 'category' => 'Lauk', 'active' => true]);
        $package = Package::create([
            'package_name' => 'Mock Package',
            'price' => 15.00,
            'min_order' => 50,
            'description' => 'Mock Package Desc',
        ]);

        $deliveryDate = date('Y-m-d', strtotime('+8 days'));

        $response = $this
            ->actingAs($customer)
            ->post('/orders/custom-proposal', [
                'budget' => 2500,
                'guest_count' => 100,
                'delivery_date' => $deliveryDate,
                'delivery_time' => '13:00',
                'address' => 'Gong Badak, Terengganu',
                'dishes' => [$dish1->id, $dish2->id],
                'notes' => 'Special requirement: No MSG',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/orders');

        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'status' => 'Pending Proposal',
            'is_custom_proposal' => true,
            'total_price' => 2500,
            'delivery_date' => $deliveryDate,
            'delivery_time' => '13:00',
        ]);

        $order = Order::where('user_id', $customer->id)->first();
        $this->assertNotNull($order);
        $this->assertCount(1, $order->items);

        $orderItem = $order->items->first();
        $this->assertNotNull($orderItem->selected_dishes);
        
        // Assert selected dishes wishlist is stored correctly in order item
        $selectedDishes = $orderItem->selected_dishes;
        $this->assertCount(2, $selectedDishes);
        $this->assertEquals('Nasi Minyak', $selectedDishes[0]['name']);
        $this->assertEquals('Ayam Goreng Berempah', $selectedDishes[1]['name']);
    }

    public function test_admin_can_build_and_send_proposal(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $admin = User::factory()->create(['role' => 'admin']);
        
        $dish1 = Dish::create(['name' => 'Nasi Minyak', 'category' => 'Nasi', 'active' => true]);
        $dish2 = Dish::create(['name' => 'Ayam Goreng Berempah', 'category' => 'Lauk', 'active' => true]);
        
        $order = Order::create([
            'user_id' => $customer->id,
            'package_name' => 'Custom Menu Proposal',
            'delivery_address' => 'Gong Badak, Terengganu',
            'total_price' => 2000,
            'status' => 'Pending Proposal',
            'delivery_date' => date('Y-m-d', strtotime('+8 days')),
            'delivery_time' => '12:00',
            'is_custom_proposal' => true,
        ]);

        $package = Package::create([
            'package_name' => 'Mock Package',
            'price' => 15.00,
            'min_order' => 50,
            'description' => 'Mock Package Desc',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'package_id' => $package->id,
            'quantity' => 100,
            'price' => 20,
            'subtotal' => 2000,
            'selected_dishes' => [
                ['id' => $dish1->id, 'name' => $dish1->name, 'category' => $dish1->category]
            ],
        ]);

        $response = $this
            ->actingAs($admin)
            ->post("/admin/orders/{$order->id}/send-proposal", [
                'total_price' => 2200,
                'dishes' => [$dish1->id, $dish2->id],
                'admin_note' => 'Added Ayam Goreng Berempah as requested.',
            ]);

        $response->assertSessionHasNoErrors();
        
        $order->refresh();
        $this->assertEquals('Proposal Sent', $order->status);
        $this->assertEquals(2200, $order->total_price);
        $this->assertEquals('Added Ayam Goreng Berempah as requested.', $order->admin_note);

        $orderItem = $order->items->first();
        $this->assertEquals(2200, $orderItem->subtotal);
        $this->assertEquals(22, $orderItem->price); // 2200 / 100 pax
        $this->assertCount(2, $orderItem->selected_dishes);
    }

    public function test_customer_can_approve_proposal(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        
        $order = Order::create([
            'user_id' => $customer->id,
            'package_name' => 'Custom Menu Proposal',
            'delivery_address' => 'Gong Badak, Terengganu',
            'total_price' => 2200,
            'status' => 'Proposal Sent',
            'delivery_date' => date('Y-m-d', strtotime('+8 days')),
            'delivery_time' => '12:00',
            'is_custom_proposal' => true,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post("/orders/{$order->id}/approve-proposal");

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/orders');

        $order->refresh();
        $this->assertEquals('Pending', $order->status); // Transitions to Pending for deposit verification
    }

    public function test_customer_can_reject_proposal(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        
        $order = Order::create([
            'user_id' => $customer->id,
            'package_name' => 'Custom Menu Proposal',
            'delivery_address' => 'Gong Badak, Terengganu',
            'total_price' => 2200,
            'status' => 'Proposal Sent',
            'delivery_date' => date('Y-m-d', strtotime('+8 days')),
            'delivery_time' => '12:00',
            'is_custom_proposal' => true,
        ]);

        $response = $this
            ->actingAs($customer)
            ->post("/orders/{$order->id}/reject-proposal");

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/orders');

        $order->refresh();
        $this->assertEquals('Cancelled', $order->status);
        $this->assertEquals('user', $order->cancelled_by);
        $this->assertNotNull($order->cancelled_at);
    }
}
