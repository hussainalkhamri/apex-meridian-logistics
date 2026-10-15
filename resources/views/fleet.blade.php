@extends('layouts.app')

@section('title', __('messages.fleet') . ' — ' . __('messages.site_name'))

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-extrabold md:text-4xl">{{ __('messages.fleet_heading') }}</h1>
            <p class="mt-2 text-white/80">{{ __('messages.fleet_desc') }}</p>
        </div>
    </section>

    <section class="container mx-auto grid gap-5 px-4 py-12 md:grid-cols-2 lg:grid-cols-3">
        @php
            $statusColors = [
                'قيد الإبحار' => 'bg-success text-success-foreground border-success/30',
                'في الميناء' => 'bg-accent text-accent-foreground border-accent/30',
                'قيد الصيانة' => 'bg-warning text-warning-foreground border-warning/30',
            ];
        @endphp
        @foreach($vessels as $v)
            <div class="group rounded-2xl border border-border bg-card shadow-card overflow-hidden transition-all hover:-translate-y-1 hover:shadow-elegant">
                <!-- Card Header Top Graphic -->
                <div class="relative h-32 bg-gradient-to-l from-primary to-ocean">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,oklch(0.72_0.16_215_/_0.4),transparent)]"></div>
                    <i data-lucide="ship" class="absolute bottom-3 left-6 h-16 w-16 text-white/70 transition-transform group-hover:scale-110 animate-float"></i>
                    <span class="absolute right-4 top-4 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold border {{ $statusColors[$v->status] ?? 'bg-secondary' }}">
                        {{ __('messages.status_' . $v->status) }}
                    </span>
                </div>
                <!-- Card Body -->
                <div class="p-6 space-y-4">
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-lg font-black text-foreground">{{ $v->name }}</h3>
                        <span class="text-2xl font-bold">{{ $v->flag }}</span>
                    </div>
                    <p class="text-xs text-muted-foreground">{{ $v->type }}</p>
                    
                    <div class="grid grid-cols-2 gap-3 pt-2 text-xs">
                        <div class="rounded-lg bg-secondary/60 p-2.5">
                            <div class="flex items-center gap-1 text-muted-foreground text-[10px]">
                                <i data-lucide="anchor" class="h-3.5 w-3.5 text-accent"></i>
                                <span>{{ __('messages.capacity') }}</span>
                            </div>
                            <div class="mt-0.5 font-bold text-foreground">{{ $v->capacity }}</div>
                        </div>

                        <div class="rounded-lg bg-secondary/60 p-2.5">
                            <div class="flex items-center gap-1 text-muted-foreground text-[10px]">
                                <i data-lucide="calendar" class="h-3.5 w-3.5 text-accent"></i>
                                <span>{{ __('messages.built') }}</span>
                            </div>
                            <div class="mt-0.5 font-bold text-foreground">{{ $v->year }}</div>
                        </div>

                        <div class="rounded-lg bg-secondary/60 p-2.5 col-span-2">
                            <div class="flex items-center gap-1 text-muted-foreground text-[10px]">
                                <i data-lucide="flag" class="h-3.5 w-3.5 text-accent"></i>
                                <span>{{ __('messages.active_route') }}</span>
                            </div>
                            @php
                                $routeParts = explode(' → ', $v->route);
                                $translatedRoute = collect($routeParts)->map(function($part) {
                                    $part = trim($part);
                                    return __('messages.' . $part);
                                })->implode(' → ');
                            @endphp
                            <div class="mt-0.5 font-bold text-foreground">{{ $translatedRoute ?: $v->route }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </section>
</div>
@endsection
