<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Dish;
use App\Models\Setting;
use App\Models\BlockedDate;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomProposalController extends Controller
{
    /**
     * Store a new custom menu request from the customer.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'budget' => 'required|numeric|min:100',
            'guest_count' => 'required|integer|min:20',
            'delivery_date' => 'required|date|after_or_equal:+7 days',
            'delivery_time' => 'required',
            'address' => 'required|string',
            'dishes' => 'required|array|min:1',
            'dishes.*' => 'exists:dishes,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // 1. Check blocked dates
        $deliveryDate = $request->input('delivery_date');
        $isBlocked = BlockedDate::where('blocked_date', $deliveryDate)->exists();
        if ($isBlocked) {
            return redirect()->back()->withErrors(['delivery_date' => 'The selected date is fully booked/blocked. Please select another date.'])->withInput();
        }

        $user = Auth::user();

        // 2. Fetch selected dishes to store as JSON wishlist
        $dishes = Dish::whereIn('id', $request->input('dishes'))->get()->map(function ($dish) {
            return [
                'id' => $dish->id,
                'name' => $dish->name,
                'category' => $dish->category,
            ];
        })->toArray();

        // Get QR Code default
        $qrSetting = Setting::where('setting_key', 'qr_code_path')->first();
        $qrCodeFile = $qrSetting ? $qrSetting->setting_value : 'admin/uploads/qr_default.png';

        // 3. Create the Custom Proposal Order
        $order = DB::transaction(function () use ($user, $request, $dishes, $qrCodeFile) {
            $order = Order::create([
                'user_id' => $user->id,
                'package_name' => 'Custom Menu Proposal (' . $request->input('guest_count') . ' Pax)',
                'delivery_address' => $request->input('address'),
                'total_price' => $request->input('budget'),
                'package_image' => null,
                'payment_proof' => null,
                'status' => 'Pending Proposal',
                'delivery_date' => $request->input('delivery_date'),
                'delivery_time' => $request->input('delivery_time'),
                'qr_code_path' => $qrCodeFile,
                'is_custom_proposal' => true,
                'admin_note' => $request->input('notes'), // Holds customer request notes initially
            ]);

            // Create order item with first package or placeholder
            $packageId = \App\Models\Package::first()->id ?? 1;

            OrderItem::create([
                'order_id' => $order->id,
                'package_id' => $packageId,
                'quantity' => $request->input('guest_count'),
                'price' => (float)$request->input('budget') / (int)$request->input('guest_count'),
                'subtotal' => $request->input('budget'),
                'selected_dishes' => $dishes,
            ]);

            return $order;
        });

        // Trigger notification
        \App\Models\SystemNotification::notifyAdmins(
            'Permohonan Menu Kustom Baru / New Custom Menu Request',
            "Permohonan menu kustom baru telah dihantar oleh {$user->name} untuk tarikh {$order->delivery_date}.",
            'proposal_requested',
            "/admin/orders?status=Pending+Proposal"
        );

        return redirect()->route('orders.index')->with('success', 'Your custom menu request has been submitted! Waiting for owner proposal.');
    }

    /**
     * Customer approves the owner's proposal.
     * Transitions status to 'Pending' so they can upload their payment receipt.
     */
    public function approve(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)
            ->where('is_custom_proposal', true)
            ->where('status', 'Proposal Sent')
            ->findOrFail($id);

        $order->status = 'Pending';
        $order->save();

        // Trigger notification
        \App\Models\SystemNotification::notifyAdmins(
            'Cadangan Menu Diterima / Proposal Approved',
            "Pelanggan {$user->name} bersetuju dengan cadangan menu untuk tempahan #{$order->id}.",
            'proposal_approved',
            "/admin/orders?search={$order->id}"
        );

        return redirect()->route('orders.index')->with('success', 'Proposal approved! Please upload your 30% deposit payment slip to confirm your booking date.');
    }

    /**
     * Customer rejects the owner's proposal.
     * Transitions status to 'Cancelled'.
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $order = Order::where('user_id', $user->id)
            ->where('is_custom_proposal', true)
            ->where('status', 'Proposal Sent')
            ->findOrFail($id);

        $order->status = 'Cancelled';
        $order->cancelled_by = 'user';
        $order->cancelled_at = now();
        $order->save();

        // Trigger notification
        \App\Models\SystemNotification::notifyAdmins(
            'Cadangan Menu Ditolak / Proposal Rejected',
            "Pelanggan {$user->name} menolak cadangan menu untuk tempahan #{$order->id}.",
            'proposal_rejected',
            "/admin/orders?search={$order->id}"
        );

        return redirect()->route('orders.index')->with('success', 'Proposal rejected and cancelled.');
    }
}
