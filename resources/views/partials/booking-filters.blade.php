@php
    $filterAction = $filterAction ?? url()->current();
    $filterId = $filterId ?? 'booking-filter-form';
    $filterValues = $filters ?? [
        'q' => '',
        'status' => 'all',
        'transport_mode' => 'all',
        'sea_type' => 'all',
    ];
    $filterStatuses = $filterStatuses ?? [
        'all' => __('messages.orders_status_all'),
        'new' => __('messages.orders_status_new'),
        'needs_pricing' => __('messages.orders_status_needs_pricing'),
        'priced' => __('messages.orders_status_priced'),
        'sent' => __('messages.orders_status_sent'),
        'customer_approved' => __('messages.sales_status_customer_approved'),
        'customer_rejected' => __('messages.sales_status_customer_rejected'),
        'approved' => __('messages.orders_status_approved'),
        'booked' => __('messages.orders_status_booked'),
        'rejected' => __('messages.orders_status_rejected'),
    ];
    $currentTransportMode = $filterValues['transport_mode'] ?? 'all';
    $currentSeaType = $filterValues['sea_type'] ?? 'all';
    $currentStatus = $filterValues['status'] ?? 'all';
@endphp

<div class="mb-6 rounded-xl border border-border/60 bg-card p-4 shadow-card sm:p-5">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2 text-sm font-black text-card-foreground">
            <i data-lucide="list-filter" class="h-4 w-4 text-accent"></i>
            <span>{{ __('messages.orders_filter_heading') }}</span>
        </div>
        <a href="{{ $filterAction }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-border px-3 text-xs font-bold text-muted-foreground transition hover:bg-secondary/50 hover:text-foreground" title="{{ __('messages.orders_filter_reset') }}">
            <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
            <span>{{ __('messages.orders_filter_reset') }}</span>
        </a>
    </div>

    <form id="{{ $filterId }}" data-booking-filter-form action="{{ $filterAction }}" method="GET" class="space-y-3">
        <div class="space-y-1.5">
            <label for="{{ $filterId }}-search" class="text-xs font-black text-muted-foreground">{{ __('messages.orders_search_label') }}</label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <div class="relative min-w-0 flex-1">
                    <i data-lucide="search" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                    <input id="{{ $filterId }}-search" type="search" name="q" value="{{ $filterValues['q'] ?? '' }}" data-booking-search-input autocomplete="off" aria-label="{{ __('messages.orders_filter_search_placeholder') }}" placeholder="{{ __('messages.orders_filter_search_placeholder') }}" class="h-10 w-full rounded-lg border border-border bg-background pr-9 pl-4 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-secondary/40 px-4 text-sm font-bold text-foreground transition hover:bg-secondary">
                    <i data-lucide="search" class="h-4 w-4"></i>
                    <span>{{ __('messages.orders_search_submit') }}</span>
                </button>
            </div>
        </div>

        <div class="border-t border-border/60 pt-3">
            <div class="mb-2 flex items-center gap-2 text-xs font-black text-muted-foreground">
                <i data-lucide="sliders-horizontal" class="h-3.5 w-3.5 text-accent"></i>
                <span>{{ __('messages.orders_filters_label') }}</span>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <select name="transport_mode" data-booking-transport-filter class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent sm:w-44">
                    <option value="all" {{ $currentTransportMode === 'all' ? 'selected' : '' }}>{{ __('messages.orders_transport_filter_all') }}</option>
                    <option value="sea" {{ $currentTransportMode === 'sea' ? 'selected' : '' }}>{{ __('messages.orders_transport_filter_sea') }}</option>
                    <option value="air" {{ $currentTransportMode === 'air' ? 'selected' : '' }}>{{ __('messages.orders_transport_filter_air') }}</option>
                    <option value="land" {{ $currentTransportMode === 'land' ? 'selected' : '' }}>{{ __('messages.orders_transport_filter_land') }}</option>
                </select>

                <div data-booking-sea-type-wrapper class="w-full sm:w-52 {{ $currentTransportMode === 'sea' ? '' : 'hidden' }}">
                    <select name="sea_type" data-booking-sea-type-filter @disabled($currentTransportMode !== 'sea') class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="all" {{ $currentSeaType === 'all' ? 'selected' : '' }}>{{ __('messages.orders_sea_type_filter_all') }}</option>
                        <option value="FCL" {{ $currentSeaType === 'FCL' ? 'selected' : '' }}>{{ __('messages.orders_sea_type_filter_fcl') }}</option>
                        <option value="LCL" {{ $currentSeaType === 'LCL' ? 'selected' : '' }}>{{ __('messages.orders_sea_type_filter_lcl') }}</option>
                        <option value="Bulk" {{ $currentSeaType === 'Bulk' ? 'selected' : '' }}>{{ __('messages.orders_sea_type_filter_bulk') }}</option>
                        <option value="RoRo" {{ $currentSeaType === 'RoRo' ? 'selected' : '' }}>{{ __('messages.orders_sea_type_filter_roro') }}</option>
                    </select>
                </div>

                <select name="status" data-booking-status-filter class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent sm:w-56">
                    @foreach($filterStatuses as $value => $label)
                        <option value="{{ $value }}" {{ $currentStatus === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>

                <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-accent px-4 text-sm font-bold text-accent-foreground shadow-sm transition hover:opacity-90">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    <span>{{ __('messages.orders_filter_apply') }}</span>
                </button>
            </div>
        </div>
    </form>
</div>
