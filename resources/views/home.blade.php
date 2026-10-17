@extends('layouts.app')

@section('title', __('messages.site_name') . ' - ' . (app()->getLocale() === 'ar' ? 'بوابة النظام والعمليات اللوجستية' : 'Enterprise Operations & Client Portal'))

@section('content')
@php
    $isArabic = app()->getLocale() === 'ar';
    $copy = fn (string $ar, string $en): string => $isArabic ? $ar : $en;
    $customerPortalUrl = auth('customer')->check() ? route('customer.dashboard') : route('customer.login');
    $customerStartUrl = auth('customer')->check() ? route('customer.shipments.create') : route('customer.login');
    $staffPortalUrl = auth()->check() ? route('dashboard') : route('login');
    $statValues = collect($trustStats ?? [])->pluck('value')->values();

    $systemStats = [
        ['value' => $statValues->get(0, '+18M'), 'label' => $copy('شحنة مسجلة ومدارة', 'Managed Cargo Shipments'), 'icon' => 'clipboard-check'],
        ['value' => $statValues->get(1, '420'), 'label' => $copy('عميل وشركة تجارية', 'Active B2B Clients'), 'icon' => 'building-2'],
        ['value' => $statValues->get(2, '90+'), 'label' => $copy('ميناء ومسار بحري', 'Connected Ports & Lanes'), 'icon' => 'anchor'],
        ['value' => $statValues->get(3, '<24h'), 'label' => $copy('متوسط دورة التسعير', 'Avg Pricing Turnaround'), 'icon' => 'timer'],
    ];
@endphp

<div class="motion-page min-h-screen bg-background">
    <!-- System Master Banner -->
    <section class="relative overflow-hidden bg-primary py-16 text-primary-foreground md:py-24">
        <div class="absolute inset-0 bg-cover bg-center opacity-15" style="background-image: url('{{ asset('images/brand/logistics.jpg') }}');"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-primary/95 via-primary/85 to-primary"></div>
        
        <div class="container relative mx-auto px-4">
            <div class="mx-auto max-w-4xl text-center">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-bold tracking-wide backdrop-blur-md">
                    <i data-lucide="shield-check" class="h-4 w-4 text-accent"></i>
                    {{ $copy('منظومة الشحن والخدمات اللوجستية المتكاملة', 'Integrated Shipping & Logistics Management System') }}
                </span>
                
                <h1 class="mt-6 text-3xl font-black leading-tight tracking-tight sm:text-5xl md:text-6xl">
                    {{ config('company.name.' . app()->getLocale()) }}
                </h1>
                
                <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-primary-foreground/80 sm:text-lg">
                    {{ $copy('بوابة رقمية موحدة لإدارة العمليات اللوجستية، تتبع الحاويات، جداول الإبحار، والتخليص الجمركي للشاحنين وفرق العمليات.', 'A unified digital platform for freight operations, live container tracking, vessel schedules, and customs clearance for shippers and operations teams.') }}
                </p>

                <!-- System Primary Access Portals -->
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <a href="{{ $staffPortalUrl }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-gradient-accent px-6 text-sm font-bold text-accent-foreground shadow-glow hover:opacity-95 transition-all">
                        <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                        {{ auth()->check() ? $copy('لوحة تحكم العمليات', 'Operations Dashboard') : $copy('دخول الموظفين والعمليات', 'Staff & Operations Login') }}
                    </a>

                    <a href="{{ $customerPortalUrl }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/10 px-6 text-sm font-bold text-white backdrop-blur hover:bg-white/20 transition-all">
                        <i data-lucide="user-round-check" class="h-4 w-4"></i>
                        {{ auth('customer')->check() ? $copy('لوحة تحكم العميل', 'Customer Dashboard') : $copy('بوابة العملاء والشركات', 'Customer & Client Portal') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Utilities & Gateway Modules -->
    <section class="container mx-auto -mt-8 px-4 pb-16">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            <!-- 1. Live Container Tracking Quick Card -->
            <div class="rounded-2xl border border-border bg-card p-6 shadow-card transition-all hover:border-accent/40 hover:shadow-lg">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-accent/10 text-accent">
                    <i data-lucide="map-pin" class="h-6 w-6"></i>
                </div>
                <h3 class="mt-4 text-base font-black text-foreground">{{ $copy('تتبع الشحنات والحاويات', 'Live Shipment Tracking') }}</h3>
                <p class="mt-1 text-xs leading-5 text-muted-foreground">{{ $copy('استعلم مباشرة عن موقع الحاوية ومراحل الشحن والتخليص.', 'Query real-time container coordinates and milestone telemetry.') }}</p>
                <form action="{{ route('tracking') }}" method="GET" class="mt-4 flex gap-2">
                    <input type="text" name="q" placeholder="{{ $copy('رقم الحاوية أو الشحنة...', 'Container or booking ref...') }}" class="h-9 w-full rounded-lg border border-border bg-background px-3 text-xs outline-none focus:border-accent">
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-primary px-3 text-xs font-bold text-primary-foreground hover:bg-primary/90">
                        <i data-lucide="search" class="h-3.5 w-3.5"></i>
                    </button>
                </form>
            </div>

            <!-- 2. Vessel Sailing Schedules Card -->
            <div class="rounded-2xl border border-border bg-card p-6 shadow-card transition-all hover:border-accent/40 hover:shadow-lg">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <i data-lucide="calendar" class="h-6 w-6"></i>
                </div>
                <h3 class="mt-4 text-base font-black text-foreground">{{ $copy('مواعيد وجداول الإبحار', 'Vessel Sailing Schedules') }}</h3>
                <p class="mt-1 text-xs leading-5 text-muted-foreground">{{ $copy('استعرض الخطوط الملاحية النشطة، زمن الرحلة، وتواريخ الإغلاق.', 'Browse active carrier routes, transit durations, and cut-off dates.') }}</p>
                <a href="{{ route('schedules') }}" class="mt-4 inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-lg border border-border bg-secondary/50 text-xs font-bold text-foreground hover:bg-secondary transition-colors">
                    <span>{{ $copy('استعراض الجداول الملاحية', 'View Schedules') }}</span>
                    <i data-lucide="arrow-left" class="h-3.5 w-3.5 rtl:rotate-0 ltr:rotate-180"></i>
                </a>
            </div>

            <!-- 3. Shipment Booking Request Card -->
            <div class="rounded-2xl border border-border bg-card p-6 shadow-card transition-all hover:border-accent/40 hover:shadow-lg">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-accent/10 text-accent">
                    <i data-lucide="file-plus-2" class="h-6 w-6"></i>
                </div>
                <h3 class="mt-4 text-base font-black text-foreground">{{ $copy('طلب شحنة من حسابك', 'Book Shipment') }}</h3>
                <p class="mt-1 text-xs leading-5 text-muted-foreground">{{ $copy('أنشئ طلب شحن جديد مع تفاصيل المسار والوزن والمستندات.', 'Submit a new sea, land, or door-to-door shipment request.') }}</p>
                <a href="{{ $customerStartUrl }}" class="mt-4 inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-lg bg-accent text-xs font-bold text-accent-foreground hover:opacity-90 transition-opacity">
                    <span>{{ $copy('طلب شحنة من حسابك', 'Book Shipment') }}</span>
                    <i data-lucide="arrow-left" class="h-3.5 w-3.5 rtl:rotate-0 ltr:rotate-180"></i>
                </a>
            </div>

            <!-- 4. Client Account Registration Card -->
            <div class="rounded-2xl border border-border bg-card p-6 shadow-card transition-all hover:border-accent/40 hover:shadow-lg">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <i data-lucide="user-plus" class="h-6 w-6"></i>
                </div>
                <h3 class="mt-4 text-base font-black text-foreground">{{ $copy('حساب عميل جديد', 'Register Client Account') }}</h3>
                <p class="mt-1 text-xs leading-5 text-muted-foreground">{{ $copy('سجل شركتك للانضمام إلى البوابة ومتابعة شحناتك إلكترونياً.', 'Create a company account to track shipments and manage quotes.') }}</p>
                <a href="{{ route('customer.register') }}" class="mt-4 inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-lg border border-border bg-secondary/50 text-xs font-bold text-foreground hover:bg-secondary transition-colors">
                    <span>{{ $copy('إنشاء حساب عميل', 'Create Customer Account') }}</span>
                    <i data-lucide="arrow-left" class="h-3.5 w-3.5 rtl:rotate-0 ltr:rotate-180"></i>
                </a>
            </div>
        </div>

        <!-- System Architecture & Metrics Overview -->
        <div class="mt-12 rounded-2xl border border-border bg-card p-8 shadow-card">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($systemStats as $stat)
                    <div class="flex items-center gap-4">
                        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-secondary text-primary">
                            <i data-lucide="{{ $stat['icon'] }}" class="h-6 w-6"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-black text-foreground">{{ $stat['value'] }}</div>
                            <div class="text-xs font-semibold text-muted-foreground">{{ $stat['label'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection
