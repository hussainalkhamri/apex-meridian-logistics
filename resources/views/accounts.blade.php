@extends('layouts.app')

@section('title', __('messages.accounts_page_title'))

@section('content')
@php
    $canManageFinancialDocuments = auth()->user()?->isManager();
@endphp
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs text-white border border-white/10">{{ __('messages.accounts_section_badge') }}</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">{{ __('messages.accounts_page_heading') }}</h1>
            <p class="mt-2 max-w-2xl text-white/80">
                {{ __('messages.accounts_page_description') }}
            </p>
            <p class="mt-1 text-xs text-white/60">{{ __('messages.accounts_company_tax_line') }}</p>
        </div>
    </section>

    @if($errors->any())
        <section class="container mx-auto px-4 pt-6">
            <div class="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-foreground">
                <div class="mb-2 flex items-center gap-2 font-bold text-destructive">
                    <i data-lucide="x-circle" class="h-5 w-5"></i>
                    <span>{{ __('messages.accounts_save_error_heading') }}</span>
                </div>
                <ul class="list-disc pr-5 text-xs leading-6">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <!-- Stats Grid -->
    <section class="container mx-auto px-4 py-10">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-border bg-card p-5 shadow-card flex items-center gap-4">
                <span class="grid h-12 w-12 place-items-center rounded-lg bg-accent/10 text-accent">
                    <i data-lucide="receipt" class="h-6 w-6"></i>
                </span>
                <div>
                    <div class="text-2xl font-extrabold text-card-foreground">{{ $invoices->count() }}</div>
                    <div class="text-xs text-muted-foreground">{{ __('messages.accounts_total_invoices') }}</div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-card flex items-center gap-4">
                <span class="grid h-12 w-12 place-items-center rounded-lg bg-warning/10 text-warning">
                    <i data-lucide="clock" class="h-6 w-6"></i>
                </span>
                <div>
                    <div class="text-2xl font-extrabold text-card-foreground">${{ number_format($totalDue) }}</div>
                    <div class="text-xs text-muted-foreground">{{ __('messages.accounts_total_due') }}</div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-card flex items-center gap-4">
                <span class="grid h-12 w-12 place-items-center rounded-lg bg-success/10 text-success">
                    <i data-lucide="check-circle-2" class="h-6 w-6"></i>
                </span>
                <div>
                    <div class="text-2xl font-extrabold text-card-foreground">${{ number_format($totalPaid) }}</div>
                    <div class="text-xs text-muted-foreground">{{ __('messages.accounts_total_paid') }}</div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-card flex items-center gap-4">
                <span class="grid h-12 w-12 place-items-center rounded-lg bg-destructive/10 text-destructive">
                    <i data-lucide="alert-circle" class="h-6 w-6"></i>
                </span>
                <div>
                    <div class="text-2xl font-extrabold text-card-foreground">{{ $overdueCount }}</div>
                    <div class="text-xs text-muted-foreground">{{ __('messages.accounts_overdue') }}</div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
                <button type="button" onclick="openStatementModal()" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-xs font-bold text-primary-foreground shadow hover:opacity-90">
                <i data-lucide="book-open-check" class="h-4 w-4"></i>
                <span>{{ __('messages.account_statement_print_button') }}</span>
            </button>
        </div>

        <!-- Tabs Header -->
        <div class="mt-8 flex flex-wrap gap-2 border-b border-border/80">
            <button onclick="switchTab('invoices')" id="btn-tab-invoices" class="h-10 px-5 font-extrabold text-sm border-b-2 border-accent text-primary transition-all">{{ __('messages.accounts_tab_invoices') }}</button>
            <button onclick="switchTab('vouchers')" id="btn-tab-vouchers" class="h-10 px-5 font-bold text-sm border-b-2 border-transparent text-muted-foreground hover:text-foreground transition-all">{{ __('messages.accounts_tab_vouchers') }}</button>
            <button onclick="switchTab('payments')" id="btn-tab-payments" class="h-10 px-5 font-bold text-sm border-b-2 border-transparent text-muted-foreground hover:text-foreground transition-all">{{ __('messages.accounts_tab_payments') }}</button>
            <button onclick="switchTab('agents')" id="btn-tab-agents" class="h-10 px-5 font-bold text-sm border-b-2 border-transparent text-muted-foreground hover:text-foreground transition-all">{{ __('messages.accounts_tab_agents') }}</button>
            <button onclick="switchTab('customs')" id="btn-tab-customs" class="h-10 px-5 font-bold text-sm border-b-2 border-transparent text-muted-foreground hover:text-foreground transition-all">حسابات الجمارك</button>
        </div>

        <!-- Invoices List Tab Content -->
        <div id="tab-invoices" class="mt-4 rounded-xl border border-border bg-card shadow-card overflow-hidden">
            <div class="p-6">
                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-primary">
                        <i data-lucide="file-text" class="h-5 w-5 text-accent"></i>
                        <span>{{ __('messages.accounts_invoices_record') }}</span>
                    </h2>
                </div>

                @if($invoices->isEmpty())
                    <div class="rounded-xl border border-dashed border-border bg-card py-10 text-center text-muted-foreground text-xs">
                        {{ __('messages.accounts_no_invoices') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-sm">
                            <thead class="bg-secondary/40 text-xs text-muted-foreground">
                                        <tr>
                                            <th class="px-3 py-3">{{ __('messages.invoice_table_invoice_no') }}</th>
                                            <th class="px-3 py-3">{{ __('messages.invoice_table_client') }}</th>
                                            <th class="px-3 py-3">{{ __('messages.invoice_table_shipment_request') }}</th>
                                            <th class="px-3 py-3">{{ __('messages.invoice_table_pricing_details') }}</th>
                                            <th class="px-3 py-3">{{ __('messages.invoice_table_amount') }}</th>
                                            <th class="px-3 py-3">{{ __('messages.invoice_table_paid') }}</th>
                                            <th class="px-3 py-3">{{ __('messages.invoice_table_remaining') }}</th>
                                            <th class="px-3 py-3">{{ __('messages.invoice_table_issued') }}</th>
                                            <th class="px-3 py-3">{{ __('messages.invoice_table_due') }}</th>
                                            <th class="px-3 py-3">{{ __('messages.invoice_table_status') }}</th>
                                            <th class="px-3 py-3"></th>
                                        </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $inv)
                                    @php
                                        $paid = $inv->paid_amount;
                                        $remaining = $inv->remaining_amount;
                                        $isOverdue = $remaining > 0 && $inv->due_at->lt(\Illuminate\Support\Carbon::now());
                                        $invoiceBooking = $inv->booking;
                                        $invoiceItemLines = $invoiceBooking->itemPricingLines();
                                        $invoicePricingUnit = \App\Models\Booking::cargoUnitLabel($invoiceBooking->pricingBasis());
                                        $invoicePricingQuantity = $invoiceBooking->pricingQuantity();
                                        $doorToDoorPricing = $invoiceBooking->isDoorToDoorShipment()
                                            ? $invoiceBooking->doorToDoorPricing()
                                            : null;
                                    @endphp
                                    <tr data-booking-id="{{ $inv->booking_id }}" class="border-b border-border/40 hover:bg-secondary/30 transition-colors">
                                        <td class="px-3 py-3 font-mono text-xs font-semibold">{{ $inv->id }}</td>
                                        <td class="px-3 py-3 font-medium">{{ $inv->booking->customer_company }}</td>
                                        <td class="px-3 py-3 font-mono text-xs text-muted-foreground">{{ $inv->booking_id }}</td>
                                        <td class="px-3 py-3 min-w-[220px] max-w-[360px] text-xs">
                                            @if($invoiceItemLines !== [])
                                                <div class="space-y-1">
                                                    @foreach($invoiceItemLines as $pricingLine)
                                                        <div class="leading-tight">
                                                            <span class="font-bold text-foreground">{{ $pricingLine['name'] }}</span>
                                                            <span class="text-muted-foreground" dir="ltr">
                                                                — {{ rtrim(rtrim(number_format((float) ($pricingLine['quantity'] ?? 0), 2, '.', ','), '0'), '.') }} {{ \App\Models\Booking::cargoUnitLabel($pricingLine['unit'] ?? null) }}
                                                                @ {{ $invoiceBooking->formatPricingMoney((float) ($pricingLine['sale_unit_price'] ?? 0)) }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif($invoiceBooking->isLandShipment() && $invoiceBooking->landPricingLines() !== [])
                                                @foreach($invoiceBooking->landPricingLines() as $pricingLine)
                                                    <div class="leading-tight" dir="ltr">
                                                        {{ rtrim(rtrim(number_format((float) ($pricingLine['qty'] ?? 0), 2, '.', ','), '0'), '.') }} {{ \App\Models\Booking::cargoUnitLabel($pricingLine['unit'] ?? 'ton') }} @ {{ $invoiceBooking->formatPricingMoney((float) ($pricingLine['unit_price'] ?? 0)) }}
                                                        <span class="text-muted-foreground">{{ $pricingLine['from_city'] }} → {{ $pricingLine['to_city'] }}</span>
                                                    </div>
                                                @endforeach
                                            @elseif(($inv->invoice_type ?: 'freight') === 'customs')
                                                <div class="font-bold">{{ __('messages.invoice_doc_customs_services') }}</div>
                                                <div class="text-muted-foreground" dir="ltr">{{ $invoiceBooking->formatPricingMoney((float) $inv->amount) }}</div>
                                            @elseif($invoiceBooking->isDoorToDoorShipment())
                                                <div class="font-bold">{{ __('messages.door_to_door_service_label') }}</div>
                                                <div class="text-muted-foreground" dir="ltr">
                                                    {{ rtrim(rtrim(number_format((float) ($doorToDoorPricing['quantity'] ?? $invoicePricingQuantity), 2, '.', ','), '0'), '.') }} {{ \App\Models\Booking::cargoUnitLabel($doorToDoorPricing['basis'] ?? 'ton') }}
                                                    @ {{ $invoiceBooking->formatPricingMoney((float) ($doorToDoorPricing['unit_price'] ?? ($invoiceBooking->pricingTotal() / max($invoicePricingQuantity, 1)))) }}
                                                </div>
                                            @elseif(strtoupper((string) $invoiceBooking->shipment_type) === 'AIR' || strtolower((string) data_get($invoiceBooking->route_details, 'category')) === 'air')
                                                <div class="font-bold">{{ __('messages.invoice_doc_air_shipping') }}</div>
                                                <div class="text-muted-foreground" dir="ltr">
                                                    {{ rtrim(rtrim(number_format($invoicePricingQuantity, 2, '.', ','), '0'), '.') }} {{ \App\Models\Booking::cargoUnitLabel('cbm') }}
                                                    @ {{ $invoiceBooking->formatPricingMoney($invoiceBooking->pricingTotal() / max($invoicePricingQuantity, 1)) }}
                                                </div>
                                            @elseif($invoiceBooking->fclPricingLines() !== [])
                                                <div class="space-y-1">
                                                    @foreach($invoiceBooking->fclPricingLines() as $pricingLine)
                                                        <div class="text-muted-foreground" dir="ltr">
                                                            {{ rtrim(rtrim(number_format((float) ($pricingLine['qty'] ?? 0), 2, '.', ','), '0'), '.') }} {{ $pricingLine['type'] }} {{ \App\Models\Booking::cargoUnitLabel('container') }}
                                                            @ {{ $invoiceBooking->formatPricingMoney((float) ($pricingLine['unit_price'] ?? 0)) }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="font-bold">{{ $invoiceBooking->containerSummary() ?: $invoicePricingUnit }}</div>
                                                <div class="text-muted-foreground" dir="ltr">
                                                    {{ rtrim(rtrim(number_format($invoicePricingQuantity, 2, '.', ','), '0'), '.') }} {{ $invoicePricingUnit }}
                                                    @ {{ $invoiceBooking->formatPricingMoney($invoiceBooking->pricingTotal() / max($invoicePricingQuantity, 1)) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 font-bold text-primary" dir="ltr">${{ number_format($inv->amount, 2) }}</td>
                                        <td class="px-3 py-3 font-bold text-success" dir="ltr">${{ number_format($paid, 2) }}</td>
                                        <td class="px-3 py-3 font-bold {{ $remaining > 0 ? 'text-destructive' : 'text-success' }}" dir="ltr">${{ number_format($remaining, 2) }}</td>
                                        <td class="px-3 py-3 text-xs text-muted-foreground">{{ $inv->issued_at->toDateString() }}</td>
                                        <td class="px-3 py-3 text-xs {{ $isOverdue ? 'font-bold text-destructive' : 'text-muted-foreground' }}">
                                            {{ $inv->due_at->toDateString() }}
                                            @if($isOverdue)
                                                <span class="text-[9px] bg-destructive/15 text-destructive border border-destructive/20 rounded px-1.5 py-0.5 mr-1 font-bold">{{ __('messages.accounts_invoice_overdue_badge') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            @if($canManageFinancialDocuments)
                                                <form action="{{ route('accounts.updateStatus', ['id' => $inv->id]) }}" method="POST" class="inline">
                                                    @csrf
                                                    <select name="status" onchange="this.form.submit()" class="h-8 px-2 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent w-36">
                                                        <option value="غير مدفوعة" {{ $inv->status === 'غير مدفوعة' ? 'selected' : '' }}>{{ __('messages.status_unpaid') }}</option>
                                                        <option value="مدفوعة جزئياً" {{ $inv->status === 'مدفوعة جزئياً' ? 'selected' : '' }}>{{ __('messages.status_partially_paid') }}</option>
                                                        <option value="مدفوعة" {{ $inv->status === 'مدفوعة' ? 'selected' : '' }}>{{ __('messages.status_paid') }}</option>
                                                    </select>
                                                </form>
                                            @else
                                                <span class="inline-flex h-8 items-center rounded-lg border border-border bg-secondary/40 px-3 text-xs font-bold text-muted-foreground">{{ $inv->status }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-left flex items-center justify-end gap-1">
                                            <button onclick="openDetailModal('{{ $inv->booking_id }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-secondary transition-all" title="{{ __('messages.booking_details_page_title') }}">
                                                <i data-lucide="eye" class="h-4 w-4 text-muted-foreground"></i>
                                            </button>
                                            @if($canManageFinancialDocuments)
                                                <button id="edit-invoice-{{ $inv->id }}" onclick="openEditInvoiceModal('{{ $inv->id }}', '{{ $inv->amount }}', '{{ $inv->issued_at->toDateString() }}', '{{ $inv->due_at->toDateString() }}', '{{ $inv->status }}', '{{ $inv->booking->ocean_freight }}', '{{ $inv->booking->origin_charges }}', '{{ $inv->booking->exw_charges }}', '{{ $inv->booking->isExwSeaShipment() ? 1 : 0 }}', '{{ $inv->booking->dthc }}', '{{ $inv->booking->war_risk_surcharge }}', '{{ $inv->booking->other_charges }}', '{{ $inv->booking->destination_port_change_fee }}', '{{ $inv->booking->other_penalties }}', '{{ addslashes($inv->booking->pol) }}', '{{ addslashes($inv->booking->pod) }}', '{{ addslashes($inv->booking->custom_charge_desc) }}', '{{ $inv->booking->custom_charge_amount }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-secondary transition-all text-muted-foreground hover:text-foreground" title="تعديل الفاتورة">
                                                    <i data-lucide="edit-3" class="h-4 w-4"></i>
                                                </button>
                                            @endif
                                            <a href="{{ route('accounts.invoice', ['id' => $inv->id]) }}" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-secondary transition-all text-muted-foreground hover:text-foreground" title="{{ __('messages.print_invoice') }}">
                                                <i data-lucide="printer" class="h-4 w-4"></i>
                                            </a>
                                            @if($canManageFinancialDocuments)
                                                <form action="{{ route('accounts.destroyInvoice', ['id' => $inv->id]) }}" method="POST" class="inline" onsubmit="return confirm('هل تريد حذف هذه الفاتورة؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" id="delete-invoice-{{ $inv->id }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-destructive/10 transition-all text-muted-foreground hover:text-destructive" title="حذف الفاتورة" aria-label="حذف الفاتورة">
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Customer Receipt Vouchers Tab Content -->
        <div id="tab-vouchers" class="hidden mt-4 rounded-xl border border-border bg-card shadow-card overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-primary">
                        <i data-lucide="file-check" class="h-5 w-5 text-accent"></i>
                        <span>{{ __('messages.accounts_vouchers_record') }}</span>
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="openVoucherModal('receipt')" class="inline-flex h-9 items-center justify-center rounded-lg bg-accent text-accent-foreground px-4 text-xs font-bold shadow hover:opacity-90">
                            <i data-lucide="plus" class="ml-1 h-4 w-4"></i>
                            <span>{{ __('messages.accounts_new_receipt_voucher_btn') }}</span>
                        </button>
                    </div>
                </div>

                @if($vouchers->isEmpty())
                    <div class="rounded-xl border border-dashed border-border bg-card py-10 text-center text-muted-foreground text-xs">
                        {{ __('messages.accounts_no_vouchers') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-sm">
                            <thead class="bg-secondary/40 text-xs text-muted-foreground">
                                <tr>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_voucher_no') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_client') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_invoice') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_shipment_request') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_payment_method') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_amount') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_after_voucher') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_date') }}</th>
                                    <th class="px-3 py-3 text-left"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vouchers as $v)
                                    @php
                                        $receiptEditPayload = [
                                            'id' => $v->id,
                                            'invoice_id' => $v->invoice_id,
                                            'booking_id' => $v->booking_id,
                                            'amount' => (float) $v->amount,
                                            'payment_method' => $v->payment_method,
                                            'payee_payer' => $v->payee_payer,
                                            'description' => $v->description,
                                            'date' => optional($v->date)->toDateString(),
                                            'remaining' => (float) $v->balance_after + (float) $v->amount,
                                        ];
                                    @endphp
                                    <tr @if($v->booking_id) data-booking-id="{{ $v->booking_id }}" @endif class="border-b border-border/40 hover:bg-secondary/30 transition-colors">
                                        <td class="px-3 py-3 font-mono text-xs font-semibold">{{ $v->id }}</td>
                                        <td class="px-3 py-3 font-medium">{{ $v->payee_payer }}</td>
                                        <td class="px-3 py-3 font-mono text-xs text-muted-foreground">{{ $v->invoice_id ?: '—' }}</td>
                                        <td class="px-3 py-3 font-mono text-xs text-muted-foreground">{{ $v->booking_id ?: '—' }}</td>
                                        <td class="px-3 py-3 text-xs">{{ $v->payment_method }}</td>
                                        <td class="px-3 py-3 font-bold text-primary" dir="ltr">${{ number_format($v->amount, 2) }}</td>
                                        <td class="px-3 py-3 text-xs">
                                            @if($v->invoice_id)
                                                <div class="font-bold text-success">{{ __('messages.paid') }}: <span dir="ltr">${{ number_format($v->paid_after, 2) }}</span></div>
                                                <div class="font-bold text-destructive">{{ __('messages.balance_due') }}: <span dir="ltr">${{ number_format($v->balance_after, 2) }}</span></div>
                                            @else
                                                <span class="text-muted-foreground">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-xs text-muted-foreground">{{ $v->date->toDateString() }}</td>
                                        <td class="px-3 py-3 text-left">
                                            @if($canManageFinancialDocuments)
                                                <button type="button" id="edit-receipt-voucher-{{ $v->id }}" data-voucher='@json($receiptEditPayload)' onclick="openReceiptVoucherEditFromButton(this)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-secondary transition-all text-muted-foreground hover:text-foreground" title="تعديل سند القبض">
                                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                                </button>
                                                <form action="{{ route('accounts.destroyVoucher', ['id' => $v->id]) }}" method="POST" class="inline" onsubmit="return confirm('هل تريد حذف هذا سند القبض؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" id="delete-receipt-voucher-{{ $v->id }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-destructive/10 transition-all text-muted-foreground hover:text-destructive" title="حذف سند القبض" aria-label="حذف سند القبض">
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('accounts.showVoucher', ['id' => $v->id]) }}" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-secondary transition-all text-muted-foreground hover:text-foreground" title="{{ __('messages.print_voucher') }}">
                                                <i data-lucide="printer" class="h-4 w-4"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Agent Payment Vouchers Tab Content -->
        <div id="tab-payments" class="hidden mt-4 rounded-xl border border-border bg-card shadow-card overflow-hidden">
            <div class="p-6">
                <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-primary">
                        <i data-lucide="wallet-cards" class="h-5 w-5 text-accent"></i>
                        <span>{{ __('messages.accounts_agent_payments_record') }}</span>
                    </h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-secondary px-3 py-1 text-xs font-bold text-muted-foreground">
                            {{ __('messages.accounts_agent_accounting_note') }}
                        </span>
                        <button onclick="openAgentPaymentVoucherModal()" class="inline-flex h-9 items-center justify-center rounded-lg bg-gradient-accent px-4 text-xs font-bold text-accent-foreground shadow-glow hover:opacity-95 transition-all">
                            <i data-lucide="handshake" class="ml-1 h-4 w-4"></i>
                            <span>{{ __('messages.accounts_new_agent_payment_btn') }}</span>
                        </button>
                    </div>
                </div>

                <div class="mb-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-border bg-secondary/30 p-4">
                        <div class="text-xs font-bold text-muted-foreground">{{ __('messages.accounts_total_agent_purchases') }}</div>
                        <div class="mt-1 text-2xl font-extrabold text-foreground" dir="ltr">${{ number_format($totalAgentPurchases, 2) }}</div>
                    </div>
                    <div class="rounded-lg border border-border bg-secondary/30 p-4">
                        <div class="text-xs font-bold text-muted-foreground">{{ __('messages.accounts_total_agent_paid') }}</div>
                        <div class="mt-1 text-2xl font-extrabold text-success" dir="ltr">${{ number_format($totalAgentPaid, 2) }}</div>
                    </div>
                    <div class="rounded-lg border border-border bg-secondary/30 p-4">
                        <div class="text-xs font-bold text-muted-foreground">{{ __('messages.accounts_total_agent_remaining') }}</div>
                        <div class="mt-1 text-2xl font-extrabold {{ $totalAgentRemaining > 0 ? 'text-destructive' : 'text-success' }}" dir="ltr">${{ number_format($totalAgentRemaining, 2) }}</div>
                    </div>
                </div>

                @if($agentReports->isEmpty())
                    <div class="rounded-xl border border-dashed border-border bg-card py-10 text-center text-muted-foreground text-xs">
                        {{ __('messages.accounts_no_agent_amounts') }}
                    </div>
                @else
                    <div class="mb-8 overflow-x-auto rounded-lg border border-border">
                        <table class="w-full text-right text-sm">
                            <thead class="bg-secondary/50 text-xs text-muted-foreground">
                                <tr>
                                    <th class="px-3 py-3">{{ __('messages.agent_table_agent') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.agent_table_type') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.agent_table_deals_count') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.agent_table_total_purchase') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.agent_table_paid') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.agent_table_remaining') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.agent_table_status') }}</th>
                                    <th class="px-3 py-3 text-left"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($agentReports as $report)
                                    @php
                                        $agent = $report['agent'];
                                        $remaining = (float) $report['remaining'];
                                        $latestPaymentVoucher = $report['latest_payment_voucher'];
                                        $latestPaymentEditPayload = null;

                                        if ($agent && $latestPaymentVoucher) {
                                            $agentPurchaseTotal = (float) ($agentPurchaseTotals[$agent->id] ?? 0);
                                            $agentPaidTotal = (float) ($agentPayments[$agent->id] ?? 0);
                                            $otherPaid = max($agentPaidTotal - (float) $latestPaymentVoucher->amount, 0);
                                            $latestPaymentEditPayload = [
                                                'id' => $latestPaymentVoucher->id,
                                                'booking_id' => $latestPaymentVoucher->booking_id,
                                                'agent_id' => $agent->id,
                                                'agent_name' => $agent->name,
                                                'amount' => (float) $latestPaymentVoucher->amount,
                                                'payment_method' => $latestPaymentVoucher->payment_method,
                                                'payee_payer' => $latestPaymentVoucher->payee_payer,
                                                'description' => $latestPaymentVoucher->description,
                                                'date' => optional($latestPaymentVoucher->date)->toDateString(),
                                                'purchases' => $agentPurchaseTotal,
                                                'other_paid' => $otherPaid,
                                                'available' => max($agentPurchaseTotal - $otherPaid, 0),
                                            ];
                                        }
                                    @endphp
                                    <tr class="border-t border-border/40 hover:bg-secondary/30 transition-colors">
                                        <td class="px-3 py-3 font-bold">{{ optional($agent)->name ?: __('messages.agent_not_specified') }}</td>
                                        <td class="px-3 py-3">
                                            @if(optional($agent)->type === 'local')
                                                <span class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary">{{ __('messages.agent_local_type') }}</span>
                                            @elseif(optional($agent)->type === 'external')
                                                <span class="inline-flex items-center rounded-full bg-accent/10 px-2 py-0.5 text-[10px] font-bold text-accent">{{ __('messages.agent_external_type') }}</span>
                                            @else
                                                <span class="text-muted-foreground">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 font-mono text-xs">{{ $report['deals_count'] }}</td>
                                        <td class="px-3 py-3 font-bold text-foreground" dir="ltr">${{ number_format($report['purchases'], 2) }}</td>
                                        <td class="px-3 py-3 font-bold text-success" dir="ltr">${{ number_format($report['paid'], 2) }}</td>
                                        <td class="px-3 py-3 font-bold {{ $remaining > 0 ? 'text-destructive' : 'text-success' }}" dir="ltr">${{ number_format($remaining, 2) }}</td>
                                        <td class="px-3 py-3">
                                            @if($remaining > 0)
                                                <span class="inline-flex items-center rounded-full bg-warning/15 px-2 py-0.5 text-[10px] font-bold text-foreground">{{ __('messages.due_badge') }}</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-success/15 px-2 py-0.5 text-[10px] font-bold text-success">{{ __('messages.paid_badge') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-left">
                                            @if($latestPaymentVoucher)
                                                <div class="flex items-center justify-end gap-1">
                                                    @if($canManageFinancialDocuments)
                                                        <button type="button" id="edit-agent-voucher-latest-{{ $latestPaymentVoucher->id }}" data-voucher='@json($latestPaymentEditPayload)' onclick="openAgentPaymentVoucherEditFromButton(this)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-secondary transition-all text-muted-foreground hover:text-foreground" title="{{ __('messages.edit_last_agent_voucher') }}">
                                                            <i data-lucide="edit-3" class="h-4 w-4"></i>
                                                        </button>
                                                    @endif
                                                    <a href="{{ route('accounts.showVoucher', ['id' => $latestPaymentVoucher->id]) }}" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-secondary transition-all text-muted-foreground hover:text-foreground" title="{{ __('messages.print_last_agent_voucher') }}">
                                                        <i data-lucide="printer" class="h-4 w-4"></i>
                                                    </a>
                                                </div>
                                            @else
                                                <span class="text-xs text-muted-foreground">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-primary">
                    <i data-lucide="history" class="h-4 w-4 text-accent"></i>
                    <span>{{ __('messages.agent_payment_vouchers_registered') }}</span>
                </h3>

                @if($agentPaymentVouchers->isEmpty())
                    <div class="rounded-xl border border-dashed border-border bg-card py-8 text-center text-muted-foreground text-xs">
                        {{ __('messages.agent_no_vouchers_created') }}
                    </div>
                @else
                    <div class="overflow-x-auto rounded-lg border border-border">
                        <table class="w-full text-right text-sm">
                            <thead class="bg-secondary/40 text-xs text-muted-foreground">
                                <tr>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_voucher_no') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_client') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_shipment_request') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_amount') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_after_voucher') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_balance_after') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_payment_method') }}</th>
                                    <th class="px-3 py-3">{{ __('messages.voucher_table_date') }}</th>
                                    <th class="px-3 py-3 text-left"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($agentPaymentVouchers as $voucher)
                                    @php
                                        $historyAgent = $voucher->agent;
                                        $historyAgentPurchaseTotal = $historyAgent ? (float) ($agentPurchaseTotals[$historyAgent->id] ?? 0) : 0;
                                        $historyAgentPaidTotal = $historyAgent ? (float) ($agentPayments[$historyAgent->id] ?? 0) : 0;
                                        $historyOtherPaid = max($historyAgentPaidTotal - (float) $voucher->amount, 0);
                                        $historyPaymentEditPayload = [
                                            'id' => $voucher->id,
                                            'booking_id' => $voucher->booking_id,
                                            'agent_id' => optional($historyAgent)->id,
                                            'agent_name' => optional($historyAgent)->name ?: $voucher->payee_payer,
                                            'amount' => (float) $voucher->amount,
                                            'payment_method' => $voucher->payment_method,
                                            'payee_payer' => $voucher->payee_payer,
                                            'description' => $voucher->description,
                                            'date' => optional($voucher->date)->toDateString(),
                                            'purchases' => $historyAgentPurchaseTotal,
                                            'other_paid' => $historyOtherPaid,
                                            'available' => max($historyAgentPurchaseTotal - $historyOtherPaid, 0),
                                        ];
                                    @endphp
                                    <tr class="border-t border-border/40 hover:bg-secondary/30 transition-colors">
                                        <td class="px-3 py-3 font-mono text-xs font-semibold">{{ $voucher->id }}</td>
                                        <td class="px-3 py-3 font-bold">{{ optional($voucher->agent)->name ?: $voucher->payee_payer }}</td>
                                        <td class="px-3 py-3 font-mono text-xs text-muted-foreground">{{ $voucher->booking_id ?: '—' }}</td>
                                        <td class="px-3 py-3 font-bold text-foreground" dir="ltr">${{ number_format($voucher->amount, 2) }}</td>
                                        <td class="px-3 py-3 font-bold text-success" dir="ltr">${{ number_format($voucher->paid_after, 2) }}</td>
                                        <td class="px-3 py-3 font-bold {{ $voucher->balance_after > 0 ? 'text-destructive' : 'text-success' }}" dir="ltr">${{ number_format($voucher->balance_after, 2) }}</td>
                                        <td class="px-3 py-3 text-xs">{{ $voucher->payment_method }}</td>
                                        <td class="px-3 py-3 text-xs text-muted-foreground">{{ $voucher->date->toDateString() }}</td>
                                        <td class="px-3 py-3 text-left">
                                            <div class="flex items-center justify-end gap-1">
                                                @if($canManageFinancialDocuments)
                                                    <button type="button" id="edit-agent-voucher-{{ $voucher->id }}" data-voucher='@json($historyPaymentEditPayload)' onclick="openAgentPaymentVoucherEditFromButton(this)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-secondary transition-all text-muted-foreground hover:text-foreground" title="تعديل سند الصرف">
                                                        <i data-lucide="edit-3" class="h-4 w-4"></i>
                                                    </button>
                                                @endif
                                                <a href="{{ route('accounts.showVoucher', ['id' => $voucher->id]) }}" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-secondary transition-all text-muted-foreground hover:text-foreground" title="{{ __('messages.print_voucher') }}">
                                                    <i data-lucide="printer" class="h-4 w-4"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Agents Accounts Tab Content -->
        <div id="tab-agents" class="hidden mt-4 rounded-xl border border-border bg-card shadow-card overflow-hidden">
            <div class="p-6">
                <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-primary">
                        <i data-lucide="handshake" class="h-5 w-5 text-accent"></i>
                        <span>{{ __('messages.accounts_agents_title') }}</span>
                    </h2>
                    <span class="inline-flex items-center rounded-full bg-secondary px-3 py-1 text-xs font-bold text-muted-foreground">
                        {{ __('messages.accounts_agent_linked_requests_count', ['count' => $agentDeals->count()]) }}
                    </span>
                </div>

                <div class="mb-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border border-border bg-secondary/30 p-4">
                        <div class="text-xs font-bold text-muted-foreground">{{ __('messages.accounts_total_agent_sales') }}</div>
                        <div class="mt-1 text-2xl font-extrabold text-primary" dir="ltr">${{ number_format($totalAgentSales, 2) }}</div>
                    </div>
                    <div class="rounded-lg border border-border bg-secondary/30 p-4">
                        <div class="text-xs font-bold text-muted-foreground">{{ __('messages.accounts_total_agent_purchases') }}</div>
                        <div class="mt-1 text-2xl font-extrabold text-foreground" dir="ltr">${{ number_format($totalAgentPurchases, 2) }}</div>
                    </div>
                    <div class="rounded-lg border border-border bg-secondary/30 p-4">
                        <div class="text-xs font-bold text-muted-foreground">{{ __('messages.accounts_total_agent_profit_loss') }}</div>
                        <div class="mt-1 text-2xl font-extrabold {{ $totalAgentProfit >= 0 ? 'text-success' : 'text-destructive' }}" dir="ltr">${{ number_format($totalAgentProfit, 2) }}</div>
                    </div>
                </div>

                @if($agentReports->isNotEmpty())
                    <div class="mb-6 overflow-x-auto rounded-lg border border-border">
                        <table class="w-full text-right text-sm">
                            <thead class="bg-secondary/50 text-xs text-muted-foreground">
                                <tr>
                                    <th class="px-3 py-3">الوكيل</th>
                                    <th class="px-3 py-3">النوع</th>
                                    <th class="px-3 py-3">عدد الطلبات</th>
                                    <th class="px-3 py-3">إجمالي الكمية</th>
                                    <th class="px-3 py-3">الشراء</th>
                                    <th class="px-3 py-3">البيع</th>
                                    <th class="px-3 py-3">الفارق</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($agentReports as $report)
                                    <tr class="border-t border-border/40 hover:bg-secondary/30 transition-colors">
                                        <td class="px-3 py-3 font-bold">{{ optional($report['agent'])->name ?: __('messages.agent_not_specified') }}</td>
                                        <td class="px-3 py-3">
                                            @if(optional($report['agent'])->type === 'local')
                                                <span class="inline-flex items-center rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-bold text-primary">{{ __('messages.agent_local_type') }}</span>
                                            @elseif(optional($report['agent'])->type === 'external')
                                                <span class="inline-flex items-center rounded-full bg-accent/10 px-2 py-0.5 text-[10px] font-bold text-accent">{{ __('messages.agent_external_type') }}</span>
                                            @else
                                                <span class="text-muted-foreground">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 font-mono text-xs">{{ $report['deals_count'] }}</td>
                                        <td class="px-3 py-3 font-mono text-xs">{{ number_format($report['quantity'], 2) }}</td>
                                        <td class="px-3 py-3 font-bold" dir="ltr">${{ number_format($report['purchases'], 2) }}</td>
                                        <td class="px-3 py-3 font-bold text-primary" dir="ltr">${{ number_format($report['sales'], 2) }}</td>
                                        <td class="px-3 py-3 font-bold {{ $report['profit'] >= 0 ? 'text-success' : 'text-destructive' }}" dir="ltr">${{ number_format($report['profit'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($agentDeals->isEmpty())
                    <div class="rounded-xl border border-dashed border-border bg-card py-10 text-center text-muted-foreground text-xs">
                        {{ __('messages.accounts_no_agent_deals') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-right text-sm">
                            <thead class="bg-secondary/40 text-xs text-muted-foreground">
                                <tr>
                                    <th class="px-3 py-3">طلب الشحن</th>
                                    <th class="px-3 py-3">الوكيل</th>
                                    <th class="px-3 py-3">العميل</th>
                                    <th class="px-3 py-3">المسار</th>
                                    <th class="px-3 py-3">الكمية</th>
                                    <th class="px-3 py-3">سعر الشراء</th>
                                    <th class="px-3 py-3">سعر البيع</th>
                                    <th class="px-3 py-3">الربح / الخسارة</th>
                                    <th class="px-3 py-3">تاريخ التسعير</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($agentDeals as $deal)
                                    @php
                                        $profit = $deal->profitAmount();
                                    @endphp
                                    <tr data-booking-id="{{ $deal->id }}" class="border-b border-border/40 hover:bg-secondary/30 transition-colors cursor-pointer" onclick="openDetailModal('{{ $deal->id }}')">
                                        <td class="px-3 py-3 font-mono text-xs font-semibold">{{ $deal->id }}</td>
                                        <td class="px-3 py-3 font-bold">{{ optional($deal->agent)->name ?: '—' }}</td>
                                        <td class="px-3 py-3 font-medium">{{ $deal->customer_company }}</td>
                                        <td class="px-3 py-3 text-xs text-muted-foreground">{{ $deal->pol }} &rarr; {{ $deal->pod }}</td>
                                        <td class="px-3 py-3 font-mono text-xs">{{ number_format($deal->agent_purchase_quantity, 2) }}</td>
                                        <td class="px-3 py-3 font-bold text-foreground" dir="ltr">${{ number_format($deal->agentPurchaseTotal(), 2) }}</td>
                                        <td class="px-3 py-3 font-bold text-primary" dir="ltr">${{ number_format($deal->pricingTotal(), 2) }}</td>
                                        <td class="px-3 py-3 font-bold {{ $profit >= 0 ? 'text-success' : 'text-destructive' }}" dir="ltr">${{ number_format($profit, 2) }}</td>
                                        <td class="px-3 py-3 text-xs text-muted-foreground">{{ $deal->priced_at ? $deal->priced_at->toDateString() : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        @include('partials.accounts-customs-tab')
    </section>
</div>

<!-- VOUCHER CREATION MODAL -->
<div id="voucher-modal" class="hidden fixed inset-0 z-50 bg-background/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-2xl border border-border bg-card p-5 shadow-elegant md:p-7">
        <div class="mb-6 flex items-start gap-3 border-b border-border/70 pb-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-accent/10 text-accent">
                <i data-lucide="file-pen-line" class="h-5 w-5"></i>
            </span>
            <div>
                <h3 id="voucher-modal-title" class="text-lg font-bold text-primary">{{ __('messages.voucher_create_title') }}</h3>
                <p class="mt-1 text-[11px] text-muted-foreground">راجع البيانات ثم احفظ التعديلات لتحديث رصيد الفاتورة.</p>
            </div>
        </div>
        
        <form id="voucher-form" action="{{ route('accounts.storeVoucher') }}" method="POST" class="space-y-5 text-xs">
            @csrf
            <input type="hidden" name="_method" id="voucher-form-method" value="PUT" disabled />
            <input type="hidden" name="type" id="v-type" value="receipt" />
            <input type="hidden" name="agent_id" id="v-agent-id" value="" />

            <div id="v-invoice-wrap" class="space-y-2 rounded-xl border border-accent/20 bg-accent/[0.03] p-4">
                <label class="font-bold text-foreground">{{ __('messages.voucher_modal_invoice_label') }}</label>
                <select name="invoice_id" id="v-invoice" onchange="syncVoucherInvoice()" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-xs focus:outline-none">
                    <option value="">{{ __('messages.select_choose_invoice') }}</option>
                    @foreach($invoices as $inv)
                        @php
                            $paid = $inv->paid_amount;
                            $remaining = $inv->remaining_amount;
                        @endphp
                        <option value="{{ $inv->id }}"
                                data-booking="{{ $inv->booking_id }}"
                                data-customer="{{ $inv->booking->customer_company }}"
                                data-total="{{ $inv->amount }}"
                                data-paid="{{ $paid }}"
                                data-remaining="{{ $remaining }}"
                                {{ $remaining <= 0 ? 'disabled' : '' }}>
                            {{ $inv->id }} — {{ $inv->booking->customer_company }} — المتبقي ${{ number_format($remaining, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="v-agent-wrap" class="hidden space-y-1">
                <label class="font-bold text-foreground">{{ __('messages.voucher_modal_agent_label') }}</label>
                <select id="v-agent-select" onchange="syncVoucherAgent()" class="w-full h-10 border border-warning/40 bg-background px-3 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-warning">
                    <option value="">{{ __('messages.select_choose_agent') }}</option>
                    @foreach($agentReports as $report)
                        @php
                            $agent = $report['agent'];
                            $remaining = (float) $report['remaining'];
                        @endphp
                        @if($agent)
                            <option value="{{ $agent->id }}"
                                    data-name="{{ $agent->name }}"
                                    data-purchases="{{ $report['purchases'] }}"
                                    data-paid="{{ $report['paid'] }}"
                                    data-remaining="{{ $remaining }}"
                                    {{ $remaining <= 0 ? 'disabled' : '' }}>
                                {{ $agent->name }} — المتبقي ${{ number_format($remaining, 2) }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div id="v-invoice-summary" class="hidden grid grid-cols-1 gap-3 rounded-xl border border-border bg-secondary/30 p-3 text-[11px] sm:grid-cols-3">
                <div class="flex min-h-[68px] flex-col items-center justify-center gap-2 rounded-lg border border-border/70 bg-card px-2 py-3 text-center">
                    <div class="font-bold leading-tight text-muted-foreground">{{ __('messages.voucher_invoice_total') }}</div>
                    <div id="v-summary-total" class="text-base font-black leading-none text-primary" dir="ltr">$0.00</div>
                </div>
                <div class="flex min-h-[68px] flex-col items-center justify-center gap-2 rounded-lg border border-success/20 bg-success/[0.03] px-2 py-3 text-center">
                    <div class="font-bold leading-tight text-muted-foreground">{{ __('messages.voucher_invoice_paid_before') }}</div>
                    <div id="v-summary-paid" class="text-base font-black leading-none text-success" dir="ltr">$0.00</div>
                </div>
                <div class="flex min-h-[68px] flex-col items-center justify-center gap-2 rounded-lg border border-destructive/20 bg-destructive/[0.03] px-2 py-3 text-center">
                    <div class="font-bold leading-tight text-muted-foreground">{{ __('messages.voucher_invoice_remaining_before') }}</div>
                    <div id="v-summary-remaining" class="text-base font-black leading-none text-destructive" dir="ltr">$0.00</div>
                </div>
            </div>

            <div id="v-agent-summary" class="hidden grid grid-cols-3 gap-3 rounded-lg border border-warning/30 bg-warning/10 p-3 text-[11px]">
                <div>
                    <div class="font-bold text-muted-foreground">{{ __('messages.voucher_agent_total_purchase') }}</div>
                    <div id="v-agent-total" class="font-black text-foreground" dir="ltr">$0.00</div>
                </div>
                <div>
                    <div class="font-bold text-muted-foreground">{{ __('messages.voucher_agent_paid_before') }}</div>
                    <div id="v-agent-paid" class="font-black text-success" dir="ltr">$0.00</div>
                </div>
                <div>
                    <div class="font-bold text-muted-foreground">{{ __('messages.voucher_agent_remaining_before') }}</div>
                    <div id="v-agent-remaining" class="font-black text-destructive" dir="ltr">$0.00</div>
                </div>
            </div>

            <div class="grid gap-4 border-t border-border/70 pt-5 md:grid-cols-2">
                <div id="v-booking-wrap" class="space-y-2">
                    <label class="font-bold text-foreground">{{ __('messages.voucher_link_to_shipment_optional') }}</label>
                    <select name="booking_id" id="v-booking" onchange="syncVoucherBookingDescription()" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-xs focus:outline-none">
                        <option value="">{{ __('messages.select_none') }}</option>
                        @foreach($bookings as $b)
                            <option value="{{ $b->id }}"
                                    data-agent-id="{{ $b->agent_id }}"
                                    data-purchase="{{ $b->agentPurchaseTotal() }}">
                                {{ $b->id }} ({{ $b->customer_company }}) @if($b->agent) - {{ $b->agent->name }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="font-bold text-foreground">{{ __('messages.voucher_payment_method_label') }}</label>
                    <select name="payment_method" id="v-payment-method" required class="w-full h-10 border border-border bg-background px-3 rounded-lg text-xs focus:outline-none">
                        <option value="بنك الكريمي">{{ __('messages.payment_bank_alkuraimi') }}</option>
                        <option value="كاش / نقداً">{{ __('messages.payment_cash') }}</option>
                        <option value="حوالة بنكية">{{ __('messages.payment_wire') }}</option>
                        <option value="أخرى">{{ __('messages.payment_other') }}</option>
                    </select>
                </div>
            </div>

            <div class="space-y-2">
                <label id="v-payee-payer-label" class="font-bold text-foreground">{{ __('messages.voucher_received_from') }} *</label>
                <input type="text" name="payee_payer" id="v-payee-payer" required placeholder="{{ __('messages.full_name_placeholder') }}" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2 rounded-xl border border-success/25 bg-success/[0.03] p-3">
                    <label class="font-bold text-foreground">{{ __('messages.voucher_amount_usd_label') }}</label>
                    <input type="number" name="amount" id="v-amount" step="0.01" min="0.01" required placeholder="0.00" class="h-11 w-full rounded-lg border border-success/30 bg-background px-3 text-base font-black text-primary focus:outline-none focus:ring-2 focus:ring-success/30" />
                </div>

                <div class="space-y-2">
                    <label class="font-bold text-foreground">{{ __('messages.voucher_date_label') }}</label>
                    <input type="date" name="date" id="v-date" required value="{{ date('Y-m-d') }}" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                </div>
            </div>

            <div class="space-y-2">
                <label class="font-bold text-foreground">{{ __('messages.voucher_description_label') }} *</label>
                <textarea name="description" id="v-description" required rows="3" placeholder="{{ __('messages.voucher_description_placeholder') }}" class="w-full border border-border bg-background p-3 rounded-lg focus:outline-none"></textarea>
            </div>

            <div class="sticky bottom-0 flex items-center justify-end gap-2 border-t border-border/70 bg-card pt-4">
                <button type="button" onclick="closeVoucherModal()" class="h-10 rounded-lg border border-border px-5 font-bold text-foreground transition-all hover:bg-secondary/40">{{ __('messages.cancel') }}</button>
                <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg bg-accent px-5 font-bold text-accent-foreground shadow hover:opacity-90 transition-all">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    <span>{{ __('messages.voucher_save_and_sync') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- INVOICE CREATION MODAL -->
<div id="invoice-modal" class="hidden fixed inset-0 z-50 bg-background/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-xl rounded-xl border border-border bg-card p-6 shadow-elegant">
        <h3 class="text-lg font-bold text-primary mb-4">{{ __('messages.invoice_create_title') }}</h3>

        <form action="{{ route('accounts.storeInvoice') }}" method="POST" class="space-y-4 text-xs">
            @csrf

            <div class="space-y-1">
                <label class="font-bold text-foreground">{{ __('messages.invoice_modal_booking_client_label') }}</label>
                <select name="booking_id" id="new-invoice-booking" required onchange="syncNewInvoiceBooking()" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-xs focus:outline-none">
                    <option value="">{{ __('messages.select_choose_booking') }}</option>
                    @foreach($bookings as $b)
                        <option value="{{ $b->id }}"
                                data-total="{{ $b->pricingTotal() }}"
                                data-customer="{{ $b->customer_company }}">
                            {{ $b->id }} — {{ $b->customer_company }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="font-bold text-foreground">{{ __('messages.invoice_amount_label') }}</label>
                    <input type="number" name="amount" id="new-invoice-amount" step="0.01" min="0.01" required placeholder="0.00" class="w-full h-10 border border-brandSky/40 bg-brandSkyLight/10 font-bold px-3 rounded-lg focus:outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-foreground">{{ __('messages.invoice_issue_date_label') }}</label>
                    <input type="date" name="issued_at" required value="{{ date('Y-m-d') }}" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-foreground">{{ __('messages.invoice_due_date_label') }}</label>
                    <input type="date" name="due_at" required value="{{ date('Y-m-d', strtotime('+14 days')) }}" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                </div>
            </div>

            <div class="space-y-1">
                <label class="font-bold text-foreground">{{ __('messages.invoice_status_label') }}</label>
                <select name="status" required class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none">
                    <option value="غير مدفوعة">{{ __('messages.status_unpaid') }}</option>
                    <option value="مدفوعة جزئياً">{{ __('messages.status_partially_paid') }}</option>
                    <option value="مدفوعة">{{ __('messages.status_paid') }}</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeInvoiceModal()" class="h-9 px-4 rounded-lg border border-border hover:bg-secondary/40 text-foreground transition-all">{{ __('messages.cancel') }}</button>
                <button type="submit" class="h-9 px-4 rounded-lg bg-primary text-primary-foreground font-bold shadow hover:opacity-90 transition-all">{{ __('messages.invoice_create_submit') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- ACCOUNT STATEMENT MODAL -->
<div id="statement-modal" class="hidden fixed inset-0 z-50 bg-background/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-xl rounded-xl border border-border bg-card p-6 shadow-elegant">
        <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-primary">
            <i data-lucide="book-open-check" class="h-5 w-5 text-accent"></i>
            <span>{{ __('messages.statement_create_title') }}</span>
        </h3>

        <form action="{{ route('accounts.statement') }}" method="GET" target="_blank" class="space-y-4 text-xs">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="font-bold text-foreground">{{ __('messages.statement_account_type_label') }}</label>
                    <select name="account_type" id="statement-account-type" required onchange="syncStatementParties()" class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none">
                        <option value="customer">{{ __('messages.account_type_customer') }}</option>
                        <option value="agent">{{ __('messages.account_type_agent') }}</option>
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-foreground">{{ __('messages.statement_type_label') }}</label>
                    <select name="statement_type" required class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none">
                        <option value="detailed">{{ __('messages.statement_type_detailed') }}</option>
                        <option value="summary">{{ __('messages.statement_type_summary') }}</option>
                    </select>
                </div>
            </div>

            <div class="space-y-1">
                <label class="font-bold text-foreground">نطاق كشف حساب العميل</label>
                <select name="statement_scope" class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none">
                    <option value="all">كل الحركات</option>
                    <option value="freight">الشحن والفواتير العامة فقط</option>
                    <option value="customs">الجمارك فقط</option>
                </select>
            </div>

            <div id="statement-customer-wrap" class="space-y-1">
                <label class="font-bold text-foreground">{{ __('messages.statement_customer_label') }}</label>
                <select name="account_id" id="statement-customer-id" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-xs focus:outline-none">
                    <option value="">{{ __('messages.select_choose_customer') }}</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->company }} @if($customer->contact_name) — {{ $customer->contact_name }} @endif</option>
                    @endforeach
                </select>
            </div>

            <div id="statement-agent-wrap" class="hidden space-y-1">
                <label class="font-bold text-foreground">{{ __('messages.statement_agent_label') }}</label>
                <select name="account_id" id="statement-agent-id" disabled required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-xs focus:outline-none">
                    <option value="">{{ __('messages.select_choose_agent') }}</option>
                    @foreach($agents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }} @if($agent->type) — {{ $agent->type === 'local' ? __('messages.agent_local_type') : __('messages.agent_external_type') }} @endif</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="font-bold text-foreground">{{ __('messages.statement_from_date_label') }}</label>
                    <input type="date" name="date_from" class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-foreground">{{ __('messages.statement_to_date_label') }}</label>
                    <input type="date" name="date_to" value="{{ date('Y-m-d') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeStatementModal()" class="h-9 rounded-lg border border-border px-4 text-foreground transition-all hover:bg-secondary/40">{{ __('messages.cancel') }}</button>
                <button type="submit" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-primary px-4 font-bold text-primary-foreground shadow transition-all hover:opacity-90">
                    <i data-lucide="printer" class="h-4 w-4"></i>
                    <span>{{ __('messages.open_statement_for_print') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(tab) {
        const btnInvoices = document.getElementById('btn-tab-invoices');
        const btnVouchers = document.getElementById('btn-tab-vouchers');
        const btnPayments = document.getElementById('btn-tab-payments');
        const btnAgents = document.getElementById('btn-tab-agents');
        const btnCustoms = document.getElementById('btn-tab-customs');
        const tabInvoices = document.getElementById('tab-invoices');
        const tabVouchers = document.getElementById('tab-vouchers');
        const tabPayments = document.getElementById('tab-payments');
        const tabAgents = document.getElementById('tab-agents');
        const tabCustoms = document.getElementById('tab-customs');
        const buttons = [btnInvoices, btnVouchers, btnPayments, btnAgents, btnCustoms];
        const tabs = {
            invoices: tabInvoices,
            vouchers: tabVouchers,
            payments: tabPayments,
            agents: tabAgents,
            customs: tabCustoms
        };

        buttons.forEach(button => {
            button.classList.add('border-transparent', 'text-muted-foreground');
            button.classList.remove('border-accent', 'text-primary');
        });

        Object.values(tabs).forEach(tabElement => tabElement.classList.add('hidden'));

        const activeButton = tab === 'customs' ? btnCustoms : (tab === 'agents' ? btnAgents : (tab === 'payments' ? btnPayments : (tab === 'vouchers' ? btnVouchers : btnInvoices)));
        activeButton.classList.add('border-accent', 'text-primary');
        activeButton.classList.remove('border-transparent', 'text-muted-foreground');
        tabs[tab]?.classList.remove('hidden');
    }

    function formatMoney(value) {
        return '$' + (parseFloat(value) || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function openStatementModal() {
        const modal = document.getElementById('statement-modal');
        syncStatementParties();
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function closeStatementModal() {
        const modal = document.getElementById('statement-modal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function syncStatementParties() {
        const type = document.getElementById('statement-account-type')?.value || 'customer';
        const customerWrap = document.getElementById('statement-customer-wrap');
        const agentWrap = document.getElementById('statement-agent-wrap');
        const customerSelect = document.getElementById('statement-customer-id');
        const agentSelect = document.getElementById('statement-agent-id');
        const isCustomer = type === 'customer';

        customerWrap.classList.toggle('hidden', !isCustomer);
        agentWrap.classList.toggle('hidden', isCustomer);
        customerSelect.disabled = !isCustomer;
        customerSelect.required = isCustomer;
        agentSelect.disabled = isCustomer;
        agentSelect.required = !isCustomer;
    }

    function filterAgentBookingOptions(agentId = '', selectedBookingId = '') {
        const bookingSelect = document.getElementById('v-booking');
        if (!bookingSelect) return;

        const normalizedAgentId = String(agentId || '');

        Array.from(bookingSelect.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const matchesAgent = !normalizedAgentId || String(option.dataset.agentId || '') === normalizedAgentId;
            option.hidden = !matchesAgent;
            option.disabled = !matchesAgent;
        });

        if (selectedBookingId) {
            bookingSelect.value = selectedBookingId;
        }

        const selectedOption = bookingSelect.options[bookingSelect.selectedIndex];
        if (selectedOption?.disabled) {
            bookingSelect.value = '';
        }
    }

    const agentPaymentDescriptionPrefix = @json(__('messages.agent_payment_description_prefix'));

    function buildAgentPaymentDescription(agentName = '', bookingId = '') {
        const base = `${agentPaymentDescriptionPrefix} ${agentName || ''}`.trim();
        return bookingId ? (base + ' ' + @json(__('messages.agent_payment_booking_prefix')) + ' ' + bookingId) : base;
    }

    function shouldUpdateAgentPaymentDescription(value = '') {
        return !value.trim() || value.trim().startsWith(agentPaymentDescriptionPrefix);
    }

    function syncVoucherBookingDescription(force = false) {
        const agentSelect = document.getElementById('v-agent-select');
        const bookingSelect = document.getElementById('v-booking');
        const description = document.getElementById('v-description');
        const payeePayer = document.getElementById('v-payee-payer');
        const agentOption = agentSelect?.options[agentSelect.selectedIndex];
        const agentName = agentOption?.dataset?.name || payeePayer?.value || '';

        if (!description || !agentName) return;

        if (force || shouldUpdateAgentPaymentDescription(description.value)) {
            description.value = buildAgentPaymentDescription(agentName, bookingSelect?.value || '');
        }
    }

    function openInvoiceModal() {
        const modal = document.getElementById('invoice-modal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function closeInvoiceModal() {
        const modal = document.getElementById('invoice-modal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function syncNewInvoiceBooking() {
        const booking = document.getElementById('new-invoice-booking');
        const amount = document.getElementById('new-invoice-amount');
        const option = booking.options[booking.selectedIndex];
        const total = parseFloat(option?.dataset?.total || 0);

        if (total > 0) {
            amount.value = total.toFixed(2);
        }
    }

    function calculateCustomsInvoiceTotal() {
        const government = parseFloat(document.getElementById('customs-government-fees')?.value || 0) || 0;
        const service = parseFloat(document.getElementById('customs-service-fee')?.value || 0) || 0;
        const other = parseFloat(document.getElementById('customs-other-fee')?.value || 0) || 0;
        const total = government + service + other;
        const amount = document.getElementById('customs-invoice-amount');
        const display = document.getElementById('customs-invoice-total-display');

        if (amount) amount.value = total.toFixed(2);
        if (display) display.textContent = formatMoney(total);
    }

    function syncCustomsInvoiceBooking() {
        const select = document.getElementById('customs-invoice-booking');
        const option = select?.options[select.selectedIndex];
        const government = document.getElementById('customs-government-fees');
        const description = document.getElementById('customs-invoice-description');

        if (!option || !option.value) {
            if (government) government.value = '0';
            if (description) description.value = '';
            calculateCustomsInvoiceTotal();
            return;
        }

        const suggested = parseFloat(option.dataset.suggested || option.dataset.fees || 0) || 0;
        if (government) government.value = suggested.toFixed(2);
        if (description) {
            const reference = option.dataset.reference ? ` - مرجع ${option.dataset.reference}` : '';
            description.value = `فاتورة تخليص جمركي للطلب ${option.value}${reference}`;
        }

        calculateCustomsInvoiceTotal();
    }

    function openCustomsInvoiceModal(data = {}) {
        const modal = document.getElementById('customs-invoice-modal');
        const select = document.getElementById('customs-invoice-booking');
        const service = document.getElementById('customs-service-fee');
        const other = document.getElementById('customs-other-fee');

        if (select) {
            select.value = data.booking_id || '';
        }

        if (service) service.value = '0';
        if (other) other.value = '0';
        syncCustomsInvoiceBooking();

        if (data.suggested !== undefined) {
            const government = document.getElementById('customs-government-fees');
            if (government) government.value = (parseFloat(data.suggested || 0) || 0).toFixed(2);
            calculateCustomsInvoiceTotal();
        }

        modal?.classList.remove('hidden');
        if (modal) modal.style.display = 'flex';
    }

    function openCustomsInvoiceModalFromButton(button) {
        openCustomsInvoiceModal({
            booking_id: button.dataset.bookingId || '',
            customer: button.dataset.customer || '',
            reference: button.dataset.reference || '',
            fees: button.dataset.fees || 0,
            suggested: button.dataset.suggested || 0,
        });
    }

    function closeCustomsInvoiceModal() {
        const modal = document.getElementById('customs-invoice-modal');
        modal?.classList.add('hidden');
        if (modal) modal.style.display = 'none';
    }

    function syncVoucherInvoice() {
        const invoice = document.getElementById('v-invoice');
        const booking = document.getElementById('v-booking');
        const payeePayer = document.getElementById('v-payee-payer');
        const amount = document.getElementById('v-amount');
        const summary = document.getElementById('v-invoice-summary');
        const total = document.getElementById('v-summary-total');
        const paid = document.getElementById('v-summary-paid');
        const remaining = document.getElementById('v-summary-remaining');
        const option = invoice.options[invoice.selectedIndex];

        if (!option || !option.value) {
            summary.classList.add('hidden');
            amount.removeAttribute('max');
            return;
        }

        booking.value = option.dataset.booking || '';
        payeePayer.value = option.dataset.customer || payeePayer.value;
        amount.max = option.dataset.remaining || '';
        amount.placeholder = @json(__('messages.remaining_prefix')) + ' ' + formatMoney(option.dataset.remaining);

        total.textContent = formatMoney(option.dataset.total);
        paid.textContent = formatMoney(option.dataset.paid);
        remaining.textContent = formatMoney(option.dataset.remaining);
        summary.classList.remove('hidden');
    }

    function fillAgentPaymentVoucher(data) {
        const agentInput = document.getElementById('v-agent-id');
        const payeePayer = document.getElementById('v-payee-payer');
        const amount = document.getElementById('v-amount');
        const bookingSelect = document.getElementById('v-booking');
        const description = document.querySelector('#voucher-modal textarea[name="description"]');
        const summary = document.getElementById('v-agent-summary');

        if (!data || !data.id) {
            agentInput.value = '';
            payeePayer.value = '';
            amount.value = '';
            amount.placeholder = '0.00';
            if (description) description.value = '';
            summary.classList.add('hidden');
            amount.removeAttribute('max');
            bookingSelect.value = '';
            bookingSelect.disabled = true;
            filterAgentBookingOptions('');
            return;
        }

        agentInput.value = data.id || '';
        payeePayer.value = data.name || '';
        amount.max = data.remaining || '';
        amount.value = data.remaining ? parseFloat(data.remaining).toFixed(2) : '';
        amount.placeholder = @json(__('messages.remaining_prefix')) + ' ' + formatMoney(data.remaining);
        bookingSelect.value = '';
        bookingSelect.disabled = false;
        filterAgentBookingOptions(data.id);

        syncVoucherBookingDescription(true);

        document.getElementById('v-agent-total').textContent = formatMoney(data.purchases);
        document.getElementById('v-agent-paid').textContent = formatMoney(data.paid);
        document.getElementById('v-agent-remaining').textContent = formatMoney(data.remaining);
        summary.classList.remove('hidden');
    }

    function fillAgentPaymentVoucherEdit(data) {
        const agentInput = document.getElementById('v-agent-id');
        const agentSelect = document.getElementById('v-agent-select');
        const payeePayer = document.getElementById('v-payee-payer');
        const amount = document.getElementById('v-amount');
        const method = document.getElementById('v-payment-method');
        const date = document.getElementById('v-date');
        const description = document.getElementById('v-description');
        const bookingSelect = document.getElementById('v-booking');
        const summary = document.getElementById('v-agent-summary');

        agentInput.value = data.agent_id || '';
        agentSelect.value = data.agent_id || '';
        agentSelect.disabled = true;
        bookingSelect.disabled = false;
        filterAgentBookingOptions(data.agent_id || '', data.booking_id || '');
        payeePayer.value = data.payee_payer || data.agent_name || '';
        amount.max = data.available || '';
        amount.value = data.amount ? parseFloat(data.amount).toFixed(2) : '';
        amount.placeholder = @json(__('messages.available_prefix')) + ' ' + formatMoney(data.available);
        method.value = data.payment_method || method.value;
        date.value = data.date || date.value;
        description.value = data.description || '';
        syncVoucherBookingDescription();

        document.getElementById('v-agent-total').textContent = formatMoney(data.purchases);
        document.getElementById('v-agent-paid').textContent = formatMoney(data.other_paid);
        document.getElementById('v-agent-remaining').textContent = formatMoney(data.available);
        summary.classList.remove('hidden');
    }

    function syncVoucherAgent() {
        const select = document.getElementById('v-agent-select');
        const option = select.options[select.selectedIndex];

        if (!option || !option.value) {
            fillAgentPaymentVoucher(null);
            return;
        }

        fillAgentPaymentVoucher({
            id: option.value,
            name: option.dataset.name,
            purchases: option.dataset.purchases,
            paid: option.dataset.paid,
            remaining: option.dataset.remaining,
        });
    }

    function openVoucherModal(type, mode = 'general') {
        const form = document.getElementById('voucher-form');
        const formMethod = document.getElementById('voucher-form-method');
        const modal = document.getElementById('voucher-modal');
        const title = document.getElementById('voucher-modal-title');
        const typeInput = document.getElementById('v-type');
        const agentInput = document.getElementById('v-agent-id');
        const label = document.getElementById('v-payee-payer-label');
        const invoiceWrap = document.getElementById('v-invoice-wrap');
        const invoiceSelect = document.getElementById('v-invoice');
        const agentWrap = document.getElementById('v-agent-wrap');
        const agentSelect = document.getElementById('v-agent-select');
        const bookingSelect = document.getElementById('v-booking');
        const invoiceSummary = document.getElementById('v-invoice-summary');
        const agentSummary = document.getElementById('v-agent-summary');
        const amount = document.getElementById('v-amount');
        const payeePayer = document.getElementById('v-payee-payer');
        const description = document.querySelector('#voucher-modal textarea[name="description"]');

        if (type === 'payment') {
            mode = 'agent';
        }

        form.action = @json(route('accounts.storeVoucher'));
        formMethod.disabled = true;
        formMethod.value = 'PUT';
        typeInput.value = type;
        agentInput.value = '';
        amount.value = '';
        amount.placeholder = '0.00';
        payeePayer.value = '';
        if (description) description.value = '';
        agentSelect.value = '';
        agentSelect.disabled = false;
        agentSelect.required = false;
        agentWrap.classList.add('hidden');
        agentSummary.classList.add('hidden');
        filterAgentBookingOptions('');

        if (type === 'receipt') {
            title.textContent = @json(__('messages.voucher_title_receipt'));
            label.textContent = @json(__('messages.voucher_label_received_from'));
            invoiceWrap.classList.remove('hidden');
            invoiceSelect.required = true;
            bookingSelect.disabled = true;
            syncVoucherInvoice();
        } else {
            title.textContent = @json(__('messages.voucher_title_payment'));
            label.textContent = @json(__('messages.voucher_label_paid_to'));
            invoiceWrap.classList.add('hidden');
            invoiceSelect.required = false;
            invoiceSelect.value = '';
            bookingSelect.disabled = false;
            bookingSelect.value = '';
            invoiceSummary.classList.add('hidden');
            amount.removeAttribute('max');

            if (mode === 'agent') {
                title.textContent = @json(__('messages.voucher_title_agent_payment'));
                agentWrap.classList.remove('hidden');
                agentSelect.required = true;
                bookingSelect.disabled = true;
                filterAgentBookingOptions(agentSelect.value);
                syncVoucherAgent();
            }
        }

        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function openAgentPaymentVoucherModal() {
        openVoucherModal('payment', 'agent');
    }

    function openAgentPaymentVoucherEditFromButton(button) {
        const data = JSON.parse(button.getAttribute('data-voucher') || '{}');
        if (!data.id) return;

        openVoucherModal('payment', 'agent');

        const form = document.getElementById('voucher-form');
        const formMethod = document.getElementById('voucher-form-method');
        const title = document.getElementById('voucher-modal-title');

        form.action = `/accounts/vouchers/${data.id}`;
        formMethod.disabled = false;
        formMethod.value = 'PUT';
        title.textContent = `تعديل سند صرف وكيل ${data.id}`;
        fillAgentPaymentVoucherEdit(data);
    }

    function openReceiptVoucherEditFromButton(button) {
        const data = JSON.parse(button.getAttribute('data-voucher') || '{}');
        if (!data.id) return;

        openVoucherModal('receipt');

        const form = document.getElementById('voucher-form');
        const formMethod = document.getElementById('voucher-form-method');
        const title = document.getElementById('voucher-modal-title');
        const invoiceSelect = document.getElementById('v-invoice');
        const invoiceOption = Array.from(invoiceSelect.options).find(option => option.value === String(data.invoice_id));

        form.action = `/accounts/vouchers/${data.id}`;
        formMethod.disabled = false;
        formMethod.value = 'PUT';
        title.textContent = `تعديل سند قبض ${data.id}`;

        if (invoiceOption) {
            invoiceOption.disabled = false;
            invoiceSelect.value = data.invoice_id || '';
        }

        document.getElementById('v-booking').value = data.booking_id || '';
        document.getElementById('v-payee-payer').value = data.payee_payer || '';
        document.getElementById('v-amount').value = data.amount ? parseFloat(data.amount).toFixed(2) : '';
        document.getElementById('v-amount').max = data.remaining || '';
        document.getElementById('v-payment-method').value = data.payment_method || '';
        document.getElementById('v-date').value = data.date || '';
        document.getElementById('v-description').value = data.description || '';
        syncVoucherInvoice();
        document.getElementById('v-amount').max = data.remaining || '';
    }

    function closeVoucherModal() {
        const modal = document.getElementById('voucher-modal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function openEditInvoiceModal(id, amount, issued, due, status, oceanFreight, originCharges, exwCharges, showExwCharges, dthc, warRisk, other, destinationPortChangeFee, otherPenalties, pol, pod, customDesc, customAmount) {
        const modal = document.getElementById('edit-invoice-modal');
        const form = document.getElementById('edit-invoice-form');
        const showExw = showExwCharges === true || showExwCharges === '1' || showExwCharges === 'true';
        const exwInput = document.getElementById('edit-inv-exw-charges');
        const exwWrapper = document.getElementById('edit-inv-exw-wrapper');
        
        document.getElementById('edit-inv-id-display').value = id;
        document.getElementById('edit-inv-amount').value = amount;
        document.getElementById('edit-inv-issued').value = issued;
        document.getElementById('edit-inv-due').value = due;
        document.getElementById('edit-inv-status').value = status;

        document.getElementById('edit-inv-ocean-freight').value = oceanFreight;
        document.getElementById('edit-inv-origin-charges').value = originCharges;
        exwInput.value = showExw ? (exwCharges || 0) : 0;
        exwInput.disabled = !showExw;
        exwWrapper.classList.toggle('hidden', !showExw);
        document.getElementById('edit-inv-dthc').value = dthc;
        document.getElementById('edit-inv-war-risk').value = warRisk;
        document.getElementById('edit-inv-other-charges').value = other;
        document.getElementById('edit-inv-destination-port-change').value = destinationPortChangeFee || 0;
        document.getElementById('edit-inv-other-penalties').value = otherPenalties || 0;
        document.getElementById('edit-inv-pol').value = pol;
        document.getElementById('edit-inv-pod').value = pod;
        document.getElementById('edit-inv-custom-desc').value = customDesc || '';
        document.getElementById('edit-inv-custom-amount').value = customAmount || 0;
        
        form.action = `/accounts/invoices/${id}`;
        
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function closeEditInvoiceModal() {
        const modal = document.getElementById('edit-invoice-modal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function calculateInvoiceTotal() {
        const ocean = parseFloat(document.getElementById('edit-inv-ocean-freight').value) || 0;
        const origin = parseFloat(document.getElementById('edit-inv-origin-charges').value) || 0;
        const exwInput = document.getElementById('edit-inv-exw-charges');
        const exw = exwInput && !exwInput.disabled ? (parseFloat(exwInput.value) || 0) : 0;
        const dthc = parseFloat(document.getElementById('edit-inv-dthc').value) || 0;
        const war = parseFloat(document.getElementById('edit-inv-war-risk').value) || 0;
        const other = parseFloat(document.getElementById('edit-inv-other-charges').value) || 0;
        const destinationPortChange = parseFloat(document.getElementById('edit-inv-destination-port-change').value) || 0;
        const otherPenalties = parseFloat(document.getElementById('edit-inv-other-penalties').value) || 0;
        const custom = parseFloat(document.getElementById('edit-inv-custom-amount').value) || 0;
        
        document.getElementById('edit-inv-amount').value = (ocean + origin + exw + dthc + war + other + destinationPortChange + otherPenalties + custom).toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const requestedTab = new URLSearchParams(window.location.search).get('accounts_tab');
        if (['invoices', 'vouchers', 'payments', 'agents', 'customs'].includes(requestedTab)) {
            switchTab(requestedTab);
        }
    });
</script>

<!-- EDIT INVOICE MODAL -->
<div id="edit-invoice-modal" class="hidden fixed inset-0 z-50 bg-background/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-xl rounded-xl border border-border bg-card p-6 shadow-elegant text-right overflow-y-auto max-h-[90vh]">
        <h3 class="text-lg font-bold text-primary mb-4">تعديل تفاصيل الفاتورة الصادرة بالكامل</h3>
        
        <form id="edit-invoice-form" method="POST" class="space-y-4 text-xs">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="font-bold text-foreground">رقم الفاتورة</label>
                    <input type="text" id="edit-inv-id-display" disabled class="w-full h-10 border border-border bg-secondary/50 px-3 rounded-lg text-xs font-mono font-bold text-slate-500" />
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-foreground">حالة السداد *</label>
                    <select name="status" id="edit-inv-status" required class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none">
                        <option value="غير مدفوعة">غير مدفوعة</option>
                        <option value="مدفوعة جزئياً">مدفوعة جزئياً</option>
                        <option value="مدفوعة">مدفوعة</option>
                    </select>
                </div>
            </div>

            <!-- Ports editing -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="font-bold text-foreground">ميناء التحميل (POL) *</label>
                    <input type="text" name="pol" id="edit-inv-pol" required class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-foreground">ميناء الوصول (POD) *</label>
                    <input type="text" name="pod" id="edit-inv-pod" required class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                </div>
            </div>

            <!-- Cost breakdown -->
            <div class="border-t border-border/80 pt-3">
                <h4 class="font-bold text-slate-900 text-xs mb-3">تفاصيل الرسوم وتكاليف الشحن ($)</h4>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="font-bold text-foreground">نولون الشحن الأساسي (Freight Rate) *</label>
                        <input type="number" name="ocean_freight" id="edit-inv-ocean-freight" step="0.01" min="0" required oninput="calculateInvoiceTotal()" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-foreground">مصاريف بلد المنشأ (Origin Charges) *</label>
                        <input type="number" name="origin_charges" id="edit-inv-origin-charges" step="0.01" min="0" required oninput="calculateInvoiceTotal()" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-3">
                    <div id="edit-inv-exw-wrapper" class="space-y-1 hidden">
                        <label class="font-bold text-foreground">{{ __('messages.pricing_exw_charges_label') }}</label>
                        <input type="number" name="exw_charges" id="edit-inv-exw-charges" step="0.01" min="0" oninput="calculateInvoiceTotal()" placeholder="0.00" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-foreground">رسوم THC الوصول *</label>
                        <input type="number" name="dthc" id="edit-inv-dthc" step="0.01" min="0" required oninput="calculateInvoiceTotal()" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-foreground">مخاطر الحرب (WRS) *</label>
                        <input type="number" name="war_risk_surcharge" id="edit-inv-war-risk" step="0.01" min="0" required oninput="calculateInvoiceTotal()" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-foreground">{{ __('messages.pricing_other_charges_label') }} *</label>
                        <input type="number" name="other_charges" id="edit-inv-other-charges" step="0.01" min="0" required oninput="calculateInvoiceTotal()" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-3">
                    <div class="space-y-1">
                        <label class="font-bold text-foreground">{{ __('messages.pricing_destination_port_change_fee_label') }}</label>
                        <input type="number" name="destination_port_change_fee" id="edit-inv-destination-port-change" step="0.01" min="0" oninput="calculateInvoiceTotal()" placeholder="0.00" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-foreground">{{ __('messages.pricing_other_penalties_label') }}</label>
                        <input type="number" name="other_penalties" id="edit-inv-other-penalties" step="0.01" min="0" oninput="calculateInvoiceTotal()" placeholder="0.00" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>
                </div>

                <!-- Custom charge description & amount if wanted -->
                <div class="grid grid-cols-2 gap-4 mt-3">
                    <div class="space-y-1">
                        <label class="font-bold text-foreground">وصف رسوم مخصصة إضافية (اختياري)</label>
                        <input type="text" name="custom_charge_desc" id="edit-inv-custom-desc" placeholder="مثال: رسوم فحص وزن زائد" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-foreground">قيمة الرسوم المخصصة الإضافية ($)</label>
                        <input type="number" name="custom_charge_amount" id="edit-inv-custom-amount" step="0.01" min="0" oninput="calculateInvoiceTotal()" placeholder="0.00" class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>
                </div>
            </div>

            <!-- Total Amount & Dates -->
            <div class="border-t border-border/80 pt-3">
                <div class="grid grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <label class="font-bold text-foreground">مبلغ الفاتورة الإجمالي ($) *</label>
                        <input type="number" name="amount" id="edit-inv-amount" step="0.01" min="0" required class="w-full h-10 border border-brandSky/40 bg-brandSkyLight/10 font-bold px-3 rounded-lg focus:outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-foreground">تاريخ الإصدار *</label>
                        <input type="date" name="issued_at" id="edit-inv-issued" required class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>

                    <div class="space-y-1">
                        <label class="font-bold text-foreground">تاريخ الاستحقاق *</label>
                        <input type="date" name="due_at" id="edit-inv-due" required class="w-full h-10 border border-border bg-background px-3 rounded-lg focus:outline-none" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-border/80">
                <button type="button" onclick="closeEditInvoiceModal()" class="h-9 px-4 rounded-lg border border-border hover:bg-secondary/40 text-foreground transition-all">إلغاء</button>
                <button type="submit" class="h-9 px-4 rounded-lg bg-accent text-accent-foreground font-bold shadow hover:opacity-90 transition-all">حفظ ومزامنة كافة التعديلات</button>
            </div>
        </form>
    </div>
</div>

@include('partials.booking-detail-modal')
@endsection
