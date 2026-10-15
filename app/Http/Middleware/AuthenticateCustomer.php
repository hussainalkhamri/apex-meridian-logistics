<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('customer')->check()) {
            return redirect()
                ->guest(route('customer.login'))
                ->with('error', 'يرجى تسجيل دخول العميل أولاً للمتابعة.');
        }

        $customer = Auth::guard('customer')->user();

        if (! $customer?->portal_enabled) {
            Auth::guard('customer')->logout();
            $request->session()->put('pending_customer_id', $customer?->id);

            return redirect()->route('customer.pending');
        }

        return $next($request);
    }
}
