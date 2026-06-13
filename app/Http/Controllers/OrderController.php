<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $cartCount = Cart::where('user_id', $user->id)->count();

        $tab = $request->input('tab', 'all');

        $query = Order::where('user_id', $user->id)->orderBy('created_at', 'desc');

        if ($tab === 'pending') {
            $query->whereIn('status', ['Pending', 'Deposit Rejected']);
        } elseif ($tab === 'confirmed') {
            $query->where('status', 'Confirmed');
        } elseif ($tab === 'delivered') {
            $query->whereIn('status', ['Delivered', 'Balance Rejected']);
        } elseif ($tab === 'payment_submitted') {
            $query->where('status', 'Payment Submitted');
        } elseif ($tab === 'completed') {
            $query->where('status', 'Completed');
        } elseif ($tab === 'cancelled') {
            $query->where('status', 'Cancelled');
        } elseif ($tab === 'proposals') {
            $query->where('is_custom_proposal', true)
                  ->whereIn('status', ['Pending Proposal', 'Proposal Sent']);
        }

        $orders = $query->with('items.package')->get();

        // Calculate counts for badges
        $notifPending = Order::where('user_id', $user->id)->whereIn('status', ['Pending', 'Deposit Rejected'])->count();
        $notifConfirmed = Order::where('user_id', $user->id)->where('status', 'Confirmed')->count();
        $notifDelivered = Order::where('user_id', $user->id)->whereIn('status', ['Delivered', 'Balance Rejected'])->count();
        $notifProposals = Order::where('user_id', $user->id)
            ->where('is_custom_proposal', true)
            ->whereIn('status', ['Pending Proposal', 'Proposal Sent'])
            ->count();

        return Inertia::render('Customer/Orders/Index', [
            'orders' => $orders,
            'cartCount' => $cartCount,
            'tab' => $tab,
            'notifications' => [
                'pending' => $notifPending,
                'confirmed' => $notifConfirmed,
                'delivered' => $notifDelivered,
                'proposals' => $notifProposals,
                'total' => $notifPending + $notifConfirmed + $notifDelivered + $notifProposals,
            ]
        ]);
    }

    public function checkout(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $selectedItems = $request->input('selected_items', []);

        if (empty($selectedItems)) {
            // Check session fallback
            $selectedItems = session('checkout_items', []);
        } else {
            session(['checkout_items' => $selectedItems]);
        }

        if (empty($selectedItems)) {
            return redirect()->route('cart.index')->with('error', 'Please select items to checkout.');
        }

        $cartItems = Cart::where('user_id', $user->id)
            ->whereIn('id', $selectedItems)
            ->with('package')
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'No valid items selected.');
        }

        $cartCount = Cart::where('user_id', $user->id)->count();

        // Get QR Code
        $qrSetting = Setting::where('setting_key', 'qr_code_path')->first();
        $qrCodeFile = $qrSetting ? $qrSetting->setting_value : 'admin/uploads/qr_default.png';

        $blockedDates = \App\Models\BlockedDate::pluck('blocked_date')->map(function($date) {
            return is_string($date) ? $date : $date->format('Y-m-d');
        })->toArray();

        $activePromos = \App\Models\PromoCode::where('active', 1)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->orderBy('code')
            ->get();

        $deliveryZones = \App\Models\DeliveryZone::orderBy('name')->get();

        return Inertia::render('Customer/Checkout/Index', [
            'cartItems' => $cartItems,
            'cartCount' => $cartCount,
            'qrCodeFile' => $qrCodeFile,
            'blockedDates' => $blockedDates,
            'activePromos' => $activePromos,
            'deliveryZones' => $deliveryZones,
            'userData' => [
                'full_name' => $user->full_name,
                'phone' => $user->phone,
                'address' => $user->address,
            ]
        ]);
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'delivery_zone' => 'required|string',
            'delivery_date' => 'required|date|after_or_equal:+7 days',
            'delivery_time' => 'required',
            'receipt' => 'required|file|mimes:jpeg,png,jpg,pdf|max:4096',
            'promo_code_id' => 'nullable|exists:promo_codes,id',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();

        // 1. Blocked dates check
        $deliveryDateInput = $request->input('delivery_date');
        $isBlocked = \App\Models\BlockedDate::where('blocked_date', $deliveryDateInput)->exists();
        if ($isBlocked) {
            return redirect()->back()->withErrors(['delivery_date' => 'The selected date is fully booked/blocked. Please select another date.'])->withInput();
        }

        $selectedItems = session('checkout_items', []);

        if (empty($selectedItems)) {
            return redirect()->route('cart.index')->with('error', 'Checkout session expired.');
        }

        $cartItems = Cart::where('user_id', $user->id)
            ->whereIn('id', $selectedItems)
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'No items in checkout.');
        }

        // Calculate Totals
        $totalPrice = 0;
        $packageNames = [];
        foreach ($cartItems as $item) {
            $totalPrice += (float)$item->total_price;
            $packageNames[] = $item->package_name;
        }

        // Get Delivery Fee dynamically from DeliveryZone table
        $dbZones = \App\Models\DeliveryZone::all();
        $zoneFees = ['Self-Pickup' => 0.00];
        foreach ($dbZones as $dz) {
            $zoneFees[$dz->name] = (float)$dz->fee;
        }

        $deliveryZone = $request->input('delivery_zone');
        if ($deliveryZone !== 'Self-Pickup' && !isset($zoneFees[$deliveryZone])) {
            return redirect()->back()->withErrors(['delivery_zone' => 'The selected delivery zone is invalid.'])->withInput();
        }

        $deliveryFee = $zoneFees[$deliveryZone] ?? 0.00;

        // Apply Promo Code
        $discountAmount = 0.00;
        $promoCodeId = null;
        if ($request->input('promo_code_id')) {
            $promo = \App\Models\PromoCode::find($request->input('promo_code_id'));
            if ($promo && $promo->active && (!$promo->expires_at || $promo->expires_at->isFuture()) && $totalPrice >= $promo->min_spend) {
                $promoCodeId = $promo->id;
                if ($promo->type === 'percent') {
                    $discountAmount = $totalPrice * ($promo->value / 100);
                } else {
                    $discountAmount = (float)$promo->value;
                }
                $discountAmount = min($discountAmount, $totalPrice);
            }
        }

        $grandTotal = $totalPrice + $deliveryFee - $discountAmount;

        // Dynamic deposit and balance using global settings
        $depositPercentSetting = Setting::where('setting_key', 'deposit_percentage')->first();
        $depositPercent = $depositPercentSetting ? (float)$depositPercentSetting->setting_value : 30;

        $deposit = $grandTotal * ($depositPercent / 100);
        $balance = $grandTotal - $deposit;

        // Upload Receipt
        $paymentProof = null;
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/receipts'), $fileName);
            $paymentProof = 'uploads/receipts/' . $fileName;
        }

        // Get QR Code
        $qrSetting = Setting::where('setting_key', 'qr_code_path')->first();
        $qrCodeFile = $qrSetting ? $qrSetting->setting_value : 'admin/uploads/qr_default.png';

        // Save Order
        $order = DB::transaction(function () use ($user, $packageNames, $request, $grandTotal, $paymentProof, $qrCodeFile, $cartItems, $selectedItems, $promoCodeId, $discountAmount, $deliveryZone, $deliveryFee) {
            $order = Order::create([
                'user_id' => $user->id,
                'package_name' => implode(', ', $packageNames),
                'delivery_address' => $request->input('address'),
                'delivery_zone' => $deliveryZone,
                'delivery_fee' => $deliveryFee,
                'total_price' => $grandTotal,
                'package_image' => $cartItems[0]->package->image ?? null,
                'payment_proof' => $paymentProof,
                'status' => 'Pending',
                'delivery_date' => $request->input('delivery_date'),
                'delivery_time' => $request->input('delivery_time'),
                'qr_code_path' => $qrCodeFile,
                'notes' => $request->input('notes'),
                'promo_code_id' => $promoCodeId,
                'discount_amount' => $discountAmount,
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'package_id' => $item->package_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->total_price,
                    'selected_dishes' => $item->selected_dishes,
                    'selected_addons' => $item->selected_addons,
                    'addon_cost' => $item->addon_cost ?: 0.00,
                ]);
            }

            // Clear Cart items
            Cart::where('user_id', $user->id)->whereIn('id', $selectedItems)->delete();

            return $order;
        });

        // Clear Session
        session()->forget('checkout_items');

        // Trigger notification
        \App\Models\SystemNotification::notifyAdmins(
            'Tempahan Baru / New Order',
            "Tempahan #{$order->id} telah dibuat oleh {$user->name} untuk tarikh {$order->delivery_date}.",
            'order_placed',
            "/admin/orders?search={$order->id}"
        );

        // Send Email Notification
        $this->sendOrderEmail($user->email, $order, $deposit, $balance, $request->input('name'));

        return redirect()->route('orders.index')->with('success', 'Order #' . $order->id . ' placed! Waiting for admin verification.');
    }

    public function show(Request $request, int $id): Response
    {
        $user = $request->user();
        $cartCount = Cart::where('user_id', $user->id)->count();

        $order = Order::where('user_id', $user->id)
            ->with(['items.package.dishes', 'review'])
            ->findOrFail($id);

        return Inertia::render('Customer/Orders/Show', [
            'order' => $order,
            'cartCount' => $cartCount,
        ]);
    }

    public function applyPromo(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $code = strtoupper($request->input('code'));
        $subtotal = (float)$request->input('subtotal');

        $promo = \App\Models\PromoCode::where('code', $code)->first();

        if (!$promo) {
            return response()->json([
                'valid' => false,
                'message' => 'Promo code not found.'
            ]);
        }

        if (!$promo->active) {
            return response()->json([
                'valid' => false,
                'message' => 'Promo code is inactive.'
            ]);
        }

        if ($promo->expires_at && $promo->expires_at->isPast()) {
            return response()->json([
                'valid' => false,
                'message' => 'Promo code has expired.'
            ]);
        }

        if ($subtotal < $promo->min_spend) {
            return response()->json([
                'valid' => false,
                'message' => 'Minimum spend of RM ' . number_format($promo->min_spend, 2) . ' is required.'
            ]);
        }

        // Calculate discount
        $discount = 0.00;
        if ($promo->type === 'percent') {
            $discount = $subtotal * ($promo->value / 100);
        } else {
            $discount = (float)$promo->value;
        }

        $discount = min($discount, $subtotal);

        return response()->json([
            'valid' => true,
            'promo_code_id' => $promo->id,
            'code' => $promo->code,
            'discount_amount' => $discount,
            'message' => 'Promo code applied successfully!'
        ]);
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $order = Order::where('user_id', $user->id)->findOrFail($id);

        if (in_array($order->status, ['Cancelled', 'Completed'])) {
            return redirect()->back()->with('error', 'This order cannot be cancelled.');
        }

        // Pull dynamic policy setting
        $cancelPolicyDaysSetting = Setting::where('setting_key', 'cancellation_policy_days')->first();
        $cancelPolicyDays = $cancelPolicyDaysSetting ? (int)$cancelPolicyDaysSetting->setting_value : 7;

        $deliveryDate = new \DateTime($order->delivery_date);
        $today = new \DateTime();
        
        $today->setTime(0, 0, 0);
        $deliveryDate->setTime(0, 0, 0);
        $diff = $today->diff($deliveryDate);
        $daysDiff = $diff->invert ? -$diff->days : $diff->days;

        if ($daysDiff < $cancelPolicyDays) {
            return redirect()->back()->with('error', 'Cancellations must be made at least ' . $cancelPolicyDays . ' days before the event.');
        }

        $order->status = 'Cancelled';
        $order->cancelled_by = 'user';
        $order->cancelled_at = now();
        $order->save();

        // Trigger notification
        \App\Models\SystemNotification::notifyAdmins(
            'Tempahan Dibatalkan / Order Cancelled',
            "Tempahan #{$order->id} telah dibatalkan oleh {$user->name}.",
            'order_cancelled',
            "/admin/orders?search={$order->id}"
        );

        // Send Email Notice
        $this->sendCancelEmail($user->email, $order, $user->full_name);

        return redirect()->route('orders.index', ['tab' => 'cancelled'])->with('success', 'Order cancelled successfully.');
    }

    public function reupload(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'receipt' => 'required|file|mimes:jpeg,png,jpg,pdf|max:4096',
            'type' => 'required|in:deposit,balance',
        ]);

        $user = $request->user();
        $order = Order::where('user_id', $user->id)->findOrFail($id);

        // Upload Receipt
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/receipts'), $fileName);
            $order->payment_proof = 'uploads/receipts/' . $fileName;
            $type = $request->input('type');
            if ($type === 'deposit') {
                $order->status = 'Pending';
            } else {
                $order->status = 'Payment Submitted';
            }
            $order->save();

            // Trigger notification
            $typeName = $type === 'deposit' ? 'deposit' : 'baki / balance';
            \App\Models\SystemNotification::notifyAdmins(
                'Resit Pembayaran Dimuat Naik / Payment Receipt Uploaded',
                "Resit {$typeName} baru dimuat naik untuk tempahan #{$order->id} oleh {$user->name}.",
                'payment_submitted',
                "/admin/orders?search={$order->id}"
            );
        }

        return redirect()->route('orders.index')->with('success', 'Receipt uploaded. Waiting for verification.');
    }

    public function downloadInvoice(Request $request, int $id)
    {
        $user = $request->user();
        
        $query = Order::with(['items.package.dishes', 'user']);
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }
        $order = $query->findOrFail($id);

        $settings = Setting::all()->pluck('setting_value', 'setting_key')->toArray();
        $pdf = Pdf::loadView('pdf.invoice', compact('order', 'settings'));
        return $pdf->stream('invoice_' . $order->id . '.pdf');
    }

    public function downloadReceipt(Request $request, int $id)
    {
        $user = $request->user();
        
        $query = Order::with(['user']);
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }
        $order = $query->findOrFail($id);

        if (!in_array($order->status, ['Confirmed', 'Delivered', 'Completed'])) {
            return redirect()->back()->with('error', 'Receipt is not available yet.');
        }

        $settings = Setting::all()->pluck('setting_value', 'setting_key')->toArray();
        $pdf = Pdf::loadView('pdf.receipt', compact('order', 'settings'));
        return $pdf->stream('receipt_' . $order->id . '.pdf');
    }

    private function sendOrderEmail($email, $order, $deposit, $balance, $customerName)
    {
        $subject = "Order Confirmation - SmartServe Catering";

        try {
            Mail::send('emails.order_confirmation', [
                'order' => $order,
                'deposit' => $deposit,
                'balance' => $balance,
                'customerName' => $customerName,
                'subject' => $subject,
            ], function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            // Log mail failure but do not break checkout
        }
    }

    private function sendCancelEmail($email, $order, $customerName)
    {
        $subject = "Cancellation Confirmed: Order #{$order->id}";

        try {
            Mail::send('emails.order_cancellation', [
                'order' => $order,
                'customerName' => $customerName,
                'subject' => $subject,
            ], function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            // Log
        }
    }
}
