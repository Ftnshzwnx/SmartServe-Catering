<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Cart;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\Addon;
use App\Models\Dish;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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

        return Inertia::render('Customer/Menu/Index', [
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
            ->get();

        return Inertia::render('Customer/Menu/Show', [
            'category' => $categoryName,
            'variations' => $variations,
            'cartCount' => $cartCount,
        ]);
    }

    public function downloadQuotation(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'quantity' => 'required|integer|min:1',
            'addons' => 'nullable|string',
            'dishes' => 'nullable|string',
        ]);

        $package = Package::findOrFail($request->input('package_id'));
        $quantity = (int)$request->input('quantity');

        if ($quantity < $package->min_order) {
            abort(400, "Minimum order for this package is {$package->min_order} pax.");
        }

        // Get settings key-value mapping
        $settings = Setting::all()->pluck('setting_value', 'setting_key')->toArray();

        // Load active selected addons
        $addonIds = $request->input('addons') ? explode(',', $request->input('addons')) : [];
        $addons = Addon::whereIn('id', $addonIds)->where('active', true)->get();

        // Load active selected dishes
        $dishIds = $request->input('dishes') ? explode(',', $request->input('dishes')) : [];
        $dishes = Dish::whereIn('id', $dishIds)->where('active', true)->get();

        // Calculate pricing
        $packagePrice = (float)$package->price;
        $addonsPricePerPax = (float)$addons->sum('price_per_pax');
        $totalPricePerPax = $packagePrice + $addonsPricePerPax;
        $grandTotal = $totalPricePerPax * $quantity;

        $depositPercentage = isset($settings['deposit_percentage']) ? (float)$settings['deposit_percentage'] : 30.0;
        $depositAmount = $grandTotal * ($depositPercentage / 100);
        $balanceAmount = $grandTotal - $depositAmount;

        // Compile customer and date info
        $user = $request->user();
        $dateGenerated = date('d M Y');
        $dateExpiry = date('d M Y', strtotime('+30 days'));
        $quoteNumber = 'QT-' . date('Ymd') . '-' . str_pad($package->id, 3, '0', STR_PAD_LEFT);

        $pdf = Pdf::loadView('pdf.quotation', compact(
            'package',
            'quantity',
            'addons',
            'dishes',
            'settings',
            'packagePrice',
            'addonsPricePerPax',
            'totalPricePerPax',
            'grandTotal',
            'depositAmount',
            'balanceAmount',
            'user',
            'dateGenerated',
            'dateExpiry',
            'quoteNumber',
            'depositPercentage'
        ));

        $filename = 'Quotation_' . str_replace(' ', '_', $package->package_name) . '_' . date('Ymd') . '.pdf';
        return $pdf->download($filename);
    }
}
