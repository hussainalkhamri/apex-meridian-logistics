@extends('layouts.app')

@section('title', 'إدارة جداول الرحلات')

@section('content')
<div class="min-h-screen bg-secondary/20 py-6">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold text-accent">
                    <i data-lucide="calendar-days" class="h-4 w-4"></i>
                    <span>تشغيل الناقلين</span>
                </div>
                <h1 class="mt-1 text-2xl font-extrabold text-foreground">إدارة جداول الرحلات</h1>
                <p class="mt-1 text-sm text-muted-foreground">أدخل الرحلات من مصادر موثوقة، ثم انشر فقط البيانات التي تم التحقق منها.</p>
            </div>
            <a href="{{ route('schedules') }}" target="_blank" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary">
                <i data-lucide="external-link" class="h-4 w-4"></i>
                عرض الصفحة العامة
            </a>
        </div>

        @if($errors->any())
            <div class="mb-5 rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                <div class="font-bold">راجع الحقول التالية:</div>
                <ul class="mt-2 list-disc space-y-1 pe-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                <div class="text-xs font-bold text-muted-foreground">إجمالي الجداول</div>
                <div class="mt-2 text-2xl font-extrabold">{{ number_format($stats['total']) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                <div class="text-xs font-bold text-muted-foreground">منشورة للعميل</div>
                <div class="mt-2 text-2xl font-extrabold text-success">{{ number_format($stats['published']) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                <div class="text-xs font-bold text-muted-foreground">مسودات / غير منشورة</div>
                <div class="mt-2 text-2xl font-extrabold text-muted-foreground">{{ number_format($stats['draft']) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4 shadow-sm">
                <div class="text-xs font-bold text-muted-foreground">رحلات قادمة منشورة</div>
                <div class="mt-2 text-2xl font-extrabold text-accent">{{ number_format($stats['upcoming']) }}</div>
            </div>
        </div>

        <datalist id="schedule-port-options">
            @foreach($ports as $port)
                <option value="{{ $port->name }}">{{ $port->code ? $port->code . ' - ' : '' }}{{ $port->country }}</option>
            @endforeach
        </datalist>

        <section class="mb-6 rounded-lg border border-border bg-card p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-extrabold text-foreground">إضافة جدول رحلة</h2>
                    <p class="mt-1 text-xs text-muted-foreground">إذا كانت الرحلة ستظهر للعميل، اكتب اسم المصدر الرسمي أو مرجع المستند.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('schedules.store') }}">
                @csrf
                @include('pages.partials.schedule-form-fields', [
                    'schedule' => null,
                    'statuses' => $statuses,
                    'ports' => $ports,
                    'submitLabel' => 'إضافة ونشر/حفظ الجدول'
                ])
            </form>
        </section>

        <section class="rounded-lg border border-border bg-card shadow-sm">
            <div class="border-b border-border p-4">
                <form method="GET" action="{{ route('schedules.manage') }}" class="grid gap-3 md:grid-cols-5">
                    <input name="q" value="{{ $search }}" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" placeholder="بحث بالسفينة، الناقل، الميناء، المرجع">
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <select name="status" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="all" @selected($status === 'all')>كل الحالات</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="published" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="all" @selected($published === 'all')>كل الجداول</option>
                        <option value="yes" @selected($published === 'yes')>منشورة</option>
                        <option value="no" @selected($published === 'no')>غير منشورة</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-4 text-xs font-bold text-accent-foreground shadow hover:opacity-95">
                            <i data-lucide="search" class="h-4 w-4"></i>
                            فلترة
                        </button>
                        <a href="{{ route('schedules.manage') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-border px-3 text-xs font-bold hover:bg-secondary">
                            <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-right text-sm">
                    <thead class="bg-secondary/50 text-[11px] font-bold text-muted-foreground">
                        <tr>
                            <th class="p-4">السفينة / الرحلة</th>
                            <th class="p-4">المسار</th>
                            <th class="p-4">المواعيد</th>
                            <th class="p-4">المصدر</th>
                            <th class="p-4">الحالة</th>
                            <th class="p-4">النشر</th>
                            <th class="p-4 text-left">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($schedules as $schedule)
                            @php
                                $statusClasses = match($schedule->status) {
                                    'published' => 'bg-success/10 text-success border-success/20',
                                    'delayed' => 'bg-warning/10 text-warning border-warning/20',
                                    'cancelled' => 'bg-destructive/10 text-destructive border-destructive/20',
                                    'completed' => 'bg-primary/10 text-primary border-primary/20',
                                    default => 'bg-secondary text-muted-foreground border-border',
                                };
                                $sourceIsStale = $schedule->source_checked_at && $schedule->source_checked_at->lt(now()->subDays(14));
                                $displayIsExpired = $schedule->expires_at && $schedule->expires_at->lt(today());
                            @endphp
                            <tr class="align-top hover:bg-secondary/25">
                                <td class="p-4">
                                    <div class="font-extrabold text-foreground">{{ $schedule->vessel }}</div>
                                    <div class="mt-1 font-mono text-xs text-muted-foreground">{{ $schedule->voyage }}</div>
                                    @if($schedule->carrier)
                                        <div class="mt-1 text-xs text-muted-foreground">{{ $schedule->carrier }}</div>
                                    @endif
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2 text-xs font-bold">
                                        <span>{{ $schedule->origin }}</span>
                                        <i data-lucide="arrow-left" class="h-3.5 w-3.5 text-accent"></i>
                                        <span>{{ $schedule->destination }}</span>
                                    </div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">
                                        {{ $schedule->origin_code ?: '---' }} / {{ $schedule->destination_code ?: '---' }}
                                    </div>
                                    @if($schedule->service_name)
                                        <div class="mt-1 text-[11px] text-muted-foreground">{{ $schedule->service_name }}</div>
                                    @endif
                                </td>
                                <td class="p-4 text-xs text-muted-foreground">
                                    <div>ETD: <span class="font-bold text-foreground">{{ optional($schedule->etd)->toDateString() }}</span></div>
                                    <div>ETA: <span class="font-bold text-foreground">{{ optional($schedule->eta)->toDateString() }}</span></div>
                                    <div>Cutoff: <span class="font-bold text-foreground">{{ optional($schedule->cutoff)->toDateString() ?: 'يتأكد عند الحجز' }}</span></div>
                                    <div class="mt-1 inline-flex rounded-full bg-secondary px-2 py-0.5 text-[11px] font-bold text-foreground">{{ $schedule->transit }}</div>
                                </td>
                                <td class="p-4 text-xs">
                                    <div class="font-bold text-foreground">{{ $schedule->source_name ?: 'غير محدد' }}</div>
                                    @if($schedule->source_reference)
                                        <div class="mt-1 text-muted-foreground">{{ $schedule->source_reference }}</div>
                                    @endif
                                    @if($schedule->source_checked_at)
                                        <div class="mt-1 text-muted-foreground">تحقق: {{ $schedule->source_checked_at->toDateString() }}</div>
                                    @endif
                                    @if($schedule->expires_at)
                                        <div class="mt-1 text-muted-foreground">ينتهي العرض: {{ $schedule->expires_at->toDateString() }}</div>
                                    @endif
                                    @if($sourceIsStale)
                                        <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-warning/10 px-2 py-0.5 text-[11px] font-bold text-warning">
                                            <i data-lucide="refresh-cw" class="h-3 w-3"></i>
                                            يحتاج تحديث مصدر
                                        </div>
                                    @endif
                                    @if($schedule->source_url)
                                        <a href="{{ $schedule->source_url }}" target="_blank" class="mt-1 inline-flex items-center gap-1 font-bold text-accent hover:underline">
                                            <i data-lucide="link" class="h-3 w-3"></i>
                                            المصدر
                                        </a>
                                    @endif
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex rounded-full border px-2 py-1 text-[11px] font-bold {{ $statusClasses }}">
                                        {{ $statuses[$schedule->status] ?? $schedule->status }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    @if($schedule->is_published)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-success/10 px-2 py-1 text-[11px] font-bold text-success">
                                            <i data-lucide="eye" class="h-3 w-3"></i>
                                            منشور
                                        </span>
                                        @if($displayIsExpired)
                                            <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-destructive/10 px-2 py-1 text-[11px] font-bold text-destructive">
                                                <i data-lucide="calendar-x" class="h-3 w-3"></i>
                                                منتهي العرض
                                            </span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-secondary px-2 py-1 text-[11px] font-bold text-muted-foreground">
                                            <i data-lucide="eye-off" class="h-3 w-3"></i>
                                            مخفي
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 text-left">
                                    <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" onsubmit="return confirm('هل تريد حذف جدول هذه الرحلة؟')" class="inline-flex">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-8 items-center justify-center gap-1 rounded-lg border border-destructive/30 px-3 text-[11px] font-bold text-destructive hover:bg-destructive/10">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            حذف
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="7" class="bg-secondary/15 p-0">
                                    <details>
                                        <summary class="cursor-pointer px-4 py-2 text-xs font-bold text-accent hover:bg-secondary/40">تعديل بيانات الرحلة والمصدر</summary>
                                        <form method="POST" action="{{ route('schedules.update', $schedule) }}" class="border-t border-border bg-card p-4">
                                            @csrf
                                            @method('PUT')
                                            @include('pages.partials.schedule-form-fields', [
                                                'schedule' => $schedule,
                                                'statuses' => $statuses,
                                                'ports' => $ports,
                                                'submitLabel' => 'حفظ التعديلات'
                                            ])
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-10 text-center text-sm text-muted-foreground">لا توجد جداول رحلات مطابقة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border p-4">
                {{ $schedules->links() }}
            </div>
        </section>
    </div>
</div>
@endsection
