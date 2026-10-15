<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Support\AuthorizedRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->to(AuthorizedRedirect::urlFor(Auth::user()));
        }

        return view('customer.auth', [
            'accountType' => 'staff',
            'mode' => request('mode') === 'register' ? 'register' : 'login',
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'اسم المستخدم مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        if (Auth::attempt([
            'username'  => $credentials['username'],
            'password'  => $credentials['password'],
            'is_active' => true,
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();

            AuditLog::logAction('LOGIN', 'users', Auth::id());

            return redirect()->intended(AuthorizedRedirect::urlFor(Auth::user()));
        }

        AuditLog::logAction('LOGIN_FAILED', 'users', $credentials['username'], [], [
            'username' => $credentials['username'],
            'guard' => 'web',
        ]);

        return back()->withErrors([
            'username' => 'بيانات دخول الموظف غير صحيحة أو الحساب غير مفعل.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        AuditLog::logAction('LOGOUT', 'users', Auth::id() ?? '');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
