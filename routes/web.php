<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\BudgetPlannerController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminDashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Cart;
use App\Models\Order;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Customer Dashboard
Route::get('/dashboard', function () {
    $user = auth()->user();
    
    // Redirect admin to admin dashboard automatically
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    $cartCount = Cart::where('user_id', $user->id)->count();
    $ordersCount = Order::where('user_id', $user->id)->count();

    return Inertia::render('Dashboard', [
        'cartCount' => $cartCount,
        'ordersCount' => $ordersCount,
    ]);
})->middleware(['auth'])->name('dashboard');

// Auth routes for Customer profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Menu / Packages
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menu/{category}', [MenuController::class, 'show'])->name('menu.show');

    // Budget Planner
    Route::get('/budget-planner', [BudgetPlannerController::class, 'index'])->name('budget.planner');
    Route::post('/budget-planner', [BudgetPlannerController::class, 'calculate'])->name('budget.calculate');

    // Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'store'])->name('cart.add');
    Route::get('/cart/customize/{package_id}', [CartController::class, 'customize'])->name('cart.customize');
    Route::post('/cart/add-custom', [CartController::class, 'storeCustom'])->name('cart.addCustom');
    Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/delete/{id}', [CartController::class, 'destroy'])->name('cart.destroy');

    // Checkout
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout.index');
    Route::post('/checkout/place', [OrderController::class, 'placeOrder'])->name('checkout.place');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{id}/reupload', [OrderController::class, 'reupload'])->name('orders.reupload');
});

// Admin panel routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/orders', [AdminDashboardController::class, 'orders'])->name('admin.orders');
    Route::post('/orders/{id}/verify', [AdminDashboardController::class, 'verifyPayment'])->name('admin.orders.verify');
    Route::get('/packages', [AdminDashboardController::class, 'packages'])->name('admin.packages');
    Route::post('/packages/store', [AdminDashboardController::class, 'storePackage'])->name('admin.packages.store');
    Route::post('/packages/update/{id}', [AdminDashboardController::class, 'updatePackage'])->name('admin.packages.update');
    Route::delete('/packages/delete/{id}', [AdminDashboardController::class, 'deletePackage'])->name('admin.packages.delete');
    
    Route::post('/addons/store', [AdminDashboardController::class, 'storeAddon'])->name('admin.addons.store');
    Route::delete('/addons/delete/{id}', [AdminDashboardController::class, 'deleteAddon'])->name('admin.addons.delete');
    
    Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('admin.settings');
    Route::post('/settings/update', [AdminDashboardController::class, 'updateSettings'])->name('admin.settings.update');
});

require __DIR__.'/auth.php';
