<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();

            if (! $customer?->portal_enabled) {
                Auth::guard('customer')->logout();
                session(['pending_customer_id' => $customer?->id]);

                return redirect()->route('customer.pending');
            }

            return redirect()->route('customer.dashboard');
        }

        return view('customer.auth', [
            'accountType' => 'customer',
            'mode' => 'login',
        ]);
    }

    public function showRegister()
    {
        if (Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();

            if (! $customer?->portal_enabled) {
                Auth::guard('customer')->logout();
                session(['pending_customer_id' => $customer?->id]);

                return redirect()->route('customer.pending');
            }

            return redirect()->route('customer.dashboard');
        }

        return view('customer.auth', [
            'accountType' => 'customer',
            'mode' => 'register',
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'company' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = Customer::where('email', $data['email'])->first();

        if ($customer && filled($customer->password)) {
            if (! $customer->portal_enabled) {
                $request->session()->put('pending_customer_id', $customer->id);

                return redirect()->route('customer.pending');
            }

            return back()
                ->withErrors(['email' => 'هذا البريد لديه حساب عميل مسبقاً. يرجى تسجيل الدخول.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $customer = $customer ?: new Customer();
        $customer->fill([
            'company' => $data['company'],
            'contact_name' => $data['contact_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'password' => $data['password'],
            'portal_enabled' => false,
            'notes' => $customer->notes ?: 'تم إنشاء حساب العميل من البوابة الإلكترونية.',
        ]);
        $customer->save();

        $request->session()->put('pending_customer_id', $customer->id);

        AuditLog::logAction('CUSTOMER_REGISTER_PENDING', 'customers', (string) $customer->id, [], [
            'email' => $customer->email,
            'portal_enabled' => false,
            'source' => 'customer_portal',
        ]);

        $this->createNotification([
            'message' => 'طلب اعتماد حساب عميل جديد: ' . $customer->company,
            'booking_id' => null,
            'target' => 'general',
            'read' => false,
        ]);

        return redirect()->route('customer.pending');
    }

    public function pending(Request $request)
    {
        if (Auth::guard('customer')->check()) {
            $customer = Auth::guard('customer')->user();

            if ($customer?->portal_enabled) {
                return redirect()->route('customer.dashboard');
            }

            Auth::guard('customer')->logout();
            $request->session()->put('pending_customer_id', $customer?->id);
        }

        $customer = null;
        $pendingCustomerId = $request->session()->get('pending_customer_id');

        if ($pendingCustomerId) {
            $customer = Customer::find($pendingCustomerId);

            if ($customer?->portal_enabled) {
                $request->session()->forget('pending_customer_id');

                return redirect()
                    ->route('customer.login')
                    ->with('success', 'تم اعتماد حسابك. يمكنك تسجيل الدخول الآن.');
            }
        }

        return view('customer.pending-approval', compact('customer'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $customer = Customer::where('email', $credentials['email'])->first();

        if ($customer && filled($customer->password) && Hash::check($credentials['password'], $customer->password)) {
            if (! $customer->portal_enabled) {
                $request->session()->put('pending_customer_id', $customer->id);

                AuditLog::logAction('CUSTOMER_LOGIN_PENDING', 'customers', (string) $customer->id, [], [
                    'email' => $customer->email,
                    'guard' => 'customer',
                    'portal_enabled' => false,
                ]);

                return redirect()->route('customer.pending');
            }
        }

        if (Auth::guard('customer')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'portal_enabled' => true,
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $customer = Auth::guard('customer')->user();
            $customer?->forceFill(['last_login_at' => now()])->save();

            if ($customer) {
                AuditLog::logAction('CUSTOMER_LOGIN', 'customers', (string) $customer->id, [], [
                    'email' => $customer->email,
                    'guard' => 'customer',
                ]);
            }

            return redirect()->intended(route('customer.dashboard'));
        }

        AuditLog::logAction('CUSTOMER_LOGIN_FAIL', 'customers', $credentials['email'], [], [
            'email' => $credentials['email'],
            'guard' => 'customer',
        ]);

        return back()
            ->withErrors(['email' => 'بيانات الدخول غير صحيحة أو حساب العميل غير مفعل.'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if ($customer) {
            AuditLog::logAction('CUSTOMER_LOGOUT', 'customers', (string) $customer->id, [], [
                'email' => $customer->email,
                'guard' => 'customer',
            ]);
        }

        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login')->with('success', 'تم تسجيل الخروج من بوابة العميل.');
    }
}
