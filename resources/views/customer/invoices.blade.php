@extends('layouts.customer')

@section('title', __('messages.customer_invoices_page_title') . ' — ' . __('messages.site_name'))

@section('content')
@php
    $summaryCards = [
        ['label' => __('messages.customer_invoices_total_count'), 'value' => $stats['total'], 'icon' => 'receipt-text'],
        ['label' => __('messages.customer_invoices_total_due'), 'value' => $stats['amount_label'], 'icon' => 'wallet'],
        ['label' => __('messages.customer_invoices_paid'), 'value' => $stats['paid_label'], 'icon' => 'check-circle-2'],
        ['label' => __('messages.customer_invoices_remaining'), 'value' => $stats['remaining_label'], 'icon' => 'circle-dollar-sign'],
    ];
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-border bg-card p-5 shadow-card">
        <h1 class="text-2xl font-black">{{ __('messages.customer_invoices_heading') }}</h1>
        <p class="mt-2 max-w-2xl text-sm leading-7 text-muted-foreground">
            {{ __('messages.customer_invoices_intro') }}
        </p>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($summaryCards as $card)
            <article class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-secondary text-primary">
                        <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <div class="text-2xl font-black">{{ $card['value'] }}</div>
                </div>
                <div class="mt-3 text-sm font-black">{{ $card['label'] }}</div>
            </article>
        @endforeach
    </section>

    <section class="rounded-xl border border-border bg-card shadow-card">
        <div class="flex flex-col gap-2 border-b border-border p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-black">{{ __('messages.customer_invoices_history_title') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ __('messages.customer_invoices_history_desc') }}</p>
            </div>
            @if($stats['overdue'] > 0)
                <span class="inline-flex h-8 w-fit items-center rounded-full bg-destructive/10 px-3 text-xs font-black text-destructive">{{ $stats['overdue'] }} {{ __('messages.customer_invoices_overdue_label') }}</span>
            @endif
        </div>

        @if($invoices->isEmpty())
            <div class="p-12 text-center text-sm text-muted-foreground">{{ __('messages.customer_invoices_empty_state') }}</div>
        @else
            @include('customer.partials.list-search', ['tableId' => 'portal-invoices', 'unpaidFilter' => true])
            <div class="overflow-x-auto">
                <table id="portal-invoices" data-responsive-table class="w-full min-w-[980px] text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">{{ __('messages.customer_invoices_table_invoice') }}</th>
                            <th class="px-5 py-3">{{ __('messages.customer_invoices_table_shipment_request') }}</th>
                            <th class="px-5 py-3">{{ __('messages.customer_invoices_table_amount') }}</th>
                            <th class="px-5 py-3">{{ __('messages.customer_invoices_table_paid') }}</th>
                            <th class="px-5 py-3">{{ __('messages.customer_invoices_table_remaining') }}</th>
                            <th class="px-5 py-3">{{ __('messages.customer_invoices_table_dates') }}</th>
                            <th class="px-5 py-3">{{ __('messages.customer_invoices_table_status') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                            @php
                                $isLate = $invoice->due_at && $invoice->due_at->isPast() && $invoice->remaining_amount > 0;
                            @endphp
                            <tr data-outstanding="{{ $invoice->remaining_amount > 0 ? 'true' : 'false' }}" class="border-t border-border/50 align-top hover:bg-secondary/20 {{ $isLate ? 'bg-destructive/5' : '' }}">
                                <td class="px-5 py-4">
                                    <div class="font-mono text-xs font-black" dir="ltr">{{ $invoice->id }}</div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">{{ optional($invoice->created_at)->format('Y-m-d') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-mono text-xs font-black" dir="ltr">{{ $invoice->booking_id }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">{{ $invoice->booking?->pol }} {{ __('messages.to_separator') }} {{ $invoice->booking?->pod }}</div>
                                </td>
                                <td class="px-5 py-4 font-black text-primary" dir="ltr">{{ $invoice->formatMoney($invoice->amount) }}</td>
                                <td class="px-5 py-4 font-bold text-success" dir="ltr">{{ $invoice->formatMoney($invoice->paid_amount) }}</td>
                                <td class="px-5 py-4 font-bold {{ $invoice->remaining_amount > 0 ? 'text-destructive' : 'text-success' }}" dir="ltr">{{ $invoice->formatMoney($invoice->remaining_amount) }}</td>
                                <td class="px-5 py-4">
                                    <div class="text-xs">{{ __('messages.customer_invoices_issue_label') }} {{ $invoice->issued_at?->format('Y-m-d') ?: '—' }}</div>
                                    <div class="mt-1 text-xs {{ $isLate ? 'font-black text-destructive' : 'text-muted-foreground' }}">{{ __('messages.customer_invoices_due_label') }} {{ $invoice->due_at?->format('Y-m-d') ?: '—' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-black {{ $invoice->remaining_amount <= 0 ? 'border-success/25 bg-success/15 text-success' : ($isLate ? 'border-destructive/20 bg-destructive/15 text-destructive' : 'border-warning/30 bg-warning/20 text-warning-foreground') }}">
                                        {{ $invoice->remaining_amount <= 0 ? __('messages.customer_invoices_paid_status') : ($isLate ? __('messages.customer_invoices_overdue_status') : $invoice->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    @if($invoice->booking)
                                        <a href="{{ route('customer.shipments.show', $invoice->booking) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border px-3 text-xs font-black hover:bg-secondary">
                                            {{ __('messages.customer_invoices_request_link') }}
                                            <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
