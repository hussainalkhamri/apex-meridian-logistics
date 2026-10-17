@extends('layouts.app')

@section('title', 'سجل التدقيق — ' . config('company.name.ar'))

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs">النظام</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">سجل التدقيق</h1>
            <p class="mt-2 max-w-2xl text-white/80">سجل شامل لجميع العمليات والتغييرات التي تمت على النظام.</p>
        </div>
    </section>

    <section class="container mx-auto px-4 py-8">
        {{-- Stats --}}
        <div class="grid gap-4 md:grid-cols-5 mb-6">
            @foreach([
                ['action' => null, 'label' => 'إجمالي السجلات', 'value' => $logs->count(), 'color' => 'text-accent bg-accent/10', 'icon' => 'list'],
                ['action' => 'CREATE', 'label' => \App\Models\AuditLog::actionLabelFor('CREATE'), 'value' => $logs->where('action_type', 'CREATE')->count(), 'color' => 'text-success bg-success/10', 'icon' => 'plus-circle'],
                ['action' => 'UPDATE', 'label' => \App\Models\AuditLog::actionLabelFor('UPDATE'), 'value' => $logs->where('action_type', 'UPDATE')->count(), 'color' => 'text-primary bg-primary/10', 'icon' => 'edit-3'],
                ['action' => 'DELETE', 'label' => \App\Models\AuditLog::actionLabelFor('DELETE'), 'value' => $logs->where('action_type', 'DELETE')->count(), 'color' => 'text-destructive bg-destructive/10', 'icon' => 'trash-2'],
                ['action' => 'LOGIN', 'label' => \App\Models\AuditLog::actionLabelFor('LOGIN'), 'value' => $logs->whereIn('action_type', ['LOGIN', 'LOGOUT'])->count(), 'color' => 'text-warning bg-warning/10', 'icon' => 'log-in'],
            ] as $stat)
                <div class="rounded-xl border border-border/60 bg-card p-4 shadow-card flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg {{ $stat['color'] }}">
                        <i data-lucide="{{ $stat['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <div class="text-xl font-extrabold">{{ $stat['value'] }}</div>
                        <div class="text-xs text-muted-foreground">{{ $stat['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl border border-border/60 bg-card shadow-card overflow-hidden">
            {{-- Filters --}}
            <form action="{{ route('audit-logs.index') }}" method="GET" class="flex flex-col gap-2 border-b border-border p-4 md:flex-row md:items-center">
                <div class="relative flex-1">
                    <i data-lucide="search" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث بالمستخدم أو الجدول أو الإجراء..." class="w-full h-10 border border-border bg-background pr-9 pl-4 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <select name="action_type" onchange="this.form.submit()" class="h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">كل الإجراءات</option>
                    @foreach(['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT'] as $val)
                        @php
                            $lbl = \App\Models\AuditLog::actionLabelFor($val);
                        @endphp
                        <option value="{{ $val }}" {{ request('action_type') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
                <select name="table_name" onchange="this.form.submit()" class="h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">كل الجداول</option>
                    @foreach($tables as $tbl)
                        <option value="{{ $tbl }}" {{ request('table_name') === $tbl ? 'selected' : '' }}>{{ $tbl }}</option>
                    @endforeach
                </select>
                <button type="submit" class="h-10 rounded-lg bg-secondary px-4 text-sm font-semibold hover:bg-secondary/70 transition-all">
                    <i data-lucide="filter" class="h-4 w-4 inline-block"></i> فلتر
                </button>
            </form>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-right">الوقت</th>
                            <th class="px-4 py-3 text-right">المستخدم</th>
                            <th class="px-4 py-3 text-right">الإجراء</th>
                            <th class="px-4 py-3 text-right">الجدول</th>
                            <th class="px-4 py-3 text-right">المعرف</th>
                            <th class="px-4 py-3 text-right">IP</th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr class="border-b border-border/40 hover:bg-secondary/20 transition-colors">
                                <td class="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">
                                    {{ $log->created_at->diffForHumans() }}
                                    <div class="text-[10px] font-mono opacity-60">{{ $log->created_at->toDateTimeString() }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($log->user)
                                        <div class="flex items-center gap-2">
                                            <div class="grid h-7 w-7 place-items-center rounded-full bg-gradient-accent text-accent-foreground text-[10px] font-bold shrink-0">
                                                {{ mb_substr($log->user->full_name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="font-medium text-xs">{{ $log->user->full_name }}</div>
                                                <div class="text-[10px] font-mono text-muted-foreground">{{ $log->user->username }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-xs text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold border {{ $log->action_color }}">
                                        {{ $log->action_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $log->table_name ?: '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                                    {{ $log->record_id ? Str::limit($log->record_id, 12) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground" dir="ltr">{{ $log->ip_address ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($log->old_values || $log->new_values)
                                        <button onclick="showDiff({{ json_encode(['old' => $log->old_values, 'new' => $log->new_values]) }})" class="h-7 w-7 flex items-center justify-center rounded-lg hover:bg-secondary transition-all" title="عرض التغييرات">
                                            <i data-lucide="eye" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-muted-foreground">
                                    <i data-lucide="list" class="h-10 w-10 mx-auto mb-3 opacity-30"></i>
                                    لا توجد سجلات تدقيق
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

{{-- Diff Modal --}}
<div id="diff-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-2xl bg-card rounded-2xl border border-border shadow-elegant max-h-[80vh] flex flex-col" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b p-5">
            <h3 class="font-extrabold">تفاصيل التغييرات</h3>
            <button onclick="document.getElementById('diff-modal').classList.add('hidden')" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div class="p-5 overflow-y-auto flex-1">
            <div id="diff-list" class="space-y-3"></div>
            <div id="diff-empty" class="hidden rounded-xl border border-border bg-secondary/30 p-6 text-center text-sm font-bold text-muted-foreground">
                لا توجد تفاصيل إضافية لهذا السجل
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const diffModal = document.getElementById('diff-modal');
    const diffList = document.getElementById('diff-list');
    const diffEmpty = document.getElementById('diff-empty');
    const auditFieldLabels = {
        id: 'المعرف',
        name: 'الاسم',
        full_name: 'الاسم الكامل',
        username: 'اسم المستخدم',
        email: 'البريد الإلكتروني',
        phone: 'الهاتف',
        company: 'الشركة',
        contact_name: 'المسؤول',
        permissions: 'الصلاحيات',
        role_id: 'الدور',
        is_active: 'الحالة',
        status: 'الحالة',
        action_type: 'الإجراء',
        table_name: 'الجدول',
        record_id: 'معرف السجل',
        portal_enabled: 'حالة بوابة العميل',
        approval_action: 'إجراء الاعتماد',
        source: 'المصدر',
    };
    const permissionLabels = @json(collect(\App\Models\Role::$allPermissions)->mapWithKeys(fn ($label, $key) => [$key => \App\Models\Role::permissionLabelFor($key)])->all());

    function showDiff(data) {
        const oldValues = data.old || {};
        const newValues = data.new || {};
        const keys = [...new Set([...Object.keys(oldValues), ...Object.keys(newValues)])];
        const changedKeys = keys.filter(key => !sameAuditValue(oldValues[key], newValues[key]));
        const visibleKeys = changedKeys.length ? changedKeys : keys;

        diffList.innerHTML = visibleKeys.map(key => renderDiffRow(key, oldValues[key], newValues[key])).join('');
        diffList.classList.toggle('hidden', visibleKeys.length === 0);
        diffEmpty.classList.toggle('hidden', visibleKeys.length !== 0);

        diffModal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function renderDiffRow(key, oldValue, newValue) {
        return `
            <div class="overflow-hidden rounded-xl border border-border bg-background">
                <div class="flex items-center justify-between gap-3 border-b border-border/60 bg-secondary/40 px-4 py-3">
                    <div class="text-sm font-black">${escapeHtml(fieldLabel(key))}</div>
                    <span class="rounded-full bg-card px-2.5 py-1 text-[11px] font-mono text-muted-foreground">${escapeHtml(key)}</span>
                </div>
                <div class="grid gap-0 md:grid-cols-2">
                    <div class="border-b border-border/60 p-4 md:border-b-0 md:border-l">
                        <div class="mb-2 flex items-center gap-2 text-xs font-black text-destructive">
                            <i data-lucide="history" class="h-3.5 w-3.5"></i>
                            القيمة السابقة
                        </div>
                        ${renderAuditValue(key, oldValue, newValue, 'old')}
                    </div>
                    <div class="p-4">
                        <div class="mb-2 flex items-center gap-2 text-xs font-black text-success">
                            <i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>
                            القيمة الجديدة
                        </div>
                        ${renderAuditValue(key, newValue, oldValue, 'new')}
                    </div>
                </div>
            </div>
        `;
    }

    function renderAuditValue(key, value, compareValue = null, side = 'new') {
        if (value === null || value === undefined || value === '') {
            return '<span class="text-sm font-bold text-muted-foreground">لا يوجد</span>';
        }

        if (Array.isArray(value)) {
            if (value.length === 0) {
                return '<span class="text-sm font-bold text-muted-foreground">لا يوجد</span>';
            }

            const compareArray = Array.isArray(compareValue) ? compareValue : [];
            const pills = value.map(item => {
                const label = key === 'permissions' ? permissionLabel(item) : scalarLabel(item);
                const isChanged = !compareArray.some(compareItem => sameAuditValue(compareItem, item));
                const tone = isChanged
                    ? (side === 'new' ? 'border-success/30 bg-success/10 text-success' : 'border-destructive/30 bg-destructive/10 text-destructive')
                    : 'border-border bg-card text-foreground';

                return `<span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-bold ${tone}">${escapeHtml(label)}</span>`;
            }).join('');

            return `<div class="flex flex-wrap gap-2">${pills}</div>`;
        }

        if (typeof value === 'object') {
            const entries = Object.entries(value);

            if (entries.length === 0) {
                return '<span class="text-sm font-bold text-muted-foreground">لا يوجد</span>';
            }

            return `
                <div class="space-y-2">
                    ${entries.map(([nestedKey, nestedValue]) => `
                        <div class="rounded-lg border border-border bg-card p-3">
                            <div class="text-[11px] font-black text-muted-foreground">${escapeHtml(fieldLabel(nestedKey))}</div>
                            <div class="mt-1">${renderAuditValue(nestedKey, nestedValue)}</div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        return `<span class="inline-flex max-w-full items-center rounded-lg border border-border bg-card px-3 py-2 text-sm font-bold text-foreground" dir="auto">${escapeHtml(scalarLabel(value))}</span>`;
    }

    function fieldLabel(key) {
        return auditFieldLabels[key] || String(key).replaceAll('_', ' ');
    }

    function permissionLabel(permission) {
        return permissionLabels[permission] || scalarLabel(permission);
    }

    function scalarLabel(value) {
        if (value === true || value === 1) return 'نعم';
        if (value === false || value === 0) return 'لا';
        return String(value);
    }

    function sameAuditValue(first, second) {
        return JSON.stringify(first) === JSON.stringify(second);
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    diffModal.addEventListener('click', () => diffModal.classList.add('hidden'));
</script>
@endsection

