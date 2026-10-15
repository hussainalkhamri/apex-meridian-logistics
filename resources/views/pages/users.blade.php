@extends('layouts.app')

@section('title', 'المستخدمون — {{ config('company.name.ar') }}')

@section('content')
<div>
    {{-- Hero --}}
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs">إدارة النظام</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">إدارة المستخدمين</h1>
            <p class="mt-2 max-w-2xl text-white/80">إدارة حسابات الموظفين، تعيين الأدوار، وضبط حالة النشاط.</p>
        </div>
    </section>

    <section class="container mx-auto px-4 py-8">
        @if($errors->any())
            <div class="mb-5 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                <div class="flex items-center gap-2 font-extrabold">
                    <i data-lucide="circle-alert" class="h-4 w-4"></i>
                    <span>لم يتم حفظ بيانات المستخدم</span>
                </div>
                <ul class="mt-2 list-disc space-y-1 pe-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid gap-4 md:grid-cols-4 mb-6">
            @foreach([
                ['icon' => 'user-round-cog', 'label' => 'إجمالي المستخدمين', 'value' => $users->count(), 'color' => 'text-accent bg-accent/10'],
                ['icon' => 'user-check', 'label' => 'نشطون', 'value' => $users->where('is_active', true)->count(), 'color' => 'text-success bg-success/10'],
                ['icon' => 'user-x', 'label' => 'معطّلون', 'value' => $users->where('is_active', false)->count(), 'color' => 'text-destructive bg-destructive/10'],
                ['icon' => 'shield', 'label' => 'عدد الأدوار', 'value' => $roles->count(), 'color' => 'text-primary bg-primary/10'],
            ] as $stat)
                <div class="rounded-xl border border-border/60 bg-card p-5 shadow-card flex items-center gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-lg {{ $stat['color'] }}">
                        <i data-lucide="{{ $stat['icon'] }}" class="h-6 w-6"></i>
                    </span>
                    <div>
                        <div class="text-2xl font-extrabold">{{ $stat['value'] }}</div>
                        <div class="text-sm text-muted-foreground">{{ $stat['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl border border-border/60 bg-card shadow-card overflow-hidden">
            {{-- Top bar: filter + add button --}}
            <div class="flex flex-col gap-3 border-b border-border p-5 md:flex-row md:items-center md:justify-between">
                <form action="{{ route('users.index') }}" method="GET" class="flex flex-col gap-2 md:flex-row md:items-center flex-1">
                    <div class="relative flex-1 max-w-sm">
                        <i data-lucide="search" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث بالاسم أو اسم المستخدم..." class="w-full h-10 border border-border bg-background pr-9 pl-4 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <select name="role_id" onchange="this.form.submit()" class="h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">جميع الأدوار</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ request('role_id') === $role->id ? 'selected' : '' }}>{{ $role->getDisplayName() }}</option>
                        @endforeach
                    </select>
                    <select name="is_active" onchange="this.form.submit()" class="h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">كل الحالات</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>نشط</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>معطّل</option>
                    </select>
                </form>
                <button onclick="openUserModal()" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground hover:opacity-95">
                    <i data-lucide="user-plus" class="h-4 w-4"></i> مستخدم جديد
                </button>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-right">المستخدم</th>
                            <th class="px-4 py-3 text-right">اسم الدخول</th>
                            <th class="px-4 py-3 text-right">الدور</th>
                            <th class="px-4 py-3 text-right">الهاتف</th>
                            <th class="px-4 py-3 text-right">الحالة</th>
                            <th class="px-4 py-3 text-right">تاريخ الإنشاء</th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr class="border-b border-border/40 hover:bg-secondary/20 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-accent text-accent-foreground text-xs font-bold">
                                            {{ mb_substr($user->full_name, 0, 1) }}
                                        </div>
                                        <div class="font-semibold">{{ $user->full_name }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $user->username }}</td>
                                <td class="px-4 py-3">
                                    @if($user->role)
                                        <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary border border-primary/20">
                                            {{ $user->role->getDisplayName() }}
                                        </span>
                                    @else
                                        <span class="text-xs text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground" dir="ltr">{{ $user->phone ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($user->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-bold text-success border border-success/25">
                                            <span class="h-1.5 w-1.5 rounded-full bg-success"></span>نشط
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-destructive/15 px-2.5 py-0.5 text-xs font-bold text-destructive border border-destructive/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-destructive"></span>معطّل
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ $user->created_at->toDateString() }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1 justify-end">
                                        <button onclick="openUserModal({{ $user->id ? "'" . $user->id . "'" : 'null' }}, {{ json_encode(['full_name' => $user->full_name, 'username' => $user->username, 'phone' => $user->phone, 'role_id' => $user->role_id, 'is_active' => $user->is_active]) }})" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-secondary transition-all" title="تعديل">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                        </button>
                                        <button type="button" onclick="openPasswordModal('{{ $user->id }}', @js($user->full_name), @js($user->username))" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-secondary transition-all" title="تغيير كلمة المرور">
                                            <i data-lucide="key-round" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                        </button>
                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('users.toggle', $user->id) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-secondary transition-all" title="{{ $user->is_active ? 'تعطيل' : 'تفعيل' }}">
                                                    <i data-lucide="{{ $user->is_active ? 'toggle-right' : 'toggle-left' }}" class="h-3.5 w-3.5 {{ $user->is_active ? 'text-success' : 'text-muted-foreground' }}"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-destructive/10 transition-all" title="حذف">
                                                    <i data-lucide="trash-2" class="h-3.5 w-3.5 text-destructive"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-muted-foreground">
                                    <i data-lucide="user-round-cog" class="h-10 w-10 mx-auto mb-3 opacity-30"></i>
                                    <p>لا يوجد مستخدمون مطابقون</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

{{-- Create/Edit Modal --}}
<div id="user-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-card rounded-2xl border border-border shadow-elegant" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b p-5">
            <h3 id="modal-title" class="font-extrabold text-lg">مستخدم جديد</h3>
            <button onclick="closeUserModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form id="user-form" action="{{ route('users.store') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <div id="method-field"></div>
            <input type="hidden" name="modal_user_id" id="modal-user-id" value="">

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">الاسم الكامل <span class="text-destructive">*</span></label>
                    <input type="text" name="full_name" id="inp-full-name" required placeholder="مثال: أحمد محمد" class="w-full h-10 border {{ $errors->has('full_name') ? 'border-destructive ring-1 ring-destructive/40' : 'border-border' }} bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    @error('full_name')
                        <p class="mt-1 text-xs font-semibold text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">اسم المستخدم <span class="text-destructive">*</span></label>
                    <input type="text" name="username" id="inp-username" required placeholder="مثال: ahmed_ali" dir="ltr" class="w-full h-10 border {{ $errors->has('username') ? 'border-destructive ring-1 ring-destructive/40' : 'border-border' }} bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    @error('username')
                        <p class="mt-1 text-xs font-semibold text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">الهاتف</label>
                    <input type="text" name="phone" id="inp-phone" placeholder="+966XXXXXXXXX" dir="ltr" class="w-full h-10 border {{ $errors->has('phone') ? 'border-destructive ring-1 ring-destructive/40' : 'border-border' }} bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    @error('phone')
                        <p class="mt-1 text-xs font-semibold text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">الدور</label>
                    <select name="role_id" id="inp-role" class="w-full h-10 border {{ $errors->has('role_id') ? 'border-destructive ring-1 ring-destructive/40' : 'border-border' }} bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">بدون دور</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->getDisplayName() }}</option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="mt-1 text-xs font-semibold text-destructive">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold">كلمة المرور <span id="pwd-required" class="text-destructive">*</span></label>
                <input type="password" name="password" id="inp-password" placeholder="8 أحرف على الأقل" dir="ltr" class="w-full h-10 border {{ $errors->has('password') ? 'border-destructive ring-1 ring-destructive/40' : 'border-border' }} bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                <p id="pwd-hint" class="mt-1 text-xs text-muted-foreground hidden">اتركه فارغاً للإبقاء على كلمة المرور الحالية</p>
                @error('password')
                    <p class="mt-1 text-xs font-semibold text-destructive">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="inp-active" value="1" checked class="h-4 w-4 rounded border-border accent-accent">
                <label for="inp-active" class="text-sm font-semibold">مستخدم نشط</label>
            </div>

            <div class="flex gap-3 justify-end border-t pt-4">
                <button type="button" onclick="closeUserModal()" class="h-10 rounded-lg border border-border px-5 text-sm font-semibold hover:bg-secondary transition-all">إلغاء</button>
                <button type="submit" class="h-10 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground hover:opacity-95">حفظ</button>
            </div>
        </form>
    </div>
</div>

{{-- Password Modal --}}
<div id="password-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-md bg-card rounded-2xl border border-border shadow-elegant" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b p-5">
            <div>
                <h3 class="font-extrabold text-lg">تغيير كلمة المرور</h3>
                <p id="password-modal-user" class="mt-1 text-xs text-muted-foreground"></p>
            </div>
            <button onclick="closePasswordModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form id="password-form" action="" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label for="password-new" class="mb-1.5 block text-sm font-semibold">كلمة المرور الجديدة <span class="text-destructive">*</span></label>
                <input type="password" name="password" id="password-new" required minlength="8" placeholder="8 أحرف على الأقل" dir="ltr" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
            </div>

            <div>
                <label for="password-confirmation" class="mb-1.5 block text-sm font-semibold">تأكيد كلمة المرور <span class="text-destructive">*</span></label>
                <input type="password" name="password_confirmation" id="password-confirmation" required minlength="8" placeholder="أعد كتابة كلمة المرور" dir="ltr" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
            </div>

            <div class="flex gap-3 justify-end border-t pt-4">
                <button type="button" onclick="closePasswordModal()" class="h-10 rounded-lg border border-border px-5 text-sm font-semibold hover:bg-secondary transition-all">إلغاء</button>
                <button type="submit" class="h-10 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground hover:opacity-95">تغيير كلمة المرور</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const modal = document.getElementById('user-modal');
    const form = document.getElementById('user-form');
    const passwordModal = document.getElementById('password-modal');
    const passwordForm = document.getElementById('password-form');
    const baseRoute = '{{ route('users.store') }}';
    const shouldReopenUserModal = @json($errors->any() && (old('full_name') !== null || old('username') !== null || old('modal_user_id') !== null));
    const oldUserModalId = @json(old('modal_user_id') ?: null);
    const oldUserModalData = {
        full_name: @json(old('full_name', '')),
        username: @json(old('username', '')),
        phone: @json(old('phone', '')),
        role_id: @json(old('role_id', '')),
        is_active: @json((string) old('is_active', '1') === '1'),
    };

    function openUserModal(id = null, data = null) {
        document.getElementById('modal-title').textContent = id ? 'تعديل المستخدم' : 'مستخدم جديد';
        document.getElementById('pwd-hint').classList.toggle('hidden', !id);
        document.getElementById('pwd-required').classList.toggle('hidden', !!id);
        document.getElementById('inp-password').required = !id;
        document.getElementById('modal-user-id').value = id || '';

        const methodField = document.getElementById('method-field');
        if (id) {
            form.action = `/users/${id}`;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        } else {
            form.action = baseRoute;
            methodField.innerHTML = '';
        }

        if (data) {
            document.getElementById('inp-full-name').value = data.full_name || '';
            document.getElementById('inp-username').value = data.username || '';
            document.getElementById('inp-phone').value = data.phone || '';
            document.getElementById('inp-password').value = '';
            document.getElementById('inp-active').checked = data.is_active;

            const roleSelect = document.getElementById('inp-role');
            roleSelect.value = data.role_id || '';
        } else {
            form.reset();
            document.getElementById('inp-active').checked = true;
        }

        modal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeUserModal() {
        modal.classList.add('hidden');
        form.reset();
        document.getElementById('modal-user-id').value = '';
    }

    function openPasswordModal(id, fullName, username) {
        passwordForm.action = `/users/${id}/password`;
        document.getElementById('password-modal-user').textContent = `${fullName} (${username})`;
        document.getElementById('password-new').value = '';
        document.getElementById('password-confirmation').value = '';
        passwordModal.classList.remove('hidden');
        document.getElementById('password-new').focus();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closePasswordModal() {
        passwordModal.classList.add('hidden');
        passwordForm.reset();
    }

    modal.addEventListener('click', closeUserModal);
    passwordModal.addEventListener('click', closePasswordModal);

    if (shouldReopenUserModal) {
        openUserModal(oldUserModalId, oldUserModalData);
    }
</script>
@endsection

