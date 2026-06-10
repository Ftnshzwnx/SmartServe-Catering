<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Package;
use App\Models\Addon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $cartItems = Cart::where('user_id', $user->id)
            ->with('package')
            ->get();

        $cartCount = $cartItems->count();

        return Inertia::render('Customer/Cart/Index', [
            'cartItems' => $cartItems,
            'cartCount' => $cartCount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $packageId = $request->input('package_id');
        $quantity = $request->input('quantity');

        $pkg = Package::findOrFail($packageId);
        $minOrder = (int)$pkg->min_order;

        if ($quantity < $minOrder) {
            $quantity = $minOrder;
        }

        $price = (float)$pkg->price;
        $totalPrice = $price * $quantity;

        // Check if package already exists in cart without customization
        $existing = Cart::where('user_id', $user->id)
            ->where('package_id', $packageId)
            ->whereNull('selected_addons')
            ->first();

        if ($existing) {
            $existing->quantity += $quantity;
            $existing->total_price += $totalPrice;
            $existing->save();
        } else {
            Cart::create([
                'user_id' => $user->id,
                'package_id' => $packageId,
                'package_name' => $pkg->package_name,
                'quantity' => $quantity,
                'price' => $price,
                'total_price' => $totalPrice,
                'selected_addons' => null,
                'addon_cost' => 0.00,
            ]);
        }

        return redirect()->back()->with('success', 'Package added to cart successfully!');
    }

    public function customize(Request $request, int $packageId): Response
    {
        $user = $request->user();
        $cartCount = Cart::where('user_id', $user->id)->count();

        $pkg = Package::with('dishes')->findOrFail($packageId);
        
        $cartId = $request->query('cart_id') ?? $request->input('cart_id');
        $cartItem = null;
        
        if ($cartId) {
            $cartItem = Cart::where('user_id', $user->id)->find($cartId);
            \Illuminate\Support\Facades\Log::info('Customize cart item query', [
                'cart_id_input' => $cartId,
                'user_id' => $user ? $user->id : 'guest',
                'cartItem_found' => $cartItem ? $cartItem->toArray() : null,
            ]);
        } else {
            \Illuminate\Support\Facades\Log::info('Customize cart item query: no cart_id in request');
        }

        $categories = \App\Models\DishCategory::orderBy('name')->pluck('name')->toArray();

        return Inertia::render('Customer/Menu/Customize', [
            'package' => $pkg,
            'cartCount' => $cartCount,
            'cartItem' => $cartItem,
            'categories' => $categories,
        ]);
    }

    public function storeCustom(Request $request): RedirectResponse
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'quantity' => 'required|integer|min:1',
            'addons' => 'nullable|array',
            'addons.*' => 'exists:addons,id',
            'dishes' => 'nullable|array',
            'dishes.*' => 'exists:dishes,id',
            'cart_id' => 'nullable|exists:carts,id',
        ]);

        $user = $request->user();
        $packageId = $request->input('package_id');
        $quantity = $request->input('quantity');
        $addonIds = $request->input('addons', []);
        $dishIds = $request->input('dishes', []);
        $cartId = $request->input('cart_id');

        $pkg = Package::with('dishes')->findOrFail($packageId);
        $minOrder = (int)$pkg->min_order;
        if ($quantity < $minOrder) {
            $quantity = $minOrder;
        }

        // Validate dish limits
        $selectedDishes = \App\Models\Dish::whereIn('id', $dishIds)->where('active', true)->get();
        $selectedGrouped = $selectedDishes->groupBy('category');

        $limits = $pkg->dish_limits ?? [];
        foreach ($limits as $category => $limit) {
            $limit = (int)$limit;
            if ($limit > 0) {
                $count = isset($selectedGrouped[$category]) ? $selectedGrouped[$category]->count() : 0;
                if ($count !== $limit) {
                    return redirect()->back()->withErrors([
                        'dishes' => "Sila pilih tepat {$limit} hidangan untuk kategori '{$category}'."
                    ])->withInput();
                }
            }
        }

        // Calculate addon cost
        $addonCostPerPax = 0.00;
        $selectedAddonNames = [];

        if (!empty($addonIds)) {
            $addons = Addon::whereIn('id', $addonIds)
                ->where('active', true)
                ->get();

            foreach ($addons as $addon) {
                $addonCostPerPax += (float)$addon->price_per_pax;
                $selectedAddonNames[] = $addon->addon_name . ' (+RM' . number_format($addon->price_per_pax, 2) . ')';
            }
        }

        $pricePerPax = (float)$pkg->price + $addonCostPerPax;
        $totalPrice = $pricePerPax * $quantity;

        $selectedDishesNames = $selectedDishes->pluck('name')->toArray();

        if ($cartId) {
            $cartItem = Cart::where('user_id', $user->id)->findOrFail($cartId);
            $cartItem->update([
                'package_id' => $packageId,
                'package_name' => $pkg->package_name,
                'quantity' => $quantity,
                'price' => $pricePerPax,
                'total_price' => $totalPrice,
                'selected_addons' => $selectedAddonNames,
                'addon_cost' => $addonCostPerPax,
                'selected_dishes' => $selectedDishesNames,
            ]);
            return redirect()->route('cart.index')->with('success', 'Cart item updated successfully!');
        }

        Cart::create([
            'user_id' => $user->id,
            'package_id' => $packageId,
            'package_name' => $pkg->package_name,
            'quantity' => $quantity,
            'price' => $pricePerPax,
            'total_price' => $totalPrice,
            'selected_addons' => $selectedAddonNames,
            'addon_cost' => $addonCostPerPax,
            'selected_dishes' => $selectedDishesNames,
        ]);

        return redirect()->route('cart.index')->with('success', 'Customized package added to cart!');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $cartItem = Cart::where('user_id', $user->id)->findOrFail($id);
        $quantity = $request->input('quantity');

        // Check package min_order
        $pkg = Package::findOrFail($cartItem->package_id);
        if ($quantity < $pkg->min_order) {
            $quantity = $pkg->min_order;
        }

        $cartItem->quantity = $quantity;
        $cartItem->total_price = $cartItem->price * $quantity;
        $cartItem->save();

        return redirect()->back()->with('success', 'Cart updated successfully.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $cartItem = Cart::where('user_id', $user->id)->findOrFail($id);
        $cartItem->delete();

        return redirect()->back()->with('success', 'Item removed from cart.');
    }
}
