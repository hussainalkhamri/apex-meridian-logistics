@extends('layouts.app')

@section('title', __('messages.shipment_tracking') . ' — ' . __('messages.site_name'))

@section('head')
<meta name="description" content="{{ __('messages.home_meta_description') }}">
@endsection

@section('content')
<div>
    {{-- Hero / Search Bar --}}
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-extrabold md:text-4xl">{{ __('messages.shipment_tracking') }}</h1>
            <p class="mt-2 text-white/80">{{ __('messages.track_description') }}</p>
            <div class="mt-6 flex max-w-2xl flex-col gap-2 md:flex-row">
                <form action="{{ route('tracking') }}" method="GET" class="flex flex-col gap-2 md:flex-row w-full">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground"></i>
                        <input
                            type="text"
                            name="q"
                            value="{{ $q ?? '' }}"
                            placeholder="BHRL-2026-104587 {{ __('messages.or') }} BHLU-4825610"
                            class="h-12 w-full bg-card border border-border pr-11 pl-4 text-foreground rounded-lg focus:outline-none focus:ring-2 focus:ring-accent"
                        >
                    </div>
                    <button type="submit" class="h-12 bg-gradient-accent px-8 text-base font-bold text-accent-foreground hover:opacity-95 rounded-lg">{{ __('messages.tracking_button') }}</button>
                </form>
            </div>
        </div>
    </section>

    @if($found)
        @php
            $progress = max(0, min(100, (int) $found->progress));
            $latestEvent = $found->events->last();
            $eventCount = $found->events->count();
        @endphp
        <section class="container mx-auto px-4 py-12">
            {{-- Summary Card --}}
            <div class="overflow-hidden rounded-xl border border-border/60 shadow-card">
                <div class="bg-gradient-to-l from-primary to-ocean p-6 text-primary-foreground">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-xs uppercase tracking-widest text-white/70">{{ __('messages.shipment_no') }}</div>
                            <div class="text-2xl font-extrabold">{{ $found->id }}</div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" data-tracking-map-open aria-haspopup="dialog" aria-controls="tracking-map-dialog" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-white/25 bg-white/10 px-3 text-xs font-black text-white hover:bg-white/20">
                                <i data-lucide="map" class="h-4 w-4"></i>{{ __('tracking_map.preview') }}
                            </button>
                            <span class="inline-flex h-9 items-center gap-2 rounded-lg border border-white/25 bg-white/10 px-3 text-xs font-black text-white">
                                <i data-lucide="database" class="h-3.5 w-3.5"></i>
                                بيانات النظام
                            </span>
                            <button type="button" data-share-tracking-url="{{ route('tracking', ['q' => $found->id]) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-white/25 bg-white/10 px-3 text-xs font-black text-white hover:bg-white/20">
                                <i data-lucide="share-2" class="h-3.5 w-3.5"></i>
                                مشاركة
                            </button>
                            <span class="inline-flex items-center rounded-full bg-accent px-3 py-1 text-sm font-bold text-accent-foreground">
                                {{ $found->status }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="p-6 bg-card">
                    <div class="mb-5 grid gap-3 md:grid-cols-3">
                        <div class="rounded-lg border border-border/60 bg-secondary/35 p-4">
                            <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground">
                                <i data-lucide="activity" class="h-4 w-4 text-accent"></i>
                                نسبة التقدم المسجلة
                            </div>
                            <div class="mt-2 text-2xl font-black text-foreground">{{ $progress }}%</div>
                        </div>
                        <div class="rounded-lg border border-border/60 bg-secondary/35 p-4">
                            <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground">
                                <i data-lucide="list-checks" class="h-4 w-4 text-accent"></i>
                                أحداث التتبع
                            </div>
                            <div class="mt-2 text-2xl font-black text-foreground">{{ $eventCount }}</div>
                        </div>
                        <div class="rounded-lg border border-border/60 bg-secondary/35 p-4">
                            <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground">
                                <i data-lucide="calendar-clock" class="h-4 w-4 text-accent"></i>
                                آخر تحديث
                            </div>
                            <div class="mt-2 text-sm font-black text-foreground">{{ $latestEvent?->event_date ?: ($found->updated_at?->format('Y-m-d') ?? '—') }}</div>
                        </div>
                    </div>

                    {{-- Info Cards Grid --}}
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="rounded-lg border border-border/60 p-4">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <i data-lucide="package" class="h-4 w-4 text-accent"></i>{{ __('messages.container_no') }}
                            </div>
                            <div class="mt-1 font-bold">{{ $found->booking->carrier_booking_ref ?: '—' }}</div>
                        </div>
                        <div class="rounded-lg border border-border/60 p-4">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <i data-lucide="ship" class="h-4 w-4 text-accent"></i>{{ __('messages.voyage') }}
                            </div>
                            <div class="mt-1 font-bold">
                                @if($found->booking->carrier_vessel)
                                    {{ $found->booking->carrier_vessel }} · {{ $found->booking->carrier_voyage }}
                                @else
                                    {{ __('messages.pending_arrangement') }}
                                @endif
                            </div>
                        </div>
                        <div class="rounded-lg border border-border/60 p-4">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <i data-lucide="weight" class="h-4 w-4 text-accent"></i>{{ __('messages.weight') }}
                            </div>
                            <div class="mt-1 font-bold">{{ number_format($found->booking->weight_kg) }} {{ __('messages.kg') }}</div>
                        </div>
                        <div class="rounded-lg border border-border/60 p-4">
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <i data-lucide="file-text" class="h-4 w-4 text-accent"></i>{{ __('messages.type') }}
                            </div>
                            <div class="mt-1 font-bold">
                                {{ $found->booking->shipmentDisplay() }}
                            </div>
                        </div>
                    </div>

                    {{-- Route Progress --}}
                    <div class="mt-8 rounded-xl bg-secondary/50 p-6">
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
                                    <div class="absolute inset-y-0 right-0 rounded-full bg-gradient-accent" style="width: {{ $progress }}%"></div>
                                    <div class="absolute -top-1.5 transition-all" style="right: calc({{ $progress }}% - 12px)">
                                        <div class="grid h-5 w-5 place-items-center rounded-full bg-primary text-primary-foreground shadow-glow">
                                            <i data-lucide="ship" class="h-3 w-3"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 text-center text-sm text-muted-foreground">{{ $progress }}% {{ __('messages.route_completed') }}</div>
                            </div>
                            <div class="text-left md:text-left">
                                <div class="text-xs uppercase tracking-widest text-muted-foreground">{{ __('messages.discharge_port') }}</div>
                                <div class="mt-1 text-lg font-extrabold">{{ $found->booking->pod }}</div>
                                <div class="mt-1 flex items-center gap-1 text-xs text-muted-foreground justify-end">
                                    <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                                    {{ $found->eta ? $found->eta->toDateString() : '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Events Timeline --}}
                    <div class="mt-8">
                        <h3 class="mb-4 text-lg font-bold">{{ __('messages.event_log') }}</h3>
                        <ol class="relative space-y-5 pr-6">
                            <div class="absolute right-[11px] top-2 bottom-2 w-px bg-border"></div>
                            @forelse($found->events->reverse() as $i => $event)
                                @php $isLatest = $i === 0; @endphp
                                <li class="relative">
                                    <span class="absolute -right-6 top-0.5 grid h-6 w-6 place-items-center rounded-full {{ $isLatest ? 'bg-gradient-accent text-accent-foreground shadow-glow' : 'bg-secondary text-muted-foreground' }}">
                                        @if($isLatest)
                                            <i data-lucide="check-circle-2" class="h-4 w-4"></i>
                                        @else
                                            <i data-lucide="circle" class="h-3 w-3"></i>
                                        @endif
                                    </span>
                                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                                        <div class="font-semibold">{{ $event->description }}</div>
                                        <div class="text-xs text-muted-foreground">{{ $event->event_date }}</div>
                                    </div>
                                    <div class="mt-1 flex items-center gap-1 text-sm text-muted-foreground">
                                        <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
                                        {{ $event->location }} ·
                                        <span class="inline-flex items-center rounded-full bg-secondary px-1.5 py-0.5 text-[10px] font-medium border border-border">{{ $event->status }}</span>
                                        <span class="inline-flex items-center gap-1 text-xs">
                                            <i data-lucide="database" class="h-3.5 w-3.5"></i>
                                            المصدر: {{ $event->source_name ?: 'سجل العمليات' }}
                                        </span>
                                    </div>
                                </li>
                            @empty
                                <li class="py-6 text-center text-muted-foreground text-sm">{{ __('messages.no_events') }}</li>
                            @endforelse
                        </ol>
                    </div>
                </div>
            </div>
        </section>
        @include('partials.tracking-map')
    @elseif(request('q'))
        <section class="container mx-auto px-4 py-20 text-center">
            <div class="max-w-md mx-auto rounded-2xl border p-10 bg-card shadow-card">
                <i data-lucide="package-search" class="h-12 w-12 text-muted-foreground mx-auto"></i>
                <h2 class="mt-4 font-bold text-lg">{{ __('messages.no_results') }}</h2>
                <p class="text-sm text-muted-foreground mt-2">{{ __('messages.no_results_desc') }}</p>
            </div>
        </section>
    @else
        <section class="container mx-auto px-4 py-12">
            <div class="rounded-lg border border-border bg-card p-6 shadow-card">
                <div class="grid gap-6 md:grid-cols-[220px_1fr] md:items-center">
                    <div class="grid h-36 place-items-center rounded-lg bg-secondary/40">
                        <i data-lucide="radar" class="h-16 w-16 text-accent"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-foreground">ابدأ بتتبع شحنتك</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-7 text-muted-foreground">
                            أدخل رقم التتبع أو رقم الطلب أو مرجع الحجز الملاحي، وستظهر البيانات المسجلة فعليًا في نظام العمليات.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-share-tracking-url]').forEach(button => {
            button.addEventListener('click', async () => {
                const url = button.dataset.shareTrackingUrl;

                try {
                    if (navigator.share) {
                        await navigator.share({ title: document.title, url });
                    } else if (navigator.clipboard) {
                        await navigator.clipboard.writeText(url);
                        window.showGlobalToast?.('تم نسخ رابط التتبع', 'success');
                    }
                } catch (error) {
                    window.showGlobalToast?.('تعذر مشاركة الرابط', 'error');
                }
            });
        });
    });
</script>
@endsection
