@extends('layouts.app')

@section('title', 'الأدوار والصلاحيات — {{ config('company.name.ar') }}')

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs">إدارة النظام</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">الأدوار والصلاحيات</h1>
            <p class="mt-2 max-w-2xl text-white/80">إنشاء وتعديل أدوار النظام وتحديد صلاحيات كل دور بدقة.</p>
        </div>
    </section>

    <section class="container mx-auto px-4 py-8">
        <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
            {{-- Roles List --}}
            <div class="space-y-4">
                @foreach($roles as $role)
                    @php
                        $isSuperAdmin = $role->isSuperAdmin();
                        $permCount = count($role->permissions ?? []);
                    @endphp
                    <div class="rounded-xl border border-border/60 bg-card shadow-card overflow-hidden">
                        <div class="flex items-center justify-between gap-3 p-5 border-b border-border/40">
                            <div class="flex items-center gap-3">
                                <div class="grid h-10 w-10 place-items-center rounded-lg {{ $isSuperAdmin ? 'bg-gradient-accent shadow-glow' : 'bg-secondary' }} text-{{ $isSuperAdmin ? 'accent-foreground' : 'foreground' }}">
                                    <i data-lucide="{{ $isSuperAdmin ? 'crown' : 'shield' }}" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <div class="font-bold">{{ $role->getDisplayName() }}</div>
                                    <div class="text-xs font-mono text-muted-foreground">{{ $role->name }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-muted-foreground">{{ $role->users_count }} مستخدم · {{ $permCount }} صلاحية</span>
                                @if(!$isSuperAdmin)
                                    <button onclick="openRoleModal({{ json_encode(['id' => $role->id, 'name' => $role->name, 'permissions' => $role->permissions ?? []]) }})" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-secondary transition-all">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                    </button>
                                    @if($role->users_count === 0)
                                        <form action="{{ route('roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('حذف الدور؟')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-destructive/10 transition-all">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5 text-destructive"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="p-5">
                            @if($isSuperAdmin)
                                <p class="text-sm text-muted-foreground">{{ __('messages.super_admin_full_permissions') }}</p>
                            @else
                                <div class="flex flex-wrap gap-2">
                                    @forelse($role->permissions ?? [] as $perm)
                                        <span class="inline-flex items-center rounded-full bg-accent/10 border border-accent/20 px-2.5 py-1 text-xs font-medium text-accent-foreground">
                                            {{ \App\Models\Role::permissionLabelFor($perm) }}
                                        </span>
                                    @empty
                                        <span class="text-sm text-muted-foreground">لا توجد صلاحيات مخصصة</span>
                                    @endforelse
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Add Role Panel --}}
            <div>
                <div class="sticky top-20 rounded-xl border border-border/60 bg-card shadow-card p-6">
                    <h2 class="mb-5 text-lg font-extrabold border-b pb-3">دور جديد</h2>
                    <form action="{{ route('roles.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="mb-1.5 block text-sm font-semibold">اسم الدور (بالإنجليزية) <span class="text-destructive">*</span></label>
                            <input type="text" name="name" required placeholder="مثال: supervisor" dir="ltr" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold">الصلاحيات</label>
                            <div class="space-y-2 max-h-64 overflow-y-auto border border-border rounded-lg p-3 bg-background">
                                @foreach(\App\Models\Role::$allPermissions as $key => $label)
                                    <label class="flex items-center gap-2 cursor-pointer hover:text-foreground text-sm">
                                        <input type="checkbox" name="permissions[]" value="{{ $key }}" class="h-4 w-4 rounded border-border accent-accent">
                                        {{ \App\Models\Role::permissionLabelFor($key) }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <button type="submit" class="w-full h-11 rounded-lg bg-gradient-accent text-sm font-bold text-accent-foreground hover:opacity-95">
                            إضافة الدور
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

{{-- Edit Role Modal --}}
<div id="role-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-card rounded-2xl border border-border shadow-elegant" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b p-5">
            <h3 class="font-extrabold text-lg">تعديل الدور</h3>
            <button onclick="closeRoleModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form id="role-form" action="" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="mb-1.5 block text-sm font-semibold">اسم الدور <span class="text-destructive">*</span></label>
                <input type="text" name="name" id="edit-role-name" required dir="ltr" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
            </div>
            <div>
                <label class="mb-2 block text-sm font-semibold">الصلاحيات</label>
                <div id="edit-permissions" class="space-y-2 max-h-64 overflow-y-auto border border-border rounded-lg p-3 bg-background">
                    @foreach(\App\Models\Role::$allPermissions as $key => $label)
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="permissions[]" value="{{ $key }}" class="edit-perm-check h-4 w-4 rounded border-border accent-accent">
                            {{ \App\Models\Role::permissionLabelFor($key) }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-3 justify-end border-t pt-4">
                <button type="button" onclick="closeRoleModal()" class="h-10 rounded-lg border border-border px-5 text-sm font-semibold hover:bg-secondary transition-all">إلغاء</button>
                <button type="submit" class="h-10 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground hover:opacity-95">حفظ التغييرات</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const roleModal = document.getElementById('role-modal');

    function openRoleModal(role) {
        document.getElementById('role-form').action = `/roles/${role.id}`;
        document.getElementById('edit-role-name').value = role.name;

        document.querySelectorAll('.edit-perm-check').forEach(cb => {
            cb.checked = (role.permissions || []).includes(cb.value);
        });

        roleModal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeRoleModal() { roleModal.classList.add('hidden'); }
    roleModal.addEventListener('click', closeRoleModal);
</script>
@endsection

