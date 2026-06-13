<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Package;
use App\Models\Addon;
use App\Models\Setting;
use App\Models\User;
use App\Models\Review;
use App\Models\BlockedDate;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminDashboardController extends Controller
{
    // Dashboard overview
    public function index(Request $request): Response
    {
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'Pending')->count();
        $completedOrders = Order::where('status', 'Completed')->count();
        $cancelledOrders = Order::where('status', 'Cancelled')->count();
        $totalRevenue = Order::where('status', 'Completed')->sum('total_price');
        $pendingPayment = Order::where('status', 'Payment Submitted')->count();

        // 1. Pending Verification orders (need approval/rejection)
        $pendingVerification = Order::with(['user', 'items.package.dishes'])
            ->whereIn('status', ['Pending', 'Payment Submitted'])
            ->orderBy('created_at', 'asc')
            ->take(5)
            ->get();

        // 2. Upcoming events / deliveries (confirmed bookings in the future)
        $upcomingEvents = Order::with(['user', 'items.package.dishes'])
            ->whereIn('status', ['Confirmed', 'Delivered'])
            ->where('delivery_date', '>=', date('Y-m-d'))
            ->orderBy('delivery_date', 'asc')
            ->orderBy('delivery_time', 'asc')
            ->take(5)
            ->get();

        // 3. Recent Reviews
        $recentReviews = Review::with(['user', 'order'])
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'totalOrders' => $totalOrders,
                'pendingOrders' => $pendingOrders,
                'completedOrders' => $completedOrders,
                'cancelledOrders' => $cancelledOrders,
                'totalRevenue' => $totalRevenue,
                'pendingPayment' => $pendingPayment,
            ],
            'pendingVerification' => $pendingVerification,
            'upcomingEvents' => $upcomingEvents,
            'recentReviews' => $recentReviews,
        ]);
    }

    // Orders Management
    public function orders(Request $request): Response
    {
        $status = $request->input('status');
        $search = $request->input('search');
        $query = Order::with(['user', 'items.package.dishes']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($q) use ($search, $searchTerm) {
                $q->where('id', $search)
                  ->orWhere('package_name', 'like', $searchTerm)
                  ->orWhere('delivery_address', 'like', $searchTerm)
                  ->orWhereHas('user', function ($uq) use ($searchTerm) {
                      $uq->where('full_name', 'like', $searchTerm)
                         ->orWhere('name', 'like', $searchTerm)
                         ->orWhere('email', 'like', $searchTerm)
                         ->orWhere('phone', 'like', $searchTerm);
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $dishes = \App\Models\Dish::where('active', true)->orderBy('category')->orderBy('name')->get();

        $rawCounts = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $statusCounts = [
            'all' => array_sum($rawCounts),
            'pending_proposal' => $rawCounts['Pending Proposal'] ?? 0,
            'pending_deposit' => $rawCounts['Pending'] ?? 0,
            'payment_submitted' => $rawCounts['Payment Submitted'] ?? 0,
            'confirmed' => $rawCounts['Confirmed'] ?? 0,
            'delivered' => $rawCounts['Delivered'] ?? 0,
            'completed' => $rawCounts['Completed'] ?? 0,
            'cancelled' => $rawCounts['Cancelled'] ?? 0,
        ];

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'currentStatus' => $status,
            'search' => $search,
            'dishes' => $dishes,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function verifyPayment(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'admin_note' => 'nullable|string',
        ]);

        $order = Order::with('user')->findOrFail($id);
        $action = $request->input('action');
        $note = $request->input('admin_note');

        if ($action === 'approve') {
            if ($order->status === 'Pending') {
                $order->status = 'Confirmed';
            } elseif ($order->status === 'Payment Submitted') {
                $order->status = 'Completed';
            }
        } else {
            if ($order->status === 'Pending') {
                $order->status = 'Deposit Rejected';
            } elseif ($order->status === 'Payment Submitted') {
                $order->status = 'Balance Rejected';
            }
            $order->admin_note = $note;
        }

        $order->save();

        // Trigger notification
        $statusText = $action === 'approve' ? 'diluluskan / approved' : 'ditolak / rejected';
        \App\Models\SystemNotification::send(
            $order->user_id,
            "Status Pembayaran Tempahan #{$order->id} / Payment Status Update",
            "Pembayaran resit anda untuk tempahan #{$order->id} telah {$statusText}.",
            'payment_verified',
            "/orders/{$order->id}"
        );

        // Send Status Email
        $this->sendStatusMail($order->user->email, $order, $action, $note);

        return redirect()->back()->with('success', 'Order status updated and customer notified.');
    }

    public function deliverOrder(int $id): RedirectResponse
    {
        $order = Order::with('user')->findOrFail($id);

        if ($order->status !== 'Confirmed') {
            return redirect()->back()->withErrors(['error' => 'Hanya tempahan yang telah disahkan (Confirmed) boleh dihantar.']);
        }

        $order->status = 'Delivered';
        $order->save();

        // Trigger notification
        \App\Models\SystemNotification::send(
            $order->user_id,
            "Tempahan Sedang Dihantar / Order Out for Delivery",
            "Tempahan #{$order->id} anda sedang dihantar/dipasang ke lokasi.",
            'delivery_update',
            "/orders/{$order->id}"
        );

        // Calculate baki (70% balance remaining) dynamically based on settings
        $settings = Setting::all()->pluck('setting_value', 'setting_key');
        $depositPercent = isset($settings['deposit_percentage']) ? (float)$settings['deposit_percentage'] : 30;
        $grandTotal = (float)$order->total_price;
        $balance = $grandTotal * ((100 - $depositPercent) / 100);

        // Send Notification Email to Customer
        try {
            $email = $order->user->email;
            $subject = "Pesanan Katering Anda Sedang Dihantar! / Your Order is Out for Delivery! - SmartServe Catering";
            
            Mail::send('emails.order_delivery_update', [
                'order' => $order,
                'balance' => $balance,
                'subject' => $subject,
            ], function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            // fail-silent
        }

        return redirect()->back()->with('success', 'Tempahan telah ditandakan sebagai sedang dihantar dan pelanggan telah dimaklumkan.');
    }

    public function sendProposal(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'total_price' => 'required|numeric|min:0',
            'dishes'      => 'required|array|min:1',
            'dishes.*'    => 'exists:dishes,id',
            'admin_note'  => 'nullable|string',
        ]);

        $order = Order::with('items')->where('is_custom_proposal', true)->findOrFail($id);

        $dishes = \App\Models\Dish::whereIn('id', $request->input('dishes'))->get()->map(function ($dish) {
            return [
                'id' => $dish->id,
                'name' => $dish->name,
                'category' => $dish->category,
            ];
        })->toArray();

        DB::transaction(function () use ($order, $request, $dishes) {
            $order->total_price = $request->input('total_price');
            $order->status = 'Proposal Sent';
            if ($request->has('admin_note')) {
                $order->admin_note = $request->input('admin_note');
            }
            $order->save();

            $item = $order->items()->first();
            if ($item) {
                $quantity = $item->quantity ?: 1;
                $item->price = (float)$request->input('total_price') / $quantity;
                $item->subtotal = $request->input('total_price');
                $item->selected_dishes = $dishes;
                $item->save();
            }
        });

        // Trigger notification
        \App\Models\SystemNotification::send(
            $order->user_id,
            "Cadangan Menu Sedia / Menu Proposal Ready",
            "Cadangan menu kustom untuk tempahan #{$order->id} telah dihantar. Sila semak dan luluskan.",
            'proposal_sent',
            "/orders/{$order->id}"
        );

        // Try to send notification email to the customer
        try {
            $email = $order->user->email;
            $subject = "Custom Menu Proposal Ready - SmartServe Catering";
            
            Mail::send('emails.custom_proposal', [
                'order' => $order,
                'subject' => $subject,
            ], function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            // fail silently
        }

        return redirect()->back()->with('success', 'Custom menu proposal sent successfully to customer.');
    }

    // Packages Management
    public function packages(): Response
    {
        $packages = Package::with('dishes')->orderBy('package_name')->get();
        $addons = Addon::orderBy('addon_name')->get();
        $dishes = \App\Models\Dish::orderBy('name')->get();
        $categories = \App\Models\DishCategory::orderBy('name')->get();
        return Inertia::render('Admin/Packages/Index', [
            'packages' => $packages,
            'addons' => $addons,
            'dishes' => $dishes,
            'categories' => $categories,
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:dish_categories,name',
        ]);

        \App\Models\DishCategory::create([
            'name' => $request->input('name'),
        ]);

        return redirect()->back()->with('success', 'Category added successfully.');
    }

    public function updateCategory(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:dish_categories,name,' . $id,
        ]);

        $category = \App\Models\DishCategory::findOrFail($id);
        $oldName = $category->name;
        $newName = $request->input('name');

        DB::transaction(function () use ($category, $oldName, $newName) {
            $category->update([
                'name' => $newName,
            ]);

            // Update dishes using this category name
            \App\Models\Dish::where('category', $oldName)->update([
                'category' => $newName,
            ]);

            // Update dish limits in packages
            $packages = Package::all();
            foreach ($packages as $pkg) {
                $limits = $pkg->dish_limits;
                if (is_array($limits) && isset($limits[$oldName])) {
                    $limits[$newName] = $limits[$oldName];
                    unset($limits[$oldName]);
                    $pkg->dish_limits = $limits;
                    $pkg->save();
                }
            }
        });

        return redirect()->back()->with('success', 'Category updated successfully.');
    }

    public function deleteCategory(int $id): RedirectResponse
    {
        $category = \App\Models\DishCategory::findOrFail($id);
        
        // Check if any dish uses this category
        $hasDishes = \App\Models\Dish::where('category', $category->name)->exists();
        if ($hasDishes) {
            return redirect()->back()->withErrors([
                'category' => "Kategori '{$category->name}' tidak boleh dipadam kerana terdapat hidangan yang sedang menggunakannya."
            ]);
        }

        // Check if any package has limits on this category
        $hasPackageLimit = false;
        foreach (\App\Models\Package::all() as $pkg) {
            $limits = $pkg->dish_limits;
            if (is_array($limits) && isset($limits[$category->name]) && (int)$limits[$category->name] > 0) {
                $hasPackageLimit = true;
                break;
            }
        }
        if ($hasPackageLimit) {
            return redirect()->back()->withErrors([
                'category' => "Kategori '{$category->name}' tidak boleh dipadam kerana ia sedang digunakan dalam penetapan had pakej."
            ]);
        }

        // Delete from package_limits JSON if limit is 0 or key exists
        foreach (\App\Models\Package::all() as $pkg) {
            $limits = $pkg->dish_limits;
            if (is_array($limits) && isset($limits[$category->name])) {
                unset($limits[$category->name]);
                $pkg->dish_limits = $limits;
                $pkg->save();
            }
        }

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully.');
    }

    public function clearAllCategories(): RedirectResponse
    {
        $hasDishes = \App\Models\Dish::exists();
        if ($hasDishes) {
            return redirect()->back()->withErrors([
                'category' => "Kategori tidak boleh dipadam kerana masih terdapat hidangan di dalam perpustakaan hidangan. Sila kosongkan/padam semua hidangan terlebih dahulu."
            ]);
        }

        foreach (\App\Models\Package::all() as $pkg) {
            $pkg->dish_limits = [];
            $pkg->save();
        }

        \App\Models\DishCategory::query()->delete();
        return redirect()->back()->with('success', 'Semua kategori telah dipadam.');
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $request->validate([
            'package_name' => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'min_order'    => 'required|integer|min:1',
            'description'  => 'required|string',
            'image'        => 'nullable|image|max:2048',
            'dish_limits'  => 'nullable|array',
            'dishes'       => 'nullable|array',
            'dishes.*'     => 'exists:dishes,id',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('menu'), $fileName);
            $imagePath = 'menu/' . $fileName;
        }

        $pkg = Package::create([
            'package_name' => $request->input('package_name'),
            'price'        => $request->input('price'),
            'min_order'    => $request->input('min_order'),
            'description'  => $request->input('description'),
            'image'        => $imagePath,
            'dish_limits'  => $request->input('dish_limits'),
        ]);

        if ($request->has('dishes')) {
            $pkg->dishes()->sync($request->input('dishes'));
        }

        return redirect()->back()->with('success', 'Package created successfully.');
    }

    public function updatePackage(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'package_name' => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'min_order'    => 'required|integer|min:1',
            'description'  => 'required|string',
            'image'        => 'nullable|image|max:2048',
            'dish_limits'  => 'nullable|array',
            'dishes'       => 'nullable|array',
            'dishes.*'     => 'exists:dishes,id',
        ]);

        $pkg = Package::findOrFail($id);

        if ($request->hasFile('image')) {
            $oldImagePath = $pkg->image;
            if ($oldImagePath) {
                if (strpos($oldImagePath, 'menu/') !== 0) {
                    $oldImagePath = 'menu/' . $oldImagePath;
                }
                if (file_exists(public_path($oldImagePath))) {
                    @unlink(public_path($oldImagePath));
                }
            }
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('menu'), $fileName);
            $pkg->image = 'menu/' . $fileName;
        }

        $pkg->package_name = $request->input('package_name');
        $pkg->price        = $request->input('price');
        $pkg->min_order    = $request->input('min_order');
        $pkg->description  = $request->input('description');
        $pkg->dish_limits  = $request->input('dish_limits');
        $pkg->save();

        if ($request->has('dishes')) {
            $pkg->dishes()->sync($request->input('dishes'));
        } else {
            $pkg->dishes()->sync([]);
        }

        return redirect()->back()->with('success', 'Package updated successfully.');
    }

    public function deletePackage(int $id): RedirectResponse
    {
        $pkg = Package::findOrFail($id);
        $oldImagePath = $pkg->image;
        if ($oldImagePath) {
            if (strpos($oldImagePath, 'menu/') !== 0) {
                $oldImagePath = 'menu/' . $oldImagePath;
            }
            if (file_exists(public_path($oldImagePath))) {
                @unlink(public_path($oldImagePath));
            }
        }
        $pkg->delete();
        return redirect()->back()->with('success', 'Package deleted.');
    }

    public function clearAllPackages(): RedirectResponse
    {
        $packages = Package::all();
        foreach ($packages as $pkg) {
            $oldImagePath = $pkg->image;
            if ($oldImagePath) {
                if (strpos($oldImagePath, 'menu/') !== 0) {
                    $oldImagePath = 'menu/' . $oldImagePath;
                }
                if (file_exists(public_path($oldImagePath))) {
                    @unlink(public_path($oldImagePath));
                }
            }
            $pkg->delete();
        }
        return redirect()->back()->with('success', 'Semua pakej telah dipadam.');
    }

    // Addons Management
    public function storeAddon(Request $request): RedirectResponse
    {
        $request->validate([
            'addon_name' => 'required|string|max:255|unique:addons,addon_name',
            'price_per_pax' => 'required|numeric|min:0',
        ]);

        Addon::create([
            'addon_name' => $request->input('addon_name'),
            'price_per_pax' => $request->input('price_per_pax'),
            'active' => true,
        ]);

        return redirect()->back()->with('success', 'Addon added successfully.');
    }

    public function updateAddon(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'addon_name' => 'required|string|max:255|unique:addons,addon_name,' . $id,
            'price_per_pax' => 'required|numeric|min:0',
            'active' => 'required|boolean',
        ]);

        $addon = Addon::findOrFail($id);
        $addon->update([
            'addon_name' => $request->input('addon_name'),
            'price_per_pax' => $request->input('price_per_pax'),
            'active' => $request->input('active'),
        ]);

        return redirect()->back()->with('success', 'Addon updated successfully.');
    }

    public function deleteAddon(int $id): RedirectResponse
    {
        $addon = Addon::findOrFail($id);
        $addon->delete();
        return redirect()->back()->with('success', 'Addon deleted successfully.');
    }

    public function clearAllAddons(): RedirectResponse
    {
        Addon::query()->delete();
        return redirect()->back()->with('success', 'Semua add-on telah dipadam.');
    }

    // Dishes Management
    public function storeDish(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:dishes,name',
            'category' => 'required|string|max:255',
        ]);

        \App\Models\Dish::create([
            'name' => $request->input('name'),
            'category' => $request->input('category'),
            'active' => true,
        ]);

        return redirect()->back()->with('success', 'Dish added successfully.');
    }

    public function updateDish(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:dishes,name,' . $id,
            'category' => 'required|string|max:255',
            'active' => 'required|boolean',
        ]);

        $dish = \App\Models\Dish::findOrFail($id);
        $dish->update([
            'name' => $request->input('name'),
            'category' => $request->input('category'),
            'active' => $request->input('active'),
        ]);

        return redirect()->back()->with('success', 'Dish updated successfully.');
    }

    public function deleteDish(int $id): RedirectResponse
    {
        $dish = \App\Models\Dish::findOrFail($id);
        $dish->delete();

        return redirect()->back()->with('success', 'Dish deleted successfully.');
    }

    public function clearAllDishes(): RedirectResponse
    {
        \App\Models\Dish::query()->delete();
        return redirect()->back()->with('success', 'Semua hidangan telah dipadam.');
    }

    // Reports & Analytics
    public function reports(Request $request): Response
    {
        $viewMode = $request->input('view_mode', 'monthly');
        if (!in_array($viewMode, ['monthly', 'daily'])) {
            $viewMode = 'monthly';
        }

        $availableYears = Order::where('status', 'Completed')
            ->selectRaw('YEAR(delivery_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->map(fn($y) => intval($y))
            ->toArray();
            
        if (empty($availableYears)) {
            $availableYears = [intval(date('Y'))];
        }

        $year = intval($request->input('year', date('Y')));
        if (!in_array($year, $availableYears) && $year !== intval(date('Y'))) {
            $year = $availableYears[0];
        }

        $month = intval($request->input('month', date('n')));
        if ($month < 1 || $month > 12) {
            $month = intval(date('n'));
        }

        // Apply timeframe filters to queries
        $orderQuery = Order::query();
        $orderItemQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('packages', 'order_items.package_id', '=', 'packages.id');

        if ($viewMode === 'daily') {
            $orderQuery->whereYear('delivery_date', $year)->whereMonth('delivery_date', $month);
            $orderItemQuery->whereYear('orders.delivery_date', $year)->whereMonth('orders.delivery_date', $month);
        } else {
            $orderQuery->whereYear('delivery_date', $year);
            $orderItemQuery->whereYear('orders.delivery_date', $year);
        }

        // 1. Sales performance data
        $salesData = [];
        if ($viewMode === 'daily') {
            $daysInMonth = date('t', mktime(0, 0, 0, $month, 1, $year));
            $dailySalesRaw = Order::select(
                DB::raw('DAY(delivery_date) as day'),
                DB::raw('SUM(total_price) as revenue')
            )
            ->where('status', 'Completed')
            ->whereYear('delivery_date', $year)
            ->whereMonth('delivery_date', $month)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $salesData[] = [
                    'label' => $d . ' ' . date('M', mktime(0, 0, 0, $month, 1)),
                    'revenue' => isset($dailySalesRaw[$d]) ? (float)$dailySalesRaw[$d]->revenue : 0.0,
                    'prev_revenue' => 0.0
                ];
            }
        } else {
            $monthlySalesRaw = Order::select(
                DB::raw('MONTH(delivery_date) as month'),
                DB::raw('SUM(total_price) as revenue')
            )
            ->where('status', 'Completed')
            ->whereYear('delivery_date', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

            $prevYearSalesRaw = Order::select(
                DB::raw('MONTH(delivery_date) as month'),
                DB::raw('SUM(total_price) as revenue')
            )
            ->where('status', 'Completed')
            ->whereYear('delivery_date', $year - 1)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

            for ($m = 1; $m <= 12; $m++) {
                $salesData[] = [
                    'label' => date('F', mktime(0, 0, 0, $m, 1)),
                    'revenue' => isset($monthlySalesRaw[$m]) ? (float)$monthlySalesRaw[$m]->revenue : 0.0,
                    'prev_revenue' => isset($prevYearSalesRaw[$m]) ? (float)$prevYearSalesRaw[$m]->revenue : 0.0
                ];
            }
        }

        // 2. Status distribution
        $statusDistribution = (clone $orderQuery)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        // 3. Package popularity
        $packagePopularity = $orderItemQuery
            ->select('packages.package_name', DB::raw('COUNT(*) as bookings_count'), DB::raw('SUM(order_items.subtotal) as total_revenue'))
            ->groupBy('packages.package_name')
            ->orderBy('bookings_count', 'desc')
            ->get();

        // 4. KPI stats
        $totalOrdersCount = (clone $orderQuery)->count();
        $completedOrdersCount = (clone $orderQuery)->where('status', 'Completed')->count();
        $totalRevenue = (clone $orderQuery)->where('status', 'Completed')->sum('total_price');
        $averageOrderValue = $completedOrdersCount > 0 ? ($totalRevenue / $completedOrdersCount) : 0;
        
        $cancelledOrdersCount = (clone $orderQuery)->where('status', 'Cancelled')->count();
        $cancellationRate = $totalOrdersCount > 0 ? (($cancelledOrdersCount / $totalOrdersCount) * 100) : 0;

        $topPackageName = $packagePopularity->first()->package_name ?? 'N/A';

        // 5. Addon Popularity Analysis
        $completedOrders = (clone $orderQuery)
            ->where('status', 'Completed')
            ->with('items')
            ->get();

        $addonPopularity = [];
        foreach ($completedOrders as $order) {
            foreach ($order->items as $item) {
                $addons = $item->selected_addons;
                $quantity = $item->quantity;
                if (is_array($addons)) {
                    foreach ($addons as $addonStr) {
                        if (preg_match('/^(.*)\s+\(\+RM([0-9.]+)\)$/', $addonStr, $matches)) {
                            $name = trim($matches[1]);
                            $pricePerPax = (float)$matches[2];
                        } else {
                            $name = trim($addonStr);
                            $pricePerPax = 0.0;
                        }

                        $addonRevenue = $pricePerPax * $quantity;

                        if (!isset($addonPopularity[$name])) {
                            $addonPopularity[$name] = [
                                'addon_name' => $name,
                                'bookings_count' => 0,
                                'total_revenue' => 0.0
                            ];
                        }
                        $addonPopularity[$name]['bookings_count'] += 1;
                        $addonPopularity[$name]['total_revenue'] += $addonRevenue;
                    }
                }
            }
        }

        uasort($addonPopularity, function($a, $b) {
            return $b['total_revenue'] <=> $a['total_revenue'];
        });

        $addonPopularity = array_values(array_slice($addonPopularity, 0, 5));

        // 6. Strategic audit metrics (All-time or general health based on selected parameters)
        $customersWithCompleted = Order::where('status', 'Completed')
            ->select('user_id', DB::raw('COUNT(id) as count'))
            ->groupBy('user_id')
            ->get();
        
        $totalUniqueCompletedCustomers = $customersWithCompleted->count();
        $repeatCustomersCount = $customersWithCompleted->where('count', '>=', 2)->count();
        $repeatCustomerRate = $totalUniqueCompletedCustomers > 0 
            ? round(($repeatCustomersCount / $totalUniqueCompletedCustomers) * 100, 1) 
            : 0.0;

        $customerLtv = $totalUniqueCompletedCustomers > 0 
            ? ($totalRevenue / $totalUniqueCompletedCustomers) 
            : 0.0;

        $lostRevenue = (clone $orderQuery)->where('status', 'Cancelled')->sum('total_price');

        return Inertia::render('Admin/Reports/Index', [
            'salesData' => $salesData,
            'statusDistribution' => $statusDistribution,
            'packagePopularity' => $packagePopularity,
            'kpis' => [
                'totalRevenue' => (float)$totalRevenue,
                'averageOrderValue' => (float)$averageOrderValue,
                'cancellationRate' => round($cancellationRate, 1),
                'topPackage' => $topPackageName,
                'totalBookings' => $totalOrdersCount,
                'repeatCustomerRate' => $repeatCustomerRate,
                'customerLtv' => (float)$customerLtv,
                'lostRevenue' => (float)$lostRevenue,
            ],
            'filters' => [
                'viewMode' => $viewMode,
                'year' => $year,
                'month' => $month,
            ],
            'availableYears' => $availableYears,
            'addonPopularity' => $addonPopularity,
        ]);
    }

    // Settings
    public function settings(): Response
    {
        $settings = Setting::all()->pluck('setting_value', 'setting_key');
        $deliveryZones = \App\Models\DeliveryZone::orderBy('name')->get();
        return Inertia::render('Admin/Settings', [
            'settings' => $settings,
            'deliveryZones' => $deliveryZones,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'business_name' => 'required|string',
            'deposit_percentage' => 'required|numeric|min:0|max:100',
            'cancellation_policy_days' => 'required|integer|min:0',
            'grace_period_days' => 'required|integer|min:0',
            'min_lead_time_days' => 'required|integer|min:1',
            'min_order_value' => 'required|numeric|min:0',
            'contact_phone' => 'required|string',
            'contact_email' => 'required|email',
            'business_address' => 'required|string',
            'bank_name' => 'required|string',
            'bank_account_no' => 'required|string',
            'bank_account_name' => 'required|string',
            'qr_code' => 'nullable|image|max:2048',
        ]);

        $settingsData = $request->only([
            'business_name', 
            'deposit_percentage', 
            'cancellation_policy_days', 
            'grace_period_days',
            'min_lead_time_days',
            'min_order_value',
            'contact_phone',
            'contact_email',
            'business_address',
            'bank_name',
            'bank_account_no',
            'bank_account_name',
        ]);
        foreach ($settingsData as $key => $value) {
            Setting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }

        if ($request->hasFile('qr_code')) {
            $file = $request->file('qr_code');
            $fileName = 'qr_code_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('admin/uploads'), $fileName);

            Setting::updateOrCreate(
                ['setting_key' => 'qr_code_path'],
                ['setting_value' => 'admin/uploads/' . $fileName]
            );
        }

        return redirect()->back()->with('success', 'Settings updated.');
    }

    public function storeDeliveryZone(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:delivery_zones,name',
            'fee' => 'required|numeric|min:0',
        ]);

        \App\Models\DeliveryZone::create($request->only(['name', 'fee']));

        return redirect()->back()->with('success', 'Delivery zone created successfully.');
    }

    public function updateDeliveryZone(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:delivery_zones,name,' . $id,
            'fee' => 'required|numeric|min:0',
        ]);

        $zone = \App\Models\DeliveryZone::findOrFail($id);
        $zone->update($request->only(['name', 'fee']));

        return redirect()->back()->with('success', 'Delivery zone updated successfully.');
    }

    public function deleteDeliveryZone($id): RedirectResponse
    {
        $zone = \App\Models\DeliveryZone::findOrFail($id);
        $zone->delete();

        return redirect()->back()->with('success', 'Delivery zone deleted successfully.');
    }

    public function exportOrders(Request $request)
    {
        $format = $request->input('format', 'csv');
        $status = $request->input('status');
        $viewMode = $request->input('view_mode');
        $year = $request->input('year');
        $month = $request->input('month');
        
        $query = Order::with('user')->orderBy('created_at', 'desc');
        if ($status) {
            $query->where('status', $status);
        }

        if ($viewMode === 'daily' && $year && $month) {
            $query->whereYear('delivery_date', $year)->whereMonth('delivery_date', $month);
        } elseif ($viewMode === 'monthly' && $year) {
            $query->whereYear('delivery_date', $year);
        }

        $orders = $query->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('pdf.admin_report', compact('orders', 'status', 'viewMode', 'year', 'month'));
            return $pdf->download('sales_report_' . date('Ymd') . '.pdf');
        }

        // CSV (Excel) Export
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=sales_report_' . date('Ymd') . '.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, ['Order ID', 'Customer Name', 'Customer Email', 'Catering Packages', 'Total Price (RM)', 'Status', 'Delivery Date', 'Delivery Time', 'Venue Address']);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->user->full_name,
                    $order->user->email,
                    $order->package_name,
                    $order->total_price,
                    $order->status,
                    $order->delivery_date,
                    $order->delivery_time,
                    $order->delivery_address
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Email Helper
    private function sendStatusMail($email, $order, $action, $note)
    {
        $subject = "Order Status Update - SmartServe Catering";
        
        if ($action === 'approve') {
            $title = "Payment Approved!";
            $desc = "We have successfully verified your payment for order <strong>#{$order->id}</strong>.";
            if ($order->status === 'Confirmed') {
                $desc .= "<br>Your deposit has been verified. Your booking is now <strong>CONFIRMED</strong>! See you on event date: {$order->delivery_date}.";
            } else {
                $desc .= "<br>Your full balance has been verified. The order is now completed. Thank you for choosing SmartServe Catering!";
            }
        } else {
            $title = "Payment Action Required";
            $desc = "Unfortunately, we could not verify your payment receipt for order <strong>#{$order->id}</strong>.";
            $desc .= "<br><br><strong style='color: #dc3545;'>Reason from Admin:</strong><br>{$note}";
            $desc .= "<br><br>Please log in to your dashboard and re-upload a valid payment receipt.";
        }

        try {
            Mail::send('emails.order_status_update', [
                'order' => $order,
                'action' => $action,
                'title' => $title,
                'desc' => $desc,
                'subject' => $subject,
            ], function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            // fail-silent
        }
    }

    // Admin Customer Reviews
    public function reviews(Request $request): Response
    {
        $reviews = Review::with(['order', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews
        ]);
    }

    public function replyReview(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'admin_reply' => 'required|string|max:1000',
        ]);

        $review = Review::findOrFail($id);
        $review->admin_reply = $request->input('admin_reply');
        $review->save();

        // Trigger notification
        \App\Models\SystemNotification::send(
            $review->user_id,
            "Balasan Maklum Balas / Review Replied",
            "Pihak admin telah membalas maklum balas anda untuk tempahan #{$review->order_id}.",
            'review_replied',
            "/orders/{$review->order_id}"
        );

        return redirect()->back()->with('success', 'Reply submitted successfully.');
    }

    // Admin Booking Calendar & Blocked Dates
    public function calendar(Request $request): Response
    {
        $orders = Order::with('user')
            ->whereIn('status', ['Confirmed', 'Delivered', 'Completed'])
            ->get();

        $blockedDates = BlockedDate::orderBy('blocked_date')->get();

        return Inertia::render('Admin/Calendar/Index', [
            'orders' => $orders,
            'blockedDates' => $blockedDates
        ]);
    }

    public function storeBlockedDate(Request $request): RedirectResponse
    {
        $request->validate([
            'blocked_date' => 'required|date|unique:blocked_dates,blocked_date',
            'reason' => 'nullable|string|max:255',
        ]);

        BlockedDate::create([
            'blocked_date' => $request->input('blocked_date'),
            'reason' => $request->input('reason'),
        ]);

        return redirect()->back()->with('success', 'Date has been blocked successfully.');
    }

    public function deleteBlockedDate(int $id): RedirectResponse
    {
        $blockedDate = BlockedDate::findOrFail($id);
        $blockedDate->delete();

        return redirect()->back()->with('success', 'Blocked date removed.');
    }

    // Admin Promo Codes
    public function promos(Request $request): Response
    {
        $promos = PromoCode::orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Promos/Index', [
            'promos' => $promos
        ]);
    }

    public function storePromo(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|unique:promo_codes,code|max:50',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_spend' => 'required|numeric|min:0',
            'expires_at' => 'nullable|date',
        ]);

        PromoCode::create([
            'code' => strtoupper($request->input('code')),
            'type' => $request->input('type'),
            'value' => $request->input('value'),
            'min_spend' => $request->input('min_spend'),
            'active' => true,
            'expires_at' => $request->input('expires_at'),
        ]);

        return redirect()->back()->with('success', 'Promo code created successfully.');
    }

    public function deletePromo(int $id): RedirectResponse
    {
        $promo = PromoCode::findOrFail($id);
        $promo->delete();

        return redirect()->back()->with('success', 'Promo code deleted.');
    }

    // Admin Customers Management
    public function customers(Request $request): Response
    {
        $status = $request->input('status');
        $search = $request->input('search');

        $query = User::where('role', 'customer');

        if ($status === 'active') {
            $query->where('is_blacklisted', false);
        } elseif ($status === 'suspended') {
            $query->where('is_blacklisted', true);
        }

        if ($search) {
            $searchTerm = '%' . $search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('full_name', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm)
                  ->orWhere('phone', 'like', $searchTerm)
                  ->orWhere('address', 'like', $searchTerm);
            });
        }

        $users = $query->orderBy('full_name')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'users' => $users,
            'currentStatus' => $status,
            'search' => $search,
        ]);
    }

    public function toggleCustomerStatus(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->is_blacklisted = !$user->is_blacklisted;
        $user->save();

        $status = $user->is_blacklisted ? 'suspended' : 'activated';
        return redirect()->back()->with('success', "Customer has been {$status} successfully.");
    }

    public function deleteCustomer(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->back()->with('success', 'Customer account has been permanently deleted.');
    }
}
