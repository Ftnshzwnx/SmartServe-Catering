<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Package;
use App\Models\PackageAddon;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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

        // Monthly sales data
        $monthlySales = Order::select(
            DB::raw('MONTH(delivery_date) as month'),
            DB::raw('SUM(total_price) as revenue')
        )
        ->where('status', 'Completed')
        ->whereYear('delivery_date', date('Y'))
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        $recentOrders = Order::with('user')->orderBy('created_at', 'desc')->take(5)->get();

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'totalOrders' => $totalOrders,
                'pendingOrders' => $pendingOrders,
                'completedOrders' => $completedOrders,
                'cancelledOrders' => $cancelledOrders,
                'totalRevenue' => $totalRevenue,
                'pendingPayment' => $pendingPayment,
            ],
            'monthlySales' => $monthlySales,
            'recentOrders' => $recentOrders,
        ]);
    }

    // Orders Management
    public function orders(Request $request): Response
    {
        $status = $request->input('status');
        $query = Order::with('user');

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'currentStatus' => $status,
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

        // Send Status Email
        $this->sendStatusMail($order->user->email, $order, $action, $note);

        return redirect()->back()->with('success', 'Order status updated and customer notified.');
    }

    // Packages Management
    public function packages(): Response
    {
        $packages = Package::with('addons')->orderBy('package_name')->get();
        return Inertia::render('Admin/Packages/Index', [
            'packages' => $packages
        ]);
    }

    public function storePackage(Request $request): RedirectResponse
    {
        $request->validate([
            'package_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'min_order' => 'required|integer|min:1',
            'description' => 'required|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('menu'), $fileName);
            $imagePath = $fileName;
        }

        Package::create([
            'package_name' => $request->input('package_name'),
            'price' => $request->input('price'),
            'min_order' => $request->input('min_order'),
            'description' => $request->input('description'),
            'image' => $imagePath,
        ]);

        return redirect()->back()->with('success', 'Package created successfully.');
    }

    public function updatePackage(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'package_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'min_order' => 'required|integer|min:1',
            'description' => 'required|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $pkg = Package::findOrFail($id);

        if ($request->hasFile('image')) {
            // Delete old file if present
            if ($pkg->image && file_exists(public_path('menu/' . $pkg->image))) {
                @unlink(public_path('menu/' . $pkg->image));
            }
            $file = $request->file('image');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('menu'), $fileName);
            $pkg->image = $fileName;
        }

        $pkg->package_name = $request->input('package_name');
        $pkg->price = $request->input('price');
        $pkg->min_order = $request->input('min_order');
        $pkg->description = $request->input('description');
        $pkg->save();

        return redirect()->back()->with('success', 'Package updated successfully.');
    }

    public function deletePackage(int $id): RedirectResponse
    {
        $pkg = Package::findOrFail($id);
        if ($pkg->image && file_exists(public_path('menu/' . $pkg->image))) {
            @unlink(public_path('menu/' . $pkg->image));
        }
        $pkg->delete();
        return redirect()->back()->with('success', 'Package deleted.');
    }

    // Addons Management
    public function storeAddon(Request $request): RedirectResponse
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'addon_name' => 'required|string|max:255',
            'price_per_pax' => 'required|numeric|min:0',
        ]);

        PackageAddon::create([
            'package_id' => $request->input('package_id'),
            'addon_name' => $request->input('addon_name'),
            'price_per_pax' => $request->input('price_per_pax'),
        ]);

        return redirect()->back()->with('success', 'Addon added.');
    }

    public function deleteAddon(int $id): RedirectResponse
    {
        $addon = PackageAddon::findOrFail($id);
        $addon->delete();
        return redirect()->back()->with('success', 'Addon deleted.');
    }

    // Settings
    public function settings(): Response
    {
        $settings = Setting::all()->pluck('setting_value', 'setting_key');
        return Inertia::render('Admin/Settings', [
            'settings' => $settings
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'business_name' => 'required|string',
            'qr_code' => 'nullable|image|max:2048',
        ]);

        Setting::updateOrCreate(
            ['setting_key' => 'business_name'],
            ['setting_value' => $request->input('business_name')]
        );

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

        $body = "
            <h3 style='color: #b89047;'>{$title}</h3>
            <p>Dear Customer,</p>
            <p>{$desc}</p>
            <p>If you have any questions, reply to this email or contact customer service.</p>
            <p>Warm regards,<br><strong>SmartServe Catering Admin</strong></p>
        ";

        try {
            Mail::html($body, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            // fail-silent
        }
    }
}
