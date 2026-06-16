<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            if (auth()->user()->role === 'customer') {
                return $next($request);
            }
            if (auth()->user()->role === 'admin') {
                $errorMsg = app()->getLocale() === 'en' 
                    ? 'Admin accounts cannot access customer ordering features.' 
                    : 'Akaun Admin tidak dibenarkan mengakses ciri tempahan pelanggan.';
                return redirect()->route('admin.dashboard')->with('error', $errorMsg);
            }
        }

        abort(403, 'Unauthorized action. Customers only.');
    }
}
