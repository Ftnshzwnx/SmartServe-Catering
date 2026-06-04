<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Cart;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetPlannerController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $cartCount = Cart::where('user_id', $user->id)->count();

        // Get all packages for selection
        $packages = Package::orderBy('price', 'asc')->get();

        return Inertia::render('Budget/Planner', [
            'packages' => $packages,
            'cartCount' => $cartCount,
        ]);
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:budget,guest',
            'budget' => 'nullable|numeric|min:0',
            'guest_count' => 'nullable|integer|min:0',
        ]);

        $mode = $request->input('mode');
        $budget = (float)($request->input('budget') ?? 0);
        $guest_count = (int)($request->input('guest_count') ?? 0);

        $packages = Package::orderBy('price', 'asc')->get();
        $results = [];

        foreach ($packages as $pkg) {
            $price = (float)$pkg->price;
            $min_order = (int)$pkg->min_order;

            if ($mode === 'budget' && $budget > 0) {
                $max_pax = floor($budget / $price);
                if ($max_pax < $min_order) continue;

                $recommended_pax = $max_pax;
                $total = $recommended_pax * $price;
                $deposit = $total * 0.3;
                $balance = $total * 0.7;
                $leftover = $budget - $total;

            } else if ($mode === 'guest' && $guest_count > 0) {
                $pax = max($guest_count, $min_order);
                $total = $pax * $price;
                $deposit = $total * 0.3;
                $balance = $total * 0.7;
                $recommended_pax = $pax;
                $leftover = 0;
            } else {
                continue;
            }

            $results[] = [
                'package' => $pkg,
                'recommended_pax' => $recommended_pax,
                'total' => $total,
                'deposit' => $deposit,
                'balance' => $balance,
                'leftover' => $leftover,
                'price_per_pax' => $price,
            ];
        }

        // Sort by value (highest recommended pax first)
        usort($results, fn($a, $b) => $b['recommended_pax'] <=> $a['recommended_pax']);

        $user = $request->user();
        $cartCount = Cart::where('user_id', $user->id)->count();

        return Inertia::render('Budget/Planner', [
            'packages' => $packages,
            'cartCount' => $cartCount,
            'results' => $results,
            'searched' => true,
            'input' => [
                'mode' => $mode,
                'budget' => $budget,
                'guest_count' => $guest_count,
            ]
        ]);
    }
}
