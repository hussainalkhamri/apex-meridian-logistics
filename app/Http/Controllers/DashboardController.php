<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\ShipmentTrack;
use App\Models\Voucher;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $activeShipments = ShipmentTrack::where('status', '!=', 'تم التسليم')->get();
        $inTransitCount = Booking::where('status', 'booked')->count();
        $allInvoices = Invoice::with('vouchers')
            ->whereHas('booking', fn ($query) => $query->approvedForAccounting())
            ->get();
        $agentPaymentVouchers = Voucher::agentPaymentsForApprovedAccounting()->get();
        $totalSpend = $agentPaymentVouchers
            ->filter(fn (Voucher $voucher) => $voucher->date && $voucher->date->format('Y-m') === Carbon::now()->format('Y-m'))
            ->sum('amount');

        $shipments = ShipmentTrack::with('booking')->latest()->take(10)->get();
        $invoices = Invoice::whereHas('booking', fn ($query) => $query->approvedForAccounting())
            ->latest()
            ->take(5)
            ->get();

        $bookingIdsWithInvoices = $allInvoices
            ->pluck('booking_id')
            ->filter()
            ->unique()
            ->values();
        $financialBookingIds = $bookingIdsWithInvoices;
        $financialBookings = Booking::withoutGlobalScope('archived_visibility')
            ->with('invoices')
            ->approvedForAccounting()
            ->whereIn('id', $financialBookingIds)
            ->get();
        $financialLines = $financialBookings->map(function (Booking $booking) {
            $sales = (float) $booking->invoices->sum('amount');
            $purchases = $booking->agentPurchaseTotal() + $this->customsGovernmentCost($booking);

            return [
                'sales' => $sales,
                'purchases' => $purchases,
                'net' => $sales - $purchases,
            ];
        });
        $totalSales = (float) $allInvoices->sum('amount');
        $totalAgentPurchases = (float) $financialBookings->sum(fn (Booking $booking) => $booking->agentPurchaseTotal());
        $totalCustomsGovernmentFees = (float) $financialBookings->sum(fn (Booking $booking) => $this->customsGovernmentCost($booking));
        $totalPurchases = (float) $financialLines->sum('purchases');
        $totalCollected = (float) $allInvoices->sum(fn (Invoice $invoice) => min($invoice->paid_amount, (float) $invoice->amount));
        $totalPaidToAgents = (float) $agentPaymentVouchers->sum('amount');

        $financialSummary = [
            'sales' => $totalSales,
            'purchases' => $totalPurchases,
            'profit' => (float) $financialLines->sum(fn (array $line) => max($line['net'], 0)),
            'loss' => abs((float) $financialLines->sum(fn (array $line) => min($line['net'], 0))),
            'net' => $totalSales - $totalPurchases,
            'collected' => $totalCollected,
            'paid' => $totalPaidToAgents,
            'cash_net' => $totalCollected - $totalPaidToAgents,
            'uncollected' => max($totalSales - $totalCollected, 0),
            'unpaid_purchases' => max($totalAgentPurchases - $totalPaidToAgents, 0),
            'agent_purchases' => $totalAgentPurchases,
            'customs_government_fees' => $totalCustomsGovernmentFees,
        ];
        $months = collect(range(5, 0))->map(fn (int $offset) => Carbon::now()->startOfMonth()->subMonths($offset));
        $monthlyFinancial = $months->map(function (Carbon $month) use ($allInvoices, $financialBookings) {
            $monthKey = $month->format('Y-m');
            $sales = $allInvoices
                ->filter(fn (Invoice $invoice) => ($invoice->issued_at ?: $invoice->created_at)?->format('Y-m') === $monthKey)
                ->sum('amount');
            $agentPurchases = $financialBookings
                ->filter(fn (Booking $booking) => ($booking->priced_at ?: $booking->created_at)?->format('Y-m') === $monthKey)
                ->sum(fn (Booking $booking) => $booking->agentPurchaseTotal());
            $customsGovernmentFees = $financialBookings
                ->sum(fn (Booking $booking) => $this->customsGovernmentCostForMonth($booking, $monthKey));
            $purchases = $agentPurchases + $customsGovernmentFees;

            return [
                'label' => $month->format('M Y'),
                'sales' => $sales,
                'purchases' => $purchases,
                'net' => $sales - $purchases,
            ];
        });
        $pendingPricingCount = Booking::whereIn('status', ['new', 'needs_pricing'])->count();
        $quoteExpiringCount = Booking::whereIn('status', ['priced', 'sent'])
            ->whereNotNull('validity_date')
            ->whereDate('validity_date', '>=', Carbon::today())
            ->whereDate('validity_date', '<=', Carbon::today()->addDays(7))
            ->count();
        $overdueInvoicesCount = $allInvoices
            ->filter(fn (Invoice $invoice) => $invoice->due_at && $invoice->due_at->isPast() && $invoice->remaining_amount > 0)
            ->count();
        $stalledCustomsCount = Booking::where('customs_status', 'on_hold')->count();
        $smartAlerts = collect([
            [
                'icon' => 'timer',
                'title' => 'طلبات بلا تسعير',
                'value' => $pendingPricingCount,
                'desc' => 'طلبات جديدة أو تحتاج إعادة تسعير.',
                'href' => route('pricing.index'),
                'tone' => 'warning',
            ],
            [
                'icon' => 'badge-alert',
                'title' => 'عروض قاربت الانتهاء',
                'value' => $quoteExpiringCount,
                'desc' => 'صلاحية العرض تنتهي خلال 7 أيام.',
                'href' => route('sales.index'),
                'tone' => 'accent',
            ],
            [
                'icon' => 'receipt-text',
                'title' => 'فواتير متأخرة',
                'value' => $overdueInvoicesCount,
                'desc' => 'فواتير مستحقة ولم تُسدد بالكامل.',
                'href' => route('accounts.index'),
                'tone' => 'destructive',
            ],
            [
                'icon' => 'shield-alert',
                'title' => 'تخليص متوقف',
                'value' => $stalledCustomsCount,
                'desc' => 'ملفات جمارك بحالة توقف مؤقت.',
                'href' => route('customs.index'),
                'tone' => 'warning',
            ],
        ])->filter(fn (array $alert) => $alert['value'] > 0)->values();

        $employeeOperationsPeriodOptions = [
            'day' => __('messages.dashboard_employee_operations_filter_day'),
            'week' => __('messages.dashboard_employee_operations_filter_week'),
            'month' => __('messages.dashboard_employee_operations_filter_month'),
            'all' => __('messages.dashboard_employee_operations_filter_all'),
        ];
        $employeeOperationsPeriod = (string) $request->query('employee_operations_period', 'month');
        if (! array_key_exists($employeeOperationsPeriod, $employeeOperationsPeriodOptions)) {
            $employeeOperationsPeriod = 'month';
        }
        $employeeOperationsStartDate = match ($employeeOperationsPeriod) {
            'day' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            default => null,
        };

        $activityLogsQuery = AuditLog::with('user')->latest();
        if ($employeeOperationsStartDate) {
            $activityLogsQuery->where('created_at', '>=', $employeeOperationsStartDate);
        }

        $activityLogs = $activityLogsQuery->get();
        $employeeOperations = $activityLogs
            ->groupBy(fn (AuditLog $log) => $log->user_id ?: 'system')
            ->map(function ($logs) {
                $first = $logs->first();

                return [
                    'user' => $first->user,
                    'total' => $logs->count(),
                    'last_action' => $first,
                    'actions' => $logs->groupBy('action_type')
                        ->map->count()
                        ->sortDesc(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $alerts = [];
        foreach ($shipments as $s) {
            if ($s->status === 'قيد التفريغ' || $s->status === 'تم التفريغ') {
                $alerts[] = [
                    'type' => 'warning',
                    'title' => "تفريغ شحنة {$s->id}",
                    'desc' => 'السفينة في الميناء، الحاوية قيد التفريغ والتخليص الجمركي.',
                ];
            }
        }

        foreach ($invoices as $inv) {
            if ($inv->status === 'غير مدفوعة') {
                $alerts[] = [
                    'type' => 'info',
                    'title' => 'فاتورة بانتظار الدفع',
                    'desc' => "الفاتورة {$inv->id} مستحقة الدفع بقيمة \${$inv->amount}.",
                ];
            }
        }

        if (empty($alerts)) {
            $alerts[] = [
                'type' => 'info',
                'title' => __('messages.dashboard_no_urgent_alerts_title'),
                'desc' => __('messages.dashboard_no_urgent_alerts_desc'),
            ];
        }

        return view('dashboard', compact(
            'activeShipments',
            'inTransitCount',
            'totalSpend',
            'shipments',
            'invoices',
            'alerts',
            'smartAlerts',
            'financialSummary',
            'monthlyFinancial',
            'employeeOperations',
            'employeeOperationsPeriod',
            'employeeOperationsPeriodOptions'
        ));
    }

    private function customsGovernmentCost(Booking $booking): float
    {
        return (float) collect($this->customsGovernmentCostLines($booking))->sum('amount');
    }

    private function customsGovernmentCostForMonth(Booking $booking, string $monthKey): float
    {
        return (float) collect($this->customsGovernmentCostLines($booking))
            ->filter(fn (array $line) => ($line['date'] ?? null)?->format('Y-m') === $monthKey)
            ->sum('amount');
    }

    private function customsGovernmentCostLines(Booking $booking): array
    {
        $cargoDetails = is_array($booking->cargo_details) ? $booking->cargo_details : [];
        $accounting = data_get($cargoDetails, 'customs_accounting', []);
        $invoiceLines = data_get($accounting, 'invoices', []);

        if (is_array($invoiceLines) && $invoiceLines !== []) {
            return collect($invoiceLines)
                ->map(function ($line) {
                    if (! is_array($line)) {
                        return null;
                    }

                    $amount = max(0, (float) data_get($line, 'government_fees', 0));
                    if ($amount <= 0) {
                        return null;
                    }

                    return [
                        'amount' => $amount,
                        'date' => $this->parseFinancialDate(
                            data_get($line, 'invoiced_at')
                                ?: data_get($line, 'issued_at')
                                ?: data_get($line, 'date')
                        ),
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        $legacyAmount = max(0, (float) data_get($accounting, 'government_fees', 0));
        if ($legacyAmount <= 0) {
            return [];
        }

        return [[
            'amount' => $legacyAmount,
            'date' => $this->parseFinancialDate(data_get($accounting, 'last_invoiced_at')),
        ]];
    }

    private function parseFinancialDate($date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }
}
