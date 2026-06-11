<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $notification = \App\Models\SystemNotification::where('user_id', $request->user()->id)->findOrFail($id);
        $notification->update(['read_at' => now()]);
        
        if ($request->input('redirect') && $notification->link) {
            return redirect($notification->link);
        }
        
        return redirect()->back();
    }

    public function markAllAsRead(Request $request): \Illuminate\Http\RedirectResponse
    {
        \App\Models\SystemNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
            
        return redirect()->back();
    }
}
