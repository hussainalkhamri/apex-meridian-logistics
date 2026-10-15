@extends('layouts.app')

@section('title', __('messages.dashboard_title') . ' — ' . __('messages.site_name'))

@section('content')
<div class="bg-secondary/30 min-h-screen">
    <div class="container mx-auto px-4 py-10">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold md:text-3xl">{{ __('messages.welcome') }}، {{ __('messages.site_name') }}</h1>
                <p class="text-muted-foreground">{{ __('messages.dashboard_intro') }}</p>
            </div>
            <a href="{{ route('booking.create') }}" class="inline-flex h-11 items-center justify-center rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground shadow-elegant hover:opacity-95 transition-all">
                {{ __('messages.new_shipment_booking') }}
            </a>
        </div>

        <!-- KPIs -->
        <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-start justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-gradient-accent text-accent-foreground">
                        <i data-lucide="package" class="h-5 w-5"></i>
                    </span>
                    <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold">+8%</span>
                </div>
                <div class="mt-4 text-2xl font-extrabold">{{ $activeShipments->count() }}</div>
                <div class="text-sm text-muted-foreground">{{ __('messages.active_shipments') }}</div>
            </div>
            
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-start justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-gradient-accent text-accent-foreground">
                        <i data-lucide="ship" class="h-5 w-5"></i>
                    </span>
                    <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold">{{ __('messages.orders_status_booked') }}</span>
                </div>
                <div class="mt-4 text-2xl font-extrabold">{{ $inTransitCount }}</div>
                <div class="text-sm text-muted-foreground">{{ __('messages.bookings_confirmed_bookings_heading') }}</div>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-start justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-gradient-accent text-accent-foreground">
                        <i data-lucide="dollar-sign" class="h-5 w-5"></i>
                    </span>
                    <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold">-4%</span>
                </div>
                <div class="mt-4 text-2xl font-extrabold">${{ number_format($totalSpend) }}</div>
                <div class="text-sm text-muted-foreground">{{ __('messages.monthly_spend') }}</div>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-start justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-gradient-accent text-accent-foreground">
                        <i data-lucide="trending-up" class="h-5 w-5"></i>
                    </span>
                    <span class="inline-flex items-center rounded-full {{ ($financialSummary['net'] ?? 0) >= 0 ? 'bg-success/15 text-success' : 'bg-destructive/15 text-destructive' }} px-2.5 py-0.5 text-xs font-semibold">{{ __('messages.dashboard_net') }}</span>
                </div>
                <div class="mt-4 text-2xl font-extrabold" dir="ltr">${{ number_format($financialSummary['net'] ?? 0, 2) }}</div>
                <div class="text-sm text-muted-foreground">{{ __('messages.dashboard_net_profit_loss') }}</div>
                <div class="mt-1 text-[11px] font-bold text-muted-foreground">{{ __('messages.dashboard_accounting_basis') }}</div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-primary/10 text-primary"><i data-lucide="badge-dollar-sign" class="h-5 w-5"></i></span>
                    <div>
                        <div class="text-xs font-bold text-muted-foreground">{{ __('messages.dashboard_total_sales') }}</div>
                        <div class="mt-1 text-xl font-extrabold" dir="ltr">${{ number_format($financialSummary['sales'] ?? 0, 2) }}</div>
                        <div class="mt-1 text-[11px] font-bold text-success" dir="ltr">{{ __('messages.dashboard_collected') }}: ${{ number_format($financialSummary['collected'] ?? 0, 2) }}</div>
                        <div class="text-[11px] font-bold text-muted-foreground" dir="ltr">{{ __('messages.dashboard_uncollected') }}: ${{ number_format($financialSummary['uncollected'] ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-warning/10 text-warning"><i data-lucide="shopping-cart" class="h-5 w-5"></i></span>
                    <div>
                        <div class="text-xs font-bold text-muted-foreground">{{ __('messages.dashboard_total_purchases') }}</div>
                        <div class="mt-1 text-xl font-extrabold" dir="ltr">${{ number_format($financialSummary['purchases'] ?? 0, 2) }}</div>
                        <div class="mt-1 text-[11px] font-bold text-warning-foreground" dir="ltr">{{ __('messages.dashboard_paid_to_agents') }}: ${{ number_format($financialSummary['paid'] ?? 0, 2) }}</div>
                        <div class="text-[11px] font-bold text-muted-foreground" dir="ltr">{{ __('messages.dashboard_customs_government_fees') }}: ${{ number_format($financialSummary['customs_government_fees'] ?? 0, 2) }}</div>
                        <div class="text-[11px] font-bold text-muted-foreground" dir="ltr">{{ __('messages.dashboard_unpaid_purchases') }}: ${{ number_format($financialSummary['unpaid_purchases'] ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-success/10 text-success"><i data-lucide="trending-up" class="h-5 w-5"></i></span>
                    <div>
                        <div class="text-xs font-bold text-muted-foreground">{{ __('messages.dashboard_profit') }}</div>
                        <div class="mt-1 text-xl font-extrabold text-success" dir="ltr">${{ number_format($financialSummary['profit'] ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-destructive/10 text-destructive"><i data-lucide="trending-down" class="h-5 w-5"></i></span>
                    <div>
                        <div class="text-xs font-bold text-muted-foreground">{{ __('messages.dashboard_loss') }}</div>
                        <div class="mt-1 text-xl font-extrabold text-destructive" dir="ltr">${{ number_format($financialSummary['loss'] ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
            <section class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-black">تنبيهات ذكية</h2>
                        <p class="mt-1 text-xs text-muted-foreground">أهم الملفات التي تحتاج إجراء سريع.</p>
                    </div>
                    <i data-lucide="sparkles" class="h-5 w-5 text-accent"></i>
                </div>

                @if($smartAlerts->isEmpty())
                    <div class="mt-4 rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                        لا توجد تنبيهات تشغيلية عاجلة الآن.
                    </div>
                @else
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        @foreach($smartAlerts as $alert)
                            @php
                                $toneClass = [
                                    'accent' => 'text-accent border-accent/25 bg-accent/10',
                                    'warning' => 'text-warning border-warning/30 bg-warning/10',
                                    'destructive' => 'text-destructive border-destructive/25 bg-destructive/10',
                                ][$alert['tone']] ?? 'text-primary border-border bg-secondary/20';
                            @endphp
                            <a href="{{ $alert['href'] }}" class="rounded-lg border p-4 transition hover:border-accent/40 hover:bg-secondary/30 {{ $toneClass }}">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-card">
                                        <i data-lucide="{{ $alert['icon'] }}" class="h-5 w-5"></i>
                                    </span>
                                    <span class="text-2xl font-black">{{ $alert['value'] }}</span>
                                </div>
                                <div class="mt-3 font-black text-foreground">{{ $alert['title'] }}</div>
                                <div class="mt-1 text-xs leading-6 text-muted-foreground">{{ $alert['desc'] }}</div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-xl border border-border bg-card p-5 shadow-card">
                @php
                    $maxMonthlyValue = max(1, $monthlyFinancial->flatMap(fn ($row) => [abs($row['sales']), abs($row['purchases']), abs($row['net'])])->max() ?: 1);
                @endphp
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-black">{{ __('messages.dashboard_monthly_financial_title') }}</h2>
                        <p class="mt-1 text-xs text-muted-foreground">{{ __('messages.dashboard_monthly_financial_desc') }}</p>
                    </div>
                    <i data-lucide="bar-chart-3" class="h-5 w-5 text-muted-foreground"></i>
                </div>
                <div class="mt-5 grid h-56 grid-cols-6 items-end gap-3 border-b border-border pb-3">
                    @foreach($monthlyFinancial as $month)
                        <div class="flex h-full min-w-0 flex-col justify-end gap-1">
                            <div class="flex flex-1 items-end gap-1">
                                <div class="w-full rounded-t bg-success/70" title="Sales ${{ number_format($month['sales'], 2) }}" style="height: {{ max(4, round(abs($month['sales']) / $maxMonthlyValue * 100)) }}%"></div>
                                <div class="w-full rounded-t bg-warning/70" title="Purchases ${{ number_format($month['purchases'], 2) }}" style="height: {{ max(4, round(abs($month['purchases']) / $maxMonthlyValue * 100)) }}%"></div>
                                <div class="w-full rounded-t {{ $month['net'] >= 0 ? 'bg-accent/80' : 'bg-destructive/70' }}" title="Net ${{ number_format($month['net'], 2) }}" style="height: {{ max(4, round(abs($month['net']) / $maxMonthlyValue * 100)) }}%"></div>
                            </div>
                            <div class="truncate text-center text-[10px] font-bold text-muted-foreground">{{ $month['label'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3 flex flex-wrap gap-3 text-[11px] font-bold text-muted-foreground">
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-success/70"></span>مبيعات</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-warning/70"></span>مشتريات</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-accent/80"></span>صافي</span>
                </div>
            </section>
        </div>

        <div class="mt-6 rounded-xl border border-border bg-card shadow-card overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-border p-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-bold">{{ __('messages.dashboard_employee_operations') }}</h2>
                    <p class="text-xs text-muted-foreground">{{ __('messages.dashboard_employee_operations_desc') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[11px] font-black text-muted-foreground">{{ __('messages.dashboard_employee_operations_filter_label') }}</span>
                    <form method="GET" action="{{ route('dashboard') }}" class="inline-flex flex-wrap gap-1 rounded-lg border border-border bg-background p-1">
                        @foreach($employeeOperationsPeriodOptions as $periodKey => $periodLabel)
                            <button type="submit" name="employee_operations_period" value="{{ $periodKey }}" class="inline-flex h-8 items-center justify-center rounded-md px-3 text-xs font-black transition {{ $employeeOperationsPeriod === $periodKey ? 'bg-accent text-accent-foreground shadow-sm' : 'text-muted-foreground hover:bg-secondary hover:text-foreground' }}">
                                {{ $periodLabel }}
                            </button>
                        @endforeach
                    </form>
                    <i data-lucide="activity" class="h-5 w-5 text-muted-foreground"></i>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-right">{{ __('messages.dashboard_employee') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.dashboard_total_operations') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.dashboard_operation_details') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.dashboard_last_operation') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employeeOperations as $row)
                            <tr class="border-b border-border/40 hover:bg-secondary/20">
                                <td class="px-4 py-3">
                                    <div class="font-bold">{{ $row['user']?->full_name ?: __('messages.dashboard_system') }}</div>
                                    <div class="text-[11px] text-muted-foreground">{{ $row['user']?->username ?: 'system' }}</div>
                                </td>
                                <td class="px-4 py-3 font-extrabold">{{ $row['total'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($row['actions'] as $actionType => $count)
                                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold {{ \App\Models\AuditLog::actionColorFor($actionType) }}">
                                                {{ \App\Models\AuditLog::actionLabelFor($actionType) }}: {{ $count }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    <div class="font-semibold text-foreground">{{ $row['last_action']->action_label }}</div>
                                    <div>{{ $row['last_action']->created_at->diffForHumans() }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center text-muted-foreground text-xs">{{ __('messages.dashboard_no_operations') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <!-- Shipments table -->
            <div class="rounded-xl border border-border bg-card shadow-card lg:col-span-2 overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-border p-4 bg-card">
                    <h2 class="text-lg font-bold">{{ __('messages.latest_shipments') }}</h2>
                    <div class="relative w-56">
                        <i data-lucide="search" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        <input type="text" id="shipment-search-input" placeholder="{{ __('messages.search_bl') }}" class="w-full h-9 border border-border bg-background pr-9 pl-3 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-secondary/40 text-xs text-muted-foreground">
                            <tr>
                                <th class="p-3">B/L</th>
                                <th class="p-3">{{ __('messages.route') }}</th>
                                <th class="p-3">{{ __('messages.status') }}</th>
                                <th class="p-3">{{ __('messages.eta') }}</th>
                                <th class="p-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border" id="shipment-table-body">
                            @forelse($shipments as $s)
                                @php
                                    $routeOrigin = $s->booking?->pol ? str_replace('ميناء ', '', $s->booking->pol) : '—';
                                    $routeDestination = $s->booking?->pod ? str_replace('ميناء ', '', $s->booking->pod) : '—';
                                @endphp
                                <tr class="hover:bg-secondary/30 transition-colors">
                                    <td class="p-3 font-mono font-medium text-xs">{{ $s->id }}</td>
                                    <td class="p-3 text-xs">{{ $routeOrigin }} &larr; {{ $routeDestination }}</td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center rounded-full bg-accent/20 px-2 py-0.5 text-xs font-bold text-accent-foreground border border-accent/20">
                                            {{ $s->status }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-xs text-muted-foreground">{{ $s->eta ? $s->eta->toDateString() : '—' }}</td>
                                    <td class="p-3 text-left">
                                        <a href="{{ route('tracking', ['q' => $s->id]) }}" class="inline-flex h-8 items-center justify-center rounded-lg bg-secondary/80 px-3 text-xs font-bold text-foreground hover:bg-secondary transition-all">
                                            <span>{{ __('messages.track') }}</span>
                                            <i data-lucide="arrow-up-right" class="mr-1 h-3.5 w-3.5"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-muted-foreground text-xs">{{ __('messages.no_shipments') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Alerts + Invoices -->
            <div class="space-y-6">
                <!-- Alerts -->
                <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                    <div class="flex items-center gap-2 border-b pb-3 mb-3">
                        <i data-lucide="alert-circle" class="h-5 w-5 text-warning"></i>
                        <h2 class="text-base font-bold">{{ __('messages.alerts') }}</h2>
                    </div>
                    <ul class="space-y-3 text-xs">
                        @foreach($alerts as $alert)
                            <li class="rounded-lg border p-3 {{ $alert['type'] === 'warning' ? 'border-warning/30 bg-warning/10 text-warning-foreground' : 'border-border bg-secondary/40' }}">
                                <div class="font-semibold">{{ $alert['title'] }}</div>
                                <div class="text-xs text-muted-foreground mt-1">{{ $alert['desc'] }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Latest Invoices -->
                <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                    <div class="flex items-center gap-2 border-b pb-3 mb-3">
                        <i data-lucide="receipt" class="h-5 w-5 text-primary"></i>
                        <h2 class="text-base font-bold">{{ __('messages.latest_invoices') }}</h2>
                    </div>
                    <ul class="space-y-2 text-xs">
                        @forelse($invoices as $inv)
                            <li class="flex items-center justify-between rounded-lg bg-secondary/40 p-3">
                                <div>
                                    <span class="font-mono font-medium block">{{ $inv->id }}</span>
                                    <span class="text-[10px] text-muted-foreground">{{ $inv->issued_at->toDateString() }}</span>
                                </div>
                                <span class="font-bold text-foreground">${{ number_format($inv->amount) }}</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $inv->status === 'مدفوعة' ? 'bg-success/20 text-success-foreground border border-success/30' : 'bg-warning/20 text-warning-foreground border border-warning/30' }}">
                                    {{ $inv->status === 'مدفوعة' ? __('messages.dashboard_invoice_paid') : ($inv->status === 'متأخرة' ? __('messages.dashboard_invoice_overdue') : $inv->status) }}
                                </span>
                            </li>
                        @empty
                            <li class="py-6 text-center text-muted-foreground text-xs">{{ __('messages.no_invoices') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('shipment-search-input');
        const tableBody = document.getElementById('shipment-table-body');
        
        if (searchInput && tableBody) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase();
                const rows = tableBody.getElementsByTagName('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    if (row.cells.length < 5) continue; // Skip empty row
                    
                    const blCell = row.cells[0].textContent.toLowerCase();
                    const routeCell = row.cells[1].textContent.toLowerCase();
                    
                    if (blCell.includes(query) || routeCell.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }
    });
</script>
@endsection
