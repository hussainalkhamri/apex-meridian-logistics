<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\AuditLog;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Voucher;
use App\Support\SystemId;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountsController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['booking', 'vouchers'])
            ->whereHas('booking', fn ($query) => $query->approvedForAccounting())
            ->latest()
            ->get();
        $vouchers = Voucher::with(['booking', 'invoice', 'agent'])
            ->where('type', 'receipt')
            ->where(function ($query) {
                $query
                    ->whereHas('invoice.booking', fn ($bookingQuery) => $bookingQuery->approvedForAccounting())
                    ->orWhereHas('booking', fn ($bookingQuery) => $bookingQuery->approvedForAccounting());
            })
            ->latest()
            ->get();
        $agentPaymentVouchers = Voucher::with(['agent', 'booking'])
            ->agentPaymentsForApprovedAccounting()
            ->latest()
            ->get();
        $bookings = Booking::withoutGlobalScope('archived_visibility')
            ->with(['invoices', 'agent'])
            ->approvedForAccounting()
            ->latest()
            ->get();
        $customers = Customer::orderBy('company')->get();
        $agents = Agent::orderBy('name')->get();
        $agentDeals = Booking::withoutGlobalScope('archived_visibility')
            ->with('agent')
            ->approvedForAccounting()
            ->whereNotNull('agent_id')
            ->where('agent_purchase_amount', '>', 0)
            ->latest('priced_at')
            ->get();
        $customsAccounts = Booking::withoutGlobalScope('archived_visibility')
            ->with(['customer', 'invoices.vouchers'])
            ->approvedForAccounting()
            ->where(function ($query) {
                $query->where('shipment_type', 'CUSTOMS')
                    ->orWhereNotNull('customs_reference')
                    ->orWhereNotNull('customs_notes')
                    ->orWhere('customs_fees', '>', 0)
                    ->orWhereIn('customs_status', ['documents_review', 'inspection', 'duties', 'cleared', 'on_hold']);
            })
            ->latest()
            ->get()
            ->map(function (Booking $booking) {
                $customsInvoices = $booking->invoices
                    ->filter(fn (Invoice $invoice) => ($invoice->invoice_type ?: 'freight') === 'customs');
                $invoiceTotal = (float) $customsInvoices->sum('amount');
                $paidTotal = (float) $customsInvoices->sum(fn (Invoice $invoice) => $invoice->paid_amount);
                $latestCustomsInvoice = $customsInvoices
                    ->sortByDesc(fn (Invoice $invoice) => optional($invoice->issued_at)->timestamp ?: optional($invoice->created_at)->timestamp ?: 0)
                    ->first();

                $booking->customs_invoice_total = $invoiceTotal;
                $booking->customs_paid_total = $paidTotal;
                $booking->customs_remaining_total = max($invoiceTotal - $paidTotal, 0);
                $booking->customs_unbilled_total = max(((float) $booking->customs_fees) - $invoiceTotal, 0);
                $booking->customs_invoice_count = $customsInvoices->count();
                $booking->latest_customs_invoice = $latestCustomsInvoice;

                return $booking;
            });

        $totalDue = 0;
        $totalPaid = 0;
        $overdueCount = 0;
        $totalAgentSales = 0;
        $totalAgentPurchases = 0;
        $totalAgentProfit = 0;
        $totalAgentPaid = (float) $agentPaymentVouchers->sum('amount');

        $now = Carbon::now();

        foreach ($invoices as $inv) {
            $paid = $inv->paid_amount;
            $remaining = $inv->remaining_amount;

            $totalPaid += $paid;
            $totalDue += $remaining;

            if ($remaining > 0 && $inv->due_at->lt($now)) {
                $overdueCount++;
            }
        }

        foreach ($agentDeals as $deal) {
            $totalAgentSales += $deal->pricingTotal();
            $totalAgentPurchases += $deal->agentPurchaseTotal();
            $totalAgentProfit += $deal->profitAmount();
        }

        $agentPayments = $agentPaymentVouchers
            ->groupBy('agent_id')
            ->map(fn ($payments) => (float) $payments->sum('amount'));
        $agentPurchaseTotals = $agentDeals
            ->groupBy('agent_id')
            ->map(fn ($deals) => (float) $deals->sum(fn (Booking $deal) => $deal->agentPurchaseTotal()));
        $latestAgentPayments = $agentPaymentVouchers
            ->groupBy('agent_id')
            ->map(fn ($payments) => $payments->first());

        $agentReports = $agentDeals
            ->groupBy('agent_id')
            ->map(function ($deals, $agentId) use ($agentPayments, $latestAgentPayments) {
                $firstDeal = $deals->first();
                $sales = $deals->sum(fn (Booking $deal) => $deal->pricingTotal());
                $purchases = $deals->sum(fn (Booking $deal) => $deal->agentPurchaseTotal());
                $paid = (float) ($agentPayments[$agentId] ?? 0);
                $profit = $sales - $purchases;

                return [
                    'agent' => $firstDeal->agent,
                    'deals_count' => $deals->count(),
                    'quantity' => $deals->sum('agent_purchase_quantity'),
                    'sales' => $sales,
                    'purchases' => $purchases,
                    'paid' => $paid,
                    'remaining' => max($purchases - $paid, 0),
                    'profit' => $profit,
                    'latest_payment_voucher' => $latestAgentPayments[$agentId] ?? null,
                ];
            })
            ->sortByDesc('profit')
            ->values();

        $totalAgentRemaining = max($totalAgentPurchases - $totalAgentPaid, 0);
        $totalCustomsFees = (float) $customsAccounts->sum(fn (Booking $booking) => (float) $booking->customs_fees);
        $totalCustomsInvoiced = (float) $customsAccounts->sum('customs_invoice_total');
        $totalCustomsPaid = (float) $customsAccounts->sum('customs_paid_total');
        $totalCustomsRemaining = (float) $customsAccounts->sum('customs_remaining_total');
        $totalCustomsUnbilled = (float) $customsAccounts->sum('customs_unbilled_total');

        return view('accounts', compact(
            'invoices',
            'vouchers',
            'agentPaymentVouchers',
            'bookings',
            'totalDue',
            'totalPaid',
            'overdueCount',
            'agentDeals',
            'agentReports',
            'customers',
            'agents',
            'totalAgentSales',
            'totalAgentPurchases',
            'totalAgentPaid',
            'totalAgentRemaining',
            'agentPurchaseTotals',
            'agentPayments',
            'totalAgentProfit',
            'customsAccounts',
            'totalCustomsFees',
            'totalCustomsInvoiced',
            'totalCustomsPaid',
            'totalCustomsRemaining',
            'totalCustomsUnbilled'
        ));
    }

    public function showStatement(Request $request)
    {
        $data = $request->validate([
            'account_type' => 'required|in:customer,agent',
            'account_id' => 'required|integer',
            'statement_type' => 'required|in:detailed,summary',
            'statement_scope' => 'nullable|in:all,freight,customs',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $from = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfDay()
            : null;
        $to = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->endOfDay()
            : null;

        if ($data['account_type'] === 'customer') {
            $account = Customer::findOrFail($data['account_id']);
            $transactions = $this->customerStatementTransactions($account, $data['statement_scope'] ?? 'all');
        } else {
            $account = Agent::findOrFail($data['account_id']);
            $transactions = $this->agentStatementTransactions($account);
        }

        $statement = $this->prepareStatement(
            $transactions,
            $data['account_type'],
            $data['statement_type'],
            $from,
            $to
        );

        return view('account-statement', [
            'account' => $account,
            'accountType' => $data['account_type'],
            'statementType' => $data['statement_type'],
            'statementScope' => $data['statement_scope'] ?? 'all',
            'dateFrom' => $from,
            'dateTo' => $to,
            'statement' => $statement,
            'statementNumber' => 'ST-' . now()->format('Ymd-His'),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $this->authorizeFinancialDocumentMutation($request);

        $request->validate([
            'status' => 'required'
        ]);

        $invoice = Invoice::findOrFail($id);
        $old = $invoice->only(['status']);
        $invoice->update([
            'status' => $request->input('status')
        ]);

        AuditLog::logAction('INVOICE_UPDATE', 'invoices', $invoice->id, $old, [
            'status' => $invoice->status,
        ]);

        return redirect()->route('accounts.index')->with('success', "تم تحديث حالة الفاتورة {$id} بنجاح");
    }

    public function showInvoice($id)
    {
        $invoice = Invoice::with(['booking', 'vouchers'])->findOrFail($id);
        $booking = $invoice->booking;
        return view('invoice-document', compact('invoice', 'booking'));
    }

    public function destroyInvoice(Request $request, $id)
    {
        $this->authorizeFinancialDocumentMutation($request, 'غير مسموح بحذف الفواتير إلا للمدير.');

        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return redirect()->route('accounts.index')->with('success', "تم حذف الفاتورة {$id} بنجاح");
    }

    public function storeInvoice(Request $request)
    {
        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'invoice_type' => 'nullable|in:freight,customs',
            'amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:2000',
            'customs_government_fees' => 'nullable|numeric|min:0',
            'customs_service_fee' => 'nullable|numeric|min:0',
            'customs_other_fee' => 'nullable|numeric|min:0',
            'issued_at' => 'required|date',
            'due_at' => 'required|date|after_or_equal:issued_at',
            'status' => 'required|in:غير مدفوعة,مدفوعة جزئياً,مدفوعة',
        ]);

        $booking = Booking::withoutGlobalScope('archived_visibility')->findOrFail($data['booking_id']);
        if (! $booking->isApprovedForAccounting()) {
            throw ValidationException::withMessages([
                'booking_id' => 'لا يمكن إدخال الطلب في الحسابات قبل اعتماد المبيعات.',
            ]);
        }
        $invoiceType = $data['invoice_type'] ?? 'freight';
        $amount = (float) ($data['amount'] ?? 0);
        $description = trim((string) ($data['description'] ?? ''));

        if ($invoiceType === 'customs') {
            $governmentFees = (float) ($data['customs_government_fees'] ?? 0);
            $serviceFee = (float) ($data['customs_service_fee'] ?? 0);
            $otherFee = (float) ($data['customs_other_fee'] ?? 0);
            $calculatedAmount = $governmentFees + $serviceFee + $otherFee;

            if ($calculatedAmount > 0) {
                $amount = $calculatedAmount;
            }

            if ($description === '') {
                $description = 'فاتورة تخليص جمركي للطلب ' . $booking->id;
            }
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Invoice amount must be greater than zero.',
            ]);
        }

        $invoiceId = $this->makeDocumentId('INV');

        $invoice = Invoice::create([
            'id' => $invoiceId,
            'booking_id' => $booking->id,
            'invoice_type' => $invoiceType,
            'amount' => $amount,
            'currency' => $booking->pricingCurrency(),
            'description' => $description !== '' ? $description : null,
            'status' => $data['status'],
            'issued_at' => $data['issued_at'],
            'due_at' => $data['due_at'],
        ]);

        if ($invoiceType === 'customs') {
            $cargoDetails = is_array($booking->cargo_details) ? $booking->cargo_details : [];
            $customsAccounting = data_get($cargoDetails, 'customs_accounting', []);
            $customsAccounting = is_array($customsAccounting) ? $customsAccounting : [];
            $customsInvoiceLines = data_get($customsAccounting, 'invoices', []);
            $customsInvoiceLines = is_array($customsInvoiceLines) ? $customsInvoiceLines : [];
            $customsInvoiceLines[] = [
                'invoice_id' => $invoice->id,
                'government_fees' => $governmentFees,
                'service_fee' => $serviceFee,
                'other_fee' => $otherFee,
                'total' => $amount,
                'invoiced_at' => now()->toDateString(),
            ];

            $cargoDetails['customs_accounting'] = array_merge($customsAccounting, [
                'government_fees' => (float) ($data['customs_government_fees'] ?? 0),
                'service_fee' => (float) ($data['customs_service_fee'] ?? 0),
                'other_fee' => (float) ($data['customs_other_fee'] ?? 0),
                'last_invoice_id' => $invoice->id,
                'last_invoiced_at' => now()->toDateString(),
                'invoices' => $customsInvoiceLines,
            ]);
            $customsInvoiceTotal = (float) $booking->invoices()
                ->where('invoice_type', 'customs')
                ->sum('amount');

            $booking->update([
                'customs_fees' => max((float) $booking->customs_fees, $customsInvoiceTotal),
                'customs_status' => $booking->customs_status ?: 'pending',
                'cargo_details' => $cargoDetails,
            ]);
        }

        AuditLog::logAction('INVOICE_CREATE', 'invoices', $invoice->id, [], [
            'booking_id' => $invoice->booking_id,
            'invoice_type' => $invoice->invoice_type,
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'status' => $invoice->status,
        ]);

        $redirectParameters = $invoiceType === 'customs' ? ['accounts_tab' => 'customs'] : [];

        return redirect()->route('accounts.index', $redirectParameters)->with('success', "تم إصدار الفاتورة الرسمية {$invoiceId} بنجاح");
    }

    public function storeVoucher(Request $request)
    {
        $request->validate([
            'type' => 'required|in:receipt,payment',
            'invoice_id' => 'required_if:type,receipt|nullable|exists:invoices,id',
            'agent_id' => 'required_if:type,payment|nullable|exists:agents,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payee_payer' => 'required|string',
            'description' => 'required|string',
            'date' => 'required|date',
        ]);

        $type = $request->input('type');
        $prefix = $type === 'receipt' ? 'REC' : 'PAY';
        $voucherId = $this->makeDocumentId($prefix);
        $amount = (float) $request->input('amount');
        $bookingId = $request->filled('booking_id') ? $request->input('booking_id') : null;
        $invoiceId = $request->input('invoice_id');
        $agentId = $type === 'payment' ? $request->input('agent_id') : null;
        $paidBefore = 0;
        $paidAfter = 0;
        $balanceAfter = 0;

        DB::transaction(function () use ($request, $type, $voucherId, $amount, &$bookingId, $invoiceId, $agentId, &$paidBefore, &$paidAfter, &$balanceAfter) {
            $invoice = null;
            $agent = null;

            if ($type === 'receipt') {
                $invoice = Invoice::with('vouchers')
                    ->whereHas('booking', fn ($bookingQuery) => $bookingQuery->approvedForAccounting())
                    ->lockForUpdate()
                    ->findOrFail($invoiceId);
                $bookingId = $invoice->booking_id;
                $paidBefore = $invoice->paid_amount;
                $remainingBefore = max(((float) $invoice->amount) - $paidBefore, 0);

                if ($amount > ($remainingBefore + 0.01)) {
                    throw ValidationException::withMessages([
                        'amount' => "مبلغ سند القبض أكبر من المتبقي على الفاتورة. المتبقي الحالي: $" . number_format($remainingBefore, 2),
                    ]);
                }

                $paidAfter = $paidBefore + $amount;
                $balanceAfter = max(((float) $invoice->amount) - $paidAfter, 0);
            }

            if ($type === 'payment' && $agentId) {
                $agent = Agent::lockForUpdate()->findOrFail($agentId);

                if ($bookingId) {
                    $booking = Booking::withoutGlobalScope('archived_visibility')
                        ->lockForUpdate()
                        ->findOrFail($bookingId);

                    if (! $booking->isApprovedForAccounting()) {
                        throw ValidationException::withMessages([
                            'booking_id' => 'لا يمكن صرف مبلغ وكيل قبل اعتماد المبيعات للطلب.',
                        ]);
                    }

                    if ((int) $booking->agent_id !== (int) $agent->id) {
                        throw ValidationException::withMessages([
                            'booking_id' => 'لا يمكن ربط سند صرف الوكيل بطلب شحن لا يتبع نفس الوكيل.',
                        ]);
                    }
                }

                $purchaseTotal = (float) Booking::withoutGlobalScope('archived_visibility')
                    ->approvedForAccounting()
                    ->where('agent_id', $agent->id)
                    ->where('agent_purchase_amount', '>', 0)
                    ->sum('agent_purchase_amount');
                $paidBefore = (float) Voucher::agentPaymentsForApprovedAccounting()
                    ->where('agent_id', $agent->id)
                    ->sum('amount');
                $remainingBefore = max($purchaseTotal - $paidBefore, 0);

                if ($amount > ($remainingBefore + 0.01)) {
                    throw ValidationException::withMessages([
                        'amount' => "مبلغ سند الصرف أكبر من المتبقي للوكيل. المتبقي الحالي: $" . number_format($remainingBefore, 2),
                    ]);
                }

                $paidAfter = $paidBefore + $amount;
                $balanceAfter = max($purchaseTotal - $paidAfter, 0);
            }

            Voucher::create([
                'id' => $voucherId,
                'type' => $type,
                'booking_id' => $bookingId,
                'invoice_id' => $invoice?->id,
                'agent_id' => $agent?->id,
                'amount' => $amount,
                'paid_before' => $paidBefore,
                'paid_after' => $paidAfter,
                'balance_after' => $balanceAfter,
                'payment_method' => $request->input('payment_method'),
                'payee_payer' => $request->input('payee_payer'),
                'description' => $request->input('description'),
                'date' => $request->input('date'),
            ]);

            if ($invoice) {
                $invoice->update([
                    'status' => $this->statusFromPayment($paidAfter, (float) $invoice->amount),
                ]);
            }
        }, 5);

        AuditLog::logAction('VOUCHER_CREATE', 'vouchers', $voucherId, [], [
            'type' => $type,
            'booking_id' => $bookingId,
            'invoice_id' => $invoiceId,
            'agent_id' => $agentId,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
        ]);

        $msg = $type === 'receipt' ? 'سند القبض' : 'سند الصرف';
        return redirect()->route('accounts.index')->with('success', "تم إنشاء {$msg} بنجاح برقم {$voucherId}");
    }

    public function showVoucher($id)
    {
        $voucher = Voucher::with(['booking', 'invoice', 'agent'])->findOrFail($id);
        $booking = $voucher->booking;
        return view('voucher-document', compact('voucher', 'booking'));
    }

    public function destroyVoucher(Request $request, $id)
    {
        $this->authorizeFinancialDocumentMutation($request, 'غير مسموح بحذف سندات القبض إلا للمدير.');

        $voucher = Voucher::with(['invoice'])->findOrFail($id);
        abort_unless($voucher->type === 'receipt', 404);

        $invoiceId = $voucher->invoice_id;

        DB::transaction(function () use ($voucher, $invoiceId) {
            $voucher->delete();

            if ($invoiceId) {
                $this->recalculateCustomerReceiptBalances($invoiceId);
            }
        }, 5);

        return redirect()->route('accounts.index')->with('success', "تم حذف سند القبض {$id} بنجاح");
    }

    public function updateVoucher(Request $request, $id)
    {
        $this->authorizeFinancialDocumentMutation($request, 'غير مسموح بتعديل سندات القبض إلا للمدير.');

        $request->validate([
            'type' => 'required|in:receipt,payment',
            'invoice_id' => 'required_if:type,receipt|nullable|exists:invoices,id',
            'agent_id' => 'required_if:type,payment|nullable|exists:agents,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payee_payer' => 'required|string',
            'description' => 'required|string',
            'date' => 'required|date',
        ]);

        $amount = (float) $request->input('amount');
        $type = $request->input('type');
        $invoiceId = $request->input('invoice_id');
        $agentId = $type === 'payment' ? (int) $request->input('agent_id') : null;
        $bookingId = $request->filled('booking_id') ? $request->input('booking_id') : null;
        $oldAgentId = null;
        $oldInvoiceId = null;

        DB::transaction(function () use ($request, $id, $amount, $type, $invoiceId, $agentId, $bookingId, &$oldAgentId, &$oldInvoiceId) {
            $voucher = Voucher::lockForUpdate()->findOrFail($id);
            abort_unless($voucher->type === $type, 404);

            $old = $voucher->only([
                'booking_id',
                'agent_id',
                'amount',
                'payment_method',
                'payee_payer',
                'description',
                'date',
                'paid_before',
                'paid_after',
                'balance_after',
            ]);
            $oldAgentId = $voucher->agent_id;
            $oldInvoiceId = $voucher->invoice_id;

            if ($type === 'receipt') {
                $invoice = Invoice::whereHas('booking', fn ($bookingQuery) => $bookingQuery->approvedForAccounting())
                    ->lockForUpdate()
                    ->findOrFail($invoiceId);
                $otherPaid = (float) Voucher::where('type', 'receipt')
                    ->where('invoice_id', $invoice->id)
                    ->where('id', '!=', $voucher->id)
                    ->sum('amount');
                $remaining = max((float) $invoice->amount - $otherPaid, 0);

                if ($amount > ($remaining + 0.01)) {
                    throw ValidationException::withMessages([
                        'amount' => "مبلغ سند القبض أكبر من المتبقي على الفاتورة. المتبقي المتاح: $" . number_format($remaining, 2),
                    ]);
                }

                $voucher->update([
                    'booking_id' => $invoice->booking_id,
                    'invoice_id' => $invoice->id,
                    'agent_id' => null,
                    'amount' => $amount,
                    'payment_method' => $request->input('payment_method'),
                    'payee_payer' => $request->input('payee_payer'),
                    'description' => $request->input('description'),
                    'date' => $request->input('date'),
                ]);

                $this->recalculateCustomerReceiptBalances($invoice->id);
                if ($oldInvoiceId && $oldInvoiceId !== $invoice->id) {
                    $this->recalculateCustomerReceiptBalances($oldInvoiceId);
                }

                AuditLog::logAction('VOUCHER_UPDATE', 'vouchers', $voucher->id, $old, [
                    'type' => $type,
                    'invoice_id' => $invoice->id,
                    'amount' => $amount,
                ]);
                return;
            }

            abort_unless($voucher->agent_id, 404);

            if ($bookingId) {
                $booking = Booking::withoutGlobalScope('archived_visibility')
                    ->lockForUpdate()
                    ->findOrFail($bookingId);

                if (! $booking->isApprovedForAccounting()) {
                    throw ValidationException::withMessages([
                        'booking_id' => 'لا يمكن صرف مبلغ وكيل قبل اعتماد المبيعات للطلب.',
                    ]);
                }

                if ((int) $booking->agent_id !== $agentId) {
                    throw ValidationException::withMessages([
                        'booking_id' => 'لا يمكن ربط سند صرف الوكيل بطلب شحن لا يتبع نفس الوكيل.',
                    ]);
                }
            }

            $purchaseTotal = (float) Booking::withoutGlobalScope('archived_visibility')
                ->approvedForAccounting()
                ->where('agent_id', $agentId)
                ->where('agent_purchase_amount', '>', 0)
                ->sum('agent_purchase_amount');
            $paidByOtherVouchers = (float) Voucher::agentPaymentsForApprovedAccounting()
                ->where('agent_id', $agentId)
                ->where('id', '!=', $voucher->id)
                ->sum('amount');
            $available = max($purchaseTotal - $paidByOtherVouchers, 0);

            if ($amount > ($available + 0.01)) {
                throw ValidationException::withMessages([
                    'amount' => "مبلغ سند الصرف أكبر من المتبقي للوكيل. المتبقي المتاح: $" . number_format($available, 2),
                ]);
            }

            $voucher->update([
                'booking_id' => $bookingId,
                'invoice_id' => null,
                'agent_id' => $agentId,
                'amount' => $amount,
                'payment_method' => $request->input('payment_method'),
                'payee_payer' => $request->input('payee_payer'),
                'description' => $request->input('description'),
                'date' => $request->input('date'),
            ]);

            if ($oldAgentId && $oldAgentId !== $agentId) {
                $this->recalculateAgentPaymentBalances((int) $oldAgentId);
            }

            $this->recalculateAgentPaymentBalances($agentId);

            AuditLog::logAction('VOUCHER_UPDATE', 'vouchers', $voucher->id, $old, [
                'agent_id' => $agentId,
                'booking_id' => $bookingId,
                'amount' => $amount,
            ]);
        }, 5);

        $message = $type === 'receipt' ? 'سند القبض' : 'سند الصرف';
        return redirect()->route('accounts.index')->with('success', "تم تعديل {$message} {$id} بنجاح");
    }

    private function recalculateCustomerReceiptBalances(string $invoiceId): void
    {
        $invoice = Invoice::lockForUpdate()->findOrFail($invoiceId);
        $paidBefore = 0.0;

        Voucher::where('type', 'receipt')
            ->where('invoice_id', $invoice->id)
            ->orderBy('date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->each(function (Voucher $voucher) use (&$paidBefore, $invoice) {
                $paidAfter = $paidBefore + (float) $voucher->amount;
                $voucher->update([
                    'paid_before' => $paidBefore,
                    'paid_after' => $paidAfter,
                    'balance_after' => max((float) $invoice->amount - $paidAfter, 0),
                ]);
                $paidBefore = $paidAfter;
            });

        $invoice->update([
            'status' => $this->statusFromPayment($paidBefore, (float) $invoice->amount),
        ]);
    }

    public function updateInvoice(Request $request, $id)
    {
        $this->authorizeFinancialDocumentMutation($request, 'غير مسموح بتعديل الفواتير إلا للمدير.');

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'issued_at' => 'required|date',
            'due_at' => 'required|date',
            'status' => 'required|string',
            'ocean_freight' => 'required|numeric|min:0',
            'origin_charges' => 'required|numeric|min:0',
            'exw_charges' => 'nullable|numeric|min:0',
            'dthc' => 'required|numeric|min:0',
            'war_risk_surcharge' => 'required|numeric|min:0',
            'other_charges' => 'required|numeric|min:0',
            'destination_port_change_fee' => 'nullable|numeric|min:0',
            'other_penalties' => 'nullable|numeric|min:0',
            'pol' => 'required|string',
            'pod' => 'required|string',
            'custom_charge_desc' => 'nullable|string',
            'custom_charge_amount' => 'nullable|numeric|min:0',
        ]);

        $invoice = Invoice::findOrFail($id);
        $oldInvoice = $invoice->only(['amount', 'issued_at', 'due_at', 'status']);
        $invoice->update([
            'amount' => $request->input('amount'),
            'issued_at' => $request->input('issued_at'),
            'due_at' => $request->input('due_at'),
            'status' => $request->input('status'),
        ]);

        $booking = $invoice->booking;
        $oldBooking = $booking?->only([
            'ocean_freight',
            'origin_charges',
            'exw_charges',
            'dthc',
            'war_risk_surcharge',
            'other_charges',
            'destination_port_change_fee',
            'other_penalties',
            'pol',
            'pod',
            'custom_charge_desc',
            'custom_charge_amount',
        ]) ?? [];

        if ($booking) {
            $exwCharges = $booking->isExwSeaShipment() ? ($request->input('exw_charges') ?? 0) : 0;

            $booking->update([
                'ocean_freight' => $request->input('ocean_freight'),
                'origin_charges' => $request->input('origin_charges'),
                'exw_charges' => $exwCharges,
                'dthc' => $request->input('dthc'),
                'war_risk_surcharge' => $request->input('war_risk_surcharge'),
                'other_charges' => $request->input('other_charges'),
                'destination_port_change_fee' => $request->input('destination_port_change_fee') ?? 0,
                'other_penalties' => $request->input('other_penalties') ?? 0,
                'pol' => $request->input('pol'),
                'pod' => $request->input('pod'),
                'custom_charge_desc' => $request->input('custom_charge_desc'),
                'custom_charge_amount' => $request->input('custom_charge_amount') ?? 0,
            ]);
        }

        AuditLog::logAction('INVOICE_UPDATE', 'invoices', $invoice->id, [
            'invoice' => $oldInvoice,
            'booking' => $oldBooking,
        ], [
            'amount' => $invoice->amount,
            'status' => $invoice->status,
            'booking_id' => $booking?->id,
        ]);

        return redirect()->route('accounts.index')->with('success', "تم تعديل تفاصيل الفاتورة وشحنة الشحن {$id} بنجاح");
    }

    private function authorizeFinancialDocumentMutation(Request $request, string $message = 'غير مسموح بتعديل الفواتير وسندات القبض إلا للمدير.'): void
    {
        abort_unless($request->user()?->isManager(), 403, $message);
    }

    private function makeDocumentId(string $prefix): string
    {
        $model = $prefix === 'INV' ? Invoice::class : Voucher::class;

        return SystemId::make($model, $prefix);
    }

    private function recalculateAgentPaymentBalances(int $agentId): void
    {
        $purchaseTotal = (float) Booking::withoutGlobalScope('archived_visibility')
            ->approvedForAccounting()
            ->where('agent_id', $agentId)
            ->where('agent_purchase_amount', '>', 0)
            ->sum('agent_purchase_amount');
        $paidBefore = 0.0;

        Voucher::agentPaymentsForApprovedAccounting()
            ->where('agent_id', $agentId)
            ->orderBy('date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->each(function (Voucher $voucher) use (&$paidBefore, $purchaseTotal) {
                $paidAfter = $paidBefore + (float) $voucher->amount;
                $voucher->update([
                    'paid_before' => $paidBefore,
                    'paid_after' => $paidAfter,
                    'balance_after' => max($purchaseTotal - $paidAfter, 0),
                ]);
                $paidBefore = $paidAfter;
            });
    }

    private function customerStatementTransactions(Customer $customer, string $scope = 'all'): Collection
    {
        $invoiceQuery = Invoice::with('booking')
            ->whereHas('booking', fn ($query) => $query
                ->withoutGlobalScope('archived_visibility')
                ->approvedForAccounting()
                ->where('customer_id', $customer->id));

        if ($scope === 'customs') {
            $invoiceQuery->where('invoice_type', $scope);
        } elseif ($scope === 'freight') {
            $invoiceQuery->where(function ($query) {
                $query->where('invoice_type', 'freight')
                    ->orWhereNull('invoice_type');
            });
        }

        $invoices = $invoiceQuery->get()
            ->map(function (Invoice $invoice) {
                $invoiceType = $invoice->invoice_type ?: 'freight';

                return [
                    'date' => $invoice->issued_at ?: $invoice->created_at,
                    'sort_order' => 10,
                    'document' => $invoice->id,
                    'document_type' => $invoiceType === 'customs' ? 'فاتورة جمارك' : 'فاتورة شحن',
                    'booking_id' => $invoice->booking_id,
                    'description' => $invoice->description ?: ($invoiceType === 'customs'
                        ? 'فاتورة تخليص جمركي للطلب ' . $invoice->booking_id
                        : 'فاتورة مبيعات لطلب الشحن ' . $invoice->booking_id),
                    'debit' => (float) $invoice->amount,
                    'credit' => 0.0,
                ];
            });

        $receiptQuery = Voucher::with(['booking', 'invoice.booking'])
            ->where('type', 'receipt')
            ->where(function ($query) use ($customer) {
                $query
                    ->whereHas('booking', fn ($bookingQuery) => $bookingQuery
                        ->withoutGlobalScope('archived_visibility')
                        ->approvedForAccounting()
                        ->where('customer_id', $customer->id))
                    ->orWhereHas('invoice.booking', fn ($bookingQuery) => $bookingQuery
                        ->withoutGlobalScope('archived_visibility')
                        ->approvedForAccounting()
                        ->where('customer_id', $customer->id));
            });

        if ($scope === 'customs') {
            $receiptQuery->whereHas('invoice', fn ($query) => $query->where('invoice_type', $scope));
        } elseif ($scope === 'freight') {
            $receiptQuery->whereHas('invoice', function ($query) {
                $query->where('invoice_type', 'freight')
                    ->orWhereNull('invoice_type');
            });
        }

        $receipts = $receiptQuery->get()
            ->map(function (Voucher $voucher) {
                $booking = $voucher->booking ?: $voucher->invoice?->booking;

                return [
                    'date' => $voucher->date ?: $voucher->created_at,
                    'sort_order' => 20,
                    'document' => $voucher->id,
                    'document_type' => 'سند قبض',
                    'booking_id' => $booking?->id ?: $voucher->booking_id,
                    'description' => $voucher->description,
                    'debit' => 0.0,
                    'credit' => (float) $voucher->amount,
                ];
            });

        return $invoices->concat($receipts);
    }

    private function agentStatementTransactions(Agent $agent): Collection
    {
        $purchases = Booking::withoutGlobalScope('archived_visibility')
            ->approvedForAccounting()
            ->where('agent_id', $agent->id)
            ->where('agent_purchase_amount', '>', 0)
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'date' => $booking->priced_at ?: $booking->created_at,
                    'sort_order' => 10,
                    'document' => $booking->id,
                    'document_type' => 'شراء وكيل',
                    'booking_id' => $booking->id,
                    'description' => 'شراء من الوكيل لطلب الشحن ' . $booking->id,
                    'debit' => 0.0,
                    'credit' => (float) $booking->agentPurchaseTotal(),
                ];
            });

        $payments = Voucher::with('booking')
            ->agentPaymentsForApprovedAccounting()
            ->where('agent_id', $agent->id)
            ->get()
            ->map(function (Voucher $voucher) {
                return [
                    'date' => $voucher->date ?: $voucher->created_at,
                    'sort_order' => 20,
                    'document' => $voucher->id,
                    'document_type' => 'سند صرف',
                    'booking_id' => $voucher->booking_id,
                    'description' => $voucher->description,
                    'debit' => (float) $voucher->amount,
                    'credit' => 0.0,
                ];
            });

        return $purchases->concat($payments);
    }

    private function prepareStatement(Collection $transactions, string $accountType, string $statementType, ?Carbon $from, ?Carbon $to): array
    {
        $sorted = $transactions
            ->map(function (array $transaction) {
                $transaction['date'] = $transaction['date'] instanceof Carbon
                    ? $transaction['date']->copy()
                    : Carbon::parse($transaction['date']);

                return $transaction;
            })
            ->sortBy(fn (array $transaction) => $transaction['date']->format('Y-m-d H:i:s') . '|' . $transaction['sort_order'] . '|' . $transaction['document'])
            ->values();

        $openingTransactions = $from
            ? $sorted->filter(fn (array $transaction) => $transaction['date']->lt($from))
            : collect();

        $periodTransactions = $sorted
            ->filter(function (array $transaction) use ($from, $to) {
                if ($from && $transaction['date']->lt($from)) {
                    return false;
                }

                if ($to && $transaction['date']->gt($to)) {
                    return false;
                }

                return true;
            })
            ->values();

        $openingBalance = (float) $openingTransactions->sum(fn (array $transaction) => $transaction['debit'] - $transaction['credit']);
        $runningBalance = $openingBalance;

        $periodTransactions = $periodTransactions
            ->map(function (array $transaction) use (&$runningBalance) {
                $runningBalance += (float) $transaction['debit'] - (float) $transaction['credit'];
                $transaction['balance'] = $runningBalance;

                return $transaction;
            })
            ->values();

        $periodDebit = (float) $periodTransactions->sum('debit');
        $periodCredit = (float) $periodTransactions->sum('credit');

        return [
            'account_type' => $accountType,
            'statement_type' => $statementType,
            'transactions' => $periodTransactions,
            'opening_balance' => $openingBalance,
            'period_debit' => $periodDebit,
            'period_credit' => $periodCredit,
            'closing_balance' => $openingBalance + $periodDebit - $periodCredit,
            'transaction_count' => $periodTransactions->count(),
        ];
    }

    private function statusFromPayment(float $paid, float $total): string
    {
        if ($paid <= 0) {
            return 'غير مدفوعة';
        }

        if ($paid >= ($total - 0.01)) {
            return 'مدفوعة';
        }

        return 'مدفوعة جزئياً';
    }
}
