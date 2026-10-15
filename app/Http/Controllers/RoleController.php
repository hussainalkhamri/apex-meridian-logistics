<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->get();
        return view('pages.roles', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(Role::validPermissionKeys())],
        ], [
            'name.required' => 'اسم الدور مطلوب',
            'name.unique'   => 'اسم الدور موجود بالفعل',
        ]);

        $data['permissions'] = Role::normalizePermissions($data['permissions'] ?? []);
        $role = Role::create($data);

        AuditLog::logAction('CREATE', 'roles', $role->id, [], ['name' => $role->name]);

        return redirect()->route('roles.index')->with('success', 'تم إضافة الدور بنجاح');
    }

    public function update(Request $request, Role $role)
    {
        // Protect super_admin from external modification
        if ($role->isSuperAdmin()) {
            return back()->with('error', 'لا يمكن تعديل دور المدير العام');
        }

        $data = $request->validate([
            'name'        => ['required', 'string', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(Role::validPermissionKeys())],
        ], [
            'name.required' => 'اسم الدور مطلوب',
            'name.unique'   => 'اسم الدور موجود بالفعل',
        ]);

        $old = $role->only(['name', 'permissions']);
        $data['permissions'] = Role::normalizePermissions($data['permissions'] ?? []);
        $role->update($data);

        AuditLog::logAction('UPDATE', 'roles', $role->id, $old, $data);

        return redirect()->route('roles.index')->with('success', 'تم تحديث الدور بنجاح');
    }

    public function destroy(Role $role)
    {
        if ($role->isSuperAdmin()) {
            return back()->with('error', 'لا يمكن حذف دور المدير العام');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'لا يمكن حذف دور مرتبط بمستخدمين');
        }

        AuditLog::logAction('DELETE', 'roles', $role->id, $role->toArray());
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'تم حذف الدور');
    }
}
