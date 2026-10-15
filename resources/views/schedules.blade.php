@extends('layouts.app')

@section('title', __('messages.schedules') . ' — ' . __('messages.site_name'))

@section('content')
@php
    $matchingSchedulesCount = $schedules->count();
    $verifiedSchedulesCount = $schedules->filter(fn ($schedule) => filled($schedule->source_checked_at))->count();
    $staleSchedulesCount = $schedules->filter(fn ($schedule) => $schedule->source_checked_at && $schedule->source_checked_at->lt(now()->subDays(14)))->count();
    $nextSchedule = $schedules->first();
@endphp
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-extrabold md:text-4xl">{{ __('messages.schedules_heading') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-white/80">{{ __('messages.schedules_desc') }}</p>
        </div>
    </section>

    <section class="container mx-auto px-4 py-10">
        <div class="mb-6 grid gap-3 md:grid-cols-4">
            <div class="rounded-lg border border-border bg-card p-4 shadow-card">
                <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground">
                    <i data-lucide="ship" class="h-4 w-4 text-accent"></i>
                    الرحلات المطابقة
                </div>
                <div class="mt-2 text-2xl font-black text-foreground">{{ number_format($matchingSchedulesCount) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4 shadow-card">
                <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground">
                    <i data-lucide="badge-check" class="h-4 w-4 text-accent"></i>
                    مصادر تم التحقق منها
                </div>
                <div class="mt-2 text-2xl font-black text-foreground">{{ number_format($verifiedSchedulesCount) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4 shadow-card">
                <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground">
                    <i data-lucide="calendar-days" class="h-4 w-4 text-accent"></i>
                    أقرب إبحار
                </div>
                <div class="mt-2 text-sm font-black text-foreground">
                    @if($nextSchedule)
                        {{ optional($nextSchedule->etd)->toDateString() }} - {{ $nextSchedule->origin }} / {{ $nextSchedule->destination }}
                    @else
                        لا توجد رحلة مطابقة
                    @endif
                </div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4 shadow-card">
                <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground">
                    <i data-lucide="refresh-cw" class="h-4 w-4 text-warning"></i>
                    تحتاج تحديث مصدر
                </div>
                <div class="mt-2 text-2xl font-black text-foreground">{{ number_format($staleSchedulesCount) }}</div>
            </div>
        </div>

        <div class="mb-6 rounded-lg border border-border bg-card p-4 shadow-card">
            <form action="{{ route('schedules') }}" method="GET" class="grid gap-3 md:grid-cols-5">
                <select name="origin" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="all">{{ __('messages.all_loading_ports') }}</option>
                    @foreach($ports as $p)
                        <option value="{{ $p }}" @selected($origin === $p)>{{ $p }}</option>
                    @endforeach
                </select>
                
                <select name="destination" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="all">{{ __('messages.all_discharge_ports') }}</option>
                    @foreach($ports as $p)
                        <option value="{{ $p }}" @selected($destination === $p)>{{ $p }}</option>
                    @endforeach
                </select>

                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('messages.search_vessel_voyage') }}" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" title="أقرب ETD من تاريخ" />
                
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-4 text-xs font-bold text-accent-foreground shadow hover:opacity-95">
                        <i data-lucide="search" class="h-4 w-4"></i>
                        {{ __('messages.filter') }}
                    </button>
                    <a href="{{ route('schedules') }}" class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        {{ __('messages.reset') }}
                    </a>
                </div>
            </form>
        </div>

        <div class="mb-4 flex flex-col gap-2 rounded-lg border border-accent/20 bg-accent/5 p-4 text-sm text-foreground md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-2 font-bold">
                <i data-lucide="badge-check" class="h-4 w-4 text-accent"></i>
                <span>تعرض هذه الصفحة الرحلات المنشورة من إدارة النظام فقط.</span>
            </div>
            <div class="text-xs text-muted-foreground">آخر تحديث يعتمد على تاريخ التحقق المحفوظ لكل رحلة.</div>
        </div>

        <div class="overflow-hidden rounded-lg border border-border bg-card shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-right text-sm">
                    <thead class="bg-secondary/60 text-xs text-muted-foreground">
                        <tr>
                            <th class="p-4">{{ __('messages.vessel_header') }}</th>
                            <th class="p-4">{{ __('messages.route_header') }}</th>
                            <th class="p-4">{{ __('messages.departure_etd') }}</th>
                            <th class="p-4">{{ __('messages.arrival_eta') }}</th>
                            <th class="p-4">{{ __('messages.duration') }}</th>
                            <th class="p-4">المصدر</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($schedules as $r)
                            @php
                                $statusClasses = match($r->status) {
                                    'delayed' => 'bg-warning/10 text-warning',
                                    'completed' => 'bg-primary/10 text-primary',
                                    default => 'bg-success/10 text-success',
                                };
                                $bookingHref = $r->booking_url ?: (auth('customer')->check() ? route('customer.shipments.create') : route('customer.login'));
                                $sourceIsStale = $r->source_checked_at && $r->source_checked_at->lt(now()->subDays(14));
                            @endphp
                            <tr class="transition-colors hover:bg-secondary/30">
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-secondary text-primary">
                                            <i data-lucide="ship" class="h-4.5 w-4.5"></i>
                                        </span>
                                        <div>
                                            <div class="font-bold text-foreground">{{ $r->vessel }}</div>
                                            <div class="font-mono text-xs text-muted-foreground">{{ $r->voyage }}</div>
                                            @if($r->carrier)
                                                <div class="mt-0.5 text-[11px] font-semibold text-muted-foreground">{{ $r->carrier }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-xs font-semibold">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $r->origin }}</span>
                                        <i data-lucide="arrow-left" class="h-3.5 w-3.5 text-accent"></i>
                                        <span>{{ $r->destination }}</span>
                                    </div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">
                                        {{ $r->origin_code ?: '---' }} / {{ $r->destination_code ?: '---' }}
                                    </div>
                                    @if($r->service_name)
                                        <div class="mt-1 text-[11px] text-muted-foreground">{{ $r->service_name }}</div>
                                    @endif
                                </td>
                                <td class="p-4 text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                        {{ optional($r->etd)->toDateString() }}
                                    </div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">Cutoff: {{ optional($r->cutoff)->toDateString() ?: 'يتأكد عند الحجز' }}</div>
                                </td>
                                <td class="p-4 text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <i data-lucide="calendar-check" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                        {{ optional($r->eta)->toDateString() }}
                                    </div>
                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-bold {{ $statusClasses }}">
                                        {{ $r->status === 'delayed' ? 'متأخرة' : ($r->status === 'completed' ? 'مكتملة' : 'منشورة') }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-secondary/80 px-2 py-0.5 text-xs font-bold text-foreground">
                                        <i data-lucide="clock" class="h-3 w-3"></i>
                                        <span>{{ $r->transit }}</span>
                                    </span>
                                </td>
                                <td class="p-4 text-xs">
                                    <div class="font-bold text-foreground">{{ $r->source_name ?: 'مصدر موثق' }}</div>
                                    @if($r->source_checked_at)
                                        <div class="mt-1 text-muted-foreground">تحقق: {{ $r->source_checked_at->toDateString() }}</div>
                                    @endif
                                    @if($r->expires_at)
                                        <div class="mt-1 text-muted-foreground">ينتهي العرض: {{ $r->expires_at->toDateString() }}</div>
                                    @endif
                                    @if($sourceIsStale)
                                        <div class="mt-2 inline-flex items-center gap-1 rounded-full bg-warning/10 px-2 py-0.5 text-[11px] font-bold text-warning">
                                            <i data-lucide="refresh-cw" class="h-3 w-3"></i>
                                            يحتاج تحديث مصدر
                                        </div>
                                    @endif
                                    @if($r->source_url)
                                        <a href="{{ $r->source_url }}" target="_blank" class="mt-1 inline-flex items-center gap-1 font-bold text-accent hover:underline">
                                            <i data-lucide="external-link" class="h-3 w-3"></i>
                                            رابط المصدر
                                        </a>
                                    @endif
                                </td>
                                <td class="p-4 text-left">
                                    <a href="{{ $bookingHref }}" @if($r->booking_url) target="_blank" @endif class="inline-flex h-8 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-4 text-xs font-bold text-accent-foreground shadow hover:opacity-95">
                                        <i data-lucide="send" class="h-3.5 w-3.5"></i>
                                        {{ __('messages.book') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-12 text-center">
                                    <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-secondary text-muted-foreground">
                                        <i data-lucide="calendar-x" class="h-7 w-7"></i>
                                    </div>
                                    <div class="mt-3 text-sm font-black text-foreground">{{ __('messages.no_matching_schedules') }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">جرّب تغيير الميناء أو تاريخ الإبحار أو مسح الفلاتر.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
@endsection
