@extends('layouts.app')

@section('title', __('messages.targets_title') . ' — ' . __('messages.site_name'))

@section('head')
<style>
    @keyframes targetRise { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes targetSweep { from { stroke-dashoffset: 100; } to { stroke-dashoffset: {{ 100 - $percentage }}; } }
    .target-rise { animation: targetRise .7s cubic-bezier(.2,.8,.2,1) both; }
    .target-delay-1 { animation-delay: .08s; }
    .target-delay-2 { animation-delay: .16s; }
    .target-delay-3 { animation-delay: .24s; }
    .target-progress-ring { animation: targetSweep 1.4s cubic-bezier(.2,.8,.2,1) .25s both; }
</style>
@endsection

@section('content')
@php
    $daysInMonth = $month->daysInMonth;
    $dailyMax = max(1, (int) $dailyApproved->max());
    $remaining = max(0, $target->target - $approvedCount);
@endphp
<div class="min-h-full bg-secondary/20">
    <div class="mx-auto max-w-7xl px-1 py-2 sm:px-3">
        <div class="target-rise flex flex-col justify-between gap-4 rounded-2xl border border-border bg-card p-6 shadow-card md:flex-row md:items-center">
            <div>
                <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-accent/10 px-3 py-1 text-xs font-black text-accent">
                    <i data-lucide="target" class="h-3.5 w-3.5"></i>{{ __('messages.target_monthly_badge') }}
                </div>
                <h1 class="text-2xl font-black tracking-tight sm:text-3xl">{{ __('messages.targets_title') }}</h1>
                <p class="mt-2 text-sm text-muted-foreground">{{ __('messages.target_intro') }}</p>
            </div>
            <div class="text-left text-sm font-bold text-muted-foreground" dir="ltr">
                <div>{{ $month->translatedFormat('F Y') }}</div>
                <div class="mt-1 text-xs">{{ $approvedCount }} / {{ $target->target }} {{ __('messages.target_approved_short') }}</div>
            </div>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-[.8fr_1.2fr]">
            <section class="target-rise target-delay-1 relative overflow-hidden rounded-2xl border border-border bg-card p-6 shadow-card">
                <div class="absolute -left-20 -top-20 h-48 w-48 rounded-full bg-accent/10 blur-3xl"></div>
                <div class="relative flex items-center justify-between gap-4">
                    <div>
                        <div class="text-sm font-bold text-muted-foreground">{{ __('messages.target_current_progress') }}</div>
                        <div class="mt-2 text-4xl font-black text-foreground" dir="ltr">{{ $approvedCount }}<span class="text-lg text-muted-foreground"> / {{ $target->target }}</span></div>
                        <div class="mt-2 text-xs font-bold text-accent">{{ $remaining > 0 ? __('messages.target_remaining', ['count' => $remaining]) : __('messages.target_achieved') }}</div>
                    </div>
                    <div class="relative h-32 w-32 shrink-0">
                        <svg viewBox="0 0 42 42" class="h-full w-full -rotate-90">
                            <circle cx="21" cy="21" r="15.9155" fill="none" stroke="hsl(var(--secondary))" stroke-width="4"></circle>
                            <circle cx="21" cy="21" r="15.9155" fill="none" stroke="hsl(var(--accent))" stroke-width="4" stroke-linecap="round" pathLength="100" stroke-dasharray="100" stroke-dashoffset="100" class="target-progress-ring"></circle>
                        </svg>
                        <div class="absolute inset-0 grid place-items-center text-xl font-black" dir="ltr">{{ $percentage }}%</div>
                    </div>
                </div>
                <div class="mt-6 h-3 overflow-hidden rounded-full bg-secondary">
                    <div class="h-full rounded-full bg-gradient-accent transition-all duration-1000" style="width: {{ $percentage }}%"></div>
                </div>
            </section>

            @if(auth()->user()->isManager())
                <section class="target-rise target-delay-2 rounded-2xl border border-accent/25 bg-gradient-to-br from-accent/10 via-card to-card p-6 shadow-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2 text-sm font-black"><i data-lucide="sliders-horizontal" class="h-4 w-4 text-accent"></i>{{ __('messages.target_manager_control') }}</div>
                            <p class="mt-2 text-xs leading-6 text-muted-foreground">{{ __('messages.target_manager_control_desc') }}</p>
                        </div>
                        <span class="rounded-full bg-success/10 px-2.5 py-1 text-[10px] font-black text-success">{{ __('messages.target_manager_only') }}</span>
                    </div>
                    <form method="POST" action="{{ route('targets.update') }}" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        <label class="flex-1 text-xs font-black" for="target">{{ __('messages.target_input_label') }}
                            <input id="target" name="target" type="number" min="1" max="1000000" required value="{{ old('target', $target->target) }}" class="mt-2 h-11 w-full rounded-lg border border-border bg-background px-3 text-base font-black focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/20">
                        </label>
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground shadow-elegant transition hover:-translate-y-0.5">
                            <i data-lucide="save" class="h-4 w-4"></i>{{ __('messages.target_save') }}
                        </button>
                    </form>
                </section>
            @else
                <section class="target-rise target-delay-2 flex items-center gap-4 rounded-2xl border border-border bg-card p-6 shadow-card">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-secondary text-muted-foreground"><i data-lucide="eye" class="h-5 w-5"></i></span>
                    <div><div class="font-black">{{ __('messages.target_employee_view') }}</div><p class="mt-1 text-xs leading-6 text-muted-foreground">{{ __('messages.target_employee_view_desc') }}</p></div>
                </section>
            @endif
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-[1.3fr_.7fr]">
            <section class="target-rise target-delay-3 rounded-2xl border border-border bg-card p-6 shadow-card">
                <div class="flex items-center justify-between"><div><h2 class="font-black">{{ __('messages.target_activity_title') }}</h2><p class="mt-1 text-xs text-muted-foreground">{{ __('messages.target_activity_desc') }}</p></div><i data-lucide="bar-chart-3" class="h-5 w-5 text-accent"></i></div>
                <div class="mt-6 flex h-44 items-end gap-1 border-b border-border pb-2 sm:gap-2">
                    @for($day = 1; $day <= $daysInMonth; $day++)
                        @php $value = (int) ($dailyApproved[$day] ?? 0); @endphp
                        <div class="group flex h-full flex-1 flex-col justify-end gap-1" title="{{ $day }}: {{ $value }}">
                            <div class="w-full rounded-t-sm bg-accent/80 transition-all duration-500 group-hover:bg-accent" style="height: {{ $value ? max(8, round(($value / $dailyMax) * 100)) : 3 }}%"></div>
                            @if($day === 1 || $day % 5 === 0 || $day === $daysInMonth)<span class="text-center text-[9px] text-muted-foreground">{{ $day }}</span>@endif
                        </div>
                    @endfor
                </div>
            </section>
            <section class="target-rise target-delay-3 rounded-2xl border border-border bg-card p-6 shadow-card">
                <div class="flex items-center justify-between"><h2 class="font-black">{{ __('messages.target_recent_title') }}</h2><i data-lucide="zap" class="h-5 w-5 text-warning"></i></div>
                <div class="mt-4 space-y-3">
                    @forelse($recentApproved as $booking)
                        <div class="flex items-center gap-3 border-b border-border/60 pb-3 last:border-0"><span class="grid h-8 w-8 place-items-center rounded-lg bg-success/10 text-success"><i data-lucide="check" class="h-4 w-4"></i></span><div class="min-w-0"><div class="truncate text-xs font-black">{{ $booking->customer_company ?: $booking->id }}</div><div class="text-[10px] text-muted-foreground">{{ $booking->decided_at?->diffForHumans() }}</div></div></div>
                    @empty
                        <div class="py-8 text-center text-xs text-muted-foreground">{{ __('messages.target_no_approved') }}</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
