<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'notifications' => $request->user()
                    ? \App\Models\SystemNotification::where('user_id', $request->user()->id)
                        ->orderBy('created_at', 'desc')
                        ->take(10)
                        ->get()
                    : [],
                'unread_notifications_count' => $request->user()
                    ? \App\Models\SystemNotification::where('user_id', $request->user()->id)
                        ->whereNull('read_at')
                        ->count()
                    : 0,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'status' => $request->session()->get('status'),
            ],
            'settings' => array_merge([
                'business_name' => 'SmartServe Catering',
                'deposit_percentage' => '30',
                'cancellation_policy_days' => '7',
                'grace_period_days' => '2',
                'contact_phone' => '019-2094670',
                'contact_email' => 'admin@smartservecatering.com',
                'qr_code_path' => 'admin/uploads/qr_default.png',
            ], \Illuminate\Support\Facades\Schema::hasTable('settings')
                ? \App\Models\Setting::all()->pluck('setting_value', 'setting_key')->toArray()
                : []),
        ];
    }
}
