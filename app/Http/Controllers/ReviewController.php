<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request, int $orderId): RedirectResponse
    {
        $user = $request->user();
        
        $order = Order::where('user_id', $user->id)
            ->whereIn('status', ['Delivered', 'Completed'])
            ->findOrFail($orderId);

        // Check if review already exists
        if ($order->review()->exists()) {
            return redirect()->back()->with('error', 'You have already submitted a review for this order.');
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
        ]);

        Review::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'rating' => $request->input('rating'),
            'review_text' => $request->input('review_text'),
        ]);

        // Trigger notification
        \App\Models\SystemNotification::notifyAdmins(
            'Maklum Balas Baru / New Review',
            "{$user->name} telah menghantar maklum balas ({$request->input('rating')} bintang) untuk tempahan #{$order->id}.",
            'review_submitted',
            "/admin/reviews"
        );

        return redirect()->back()->with('success', 'Thank you for your feedback! Your review has been submitted.');
    }
}
