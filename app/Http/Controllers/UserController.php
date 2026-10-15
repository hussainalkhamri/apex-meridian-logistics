<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q
                ->where('full_name', 'like', "%$s%")
                ->orWhere('username', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")
            );
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $users  = $query->latest()->get();
        $roles  = Role::all();

        return view('pages.users', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'max:50', 'unique:users,username', 'regex:/^[a-zA-Z0-9_]+$/'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'password'  => ['required', Password::min(8)],
            'role_id'   => ['nullable', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'full_name.required' => 'الاسم الكامل مطلوب',
            'username.required'  => 'اسم المستخدم مطلوب',
            'username.unique'    => 'اسم المستخدم مستخدم بالفعل',
            'username.regex'     => 'اسم المستخدم يجب أن يحتوي على أحرف وأرقام إنجليزية فقط',
            'password.required'  => 'كلمة المرور مطلوبة',
        ]);

        $data = $request->only(['full_name', 'username', 'phone', 'password', 'role_id']);
        $data['is_active'] = $request->boolean('is_active', true);

        $user = User::create($data);

        AuditLog::logAction('CREATE', 'users', $user->id, [], collect($data)->except('password')->toArray());

        return redirect()->route('users.index')->with('success', 'تم إضافة المستخدم بنجاح');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'max:50', 'unique:users,username,' . $user->id, 'regex:/^[a-zA-Z0-9_]+$/'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'password'  => ['nullable', Password::min(8)],
            'role_id'   => ['nullable', 'exists:roles,id'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'full_name.required' => 'الاسم الكامل مطلوب',
            'username.required'  => 'اسم المستخدم مطلوب',
            'username.unique'    => 'اسم المستخدم مستخدم بالفعل',
        ]);

        $old  = $user->only(['full_name', 'username', 'phone', 'role_id', 'is_active']);
        $data = $request->only(['full_name', 'username', 'phone', 'role_id']);
        $data['is_active'] = $request->boolean('is_active');

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        AuditLog::logAction('UPDATE', 'users', $user->id, $old, collect($data)->except('password')->toArray());

        return redirect()->route('users.index')->with('success', 'تم تحديث المستخدم بنجاح');
    }

    public function updatePassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.required' => 'كلمة المرور مطلوبة',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
        ]);

        $user->update([
            'password' => $data['password'],
        ]);

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->when($user->id === Auth::id(), fn ($query) => $query->where('id', '!=', $request->session()->getId()))
                ->delete();
        }

        AuditLog::logAction('UPDATE', 'users', $user->id, [], [
            'password_changed' => true,
        ]);

        return redirect()->route('users.index')->with('success', 'تم تغيير كلمة المرور بنجاح');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'لا يمكنك حذف حسابك الخاص');
        }

        AuditLog::logAction('DELETE', 'users', $user->id, $user->toArray());
        $user->delete();

        return redirect()->route('users.index')->with('success', 'تم حذف المستخدم');
    }

    public function toggleActive(User $user)
    {
        if ($user->id === Auth::id()) {
            return response()->json(['error' => 'لا يمكنك تعطيل حسابك الخاص'], 422);
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'تفعيل' : 'تعطيل';
        AuditLog::logAction('UPDATE', 'users', $user->id, [], ['is_active' => $user->is_active]);

        return redirect()->back()->with('success', "تم {$status} المستخدم بنجاح");
    }
}
