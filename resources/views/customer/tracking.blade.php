@extends('layouts.customer')

@section('title', __('messages.tracking'))

@section('content')
@php
    $summaryCards = [
        ['label' => __('messages.tracking_files'), 'value' => $stats['total'], 'icon' => 'radar'],
        ['label' => __('messages.active_shipments'), 'value' => $stats['active'], 'icon' => 'ship'],
        ['label' => __('messages.delivered'), 'value' => $stats['delivered'], 'icon' => 'check-check'],
        ['label' => __('messages.average_progress'), 'value' => $stats['average_progress'] . '%', 'icon' => 'activity'],
    ];
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-border bg-card p-5 shadow-card">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-black">{{ __('messages.tracking') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-muted-foreground">{{ __('messages.tracking_intro') }}</p>
            </div>
            <form action="{{ route('customer.tracking') }}" method="GET" class="flex w-full flex-col gap-2 sm:flex-row lg:max-w-xl">
                <div class="relative flex-1">
                    <i data-lucide="search" class="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                    <input
                        type="text"
                        name="q"
                        aria-label="{{ __('messages.tracking_number_label') }}"
                        value="{{ $q }}"
                        placeholder="{{ __('messages.track_placeholder') }}"
                        class="h-11 w-full rounded-lg border border-border bg-background px-10 text-sm focus:outline-none focus:ring-1 focus:ring-accent"
                        dir="ltr"
                    >
                </div>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground">
                    <i data-lucide="radar" class="h-4 w-4"></i>
                    {{ __('messages.tracking_button') }}
                </button>
            </form>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($summaryCards as $card)
            <article class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-secondary text-primary">
                        <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <div class="text-2xl font-black">{{ $card['value'] }}</div>
                </div>
                <div class="mt-3 text-sm font-black">{{ $card['label'] }}</div>
            </article>
        @endforeach
    </section>

    @if($found)
        <section class="overflow-hidden rounded-xl border border-border bg-card shadow-card">
            <div class="bg-gradient-to-l from-primary to-ocean p-6 text-primary-foreground">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-xs uppercase tracking-widest text-white/70">{{ __('messages.tracking_number_label') }}</div>
                        <div class="flex items-center gap-1"><span class="text-xl font-bold" dir="ltr">{{ $found->id }}</span>@include('customer.partials.copy-reference', ['reference' => $found->id])</div>
                        <div class="mt-1 text-xs text-white/75">{{ __('messages.booking_request_label') }}: {{ $found->booking_id }}</div>
                    </div>
                    <button type="button" data-tracking-map-open aria-haspopup="dialog" aria-controls="tracking-map-dialog" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-white/25 bg-white/10 px-3 text-xs font-black text-white hover:bg-white/20">
                        <i data-lucide="map" class="h-4 w-4"></i>{{ __('tracking_map.preview') }}
                    </button>
                    <span class="inline-flex items-center rounded-full bg-accent px-3 py-1 text-sm font-bold text-accent-foreground">
                        {{ $found->status }}
                    </span>
                </div>
            </div>

            <div class="p-5">
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-lg border border-border/60 p-4">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <i data-lucide="package" class="h-4 w-4 text-accent"></i>
                            {{ __('messages.carrier_booking_label') }}
                        </div>
                        <div class="mt-1 font-bold" dir="ltr">{{ $found->booking->carrier_booking_ref ?: '—' }}</div>
                    </div>
                    <div class="rounded-lg border border-border/60 p-4">
                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <i data-lucide="ship" class="h-4 w-4 text-accent"></i>
                            {{ __('messages.vessel_voyage_label') }}
                        </div>
                        <div class="mt-1 font-bold">
                            @if($found->booking->carrier_vessel)
                                {{ $found->booking->carrier_vessel }} · {{ $found->booking->carrier_voyage ?: '—' }}
                            @else
                                {{ __('messages.waiting_for_carrier') }}
                            @endif
                        </div>
                    </div>
                    <div class="rounded-lg border border-border/60 p-4">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <i data-lucide="weight" class="h-4 w-4 text-accent"></i>
                            {{ __('messages.weight') }}
                        </div>
                        <div class="mt-1 font-bold">{{ number_format($found->booking->weight_kg, 2) }} كجم</div>
                    </div>
                    <div class="rounded-lg border border-border/60 p-4">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                            <i data-lucide="file-text" class="h-4 w-4 text-accent"></i>
                            {{ __('messages.type') }}
                        </div>
                        <div class="mt-1 font-bold">
                            {{ $found->booking->shipmentDisplay() }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-xl bg-secondary/50 p-6">
                    <div class="grid items-center gap-6 md:grid-cols-[1fr_2fr_1fr]">
                        <div>
                            <div class="text-xs uppercase tracking-widest text-muted-foreground">{{ __('messages.loading_port') }}</div>
                            <div class="mt-1 text-lg font-extrabold">{{ $found->booking->pol }}</div>
                            <div class="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                                <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                                {{ $found->etd ? $found->etd->toDateString() : '—' }}
                            </div>
                        </div>

                        <div>
                            <div class="relative h-2 rounded-full bg-border">
                                <div class="absolute inset-y-0 start-0 rounded-full bg-gradient-accent" style="width: {{ max(0, min(100, $found->progress)) }}%" role="progressbar" aria-label="{{ __('messages.progress') }}" aria-valuenow="{{ max(0, min(100, $found->progress)) }}" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="absolute -top-1.5" style="inset-inline-start: calc({{ max(0, min(100, $found->progress)) }}% - 12px)">
                                    <div class="grid h-5 w-5 place-items-center rounded-full bg-primary text-primary-foreground shadow-glow">
                                        <i data-lucide="ship" class="h-3 w-3"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-center text-sm text-muted-foreground">{{ $found->progress }}% {{ __('messages.route_completed') }}</div>
                        </div>

                        <div class="text-left">
                            <div class="text-xs uppercase tracking-widest text-muted-foreground">{{ __('messages.discharge_port') }}</div>
                            <div class="mt-1 text-lg font-extrabold">{{ $found->booking->pod }}</div>
                            <div class="mt-1 flex items-center justify-end gap-1 text-xs text-muted-foreground">
                                <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                                {{ $found->eta ? $found->eta->toDateString() : '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <h2 class="mb-4 text-lg font-black">{{ __('messages.event_log') }}</h2>
                    <ol class="relative space-y-5 ps-6">
                        @forelse($found->events->sortByDesc('event_date')->values() as $index => $event)
                            <li class="relative">
                                <span class="absolute -start-6 top-0.5 grid h-6 w-6 place-items-center rounded-full {{ $index === 0 ? 'bg-gradient-accent text-accent-foreground' : 'bg-secondary text-muted-foreground' }}">
                                    <i data-lucide="{{ $index === 0 ? 'check-circle-2' : 'circle' }}" class="h-4 w-4"></i>
                                </span>
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <div class="font-semibold">{{ $event->description }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $event->event_date }}</div>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                    <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
                                    {{ $event->location ?: '—' }}
                                    <span class="inline-flex items-center rounded-full border border-border bg-secondary px-1.5 py-0.5 text-[10px] font-medium">{{ $event->status }}</span>
                                    <span class="inline-flex items-center gap-1 text-xs">
                                        <i data-lucide="database" class="h-3.5 w-3.5"></i>
                                        {{ __('المصدر:') }} {{ $event->source_name ?: __('سجل العمليات') }}
                                    </span>
                                </div>
                            </li>
                        @empty
                            <li class="rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">{{ __('messages.no_events') }}</li>
                        @endforelse
                    </ol>
                </div>
            </div>
        </section>
        @include('partials.tracking-map')
    @elseif($q)
        <section class="rounded-xl border border-border bg-card p-12 text-center shadow-card">
            <i data-lucide="package-search" class="mx-auto h-12 w-12 text-muted-foreground"></i>
            <h2 class="mt-4 text-lg font-black">{{ __('messages.no_results') }}</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-muted-foreground">{{ __('messages.no_results_desc') }}</p>
        </section>
    @else
        <section class="rounded-xl border border-border bg-card p-12 text-center shadow-card">
            <i data-lucide="radar" class="mx-auto h-12 w-12 text-muted-foreground"></i>
            <h2 class="mt-4 text-lg font-black">{{ __('messages.no_shipments') }}</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-muted-foreground">{{ __('messages.tracking_none_desc') }}</p>
        </section>
    @endif

    @if($tracks->isNotEmpty())
        <section class="rounded-xl border border-border bg-card shadow-card">
                <div class="border-b border-border p-5">
                <h2 class="text-lg font-black">{{ __('messages.all_trackable_shipments') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ __('messages.choose_track_to_view') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table data-responsive-table class="w-full min-w-[860px] text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">{{ __('messages.tracking_number_label') }}</th>
                            <th class="px-5 py-3">{{ __('messages.request') }}</th>
                            <th class="px-5 py-3">{{ __('messages.route') }}</th>
                            <th class="px-5 py-3">{{ __('messages.status') }}</th>
                            <th class="px-5 py-3">{{ __('messages.progress') }}</th>
                            <th class="px-5 py-3">{{ __('messages.eta') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tracks as $track)
                            <tr class="border-t border-border/50 hover:bg-secondary/20">
                                <td class="px-5 py-4 font-mono text-xs font-black" dir="ltr">{{ $track->id }}</td>
                                <td class="px-5 py-4 font-mono text-xs" dir="ltr">{{ $track->booking_id }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $track->booking->pol }}</div>
                                    <div class="text-xs text-muted-foreground">إلى {{ $track->booking->pod }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-secondary px-2.5 py-1 text-[11px] font-black">{{ $track->status }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-24 rounded-full bg-border">
                                            <div class="h-2 rounded-full bg-gradient-accent" style="width: {{ $track->progress }}%"></div>
                                        </div>
                                        <span class="text-xs font-black">{{ $track->progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-xs text-muted-foreground">{{ $track->eta?->toDateString() ?: '—' }}</td>
                                <td class="px-5 py-4">
                                        <a href="{{ route('customer.tracking', ['q' => $track->id]) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border px-3 text-xs font-black hover:bg-secondary">
                                        {{ __('messages.view') }}
                                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
