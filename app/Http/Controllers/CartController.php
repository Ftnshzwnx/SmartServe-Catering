<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Package;
use App\Models\PackageAddon;
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

        return Inertia::render('Cart/Index', [
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

        $pkg = Package::with('addons')->findOrFail($packageId);
        
        return Inertia::render('Menu/Customize', [
            'package' => $pkg,
            'cartCount' => $cartCount,
        ]);
    }

    public function storeCustom(Request $request): RedirectResponse
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'quantity' => 'required|integer|min:1',
            'addons' => 'nullable|array',
            'addons.*' => 'exists:package_addons,id',
        ]);

        $user = $request->user();
        $packageId = $request->input('package_id');
        $quantity = $request->input('quantity');
        $addonIds = $request->input('addons', []);

        $pkg = Package::findOrFail($packageId);
        $minOrder = (int)$pkg->min_order;
        if ($quantity < $minOrder) {
            $quantity = $minOrder;
        }

        // Calculate addon cost
        $addonCostPerPax = 0.00;
        $selectedAddonNames = [];

        if (!empty($addonIds)) {
            $addons = PackageAddon::whereIn('id', $addonIds)
                ->where('package_id', $packageId)
                ->get();

            foreach ($addons as $addon) {
                $addonCostPerPax += (float)$addon->price_per_pax;
                $selectedAddonNames[] = $addon->addon_name . ' (+RM' . number_format($addon->price_per_pax, 2) . ')';
            }
        }

        $pricePerPax = (float)$pkg->price + $addonCostPerPax;
        $totalPrice = $pricePerPax * $quantity;

        Cart::create([
            'user_id' => $user->id,
            'package_id' => $packageId,
            'package_name' => $pkg->package_name,
            'quantity' => $quantity,
            'price' => $pricePerPax,
            'total_price' => $totalPrice,
            'selected_addons' => $selectedAddonNames,
            'addon_cost' => $addonCostPerPax,
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
