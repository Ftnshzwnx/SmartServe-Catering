<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\BudgetPlannerController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Customer\CustomProposalController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Package;

Route::get('/', function () {
    $reviews = \Illuminate\Support\Facades\Schema::hasTable('reviews')
        ? \App\Models\Review::with(['user', 'order'])
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(9)
            ->get()
        : collect();

    $packages = \Illuminate\Support\Facades\Schema::hasTable('packages')
        ? \App\Models\Package::orderBy('price', 'asc')
            ->get()
            ->unique('package_name')
            ->take(3)
            ->values()
        : collect();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'reviews' => $reviews,
        'packages' => $packages,
    ]);
});

// About Us page
Route::get('/about', function () {
    return Inertia::render('About', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('about');

// Contact Us page
Route::get('/contact', function () {
    $packages = \Illuminate\Support\Facades\Schema::hasTable('packages')
        ? \App\Models\Package::orderBy('price', 'asc')->get()->unique('package_name')->values()
        : collect();

    return Inertia::render('Contact', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'packages' => $packages,
    ]);
})->name('contact');

// Packages page
Route::get('/packages', function () {
    $packages = \Illuminate\Support\Facades\Schema::hasTable('packages')
        ? \App\Models\Package::orderBy('price', 'asc')->get()->unique('package_name')->values()
        : collect();

    return Inertia::render('Packages', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'packages' => $packages,
    ]);
})->name('packages');

// FAQ page
Route::get('/faq', function () {
    return Inertia::render('Faq', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('faq');



// Customer Dashboard
Route::get('/dashboard', function () {
    $user = auth()->user();
    
    // Redirect admin to admin dashboard automatically
    if ($user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    $cartCount = Cart::where('user_id', $user->id)->count();
    $ordersCount = Order::where('user_id', $user->id)->count();
    $recentOrders = Order::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->take(3)
        ->with(['items.package', 'review'])
        ->get();
    $featuredPackages = Package::take(3)->get();

    // Completed orders without a review yet (for the review prompt card)
    $pendingReviewOrders = Order::where('user_id', $user->id)
        ->where('status', 'Completed')
        ->whereDoesntHave('review')
        ->orderBy('updated_at', 'desc')
        ->take(1)
        ->with('items.package')
        ->get();

    return Inertia::render('Customer/Dashboard', [
        'cartCount' => $cartCount,
        'ordersCount' => $ordersCount,
        'recentOrders' => $recentOrders,
        'featuredPackages' => $featuredPackages,
        'pendingReviewOrders' => $pendingReviewOrders,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Auth routes for Customer profile
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Menu / Packages
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menu/quotation/pdf', [MenuController::class, 'downloadQuotation'])->name('menu.quotation');
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
    Route::get('/orders/{id}/invoice', [OrderController::class, 'downloadInvoice'])->name('orders.invoice.pdf');
    Route::get('/orders/{id}/receipt', [OrderController::class, 'downloadReceipt'])->name('orders.receipt.pdf');
    Route::post('/orders/{id}/review', [\App\Http\Controllers\ReviewController::class, 'store'])->name('orders.review');
    
    // Promo Code validation
    Route::post('/checkout/apply-promo', [OrderController::class, 'applyPromo'])->name('checkout.apply-promo');

    // Custom Proposals
    Route::post('/orders/custom-proposal', [CustomProposalController::class, 'store'])->name('orders.custom-proposal.store');
    Route::post('/orders/{id}/approve-proposal', [CustomProposalController::class, 'approve'])->name('orders.proposal.approve');
    Route::post('/orders/{id}/reject-proposal', [CustomProposalController::class, 'reject'])->name('orders.proposal.reject');

    // Notifications
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// Admin panel routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/orders', [AdminDashboardController::class, 'orders'])->name('admin.orders');
    Route::post('/orders/{id}/verify', [AdminDashboardController::class, 'verifyPayment'])->name('admin.orders.verify');
    Route::post('/orders/{id}/deliver', [AdminDashboardController::class, 'deliverOrder'])->name('admin.orders.deliver');
    Route::post('/orders/{id}/send-proposal', [AdminDashboardController::class, 'sendProposal'])->name('admin.orders.send-proposal');
    Route::get('/packages', [AdminDashboardController::class, 'packages'])->name('admin.packages');
    Route::post('/packages/store', [AdminDashboardController::class, 'storePackage'])->name('admin.packages.store');
    Route::post('/packages/update/{id}', [AdminDashboardController::class, 'updatePackage'])->name('admin.packages.update');
    Route::delete('/packages/delete/{id}', [AdminDashboardController::class, 'deletePackage'])->name('admin.packages.delete');
    
    Route::post('/addons/store', [AdminDashboardController::class, 'storeAddon'])->name('admin.addons.store');
    Route::post('/addons/update/{id}', [AdminDashboardController::class, 'updateAddon'])->name('admin.addons.update');
    Route::delete('/addons/delete/{id}', [AdminDashboardController::class, 'deleteAddon'])->name('admin.addons.delete');
    
    Route::post('/dishes/store', [AdminDashboardController::class, 'storeDish'])->name('admin.dishes.store');
    Route::post('/dishes/update/{id}', [AdminDashboardController::class, 'updateDish'])->name('admin.dishes.update');
    Route::delete('/dishes/delete/{id}', [AdminDashboardController::class, 'deleteDish'])->name('admin.dishes.delete');
    
    Route::post('/categories/store', [AdminDashboardController::class, 'storeCategory'])->name('admin.categories.store');
    Route::post('/categories/update/{id}', [AdminDashboardController::class, 'updateCategory'])->name('admin.categories.update');
    Route::delete('/categories/delete/{id}', [AdminDashboardController::class, 'deleteCategory'])->name('admin.categories.delete');
    
    Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('admin.settings');
    Route::post('/settings/update', [AdminDashboardController::class, 'updateSettings'])->name('admin.settings.update');
    Route::post('/delivery-zones', [AdminDashboardController::class, 'storeDeliveryZone'])->name('admin.delivery-zones.store');
    Route::put('/delivery-zones/{id}', [AdminDashboardController::class, 'updateDeliveryZone'])->name('admin.delivery-zones.update');
    Route::delete('/delivery-zones/{id}', [AdminDashboardController::class, 'deleteDeliveryZone'])->name('admin.delivery-zones.delete');
    Route::get('/reports', [AdminDashboardController::class, 'reports'])->name('admin.reports');
    Route::get('/reports/export', [AdminDashboardController::class, 'exportOrders'])->name('admin.reports.export');

    // Admin Customer Reviews
    Route::get('/reviews', [AdminDashboardController::class, 'reviews'])->name('admin.reviews');
    Route::post('/reviews/{id}/reply', [AdminDashboardController::class, 'replyReview'])->name('admin.reviews.reply');

    // Admin Booking Calendar & Blocked Dates
    Route::get('/calendar', [AdminDashboardController::class, 'calendar'])->name('admin.calendar');
    Route::post('/blocked-dates', [AdminDashboardController::class, 'storeBlockedDate'])->name('admin.blocked-dates.store');
    Route::delete('/blocked-dates/{id}', [AdminDashboardController::class, 'deleteBlockedDate'])->name('admin.blocked-dates.delete');

    // Admin Promo Codes
    Route::get('/promos', [AdminDashboardController::class, 'promos'])->name('admin.promos');
    Route::post('/promos', [AdminDashboardController::class, 'storePromo'])->name('admin.promos.store');
    Route::delete('/promos/{id}', [AdminDashboardController::class, 'deletePromo'])->name('admin.promos.delete');

    // Admin Customers
    Route::get('/customers', [AdminDashboardController::class, 'customers'])->name('admin.customers');
    Route::post('/customers/{id}/toggle-status', [AdminDashboardController::class, 'toggleCustomerStatus'])->name('admin.customers.toggle');
});

require __DIR__.'/auth.php';
