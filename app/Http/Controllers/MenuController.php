<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Cart;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $cartCount = Cart::where('user_id', $user->id)->count();

        // Get unique categories/packages by package_name
        $packages = Package::select('package_name', 'image', 'price')
            ->orderBy('price', 'asc')
            ->get()
            ->unique('package_name')
            ->values();

        return Inertia::render('Menu/Index', [
            'packages' => $packages,
            'cartCount' => $cartCount,
        ]);
    }

    public function show(Request $request, string $categoryName): Response
    {
        $user = $request->user();
        $cartCount = Cart::where('user_id', $user->id)->count();

        // Get all variations under this package name
        $variations = Package::where('package_name', $categoryName)
            ->with('addons')
            ->get();

        return Inertia::render('Menu/Show', [
            'category' => $categoryName,
            'variations' => $variations,
            'cartCount' => $cartCount,
        ]);
    }
}
