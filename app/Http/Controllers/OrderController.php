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
        }

        $orders = $query->with('items.package')->get();

        // Calculate counts for badges
        $notifPending = Order::where('user_id', $user->id)->whereIn('status', ['Pending', 'Deposit Rejected'])->count();
        $notifConfirmed = Order::where('user_id', $user->id)->where('status', 'Confirmed')->count();
        $notifDelivered = Order::where('user_id', $user->id)->whereIn('status', ['Delivered', 'Balance Rejected'])->count();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'cartCount' => $cartCount,
            'tab' => $tab,
            'notifications' => [
                'pending' => $notifPending,
                'confirmed' => $notifConfirmed,
                'delivered' => $notifDelivered,
                'total' => $notifPending + $notifConfirmed + $notifDelivered,
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

        return Inertia::render('Checkout/Index', [
            'cartItems' => $cartItems,
            'cartCount' => $cartCount,
            'qrCodeFile' => $qrCodeFile,
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
            'delivery_date' => 'required|date|after_or_equal:+7 days',
            'delivery_time' => 'required',
            'receipt' => 'required|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ]);

        $user = $request->user();
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

        $deposit = $totalPrice * 0.3;
        $balance = $totalPrice * 0.7;

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
        $order = DB::transaction(function () use ($user, $packageNames, $request, $totalPrice, $paymentProof, $qrCodeFile, $cartItems, $selectedItems) {
            $order = Order::create([
                'user_id' => $user->id,
                'package_name' => implode(', ', $packageNames),
                'delivery_address' => $request->input('address'),
                'total_price' => $totalPrice,
                'package_image' => $cartItems[0]->package->image ?? null,
                'payment_proof' => $paymentProof,
                'status' => 'Pending',
                'delivery_date' => $request->input('delivery_date'),
                'delivery_time' => $request->input('delivery_time'),
                'qr_code_path' => $qrCodeFile,
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'package_id' => $item->package_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->total_price,
                ]);
            }

            // Clear Cart items
            Cart::where('user_id', $user->id)->whereIn('id', $selectedItems)->delete();

            return $order;
        });

        // Clear Session
        session()->forget('checkout_items');

        // Send Email Notification
        $this->sendOrderEmail($user->email, $order, $deposit, $balance, $request->input('name'));

        return redirect()->route('orders.index')->with('success', 'Order #' . $order->id . ' placed! Waiting for admin verification.');
    }

    public function show(Request $request, int $id): Response
    {
        $user = $request->user();
        $cartCount = Cart::where('user_id', $user->id)->count();

        $order = Order::where('user_id', $user->id)
            ->with('items.package')
            ->findOrFail($id);

        return Inertia::render('Orders/Show', [
            'order' => $order,
            'cartCount' => $cartCount,
        ]);
    }

    public function cancel(Request $request, int $id): RedirectResponse
    {
        $user = $request->user();
        $order = Order::where('user_id', $user->id)->findOrFail($id);

        if (in_array($order->status, ['Cancelled', 'Completed'])) {
            return redirect()->back()->with('error', 'This order cannot be cancelled.');
        }

        // Must be at least 7 days before
        $deliveryDate = new \DateTime($order->delivery_date);
        $today = new \DateTime();
        $diff = $today->diff($deliveryDate)->days;

        if ($diff < 7) {
            return redirect()->back()->with('error', 'Cancellations must be made at least 7 days before the event.');
        }

        $order->status = 'Cancelled';
        $order->cancelled_by = 'user';
        $order->cancelled_at = now();
        $order->save();

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
            $order->status = 'Payment Submitted'; // reset status to verify
            $order->save();
        }

        return redirect()->route('orders.index')->with('success', 'Receipt uploaded. Waiting for verification.');
    }

    private function sendOrderEmail($email, $order, $deposit, $balance, $customerName)
    {
        $subject = "Order Confirmation - SmartServe Catering";
        $body = "
            <h3 style='color: #b89047;'>Order Confirmation</h3>
            <p>Dear <strong>{$customerName}</strong>,</p>
            <p>Thank you for choosing SmartServe Catering. We have received your order details and your deposit receipt (30%) has been uploaded.</p>
            <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                <tr><td><strong>Order ID:</strong></td><td>#{$order->id}</td></tr>
                <tr><td><strong>Delivery Date:</strong></td><td>{$order->delivery_date}</td></tr>
                <tr><td><strong>Delivery Time:</strong></td><td>{$order->delivery_time}</td></tr>
                <tr><td><strong>Total Cost:</strong></td><td>RM " . number_format($order->total_price, 2) . "</td></tr>
                <tr><td><strong>Deposit Paid (30%):</strong></td><td>RM " . number_format($deposit, 2) . "</td></tr>
                <tr><td><strong>Balance Remaining (70%):</strong></td><td style='color: #d9534f; font-weight: bold;'>RM " . number_format($balance, 2) . "</td></tr>
            </table>
            <p>Best regards,<br><strong>SmartServe Catering Team</strong></p>
        ";

        try {
            Mail::html($body, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            // Log mail failure but do not break checkout
        }
    }

    private function sendCancelEmail($email, $order, $customerName)
    {
        $subject = "Cancellation Confirmed: Order #{$order->id}";
        $body = "
            <h3 style='color: #dc3545;'>Order Cancellation Notice</h3>
            <p>Dear <strong>{$customerName}</strong>,</p>
            <p>This email is to confirm that your order <strong>#{$order->id}</strong> has been cancelled per your request.</p>
            <div style='padding: 15px; background-color: #fff5f5; border: 1px solid #feb2b2; border-radius: 8px; margin: 20px 0;'>
                <h4 style='margin-top: 0; color: #c53030;'>Deposit Policy:</h4>
                <p>According to our terms, the <strong>30% deposit payment is non-refundable</strong> for customer cancellations.</p>
            </div>
            <p>Warm regards,<br><strong>SmartServe Catering Team</strong></p>
        ";

        try {
            Mail::html($body, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            // Log
        }
    }
}
