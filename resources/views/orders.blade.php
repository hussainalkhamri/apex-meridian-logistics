@extends('layouts.app')

@section('title', __('messages.orders_page_title'))

@section('head')
<meta name="description" content="{{ __('messages.orders_meta_description') }}">
<style>
    .orders-view-toolbar {
        display: none;
        align-items: center;
        justify-content: flex-end;
        gap: 0.35rem;
        margin-bottom: 1rem;
    }

    .orders-view-switch {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        border: 1px solid var(--border);
        border-radius: 0.65rem;
        background: var(--background);
        padding: 0.25rem;
    }

    .orders-view-button {
        display: inline-flex;
        height: 2.15rem;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        border-radius: 0.5rem;
        padding: 0 0.75rem;
        color: var(--muted-foreground);
        font-size: 0.75rem;
        font-weight: 900;
        transition: background-color 0.16s ease, color 0.16s ease, box-shadow 0.16s ease;
    }

    .orders-view-button.is-active {
        background: var(--accent);
        color: var(--accent-foreground);
        box-shadow: var(--shadow-card);
    }

    @media (min-width: 1280px) {
        .orders-view-toolbar {
            display: flex;
        }

        .orders-desktop-cards {
            display: grid;
            gap: 0.95rem;
            grid-template-columns: minmax(0, 1fr);
        }

        .orders-desktop-cards[data-order-view-mode="square"] {
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .orders-desktop-card {
            background: var(--card);
            box-shadow: var(--shadow-card);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .orders-desktop-card:hover {
            border-color: var(--accent);
            box-shadow: 0 18px 42px -24px var(--primary), var(--shadow-card);
            transform: translateY(-2px);
        }

        .orders-desktop-card-header {
            background: var(--secondary);
            background: color-mix(in srgb, var(--secondary) 36%, var(--card));
        }

        .orders-desktop-info-box {
            background: var(--secondary);
            background: color-mix(in srgb, var(--secondary) 30%, transparent);
        }

        .orders-desktop-subcard {
            background: var(--secondary);
            background: color-mix(in srgb, var(--secondary) 24%, transparent);
        }

        .orders-desktop-body {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: minmax(18rem, 1.35fr) minmax(16rem, 1fr) minmax(13rem, 0.8fr);
        }

        .orders-desktop-child-grid {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .orders-route-flow {
            display: grid;
            align-items: stretch;
            gap: 0.65rem;
            grid-template-columns: minmax(0, 1fr) 4.75rem minmax(0, 1fr);
        }

        .orders-route-port {
            display: flex;
            min-height: 4.25rem;
            flex-direction: column;
            justify-content: center;
            border: 1px solid var(--border);
            border-radius: 0.7rem;
            background: var(--card);
            padding: 0.65rem 0.75rem;
        }

        .orders-route-label {
            margin-bottom: 0.3rem;
            color: var(--muted-foreground);
            font-size: 0.68rem;
            font-weight: 900;
        }

        .orders-route-name {
            color: var(--foreground);
            font-size: 0.82rem;
            font-weight: 900;
            line-height: 1.55;
        }

        .orders-route-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            min-width: 0;
            color: var(--accent);
        }

        .orders-route-arrow::before,
        .orders-route-arrow::after {
            content: "";
            height: 1px;
            flex: 1;
            background: var(--border);
        }

        .orders-route-arrow-icon {
            display: inline-grid;
            width: 2rem;
            height: 2rem;
            place-items: center;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--card);
            box-shadow: var(--shadow-card);
        }

        .orders-desktop-cards[data-order-view-mode="rect"] .orders-desktop-card-header {
            padding: 0.95rem 1rem;
        }

        .orders-desktop-cards[data-order-view-mode="rect"] .orders-desktop-body {
            padding: 0.9rem 1rem;
        }

        .orders-desktop-cards[data-order-view-mode="rect"] .orders-desktop-info-box {
            padding: 0.75rem 0.85rem;
        }

        .orders-desktop-cards[data-order-view-mode="rect"] .orders-desktop-info-title {
            margin-bottom: 0.35rem;
        }

        .orders-desktop-cards[data-order-view-mode="rect"] .orders-desktop-card-title {
            margin-top: 0.45rem;
            font-size: 0.92rem;
            line-height: 1.45;
        }

        .orders-desktop-cards[data-order-view-mode="rect"] .orders-desktop-meta {
            margin-top: 0.35rem;
        }

        .orders-desktop-cards[data-order-view-mode="rect"] .orders-desktop-accent-bar {
            height: 2.4rem;
        }

        .orders-desktop-cards[data-order-view-mode="rect"] .orders-desktop-subcard {
            padding: 0.75rem 1rem;
        }

        .orders-desktop-cards[data-order-view-mode="rect"] .orders-route-flow {
            grid-template-columns: minmax(0, 1fr) 4rem minmax(0, 1fr);
        }

        .orders-desktop-cards[data-order-view-mode="rect"] .orders-route-port {
            min-height: 3.65rem;
            padding: 0.55rem 0.65rem;
        }

        .orders-desktop-cards[data-order-view-mode="square"] .orders-desktop-card {
            min-height: 34rem;
        }

        .orders-desktop-cards[data-order-view-mode="square"] .orders-desktop-card-header {
            padding: 1.25rem;
        }

        .orders-desktop-cards[data-order-view-mode="square"] .orders-desktop-body {
            grid-template-columns: minmax(0, 1fr);
            padding: 1.1rem;
        }

        .orders-desktop-cards[data-order-view-mode="square"] .orders-desktop-child-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .orders-desktop-cards[data-order-view-mode="square"] .orders-desktop-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .orders-desktop-cards[data-order-view-mode="square"] .orders-route-flow {
            grid-template-columns: minmax(0, 1fr) 3.75rem minmax(0, 1fr);
        }

        .orders-desktop-cards[data-order-view-mode="square"] .orders-route-port {
            min-height: 4rem;
            padding-inline: 0.6rem;
        }
    }
</style>
@endsection

@section('content')
@php
    $emptyOrdersMessage = ($showArchived ?? false)
        ? 'لا توجد طلبات مؤرشفة.'
        : 'لا توجد طلبات نشطة.';
@endphp
<div>
    {{-- Hero --}}
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs text-white">{{ __('messages.orders_hero_badge') }}</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">{{ __('messages.orders_hero_heading') }}</h1>
            <p class="mt-2 max-w-2xl text-white/80">{{ __('messages.orders_hero_description') }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('booking.create') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground hover:opacity-95">
                    <i data-lucide="plus" class="ml-2 h-4 w-4"></i> {{ __('messages.orders_new_order_button') }}
                </a>
                <button type="button" data-orders-customize-button="xlsx" class="inline-flex h-10 items-center justify-center rounded-lg border border-white/30 bg-white/10 px-5 text-sm font-bold text-white hover:bg-white/20">
                    <i data-lucide="sliders-horizontal" class="ml-2 h-4 w-4"></i> تخصيص
                </button>
                <button type="button" data-orders-export-button="csv" class="inline-flex h-10 items-center justify-center rounded-lg border border-white/30 bg-white/10 px-5 text-sm font-bold text-white hover:bg-white/20">
                    <i data-lucide="download" class="ml-2 h-4 w-4"></i> {{ __('messages.orders_export_csv') }}
                </button>
                <button type="button" data-orders-export-button="xlsx" class="inline-flex h-10 items-center justify-center rounded-lg border border-white/30 bg-white/10 px-5 text-sm font-bold text-white hover:bg-white/20">
                    <i data-lucide="file-spreadsheet" class="ml-2 h-4 w-4"></i> {{ __('messages.orders_export_excel') }}
                </button>
            </div>
        </div>
    </section>

    <div data-orders-export-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-background/80 backdrop-blur-sm">
        <div class="w-full max-w-2xl rounded-2xl border border-border bg-card p-5 shadow-card">
            <div class="flex items-center justify-between gap-4 border-b border-border pb-3">
                <div>
                    <h2 class="text-xl font-extrabold text-foreground">تصدير الطلبات</h2>
                    <p class="text-sm text-muted-foreground">اختر الأعمدة التي تريد تنزيلها، وسيتم حفظ اختيارك في هذا المتصفح.</p>
                </div>
                <button type="button" data-orders-export-close class="rounded-lg border border-border bg-background px-2 py-1 text-sm text-muted-foreground hover:bg-secondary/50">إغلاق</button>
            </div>

            <div data-orders-export-columns class="mt-4 grid max-h-[60vh] grid-cols-1 gap-2 overflow-y-auto rounded-xl border border-border bg-background p-3 sm:grid-cols-2"></div>

            <div class="mt-5 flex items-center justify-end gap-2 border-t border-border pt-4">
                <button type="button" data-orders-export-close class="inline-flex h-10 items-center justify-center rounded-lg border border-border bg-card px-4 text-sm font-bold text-foreground hover:bg-secondary/40">إلغاء</button>
                <button type="button" data-orders-export-confirm class="inline-flex h-10 items-center justify-center rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground hover:opacity-95">حفظ</button>
            </div>
        </div>
    </div>

    <section class="container mx-auto px-4 py-10">
        {{-- Stats Cards --}}
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach([
                ['icon' => 'package', 'label' => __('messages.orders_stats_total_orders'), 'value' => $totalCount ?? 0, 'color' => 'text-accent bg-accent/10'],
                ['icon' => 'clock', 'label' => __('messages.orders_stats_pending_pricing'), 'value' => $pendingPricingCount ?? 0, 'color' => 'text-warning bg-warning/10'],
                ['icon' => 'alert-circle', 'label' => __('messages.orders_stats_pending_sales'), 'value' => $pendingSalesCount ?? 0, 'color' => 'text-accent bg-accent/10'],
                ['icon' => 'check-circle-2', 'label' => __('messages.orders_stats_approved'), 'value' => $approvedCount ?? 0, 'color' => 'text-success bg-success/10'],
            ] as $stat)
                <div class="rounded-xl border border-border/60 bg-card p-5 shadow-card flex items-center gap-4">
                    <span class="grid h-12 w-12 place-items-center rounded-lg {{ $stat['color'] }}">
                        <i data-lucide="{{ $stat['icon'] }}" class="h-6 w-6"></i>
                    </span>
                    <div>
                        <div class="text-2xl font-extrabold">{{ $stat['value'] }}</div>
                        <div class="text-sm text-muted-foreground">{{ $stat['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

     

        {{-- Filters + Table Card --}}
        <div class="mt-6 rounded-xl border border-border/60 bg-card shadow-card overflow-hidden">
            <div class="p-6">
                {{-- Filter Row --}}
                <form id="orders-filter-form" action="{{ route('orders.index') }}" method="GET" class="mb-4 flex flex-col gap-3 md:flex-row md:flex-wrap">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        <input type="search" name="q" value="{{ $q ?? '' }}" data-orders-search-input data-last-submitted="{{ $q ?? '' }}" autocomplete="off" placeholder="{{ __('messages.orders_filter_search_placeholder') }}" class="w-full h-10 border border-border bg-background pr-9 pl-4 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    @php
                        $currTransportMode = $transportMode ?? 'all';
                        $currSeaType = $seaType ?? 'all';
                    @endphp
                    <select name="transport_mode" data-orders-transport-filter class="h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent w-full md:w-44">
                        <option value="all" {{ $currTransportMode === 'all' ? 'selected' : '' }}>{{ __('messages.orders_transport_filter_all') }}</option>
                        <option value="sea" {{ $currTransportMode === 'sea' ? 'selected' : '' }}>{{ __('messages.orders_transport_filter_sea') }}</option>
                        <option value="air" {{ $currTransportMode === 'air' ? 'selected' : '' }}>{{ __('messages.orders_transport_filter_air') }}</option>
                        <option value="land" {{ $currTransportMode === 'land' ? 'selected' : '' }}>{{ __('messages.orders_transport_filter_land') }}</option>
                    </select>
                    <div data-orders-sea-type-wrapper class="w-full md:w-52 {{ $currTransportMode === 'sea' ? '' : 'hidden' }}">
                        <select name="sea_type" data-orders-sea-type-filter @disabled($currTransportMode !== 'sea') class="h-10 w-full px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                            <option value="all" {{ $currSeaType === 'all' ? 'selected' : '' }}>{{ __('messages.orders_sea_type_filter_all') }}</option>
                            <option value="FCL" {{ $currSeaType === 'FCL' ? 'selected' : '' }}>{{ __('messages.orders_sea_type_filter_fcl') }}</option>
                            <option value="LCL" {{ $currSeaType === 'LCL' ? 'selected' : '' }}>{{ __('messages.orders_sea_type_filter_lcl') }}</option>
                            <option value="Bulk" {{ $currSeaType === 'Bulk' ? 'selected' : '' }}>{{ __('messages.orders_sea_type_filter_bulk') }}</option>
                            <option value="RoRo" {{ $currSeaType === 'RoRo' ? 'selected' : '' }}>{{ __('messages.orders_sea_type_filter_roro') }}</option>
                        </select>
                    </div>
                    <select name="status" onchange="this.form.submit()" class="h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent w-full md:w-56">
                        @php $currStatus = $status ?? 'all'; @endphp
                        <option value="all" {{ $currStatus === 'all' ? 'selected' : '' }}>{{ __('messages.orders_status_all') }}</option>
                        <option value="new" {{ $currStatus === 'new' ? 'selected' : '' }}>{{ __('messages.orders_status_new') }}</option>
                        <option value="needs_pricing" {{ $currStatus === 'needs_pricing' ? 'selected' : '' }}>{{ __('messages.orders_status_needs_pricing') }}</option>
                        <option value="priced" {{ $currStatus === 'priced' ? 'selected' : '' }}>{{ __('messages.orders_status_priced') }}</option>
                        <option value="sent" {{ $currStatus === 'sent' ? 'selected' : '' }}>{{ __('messages.orders_status_sent') }}</option>
                        <option value="approved" {{ $currStatus === 'approved' ? 'selected' : '' }}>{{ __('messages.orders_status_approved') }}</option>
                        <option value="booked" {{ $currStatus === 'booked' ? 'selected' : '' }}>{{ __('messages.orders_status_booked') }}</option>
                        <option value="rejected" {{ $currStatus === 'rejected' ? 'selected' : '' }}>{{ __('messages.orders_status_rejected') }}</option>
                    </select>
                    <select name="archived" onchange="this.form.submit()" class="h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent w-full md:w-44">
                        <option value="0" {{ !($showArchived ?? false) ? 'selected' : '' }}>الطلبات النشطة</option>
                        <option value="1" {{ ($showArchived ?? false) ? 'selected' : '' }}>الأرشيف</option>
                    </select>
                </form>

                <div class="orders-view-toolbar">
                    <div class="orders-view-switch" role="group" aria-label="تغيير شكل عرض الطلبات">
                        <button type="button" class="orders-view-button" data-orders-view-button="rect" title="عرض مستطيل">
                            <i data-lucide="list" class="h-4 w-4"></i>
                            <span>مستطيل</span>
                        </button>
                        <button type="button" class="orders-view-button" data-orders-view-button="square" title="عرض مربع">
                            <i data-lucide="layout-grid" class="h-4 w-4"></i>
                            <span>مربع</span>
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="grid gap-3 xl:hidden">
                    @forelse($bookings as $b)
                        @php
                            $statusColors = [
                                'new'      => 'bg-secondary text-foreground border-border',
                                'needs_pricing' => 'bg-warning/20 text-warning-foreground border-warning/30',
                                'priced'   => 'bg-primary/15 text-primary border-primary/20',
                                'sent'     => 'bg-accent/15 text-accent-foreground border-accent/20',
                                'approved' => 'bg-success/15 text-success border-success/25',
                                'booked'   => 'bg-blue-500/15 text-blue-700 border-blue-500/20',
                                'rejected' => 'bg-destructive/15 text-destructive border-destructive/20',
                            ];
                            $statusLabels = [
                                'new'      => __('messages.orders_status_new'),
                                'needs_pricing' => __('messages.orders_status_needs_pricing'),
                                'priced'   => __('messages.orders_status_priced'),
                                'sent'     => __('messages.orders_status_sent'),
                                'approved' => __('messages.orders_status_approved'),
                                'booked'   => __('messages.orders_status_booked'),
                                'rejected' => __('messages.orders_status_rejected'),
                            ];
                            $requiresRepricing = $b->status === 'needs_pricing';
                        @endphp
                        <article class="overflow-hidden rounded-xl border border-border bg-background p-4 shadow-sm">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="font-mono text-xs font-black text-accent" dir="ltr">{{ $b->id }}</div>
                                    <div class="mt-1 max-w-full break-words text-sm font-black leading-5 text-foreground [overflow-wrap:anywhere]">{{ $b->customer_company }}</div>
                                    <div class="mt-2 grid gap-1 text-xs leading-5 text-muted-foreground">
                                        <div class="min-w-0">
                                            <span class="font-bold text-foreground/70">من: </span>
                                            <span class="break-words [overflow-wrap:anywhere]">{{ $b->pol }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="font-bold text-foreground/70">إلى: </span>
                                            <span class="break-words [overflow-wrap:anywhere]">{{ $b->pod }}</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="inline-flex w-fit shrink-0 items-center rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $statusColors[$b->status] ?? '' }}">
                                    {{ $statusLabels[$b->status] ?? $b->status }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                                <div class="min-w-0 overflow-hidden rounded-lg bg-secondary/35 p-2">
                                    <div class="text-muted-foreground">{{ __('messages.orders_table_type') }}</div>
                                    <div class="mt-1 break-words font-black leading-5 [overflow-wrap:anywhere]">{{ $b->shipmentDisplay() }}</div>
                                </div>
                                <div class="rounded-lg bg-secondary/35 p-2">
                                    <div class="text-muted-foreground">{{ __('messages.orders_table_total') }}</div>
                                    <div class="mt-1 font-black {{ $requiresRepricing ? 'text-warning-foreground' : 'text-primary' }}">
                                        {{ $requiresRepricing ? __('messages.orders_status_needs_pricing') : ($b->ocean_freight ? '$' . number_format($b->pricingTotal()) : '—') }}
                                    </div>
                                </div>
                                <div class="rounded-lg bg-secondary/35 p-2">
                                    <div class="text-muted-foreground">{{ __('messages.orders_table_date') }}</div>
                                    <div class="mt-1 font-black">{{ $b->created_at->toDateString() }}</div>
                                </div>
                                <div class="rounded-lg bg-secondary/35 p-2">
                                    <div class="text-muted-foreground">{{ __('messages.orders_table_files') }}</div>
                                    <div class="mt-1 font-black">{{ ($b->notes || $b->attachments) ? __('messages.orders_notes_files') : '—' }}</div>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button onclick="openDetailModal('{{ $b->id }}')" class="inline-flex h-9 w-10 items-center justify-center rounded-lg border border-border hover:bg-secondary transition-all" title="{{ __('messages.bookings_view') }}">
                                    <i data-lucide="eye" class="h-4 w-4 text-muted-foreground"></i>
                                </button>
                                @if(in_array($b->status, ['new', 'needs_pricing'], true) && auth()->user()?->can('manage_pricing'))
                                    <a href="{{ route('pricing.index', ['booking' => $b->id]) }}" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-warning/15 px-3 text-xs font-black text-warning-foreground" title="التسعير">
                                        <i data-lucide="calculator" class="h-3.5 w-3.5"></i> التسعير
                                    </a>
                                @elseif(in_array($b->status, ['priced', 'sent'], true) && auth()->user()?->can('manage_sales'))
                                    <a href="{{ route('sales.index', ['booking' => $b->id]) }}" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-accent/15 px-3 text-xs font-black text-accent-foreground" title="المبيعات">
                                        <i data-lucide="send" class="h-3.5 w-3.5"></i> المبيعات
                                    </a>
                                @elseif($b->status === 'approved' && auth()->user()?->can('manage_carriers'))
                                    <a href="{{ route('bookings.show', $b->id) }}" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-success/15 px-3 text-xs font-black text-success" title="الحجز">
                                        <i data-lucide="ship" class="h-3.5 w-3.5"></i> الحجز
                                    </a>
                                @endif
                                <a href="{{ route('orders.edit', $b->id) }}" class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-lg border border-border px-3 text-xs font-black hover:bg-accent/10 hover:text-accent transition-all">
                                    <i data-lucide="edit" class="h-3.5 w-3.5 shrink-0"></i>
                                    <span class="break-words [overflow-wrap:anywhere]">{{ __('messages.bookings_edit') }}</span>
                                </a>
                                <a href="{{ route('booking.create', ['parent_id' => $b->id]) }}" class="inline-flex h-9 min-w-0 flex-1 items-center justify-center gap-1.5 rounded-lg bg-gradient-accent px-2 text-xs font-black text-accent-foreground sm:gap-2 sm:px-3" title="{{ __('messages.orders_add_suborder') }}" aria-label="{{ __('messages.orders_add_suborder') }}">
                                    <i data-lucide="plus" class="h-3.5 w-3.5 shrink-0"></i>
                                    <span class="truncate sm:hidden">{{ __('messages.orders_add_suborder_short') }}</span>
                                    <span class="hidden truncate sm:inline">{{ __('messages.orders_add_suborder') }}</span>
                                </a>
                            </div>

                            <div class="mt-4 rounded-lg border border-dashed border-border bg-card p-3">
                                <div class="mb-2 text-xs font-bold text-muted-foreground">{{ __('messages.orders_suborders_heading') }}</div>
                                @if($b->children && $b->children->isNotEmpty())
                                    <div class="space-y-2">
                                        @foreach($b->children as $child)
                                            @php
                                                $childRequiresRepricing = $child->status === 'needs_pricing';
                                            @endphp
                                            <div class="rounded-lg border border-border/60 bg-background p-3 text-xs">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                                                        <div class="font-mono font-bold text-accent" dir="ltr">{{ $child->id }}</div>
                                                        @if($childRequiresRepricing)
                                                            <span data-repricing-badge-for="{{ $child->id }}" class="inline-flex items-center gap-1 rounded-full border border-warning/30 bg-warning/15 px-2 py-0.5 text-[10px] font-black text-warning-foreground">
                                                                <i data-lucide="refresh-cw" class="h-3 w-3"></i>
                                                                {{ __('messages.orders_status_needs_pricing') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="flex shrink-0 items-center gap-1">
                                                        <button type="button" onclick="openDetailModal('{{ $child->id }}')" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-border/60 hover:bg-accent/10" title="{{ __('messages.bookings_view') }}">
                                                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                                        </button>
                                                        <a href="{{ route('orders.edit', $child->id) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-border/60 hover:bg-accent/10" title="{{ __('messages.orders_edit_suborder_title') }}">
                                                            <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                                <div class="mt-2 break-words font-semibold leading-5 text-foreground [overflow-wrap:anywhere]">{{ $child->shipmentDisplay() }}</div>
                                                <div class="mt-1 grid gap-0.5 text-muted-foreground">
                                                    <div class="break-words [overflow-wrap:anywhere]">{{ $child->pol }}</div>
                                                    <div class="break-words [overflow-wrap:anywhere]">إلى {{ $child->pod }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="rounded-lg border border-dashed border-border/60 px-3 py-2 text-xs text-muted-foreground">{{ __('messages.orders_no_suborders_yet') }}</div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">{{ $emptyOrdersMessage }}</div>
                    @endforelse
                </div>

                <div class="hidden xl:block">
                    <div id="orders-desktop-cards" class="orders-desktop-cards" data-order-view-mode="rect">
                    @forelse($bookings as $b)
                        @php
                            $statusColors = [
                                'new'      => 'bg-secondary text-foreground border-border',
                                'needs_pricing' => 'bg-warning/20 text-warning-foreground border-warning/30',
                                'priced'   => 'bg-primary/15 text-primary border-primary/20',
                                'sent'     => 'bg-accent/15 text-accent-foreground border-accent/20',
                                'approved' => 'bg-success/15 text-success border-success/25',
                                'booked'   => 'bg-blue-500/15 text-blue-700 border-blue-500/20',
                                'rejected' => 'bg-destructive/15 text-destructive border-destructive/20',
                            ];
                            $statusLabels = [
                                'new'      => __('messages.orders_status_new'),
                                'needs_pricing' => __('messages.orders_status_needs_pricing'),
                                'priced'   => __('messages.orders_status_priced'),
                                'sent'     => __('messages.orders_status_sent'),
                                'approved' => __('messages.orders_status_approved'),
                                'booked'   => __('messages.orders_status_booked'),
                                'rejected' => __('messages.orders_status_rejected'),
                            ];
                            $requiresRepricing = $b->status === 'needs_pricing';
                        @endphp
                        <article data-booking-id="{{ $b->id }}" class="orders-desktop-card overflow-hidden rounded-xl border border-border">
                            <div class="orders-desktop-card-header border-b border-border/60 p-5">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="flex min-w-0 flex-1 gap-4">
                                        <span class="orders-desktop-accent-bar mt-1 h-14 w-1.5 shrink-0 rounded-full bg-accent shadow-glow"></span>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="inline-flex items-center rounded-lg bg-accent/10 px-2.5 py-1 font-mono text-xs font-black text-accent" dir="ltr">{{ $b->id }}</span>
                                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $statusColors[$b->status] ?? '' }}">
                                                    {{ $statusLabels[$b->status] ?? $b->status }}
                                                </span>
                                                @if(data_get($b->route_details, 'mode'))
                                                    <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-1 text-[11px] font-bold text-muted-foreground">
                                                        {{ data_get($b->route_details, 'mode') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <h3 class="orders-desktop-card-title mt-3 break-words text-base font-black leading-7 text-foreground [overflow-wrap:anywhere]">{{ $b->customer_company }}</h3>
                                            <div class="orders-desktop-meta mt-2 flex flex-wrap gap-2 text-xs text-muted-foreground">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <i data-lucide="calendar-days" class="h-3.5 w-3.5"></i>
                                                    {{ $b->created_at->toDateString() }}
                                                </span>
                                                @if($b->notes || $b->attachments)
                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-2 py-0.5 font-bold text-accent">
                                                        <i data-lucide="paperclip" class="h-3.5 w-3.5"></i>
                                                        {{ __('messages.orders_notes_files') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="orders-desktop-actions flex max-w-xl flex-wrap items-center justify-end gap-2">
                                        <button onclick="openDetailModal('{{ $b->id }}')" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card hover:bg-secondary transition-all" title="{{ __('messages.bookings_view') }}">
                                            <i data-lucide="eye" class="h-4 w-4 text-muted-foreground"></i>
                                        </button>
                                        @if(in_array($b->status, ['new', 'needs_pricing'], true) && auth()->user()?->can('manage_pricing'))
                                            <a href="{{ route('pricing.index', ['booking' => $b->id]) }}" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-warning/15 px-3 text-xs font-black text-warning-foreground" title="التسعير">
                                                <i data-lucide="calculator" class="h-3.5 w-3.5"></i> التسعير
                                            </a>
                                        @elseif(in_array($b->status, ['priced', 'sent'], true) && auth()->user()?->can('manage_sales'))
                                            <a href="{{ route('sales.index', ['booking' => $b->id]) }}" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-accent/15 px-3 text-xs font-black text-accent-foreground" title="المبيعات">
                                                <i data-lucide="send" class="h-3.5 w-3.5"></i> المبيعات
                                            </a>
                                        @elseif($b->status === 'approved' && auth()->user()?->can('manage_carriers'))
                                            <a href="{{ route('bookings.show', $b->id) }}" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-success/15 px-3 text-xs font-black text-success" title="الحجز">
                                                <i data-lucide="ship" class="h-3.5 w-3.5"></i> الحجز
                                            </a>
                                        @endif
                                        <a href="{{ route('orders.edit', $b->id) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card hover:bg-accent/20 hover:text-accent-foreground transition-all" title="{{ __('messages.bookings_edit') }}">
                                            <i data-lucide="edit" class="h-4 w-4 text-muted-foreground"></i>
                                        </a>
                                        @if(auth()->user()?->isManager())
                                            @if($showArchived ?? false)
                                                <form action="{{ route('orders.restore', $b->id) }}" method="POST" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card hover:bg-success/10 hover:text-success transition-all" title="استرجاع الطلب" aria-label="استرجاع الطلب">
                                                        <i data-lucide="rotate-ccw" class="h-4 w-4 text-muted-foreground"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('orders.archive', $b->id) }}" method="POST" class="inline-block" onsubmit="return confirm('هل تريد أرشفة هذا الطلب؟');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card hover:bg-secondary transition-all" title="أرشفة الطلب" aria-label="أرشفة الطلب">
                                                        <i data-lucide="archive" class="h-4 w-4 text-muted-foreground"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                        @if(auth()->user()?->isSuperAdmin())
                                            <form action="{{ route('orders.destroy', $b->id) }}" method="POST" class="inline-block" onsubmit="return confirm(@js(__('messages.orders_confirm_delete_order')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card hover:bg-destructive/10 hover:text-destructive transition-all" title="{{ __('messages.orders_delete_order') }}" aria-label="{{ __('messages.orders_delete_order') }}">
                                                    <i data-lucide="trash-2" class="h-4 w-4 text-muted-foreground"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="orders-desktop-body grid gap-4 p-5 lg:grid-cols-[1.1fr_1fr_0.85fr]">
                                <div class="orders-desktop-info-box rounded-lg border border-border/60 p-4">
                                    <div class="orders-desktop-info-title mb-3 flex items-center gap-2 text-xs font-black text-muted-foreground">
                                        <i data-lucide="route" class="h-4 w-4 text-accent"></i>
                                        {{ __('messages.orders_table_route') }}
                                    </div>
                                    <div class="orders-route-flow">
                                        <div class="orders-route-port">
                                            <div class="orders-route-label">من</div>
                                            <div class="orders-route-name break-words [overflow-wrap:anywhere]">{{ $b->pol ?: '—' }}</div>
                                        </div>
                                        <div class="orders-route-arrow" aria-hidden="true">
                                            <span class="orders-route-arrow-icon">
                                                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                            </span>
                                        </div>
                                        <div class="orders-route-port">
                                            <div class="orders-route-label">إلى</div>
                                            <div class="orders-route-name break-words [overflow-wrap:anywhere]">{{ $b->pod ?: '—' }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="orders-desktop-info-box rounded-lg border border-border/60 p-4">
                                    <div class="orders-desktop-info-title mb-3 flex items-center gap-2 text-xs font-black text-muted-foreground">
                                        <i data-lucide="package" class="h-4 w-4 text-accent"></i>
                                        {{ __('messages.orders_table_type') }}
                                    </div>
                                    <div class="break-words text-sm font-black leading-6 text-foreground [overflow-wrap:anywhere]">{{ $b->shipmentDisplay() }}</div>
                                </div>

                                <div class="grid gap-3">
                                    <div class="orders-desktop-info-box rounded-lg border border-border/60 p-4">
                                        <div class="text-xs font-black text-muted-foreground">{{ __('messages.orders_table_total') }}</div>
                                        <div class="mt-2 text-lg font-black {{ $requiresRepricing ? 'text-warning-foreground' : 'text-primary' }}">
                                            {{ $requiresRepricing ? __('messages.orders_status_needs_pricing') : ($b->ocean_freight ? '$' . number_format($b->pricingTotal()) : '—') }}
                                        </div>
                                    </div>
                                    <div class="orders-desktop-info-box rounded-lg border border-border/60 p-4">
                                        <div class="text-xs font-black text-muted-foreground">{{ __('messages.orders_table_files') }}</div>
                                        <div class="mt-2 text-sm font-black">{{ ($b->notes || $b->attachments) ? __('messages.orders_notes_files') : '—' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="orders-desktop-subcard border-t border-border/60 p-4">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 text-xs font-black text-muted-foreground">
                                        <i data-lucide="git-branch" class="h-4 w-4 text-accent"></i>
                                        {{ __('messages.orders_suborders_heading') }}
                                    </div>
                                    <a href="{{ route('booking.create', ['parent_id' => $b->id]) }}" class="inline-flex h-8 items-center justify-center rounded-lg bg-gradient-accent px-3 text-xs font-bold text-accent-foreground">
                                        <i data-lucide="plus" class="ml-1.5 h-3.5 w-3.5"></i> {{ __('messages.orders_add_suborder') }}
                                    </a>
                                </div>
                                @if($b->children && $b->children->isNotEmpty())
                                    <div class="orders-desktop-child-grid grid gap-3 md:grid-cols-2">
                                        @foreach($b->children as $child)
                                            @php
                                                $childRequiresRepricing = $child->status === 'needs_pricing';
                                            @endphp
                                            <div class="rounded-lg border border-border/60 bg-card p-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                                                        <div class="text-[11px] font-bold text-accent" dir="ltr">{{ $child->id }}</div>
                                                        @if($childRequiresRepricing)
                                                            <span data-repricing-badge-for="{{ $child->id }}" class="inline-flex items-center gap-1 rounded-full border border-warning/30 bg-warning/15 px-2 py-0.5 text-[10px] font-black text-warning-foreground">
                                                                <i data-lucide="refresh-cw" class="h-3 w-3"></i>
                                                                {{ __('messages.orders_status_needs_pricing') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <button type="button" onclick="openDetailModal('{{ $child->id }}')" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-border/60 bg-background hover:bg-accent/10" title="{{ __('messages.bookings_view') }}">
                                                            <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                                        </button>
                                                        <a href="{{ route('orders.edit', $child->id) }}" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-border/60 bg-background hover:bg-accent/10" title="{{ __('messages.orders_edit_suborder_title') }}">
                                                            <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                                                        </a>
                                                        @if(auth()->user()?->isManager())
                                                            @if($showArchived ?? false)
                                                                <form action="{{ route('orders.restore', $child->id) }}" method="POST" class="inline-block">
                                                                    @csrf
                                                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-border/60 bg-background hover:bg-success/10" title="استرجاع الطلب الفرعي">
                                                                        <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <form action="{{ route('orders.archive', $child->id) }}" method="POST" class="inline-block" onsubmit="return confirm('هل تريد أرشفة هذا الطلب الفرعي؟');">
                                                                    @csrf
                                                                    <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-border/60 bg-background hover:bg-secondary" title="أرشفة الطلب الفرعي">
                                                                        <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                                                                    </button>
                                                                </form>
                                                            @endif
                                                        @endif
                                                        @if(auth()->user()?->isSuperAdmin())
                                                            <form action="{{ route('orders.destroy', $child->id) }}" method="POST" class="inline-block" onsubmit="return confirm(@js(__('messages.orders_confirm_delete_suborder')))">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-border/60 bg-background hover:bg-destructive/10" title="{{ __('messages.orders_delete_suborder') }}">
                                                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        <span class="inline-flex items-center rounded-full bg-secondary px-2 py-0.5 text-[10px] font-semibold">{{ __('messages.orders_suborder_label') }}</span>
                                                    </div>
                                                </div>
                                                <div class="mt-2 text-xs text-muted-foreground">
                                                    <div class="break-words font-semibold leading-5 text-foreground [overflow-wrap:anywhere]">{{ $child->shipmentDisplay() }}</div>
                                                    <div class="mt-1 grid gap-0.5">
                                                        <span class="break-words [overflow-wrap:anywhere]">{{ $child->pol }}</span>
                                                        <span class="break-words [overflow-wrap:anywhere]">→ {{ $child->pod }}</span>
                                                    </div>
                                                    <div class="mt-1 line-clamp-2">{{ $child->goods_description }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="rounded-lg border border-dashed border-border/60 bg-card/60 px-3 py-2 text-xs text-muted-foreground">{{ __('messages.orders_no_suborders_yet') }}</div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">{{ $emptyOrdersMessage }}</div>
                    @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Carrier Form Modal Dialog -->
<div id="carrier-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-xl bg-card rounded-2xl border border-border shadow-elegant overflow-hidden max-h-[90vh] flex flex-col" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="flex items-center justify-between border-b p-4 bg-secondary/20">
            <h3 class="font-extrabold text-base text-primary">{{ __('messages.bookings_update_vessel_docs') }}</h3>
            <button onclick="closeCarrierFormModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center transition-all">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        
        <!-- Content Form -->
        <form id="carrier-form" action="" method="POST" class="p-6 space-y-4 text-xs overflow-y-auto">
            @csrf
            <div class="rounded-lg bg-secondary/40 p-3 border mb-1">
                <strong>{{ __('messages.bookings_pol_label') }}</strong> <span id="car-pol">POL</span> | 
                <strong>{{ __('messages.bookings_pod_label') }}</strong> <span id="car-pod">POD</span>
            </div>
            
            @include('partials.carrier-vessel-picker')
            
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1">
                    <label for="voyage" class="font-bold text-foreground">{{ __('messages.bookings_voyage_label') }}</label>
                    <input type="text" name="voyage" id="voyage" required placeholder="{{ __('messages.bookings_voyage_example') }}" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="bookingRef" class="font-bold text-foreground">{{ __('messages.bookings_booking_ref_label') }}</label>
                    <input type="text" name="bookingRef" id="bookingRef" required placeholder="{{ __('messages.bookings_booking_ref_example') }}" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="etd" class="font-bold text-foreground">{{ __('messages.bookings_etd_label') }}</label>
                    <input type="date" name="etd" id="etd" required class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="eta" class="font-bold text-foreground">{{ __('messages.bookings_eta_label') }}</label>
                    <input type="date" name="eta" id="eta" required class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
            </div>

            <div class="space-y-1">
                <label for="bookedBy" class="font-bold text-foreground">{{ __('messages.bookings_booked_by_label') }}</label>
                <input type="text" name="bookedBy" id="bookedBy" value="{{ __('messages.bookings_booked_by_default') }}" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>

            <div class="border-t pt-3">
                <h4 class="font-bold text-primary mb-2">{{ __('messages.bookings_mbl_details') }}</h4>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1 sm:col-span-2">
                    <label for="shipper" class="font-bold text-foreground">{{ __('messages.bookings_shipper_label') }}</label>
                    <textarea name="shipper" id="shipper" rows="2" class="w-full p-2 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label for="consignee" class="font-bold text-foreground">{{ __('messages.bookings_consignee_label') }}</label>
                    <textarea name="consignee" id="consignee" rows="2" class="w-full p-2 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label for="notify_party" class="font-bold text-foreground">{{ __('messages.bookings_notify_party_label') }}</label>
                    <textarea name="notify_party" id="notify_party" rows="2" class="w-full p-2 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
                <div class="space-y-1">
                    <label for="bill_type" class="font-bold text-foreground">{{ __('messages.bookings_bill_type_label') }}</label>
                    <select name="bill_type" id="bill_type" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="TELEX RELEASE">TELEX RELEASE</option>
                        <option value="ORIGINAL B/L">ORIGINAL B/L</option>
                        <option value="WAYBILL">WAYBILL</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label for="type_of_move" class="font-bold text-foreground">{{ __('messages.bookings_type_of_move_label') }}</label>
                    <input type="text" name="type_of_move" id="type_of_move" value="CY-CY" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="job_no" class="font-bold text-foreground">{{ __('messages.bookings_job_no_label') }}</label>
                    <input type="text" name="job_no" id="job_no" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="overseas_agent" class="font-bold text-foreground">{{ __('messages.bookings_overseas_agent_label') }}</label>
                    <input type="text" name="overseas_agent" id="overseas_agent" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="place_of_delivery" class="font-bold text-foreground">{{ __('messages.bookings_place_of_delivery_label') }}</label>
                    <input type="text" name="place_of_delivery" id="place_of_delivery" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="quantity_packages" class="font-bold text-foreground">{{ __('messages.bookings_quantity_packages_label') }}</label>
                    <input type="text" name="quantity_packages" id="quantity_packages" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="measurement_cbm" class="font-bold text-foreground">{{ __('messages.bookings_measurement_cbm_label') }}</label>
                    <input type="number" step="0.01" name="measurement_cbm" id="measurement_cbm" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label for="container_seal_no" class="font-bold text-foreground">{{ __('messages.bookings_container_seal_label') }}</label>
                    <textarea name="container_seal_no" id="container_seal_no" rows="2" placeholder="{{ __('messages.bookings_container_seal_example') }}" class="w-full p-2 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label for="custom_notes" class="font-bold text-foreground">{{ __('messages.bookings_custom_notes_label') }}</label>
                    <textarea name="custom_notes" id="custom_notes" rows="2" class="w-full p-2 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t">
                <button type="button" onclick="closeCarrierFormModal()" class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">{{ __('messages.bookings_cancel') }}</button>
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-gradient-accent px-5 text-xs font-bold text-accent-foreground shadow hover:opacity-95">{{ __('messages.bookings_confirm_save_shipment') }}</button>
            </div>
        </form>
    </div>
</div>

@include('partials.booking-detail-modal')
@endsection

@section('scripts')
<script>
    window.bookingDetailEndpoint = function(id) {
        return `/orders/${encodeURIComponent(id)}?hide_stale_pricing=1`;
    };

    const ordersFilterForm = document.getElementById('orders-filter-form');
    const ordersSearchInput = document.querySelector('[data-orders-search-input]');
    let ordersSearchTimer = null;

    function submitOrdersFilterForm() {
        if (ordersFilterForm) {
            ordersFilterForm.submit();
        }
    }

    if (ordersSearchInput) {
        ordersSearchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                clearTimeout(ordersSearchTimer);
                ordersSearchInput.dataset.lastSubmitted = ordersSearchInput.value.trim();
                submitOrdersFilterForm();
            }
        });

        ordersSearchInput.addEventListener('input', () => {
            clearTimeout(ordersSearchTimer);
            ordersSearchTimer = setTimeout(() => {
                const currentSearch = ordersSearchInput.value.trim();

                if (currentSearch === ordersSearchInput.dataset.lastSubmitted) {
                    return;
                }

                ordersSearchInput.dataset.lastSubmitted = currentSearch;
                submitOrdersFilterForm();
            }, 550);
        });
    }

    function syncOrdersTransportFilters(shouldSubmit = false) {
        const form = document.getElementById('orders-filter-form');
        const transportFilter = document.querySelector('[data-orders-transport-filter]');
        const seaWrapper = document.querySelector('[data-orders-sea-type-wrapper]');
        const seaFilter = document.querySelector('[data-orders-sea-type-filter]');
        const isSea = transportFilter?.value === 'sea';

        if (seaWrapper) {
            seaWrapper.classList.toggle('hidden', !isSea);
        }

        if (seaFilter) {
            seaFilter.disabled = !isSea;
            if (!isSea) {
                seaFilter.value = 'all';
            }
        }

        if (shouldSubmit && form) {
            form.submit();
        }
    }

    document.querySelector('[data-orders-transport-filter]')?.addEventListener('change', () => syncOrdersTransportFilters(true));
    document.querySelector('[data-orders-sea-type-filter]')?.addEventListener('change', () => document.getElementById('orders-filter-form')?.submit());
    syncOrdersTransportFilters(false);

    const ORDERS_VIEW_MODE_KEY = 'shipmate.orders.viewMode';

    function setOrdersDesktopViewMode(mode) {
        const normalizedMode = mode === 'square' ? 'square' : 'rect';
        const cards = document.getElementById('orders-desktop-cards');

        if (cards) {
            cards.dataset.orderViewMode = normalizedMode;
        }

        document.querySelectorAll('[data-orders-view-button]').forEach(button => {
            const active = button.dataset.ordersViewButton === normalizedMode;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        try {
            localStorage.setItem(ORDERS_VIEW_MODE_KEY, normalizedMode);
        } catch (error) {
            // Ignore storage restrictions.
        }
    }

    function initOrdersDesktopViewMode() {
        let savedMode = 'rect';

        try {
            savedMode = localStorage.getItem(ORDERS_VIEW_MODE_KEY) || 'rect';
        } catch (error) {
            savedMode = 'rect';
        }

        setOrdersDesktopViewMode(savedMode);

        document.querySelectorAll('[data-orders-view-button]').forEach(button => {
            button.addEventListener('click', () => setOrdersDesktopViewMode(button.dataset.ordersViewButton));
        });
    }

    initOrdersDesktopViewMode();

    const carrierModal = document.getElementById('carrier-modal');
    const carrierForm = document.getElementById('carrier-form');

    @include('partials.carrier-vessel-picker-script')

    function openCarrierFormModal(id, pol, pod, currentData = null) {
        if (!carrierModal) return;
        carrierModal.classList.remove('hidden');
        document.getElementById('car-pol').innerText = pol;
        document.getElementById('car-pod').innerText = pod;

        // Set action URL
        carrierForm.action = `/bookings/${id}/confirm`;

        // Get inputs
        const etdInput = document.getElementById('etd');
        const etaInput = document.getElementById('eta');
        const vesselInput = document.getElementById('vessel');
        const voyageInput = document.getElementById('voyage');
        const bookingRefInput = document.getElementById('bookingRef');
        const bookedByInput = document.getElementById('bookedBy');

        const shipperInput = document.getElementById('shipper');
        const consigneeInput = document.getElementById('consignee');
        const notifyPartyInput = document.getElementById('notify_party');
        const billTypeInput = document.getElementById('bill_type');
        const typeOfMoveInput = document.getElementById('type_of_move');
        const jobNoInput = document.getElementById('job_no');
        const overseasAgentInput = document.getElementById('overseas_agent');
        const placeOfDeliveryInput = document.getElementById('place_of_delivery');
        const qtyPackagesInput = document.getElementById('quantity_packages');
        const measCbmInput = document.getElementById('measurement_cbm');
        const sealNoInput = document.getElementById('container_seal_no');
        const customNotesInput = document.getElementById('custom_notes');

        if (currentData) {
            vesselInput.value = currentData.vessel || '';
            voyageInput.value = currentData.voyage || '';
            bookingRefInput.value = currentData.bookingRef || '';
            etdInput.value = currentData.etd || '';
            etaInput.value = currentData.eta || '';
            
            shipperInput.value = currentData.shipper || '';
            consigneeInput.value = currentData.consignee || '';
            notifyPartyInput.value = currentData.notify_party || '';
            billTypeInput.value = currentData.bill_type || 'TELEX RELEASE';
            typeOfMoveInput.value = currentData.type_of_move || 'CY-CY';
            jobNoInput.value = currentData.job_no || '';
            overseasAgentInput.value = currentData.overseas_agent || '';
            placeOfDeliveryInput.value = currentData.place_of_delivery || '';
            qtyPackagesInput.value = currentData.quantity_packages || '';
            measCbmInput.value = currentData.measurement_cbm || '';
            sealNoInput.value = currentData.container_seal_no || '';
            customNotesInput.value = currentData.custom_notes || '';
        } else {
            // Pre-fill Shipper, Consignee, Notify Party from Quote details via Ajax
            fetch(`/orders/${id}`)
                .then(res => res.json())
                .then(b => {
                    shipperInput.value = b.shipper || '';
                    consigneeInput.value = b.consignee || '';
                    notifyPartyInput.value = b.notifyParty || '';
                    measCbmInput.value = b.cargo.volumeCbm || '';
                    qtyPackagesInput.value = b.quantityPackages || b.cargo.containerSummary || (b.cargo.containerQty ? `${b.cargo.containerQty} x ${b.cargo.containerType}` : 'LCL');
                }).catch(() => {});

            vesselInput.value = '';
            bookedByInput.value = "{{ __('messages.bookings_booked_by_default') }}";

            const now = new Date();
            now.setDate(now.getDate() + 5);
            etdInput.value = now.toISOString().split('T')[0];

            const etaDate = new Date();
            etaDate.setDate(etaDate.getDate() + 25);
            etaInput.value = etaDate.toISOString().split('T')[0];

            voyageInput.value = 'VYG-0' + Math.floor(Math.random() * 90 + 10);
            bookingRefInput.value = 'BHLU-' + Math.floor(Math.random() * 9000000 + 1000000);
            
            billTypeInput.value = 'TELEX RELEASE';
            typeOfMoveInput.value = 'CY-CY';
            jobNoInput.value = '';
            overseasAgentInput.value = '';
            placeOfDeliveryInput.value = '';
            sealNoInput.value = '';
            customNotesInput.value = '';
        }

        if (window.refreshCarrierVesselPicker) window.refreshCarrierVesselPicker();
    }

    function editCarrierBooking(id) {
        fetch(`/orders/${id}`)
            .then(res => res.json())
            .then(data => {
                openCarrierFormModal(
                    data.id, 
                    data.route.pol, 
                    data.route.pod, 
                    {
                        vessel: data.carrier?.vessel || '',
                        voyage: data.carrier?.voyage || '',
                        bookingRef: data.carrier?.bookingRef || '',
                        etd: data.carrier?.etd || '',
                        eta: data.carrier?.eta || '',
                        shipper: data.shipper || '',
                        consignee: data.consignee || '',
                        notify_party: data.notifyParty || '',
                        bill_type: data.billType || 'TELEX RELEASE',
                        type_of_move: data.typeOfMove || 'CY-CY',
                        job_no: data.jobNo || '',
                        overseas_agent: data.overseasAgent || '',
                        place_of_delivery: data.placeOfDelivery || '',
                        quantity_packages: data.quantityPackages || '',
                        measurement_cbm: data.measurementCbm || '',
                        container_seal_no: data.containerSealNo || '',
                        custom_notes: data.customNotes || ''
                    }
                );
            });
    }

    function closeCarrierFormModal() {
        if (window.closeCarrierVesselPickerMenu) window.closeCarrierVesselPickerMenu();
        if (carrierModal) carrierModal.classList.add('hidden');
    }
</script>
@endsection
