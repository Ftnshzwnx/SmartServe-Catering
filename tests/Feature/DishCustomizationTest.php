<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DishCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed settings because the application expects settings to be loaded
        \DB::table('settings')->insert([
            ['setting_key' => 'business_name', 'setting_value' => 'SmartServe Catering', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'deposit_percentage', 'setting_value' => '30', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_admin_can_create_dish()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.dishes.store'), [
            'name' => 'Nasi Minyak Baru',
            'category' => 'Nasi',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('dishes', [
            'name' => 'Nasi Minyak Baru',
            'category' => 'Nasi',
            'active' => true,
        ]);
    }

    public function test_customer_can_add_customized_package_to_cart_with_dish_choices()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        
        $package = Package::create([
            'package_name' => 'Wedding Silver',
            'price' => 12.00,
            'min_order' => 100,
            'description' => 'Dishes list',
            'dish_limits' => [
                'Nasi' => 1,
                'Ayam' => 1,
            ]
        ]);

        $dish1 = Dish::create(['name' => 'Nasi Hujan Panas', 'category' => 'Nasi', 'active' => true]);
        $dish2 = Dish::create(['name' => 'Ayam Masak Kicap', 'category' => 'Ayam', 'active' => true]);

        $package->dishes()->sync([$dish1->id, $dish2->id]);

        $response = $this->actingAs($customer)->post(route('cart.addCustom'), [
            'package_id' => $package->id,
            'quantity' => 100,
            'dishes' => [$dish1->id, $dish2->id],
        ]);

        $response->assertRedirect(route('cart.index'));
        
        $this->assertDatabaseHas('carts', [
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'quantity' => 100,
        ]);

        $cartItem = Cart::where('user_id', $customer->id)->first();
        $this->assertContains('Nasi Hujan Panas', $cartItem->selected_dishes);
        $this->assertContains('Ayam Masak Kicap', $cartItem->selected_dishes);
    }

    public function test_dish_limits_validation_is_enforced()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        
        $package = Package::create([
            'package_name' => 'Wedding Bronze',
            'price' => 10.00,
            'min_order' => 100,
            'description' => 'Dishes list',
            'dish_limits' => [
                'Nasi' => 1,
            ]
        ]);

        $dish1 = Dish::create(['name' => 'Nasi Briyani', 'category' => 'Nasi', 'active' => true]);
        $dish2 = Dish::create(['name' => 'Nasi Tomato', 'category' => 'Nasi', 'active' => true]);

        $package->dishes()->sync([$dish1->id, $dish2->id]);

        // Customer tries to select 2 Nasi when limit is 1
        $response = $this->actingAs($customer)->post(route('cart.addCustom'), [
            'package_id' => $package->id,
            'quantity' => 100,
            'dishes' => [$dish1->id, $dish2->id],
        ]);

        $response->assertSessionHasErrors('dishes');
        $this->assertEquals(0, Cart::where('user_id', $customer->id)->count());
    }

    public function test_order_creation_copies_dishes_from_cart_to_order_item()
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'full_name' => 'Jane Doe',
            'phone' => '0123456789',
            'address' => 'Test Address',
        ]);
        
        $package = Package::create([
            'package_name' => 'Corporate Package',
            'price' => 15.00,
            'min_order' => 50,
            'description' => 'Standard Corporate',
            'dish_limits' => [
                'Nasi' => 1,
            ]
        ]);

        $dish = Dish::create(['name' => 'Nasi Minyak', 'category' => 'Nasi', 'active' => true]);
        
        // Add item to cart
        $cart = Cart::create([
            'user_id' => $customer->id,
            'package_id' => $package->id,
            'package_name' => $package->package_name,
            'quantity' => 50,
            'price' => 15.00,
            'total_price' => 750.00,
            'selected_dishes' => ['Nasi Minyak'],
        ]);

        // Put cart item in session checkout
        session(['checkout_items' => [$cart->id]]);

        // Mock payment receipt upload
        $file = \Illuminate\Http\UploadedFile::fake()->create('receipt.jpg', 100);

        $response = $this->actingAs($customer)->post(route('checkout.place'), [
            'name' => 'Jane Doe',
            'phone' => '0123456789',
            'address' => 'Test Address',
            'delivery_zone' => 'Kuala Nerus',
            'delivery_date' => date('Y-m-d', strtotime('+8 days')),
            'delivery_time' => '12:00',
            'receipt' => $file,
            'notes' => 'Sila sediakan sudu plastik lebih',
        ]);

        $response->assertRedirect(route('orders.index'));
        
        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'total_price' => 780.00,
            'delivery_zone' => 'Kuala Nerus',
            'delivery_fee' => 30.00,
            'notes' => 'Sila sediakan sudu plastik lebih',
        ]);

        $order = Order::where('user_id', $customer->id)->first();
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'package_id' => $package->id,
            'quantity' => 50,
        ]);

        $orderItem = $order->items()->first();
        $this->assertContains('Nasi Minyak', $orderItem->selected_dishes);
    }

    public function test_admin_can_manage_categories()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 1. Create Category
        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Kategori Baru',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('dish_categories', ['name' => 'Kategori Baru']);

        $category = \App\Models\DishCategory::where('name', 'Kategori Baru')->first();

        // 2. Update Category
        $response = $this->actingAs($admin)->post(route('admin.categories.update', ['id' => $category->id]), [
            'name' => 'Kategori Kemas Kini',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('dish_categories', ['name' => 'Kategori Kemas Kini']);
        $this->assertDatabaseMissing('dish_categories', ['name' => 'Kategori Baru']);

        // 3. Delete Category
        $response = $this->actingAs($admin)->delete(route('admin.categories.delete', ['id' => $category->id]));
        $response->assertRedirect();
        $this->assertDatabaseMissing('dish_categories', ['name' => 'Kategori Kemas Kini']);
    }

    public function test_category_deletion_fails_if_has_dishes_or_package_limits()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $category = \App\Models\DishCategory::create(['name' => 'Kategori Terikat']);
        
        // Associate a dish
        Dish::create(['name' => 'Dish Terikat', 'category' => 'Kategori Terikat']);

        $response = $this->actingAs($admin)->delete(route('admin.categories.delete', ['id' => $category->id]));
        $response->assertSessionHasErrors('category');
        $this->assertDatabaseHas('dish_categories', ['name' => 'Kategori Terikat']);
    }
}
