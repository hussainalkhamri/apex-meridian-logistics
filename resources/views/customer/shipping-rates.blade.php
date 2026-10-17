@extends('layouts.customer')

@section('title', __('أسعار الشحن') . ' — ' . __('messages.site_name'))

@section('content')
@php
    $summaryCards = [
        ['label' => __('الأسعار المتاحة'), 'value' => $stats['total'], 'icon' => 'list-checks'],
        ['label' => __('المسارات'), 'value' => $stats['routes'], 'icon' => 'route'],
        ['label' => __('شركات الشحن'), 'value' => $stats['companies'], 'icon' => 'building-2'],
        ['label' => __('أقل سعر'), 'value' => $stats['lowest_price_label'], 'icon' => 'circle-dollar-sign'],
    ];
    $filterFields = ['search', 'shipping_type', 'service_type', 'incoterm', 'container_type', 'origin_country', 'origin_port', 'destination_country', 'destination_port', 'shipping_company'];
    $hasFilters = collect($filterFields)->contains(fn ($field) => request()->filled($field));
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-border bg-card p-5 shadow-card">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-black">{{ __('أسعار الشحن') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-muted-foreground">
                    {{ __('أسعار الشحن الفعالة والمتاحة من فريق العمليات، مع إمكانية البحث حسب المسار أو نوع الحاوية أو شركة الشحن.') }}
                </p>
            </div>
            <a href="{{ route('customer.shipments.create') }}" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground">
                <i data-lucide="package-plus" class="h-4 w-4"></i>
                {{ __('طلب شحنة جديدة') }}
            </a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($summaryCards as $card)
            <article class="min-w-0 rounded-xl border border-border bg-card p-4 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-secondary text-primary">
                        <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-xs font-bold text-muted-foreground">{{ $card['label'] }}</div>
                        <div class="mt-1 truncate text-xl font-black text-foreground" dir="ltr">{{ $card['value'] }}</div>
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <section class="overflow-hidden rounded-xl border border-border bg-card shadow-card">
        <div class="flex flex-col gap-2 border-b border-border p-5 md:flex-row md:items-center md:justify-between">
            <div class="min-w-0">
                <h2 class="text-lg font-black">{{ __('فلترة الأسعار') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ __('النتائج تعرض الأسعار الفعالة فقط داخل بوابة العملاء.') }}</p>
            </div>
            <span class="inline-flex h-8 w-fit shrink-0 items-center rounded-full bg-secondary px-3 text-xs font-black">
                {{ $shippingRates->total() }} {{ __('نتيجة') }}
            </span>
        </div>

        <details class="portal-filters" @if($hasFilters) open @endif>
        <summary class="cursor-pointer px-5 py-3 text-sm font-semibold">{{ __('portal.filters') }}</summary>
        <form data-filter-form action="{{ route('customer.shipping-rates') }}" method="GET" class="grid gap-3 p-5 md:grid-cols-2 xl:grid-cols-4">
            <label class="relative xl:col-span-2">
                <span class="sr-only">{{ __('بحث في أسعار الشحن') }}</span>
                <i data-lucide="search" class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="{{ __('بحث بالشركة، المسار، نوع الخدمة، أو الملاحظات') }}"
                    class="h-10 w-full rounded-lg border border-border bg-background px-10 text-sm focus:outline-none focus:ring-1 focus:ring-accent"
                >
            </label>

            <label>
                <span class="sr-only">{{ __('نوع الشحن') }}</span>
                <select name="shipping_type" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('كل أنواع الشحن') }}</option>
                    @foreach($filtersData['shipping_types'] as $value)
                        <option value="{{ $value }}" @selected(request('shipping_type') === (string) $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="sr-only">{{ __('نوع الخدمة') }}</span>
                <select name="service_type" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('كل الخدمات') }}</option>
                    @foreach($filtersData['service_types'] as $value)
                        <option value="{{ $value }}" @selected(request('service_type') === (string) $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="sr-only">{{ __('شرط الشحن') }}</span>
                <select name="incoterm" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('كل شروط الشحن') }}</option>
                    @foreach(($filtersData['incoterms'] ?? collect(['FOB', 'EXW'])) as $value)
                        <option value="{{ $value }}" @selected(request('incoterm') === (string) $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="sr-only">{{ __('نوع الحاوية') }}</span>
                <select name="container_type" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('كل الحاويات') }}</option>
                    @foreach($filtersData['container_types'] as $value)
                        <option value="{{ $value }}" @selected(request('container_type') === (string) $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="sr-only">{{ __('بلد الانطلاق') }}</span>
                <select name="origin_country" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('كل بلدان الانطلاق') }}</option>
                    @foreach($filtersData['origin_countries'] as $value)
                        <option value="{{ $value }}" @selected(request('origin_country') === (string) $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="sr-only">{{ __('ميناء الانطلاق') }}</span>
                <select name="origin_port" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('كل منافذ الانطلاق') }}</option>
                    @foreach($filtersData['origin_ports'] as $value)
                        <option value="{{ $value }}" @selected(request('origin_port') === (string) $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="sr-only">{{ __('بلد الوصول') }}</span>
                <select name="destination_country" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('كل بلدان الوصول') }}</option>
                    @foreach($filtersData['destination_countries'] as $value)
                        <option value="{{ $value }}" @selected(request('destination_country') === (string) $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="sr-only">{{ __('ميناء الوصول') }}</span>
                <select name="destination_port" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('كل منافذ الوصول') }}</option>
                    @foreach($filtersData['destination_ports'] as $value)
                        <option value="{{ $value }}" @selected(request('destination_port') === (string) $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span class="sr-only">{{ __('شركة الشحن') }}</span>
                <select name="shipping_company" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('كل شركات الشحن') }}</option>
                    @foreach($filtersData['shipping_companies'] as $value)
                        <option value="{{ $value }}" @selected(request('shipping_company') === (string) $value)>{{ $value }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex flex-wrap gap-2 md:col-span-2 xl:col-span-4">
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-black text-primary-foreground hover:opacity-95">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    {{ __('تطبيق') }}
                </button>
                <a href="{{ route('customer.shipping-rates') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 text-sm font-black hover:bg-secondary">
                    <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    {{ __('مسح') }}
                </a>
            </div>
        </form>
        </details>
    </section>

    @if($shippingRates->isEmpty())
        <section class="rounded-xl border border-dashed border-border bg-card p-12 text-center shadow-card">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-xl bg-secondary text-primary">
                <i data-lucide="search-x" class="h-7 w-7"></i>
            </span>
            <h3 class="mt-4 text-lg font-black">{{ $hasFilters ? __('لا توجد أسعار مطابقة') : __('لا توجد أسعار شحن متاحة حالياً') }}</h3>
            <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-muted-foreground">
                {{ $hasFilters ? __('جرّب تعديل الفلاتر أو مسحها لعرض كل الأسعار الفعالة.') : __('سيتم عرض الأسعار هنا عند اعتمادها من فريق العمليات.') }}
            </p>
        </section>
    @else
        <section class="space-y-3">
            @foreach($shippingRates as $rate)
                @php
                    $basePrice = (float) $rate->price;
                    $additionalFee = (float) ($rate->additional_fee ?? 0);
                    $totalPrice = $basePrice + $additionalFee;
                @endphp
                <article class="rounded-xl border border-border bg-card p-3 shadow-card transition hover:border-accent/40 sm:p-4">
                    <div class="grid gap-3 lg:grid-cols-4 lg:items-stretch">
                        <div class="min-w-0 space-y-3 lg:col-span-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-accent/20 bg-accent/10 px-3 py-1 text-xs font-black text-accent">
                                    <i data-lucide="ship" class="h-3.5 w-3.5"></i>
                                    {{ $rate->shipping_type }}
                                </span>
                                <span class="rounded-full border border-border bg-background px-3 py-1 text-xs font-bold text-muted-foreground">{{ $rate->service_type }}</span>
                                <span class="rounded-full border border-accent/20 bg-accent/10 px-3 py-1 text-xs font-black text-accent">{{ $rate->incoterm ?: 'FOB' }}</span>
                                <span class="rounded-full border border-border bg-background px-3 py-1 text-xs font-bold text-muted-foreground">{{ $rate->container_type }}</span>
                            </div>

                            <div class="flex min-w-0 items-center gap-2">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-secondary text-primary">
                                    <i data-lucide="building-2" class="h-4 w-4"></i>
                                </span>
                                <h3 class="truncate text-base font-black">{{ $rate->shipping_company }}</h3>
                            </div>

                            <div class="rounded-lg bg-secondary/20 px-3 py-2">
                                <div class="grid grid-cols-3 items-center gap-2">
                                    <div class="min-w-0 text-right">
                                        <div class="text-[10px] font-black text-muted-foreground">{{ __('من') }}</div>
                                        <div class="mt-0.5 truncate text-sm font-black">{{ $rate->origin_port }}</div>
                                        <div class="truncate text-[10px] text-muted-foreground">{{ $rate->origin_country }}</div>
                                    </div>
                                    <div class="mx-auto grid h-8 w-8 place-items-center rounded-full bg-background text-primary">
                                        <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                    </div>
                                    <div class="min-w-0 text-left">
                                        <div class="text-[10px] font-black text-muted-foreground">{{ __('إلى') }}</div>
                                        <div class="mt-0.5 truncate text-sm font-black">{{ $rate->destination_port }}</div>
                                        <div class="truncate text-[10px] text-muted-foreground">{{ $rate->destination_country }}</div>
                                    </div>
                                </div>
                            </div>

                            @if($rate->notes)
                                <details class="rounded-lg border border-dashed border-border bg-background px-3 py-2 text-xs text-muted-foreground">
                                    <summary class="cursor-pointer">{{ __('messages.notes') }}</summary>
                                    <p class="mt-2 whitespace-pre-line leading-6">{{ $rate->notes }}</p>
                                </details>
                            @endif
                        </div>

                        <div class="grid grid-cols-3 gap-2 lg:grid-cols-1">
                            <div class="min-w-0 rounded-lg border border-border/70 bg-secondary/15 p-2.5">
                                <div class="text-[11px] font-black text-muted-foreground">{{ __('مدة العبور') }}</div>
                                <div class="mt-1 text-sm font-black">{{ $rate->transit_time ? $rate->transit_time . __(' يوم') : __('غير محددة') }}</div>
                            </div>
                            <div class="min-w-0 rounded-lg border border-border/70 bg-secondary/15 p-2.5">
                                <div class="text-[11px] font-black text-muted-foreground">{{ __('تاريخ البداية') }}</div>
                                <div class="mt-1 text-sm font-black" dir="ltr">{{ $rate->valid_from?->format('Y-m-d') ?: '—' }}</div>
                            </div>
                            <div class="min-w-0 rounded-lg border border-border/70 bg-secondary/15 p-2.5">
                                <div class="text-[11px] font-black text-muted-foreground">{{ __('تاريخ النهاية') }}</div>
                                <div class="mt-1 text-sm font-black" dir="ltr">{{ $rate->valid_to?->format('Y-m-d') ?: __('مفتوحة') }}</div>
                            </div>
                        </div>

                        <div class="min-w-0 rounded-lg border border-accent/20 bg-background p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-[11px] font-black text-muted-foreground">{{ __('الإجمالي') }}</div>
                                <div class="truncate text-xl font-black text-primary sm:text-2xl" dir="ltr">{{ number_format($totalPrice, 2) }} {{ $rate->currency }}</div>
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-2 border-t border-border/60 pt-2 text-xs">
                                <div class="min-w-0 rounded-md bg-secondary/25 px-2 py-1.5">
                                    <div class="truncate text-muted-foreground">{{ __('السعر الأساسي') }}</div>
                                    <div class="mt-0.5 truncate font-black" dir="ltr">{{ number_format($basePrice, 2) }}</div>
                                </div>
                                <div class="min-w-0 rounded-md bg-secondary/25 px-2 py-1.5">
                                    <div class="truncate text-muted-foreground">{{ __('رسوم إضافية') }}</div>
                                    <div class="mt-0.5 truncate font-black" dir="ltr">{{ number_format($additionalFee, 2) }}</div>
                                </div>
                            </div>
                            <a href="{{ route('customer.shipments.create', ['shipping_rate_id' => $rate->id]) }}" class="mt-3 inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg bg-gradient-accent px-4 text-xs font-black text-accent-foreground sm:h-10">
                                {{ __('طلب على هذا المسار') }}
                                <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        <div class="rounded-xl border border-border bg-card p-4 shadow-card">
            {{ $shippingRates->links() }}
        </div>
    @endif
</div>
@endsection
