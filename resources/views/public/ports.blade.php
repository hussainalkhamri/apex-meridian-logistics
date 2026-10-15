@extends('layouts.app')

@section('title', __('messages.public_our_ports') . ' - ' . __('messages.site_name'))

@section('content')
@php
    $isArabic = app()->getLocale() === 'ar';
    $copy = [
        'badge' => $isArabic ? 'شبكة الموانئ' : 'Port Network',
        'title' => $isArabic ? 'موانئنا حول العالم' : 'Our Global Ports',
        'subtitle' => $isArabic
            ? 'استعرض الموانئ التي يعتمد عليها النظام في طلبات الشحن والتسعير وجداول الرحلات، مع إمكانية البحث حسب الدولة أو رمز الميناء.'
            : 'Explore the ports used by the system for booking, pricing, and published vessel schedules, with search by country or port code.',
        'ports' => $isArabic ? 'موانئ' : 'Ports',
        'countries' => $isArabic ? 'دول' : 'Countries',
        'filtered' => $isArabic ? 'مطابق للبحث' : 'Matching',
        'featured' => $isArabic ? 'مناطق بارزة' : 'Featured areas',
        'search' => $isArabic ? 'ابحث باسم الميناء أو الدولة أو الرمز...' : 'Search by port, country, or code...',
        'all_countries' => $isArabic ? 'كل الدول' : 'All countries',
        'filter' => $isArabic ? 'تصفية' : 'Filter',
        'reset' => $isArabic ? 'مسح' : 'Reset',
        'coverage' => $isArabic ? 'تغطية تشغيلية' : 'Operational Coverage',
        'coverage_desc' => $isArabic
            ? 'هذه القائمة مرتبطة ببيانات الموانئ في النظام، وتستخدمها نماذج طلبات الشحن بدل إدخال نصوص عشوائية.'
            : 'This list is linked to the system port data and is used by shipping forms instead of loose text entry.',
        'featured_countries' => $isArabic ? 'أهم الدول في الشبكة' : 'Key Countries In The Network',
        'directory' => $isArabic ? 'دليل الموانئ' : 'Ports Directory',
        'empty' => $isArabic ? 'لا توجد موانئ مطابقة للفلاتر الحالية.' : 'No ports match the current filters.',
        'request' => $isArabic ? 'طلب شحنة من هذا المسار' : 'Request a shipment',
        'schedules' => $isArabic ? 'عرض جداول الرحلات' : 'View schedules',
        'code' => $isArabic ? 'رمز الميناء' : 'Port code',
        'country_code' => $isArabic ? 'رمز الدولة' : 'Country code',
    ];
    $quoteHref = auth('customer')->check() ? route('customer.shipments.create') : route('customer.login');
@endphp

<div class="public-network-page motion-page">
    <section class="network-hero relative isolate overflow-hidden bg-primary text-primary-foreground">
        <div class="absolute inset-0 bg-gradient-hero"></div>
        <div class="network-hero-grid absolute inset-0"></div>
        <div class="absolute inset-0 bg-gradient-ocean"></div>

        <div class="container relative z-10 mx-auto grid gap-10 px-4 py-16 lg:grid-cols-[minmax(0,1fr)_26rem] lg:items-center">
            <div class="max-w-3xl">
                <span class="network-kicker">
                    <i data-lucide="anchor" class="h-4 w-4"></i>
                    {{ $copy['badge'] }}
                </span>
                <h1 class="mt-5 max-w-2xl text-3xl font-black leading-tight md:text-5xl">{{ $copy['title'] }}</h1>
                <p class="mt-4 max-w-2xl text-sm leading-8 text-primary-foreground/78 md:text-base">{{ $copy['subtitle'] }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ $quoteHref }}" class="motion-button inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground shadow-glow">
                        <i data-lucide="send" class="h-4 w-4"></i>
                        {{ $copy['request'] }}
                    </a>
                    <a href="{{ route('schedules') }}" class="motion-button inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-white/22 bg-white/10 px-5 text-sm font-bold text-white backdrop-blur hover:bg-white/16">
                        <i data-lucide="calendar-days" class="h-4 w-4"></i>
                        {{ $copy['schedules'] }}
                    </a>
                </div>
            </div>

            <div class="network-route-visual rounded-lg border border-white/18 bg-white/10 p-5 shadow-elegant backdrop-blur">
                <div class="flex items-center justify-between gap-3 text-xs font-bold text-white/74">
                    <span>{{ $copy['coverage'] }}</span>
                    <span>{{ number_format($stats['ports']) }} {{ $copy['ports'] }}</span>
                </div>
                <div class="network-mini-map mt-5">
                    <span class="network-map-point is-start">POL</span>
                    <span class="network-map-line"></span>
                    <span class="network-map-point is-end">POD</span>
                    <i data-lucide="ship" class="network-map-ship h-8 w-8"></i>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-2">
                    @foreach($featuredCountries->take(4) as $country)
                        <div class="rounded-lg border border-white/12 bg-white/10 p-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-lg">{{ $country['country_flag'] ?: $country['country_code'] }}</span>
                                <span class="rounded-md bg-white/14 px-2 py-0.5 text-[11px] font-black">{{ $country['count'] }}</span>
                            </div>
                            <div class="mt-2 truncate text-xs font-black text-white">{{ $country['country'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 py-10">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="network-stat-card motion-card">
                <i data-lucide="anchor" class="h-5 w-5 text-accent"></i>
                <span>{{ number_format($stats['ports']) }}</span>
                <small>{{ $copy['ports'] }}</small>
            </div>
            <div class="network-stat-card motion-card">
                <i data-lucide="globe-2" class="h-5 w-5 text-accent"></i>
                <span>{{ number_format($stats['countries']) }}</span>
                <small>{{ $copy['countries'] }}</small>
            </div>
            <div class="network-stat-card motion-card">
                <i data-lucide="search-check" class="h-5 w-5 text-accent"></i>
                <span>{{ number_format($stats['filtered']) }}</span>
                <small>{{ $copy['filtered'] }}</small>
            </div>
            <div class="network-stat-card motion-card">
                <i data-lucide="map" class="h-5 w-5 text-accent"></i>
                <span>{{ number_format($stats['featured']) }}</span>
                <small>{{ $copy['featured'] }}</small>
            </div>
        </div>

        <div class="mt-8 rounded-lg border border-border bg-card p-4 shadow-card">
            <form action="{{ route('public.ports') }}" method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_16rem_auto_auto]">
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground ltr:left-3 rtl:right-3"></i>
                    <input type="search" name="q" value="{{ $query }}" placeholder="{{ $copy['search'] }}" class="motion-input h-11 w-full rounded-lg border border-border bg-background px-10 text-sm outline-none focus:border-accent">
                </div>
                <select name="country" class="motion-input h-11 rounded-lg border border-border bg-background px-3 text-sm outline-none focus:border-accent">
                    <option value="all">{{ $copy['all_countries'] }}</option>
                    @foreach($countries as $country)
                        <option value="{{ $country['name'] }}" @selected($selectedCountry === $country['name'])>
                            {{ $country['flag'] }} {{ $country['name'] }} ({{ $country['count'] }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="motion-button inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4"></i>
                    {{ $copy['filter'] }}
                </button>
                <a href="{{ route('public.ports') }}" class="motion-button inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-border bg-card px-5 text-sm font-bold hover:bg-secondary">
                    <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    {{ $copy['reset'] }}
                </a>
            </form>
        </div>

        <div class="mt-10 grid gap-5 lg:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="space-y-4">
                <div class="rounded-lg border border-accent/20 bg-accent/5 p-5">
                    <div class="flex items-center gap-2 text-sm font-black text-foreground">
                        <i data-lucide="badge-check" class="h-4 w-4 text-accent"></i>
                        {{ $copy['coverage'] }}
                    </div>
                    <p class="mt-3 text-xs leading-6 text-muted-foreground">{{ $copy['coverage_desc'] }}</p>
                </div>

                <div class="space-y-2">
                    <h2 class="text-sm font-black text-foreground">{{ $copy['featured_countries'] }}</h2>
                    @foreach($featuredCountries as $country)
                        <a href="{{ route('public.ports', ['country' => $country['country']]) }}" class="network-country-row motion-card">
                            <span class="text-lg">{{ $country['country_flag'] ?: $country['country_code'] }}</span>
                            <span class="min-w-0 flex-1 truncate font-bold">{{ $country['country'] }}</span>
                            <span class="rounded-md bg-secondary px-2 py-0.5 text-[11px] font-black text-muted-foreground">{{ $country['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </aside>

            <div>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-xl font-black text-foreground">{{ $copy['directory'] }}</h2>
                    <span class="rounded-full bg-secondary px-3 py-1 text-xs font-bold text-muted-foreground">{{ number_format($filteredPorts->count()) }} {{ $copy['ports'] }}</span>
                </div>

                <div class="space-y-5">
                    @forelse($portGroups as $country => $groupPorts)
                        @php $firstPort = $groupPorts->first(); @endphp
                        <section class="rounded-lg border border-border bg-card p-5 shadow-card">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border/70 pb-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-secondary text-lg">{{ $firstPort->country_flag ?: $firstPort->country_code }}</span>
                                    <div class="min-w-0">
                                        <h3 class="truncate text-base font-black text-foreground">{{ $country ?: 'Global' }}</h3>
                                        <p class="text-xs text-muted-foreground">{{ $firstPort->country_code ?: $copy['country_code'] }}</p>
                                    </div>
                                </div>
                                <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-black text-accent">{{ $groupPorts->count() }} {{ $copy['ports'] }}</span>
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                @foreach($groupPorts as $port)
                                    <div class="network-port-tile motion-card">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-black text-foreground">{{ $port->name }}</div>
                                                <div class="mt-1 text-[11px] font-bold text-muted-foreground">{{ $copy['code'] }}</div>
                                            </div>
                                            <span class="rounded-md bg-primary/10 px-2 py-1 font-mono text-xs font-black text-primary dark:bg-accent/10 dark:text-accent">{{ $port->code ?: '---' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @empty
                        <div class="rounded-lg border border-dashed border-border bg-card p-10 text-center">
                            <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-secondary text-muted-foreground">
                                <i data-lucide="search-x" class="h-7 w-7"></i>
                            </div>
                            <div class="mt-3 text-sm font-black text-foreground">{{ $copy['empty'] }}</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
