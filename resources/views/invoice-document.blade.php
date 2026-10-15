<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
@php
    $isRtl = app()->getLocale() === 'ar';
    $textStart = $isRtl ? 'text-right' : 'text-left';
    $textEnd = $isRtl ? 'text-left' : 'text-right';
    $spaceStart = $isRtl ? 'pr-1' : 'pl-1';
    $invoiceContainerSummary = $booking->containerSummary() ?: ($booking->container_type ?: __('messages.invoice_doc_container'));
    $invoiceItemPricingLines = $booking->itemPricingLines();
    $invoiceFclBreakdownRows = $booking->fclPricingBreakdown();
    $invoiceFclStandardCharges = data_get($booking->cargo_details, 'fcl_standard_charges', []);
    $invoiceFclStandardCharges = is_array($invoiceFclStandardCharges) ? $invoiceFclStandardCharges : [];
    $invoiceLandPricingLines = $booking->landPricingLines();
    $invoiceRouteMode = trim((string) data_get($booking->route_details, 'mode', ''));
    $invoiceShipmentType = strtoupper((string) $booking->shipment_type);
    $invoiceRouteCategory = strtolower((string) data_get($booking->route_details, 'category', ''));
    $isAirInvoice = $invoiceShipmentType === 'AIR' || $invoiceRouteCategory === 'air';
    $isLandInvoice = $booking->isLandShipment();
    $isDoorToDoorInvoice = $booking->isDoorToDoorShipment();
    $isCustomsInvoice = ($invoice->invoice_type ?: 'freight') === 'customs';
    $invoicePricingBasis = $isCustomsInvoice ? 'service' : $booking->pricingBasis();
    $invoicePricingUnit = \App\Models\Booking::cargoUnitLabel($invoicePricingBasis);
    $invoiceQuantity = $isCustomsInvoice ? 1 : (float) $booking->pricingQuantity();
    $invoiceQuantity = $invoiceQuantity > 0 ? $invoiceQuantity : 1;
    $invoiceCurrency = $invoice->currency ?: $booking->pricingCurrency();
    $invoiceCurrencySymbol = \App\Models\Booking::pricingCurrencySymbolFor($invoiceCurrency);
    $formatInvoiceMoney = static fn ($value): string => $invoiceCurrencySymbol . ' ' . number_format((float) $value, 2);
    $formatInvoiceQuantity = static fn ($value): string => rtrim(rtrim(number_format((float) $value, 2, '.', ','), '0'), '.');
    $invoiceUnitAmount = static fn ($amount): float => ((float) $amount) / $invoiceQuantity;
    $invoiceCustomPricingCharges = $booking->customPricingCharges();
    $invoiceDocumentTitle = $isCustomsInvoice ? __('messages.invoice_doc_customs_title') : __('messages.invoice_doc_title');
    $invoiceSeaFreightDescription = __('messages.invoice_doc_sea_rate_desc', [
        'containers' => $invoiceContainerSummary,
        'pol' => $booking->pol,
        'pod' => $booking->pod,
    ]);

    if (! $isRtl && $invoiceRouteMode !== '') {
        $invoiceSeaFreightDescription = $invoiceRouteMode . ' plus ' . lcfirst($invoiceSeaFreightDescription);
    }

    $invoiceChargeRows = [];

    if ($isCustomsInvoice) {
        $invoiceChargeRows[] = [
            'label' => $invoice->description ?: __('messages.invoice_doc_customs_default_description', ['id' => $booking->id]),
            'quantity' => 1,
            'unit' => 'service',
            'unit_price' => (float) $invoice->amount,
            'amount' => (float) $invoice->amount,
        ];
    } elseif ($invoiceItemPricingLines !== []) {
        foreach ($invoiceItemPricingLines as $line) {
            $invoiceChargeRows[] = [
                'label' => $line['name'],
                'hs_code' => $line['hs_code'] ?? '',
                'quantity' => (float) ($line['quantity'] ?? 0),
                'unit' => $line['unit'] ?? 'piece',
                'unit_price' => (float) ($line['sale_unit_price'] ?? 0),
                'amount' => (float) ($line['sale_total'] ?? 0),
            ];
        }
    } elseif ($isLandInvoice && $invoiceLandPricingLines !== []) {
        foreach ($invoiceLandPricingLines as $line) {
            $plate = ! empty($line['plate_number']) ? ' | ' . $line['plate_number'] : '';
            $invoiceChargeRows[] = [
                'label' => $line['from_city'] . ' → ' . $line['to_city'] . $plate,
                'quantity' => (float) ($line['qty'] ?? 0),
                'unit' => $line['unit'] ?? 'ton',
                'unit_price' => (float) ($line['unit_price'] ?? 0),
                'amount' => (float) ($line['total'] ?? 0),
            ];
        }
    } elseif ($isDoorToDoorInvoice) {
        $doorToDoorPricing = $booking->doorToDoorPricing();
        $invoiceChargeRows[] = [
            'label' => __('messages.door_to_door_service_label'),
            'quantity' => $doorToDoorPricing['quantity'] > 0 ? $doorToDoorPricing['quantity'] : $invoiceQuantity,
            'unit' => $doorToDoorPricing['basis'],
            'unit_price' => $doorToDoorPricing['unit_price'],
            'amount' => $doorToDoorPricing['total'],
        ];
    } elseif ($invoiceFclBreakdownRows !== []) {
        foreach ($invoiceFclBreakdownRows as $line) {
            $invoiceChargeRows[] = [
                'label' => $invoiceSeaFreightDescription . ' - ' . ($line['label'] ?? ''),
                'quantity' => (float) ($line['quantity'] ?? 0),
                'unit' => 'container',
                'unit_price' => (float) ($line['unit_price'] ?? 0),
                'amount' => (float) ($line['amount'] ?? 0),
            ];
        }

        foreach ($invoiceFclStandardCharges as $line) {
            $amount = (float) ($line['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $quantity = (float) ($line['quantity'] ?? $invoiceQuantity);
            $invoiceChargeRows[] = [
                'label' => $line['label'] ?? 'Charge',
                'quantity' => $quantity > 0 ? $quantity : $invoiceQuantity,
                'unit' => 'container',
                'unit_price' => (float) ($line['unit_price'] ?? $invoiceUnitAmount($amount)),
                'amount' => $amount,
            ];
        }
    } elseif ($isAirInvoice) {
        $airPricing = is_array(data_get($booking->cargo_details, 'air_pricing'))
            ? data_get($booking->cargo_details, 'air_pricing')
            : [];
        $airUnitPrices = is_array(data_get($airPricing, 'unit_prices')) ? data_get($airPricing, 'unit_prices') : [];
        $airChargeDefinitions = [
            ['label' => 'الشحن الجوي لكل CBM', 'amount' => $booking->ocean_freight, 'price' => data_get($airUnitPrices, 'air_freight')],
            ['label' => __('messages.invoice_doc_origin_fees'), 'amount' => $booking->origin_charges, 'price' => data_get($airUnitPrices, 'origin_charges')],
            ['label' => __('messages.invoice_doc_dthc'), 'amount' => $booking->dthc, 'price' => data_get($airUnitPrices, 'dthc')],
            ['label' => __('messages.invoice_doc_war_risk'), 'amount' => $booking->war_risk_surcharge, 'price' => data_get($airUnitPrices, 'war_risk_surcharge')],
            ['label' => __('messages.invoice_doc_customs_services'), 'amount' => $booking->other_charges, 'price' => data_get($airUnitPrices, 'other_charges')],
            ['label' => __('messages.invoice_doc_destination_port_change_fee'), 'amount' => $booking->destination_port_change_fee, 'price' => data_get($airUnitPrices, 'destination_port_change_fee')],
            ['label' => __('messages.invoice_doc_other_penalties'), 'amount' => $booking->other_penalties, 'price' => data_get($airUnitPrices, 'other_penalties')],
        ];

        foreach ($airChargeDefinitions as $line) {
            $amount = (float) ($line['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $invoiceChargeRows[] = [
                'label' => $line['label'],
                'quantity' => $invoiceQuantity,
                'unit' => 'cbm',
                'unit_price' => $line['price'] !== null ? (float) $line['price'] : $invoiceUnitAmount($amount),
                'amount' => $amount,
            ];
        }
    } else {
        $standardChargeDefinitions = [
            ['label' => $invoiceSeaFreightDescription, 'amount' => $booking->ocean_freight],
            ['label' => __('messages.invoice_doc_origin_fees'), 'amount' => $booking->origin_charges],
            ['label' => __('messages.invoice_doc_exw_charges'), 'amount' => $booking->exw_charges, 'show' => $booking->shouldShowExwCharges()],
            ['label' => __('messages.invoice_doc_dthc'), 'amount' => $booking->dthc],
            ['label' => __('messages.invoice_doc_war_risk'), 'amount' => $booking->war_risk_surcharge],
            ['label' => __('messages.invoice_doc_customs_services'), 'amount' => $booking->other_charges],
            ['label' => __('messages.invoice_doc_destination_port_change_fee'), 'amount' => $booking->destination_port_change_fee],
            ['label' => __('messages.invoice_doc_other_penalties'), 'amount' => $booking->other_penalties],
        ];

        foreach ($standardChargeDefinitions as $line) {
            $amount = (float) ($line['amount'] ?? 0);
            if (($line['show'] ?? true) !== true || ($amount <= 0 && $invoiceChargeRows !== [])) {
                continue;
            }

            $invoiceChargeRows[] = [
                'label' => $line['label'],
                'quantity' => $invoiceQuantity,
                'unit' => $invoicePricingBasis,
                'unit_price' => $invoiceUnitAmount($amount),
                'amount' => $amount,
            ];
        }
    }

    if ($booking->custom_charge_amount > 0 && $booking->custom_charge_desc) {
        $invoiceChargeRows[] = [
            'label' => $booking->custom_charge_desc,
            'quantity' => 1,
            'unit' => 'service',
            'unit_price' => (float) $booking->custom_charge_amount,
            'amount' => (float) $booking->custom_charge_amount,
        ];
    }

    foreach ($invoiceCustomPricingCharges as $customPricingCharge) {
        $invoiceChargeRows[] = [
            'label' => $customPricingCharge['label'],
            'quantity' => 1,
            'unit' => 'service',
            'unit_price' => (float) ($customPricingCharge['amount'] ?? 0),
            'amount' => (float) ($customPricingCharge['amount'] ?? 0),
        ];
    }
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoiceDocumentTitle }} — {{ $invoice->id }}</title>
    
    <!-- Cairo & Inter Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons CDN -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandSky: '#c6192b', // Sky Blue Accent
                        brandSkyLight: '#e0f2fe',
                    },
                    fontFamily: {
                        sans: ['Inter', 'Cairo', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', 'Cairo', sans-serif;
            background-color: #fff;
            color: #000;
        }
        @media print {
            @page { size: A4 portrait; margin: 6mm; }
            html, body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
            body { font-size: 9px; line-height: 1.2; }
            .print-hidden { display: none !important; }
            .print-single-page { border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 auto !important; width: 100% !important; max-width: 100% !important; overflow: hidden !important; page-break-inside: avoid !important; }
            .invoice-paper { border: none !important; box-shadow: none !important; padding: 0 !important; }
        }
        .invoice-table th, .invoice-table td {
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
        }
        /* Vertical division line matching the image style */
        .invoice-table th:first-child, .invoice-table td:first-child {
            border-left: none;
            text-align: left;
        }
        .invoice-table th:last-child, .invoice-table td:last-child {
            border-right: none;
            text-align: right;
            width: 200px;
        }
        html[dir="rtl"] .invoice-table th:first-child,
        html[dir="rtl"] .invoice-table td:first-child {
            border-right: none;
            border-left: 1px solid #e2e8f0;
            text-align: right;
        }
        html[dir="rtl"] .invoice-table th:last-child,
        html[dir="rtl"] .invoice-table td:last-child {
            border-left: none;
            border-right: 1px solid #e2e8f0;
            text-align: left;
        }
    </style>
    @include('partials.company-document-style')
</head>
<body class="bg-slate-50 py-8 px-4">
    
    <!-- Action bar -->
    <div class="max-w-[210mm] mx-auto mb-6 flex items-center justify-end gap-2 px-4 print-hidden">
        <button onclick="window.print()" class="inline-flex h-10 items-center justify-center rounded-lg bg-brandSky hover:opacity-95 text-white px-4 text-xs font-bold shadow transition-all">
            <i data-lucide="printer" class="ml-2 h-4 w-4"></i>
            <span>{{ __('messages.print_invoice') }}</span>
        </button>
        <button onclick="window.close()" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow transition-all">
            <span>{{ __('messages.close_page') }}</span>
        </button>
    </div>

    <!-- Official Document Sheet -->
    <article class="invoice-paper print-single-page mx-auto max-w-[210mm] bg-white p-12 shadow-lg border border-slate-200 rounded-xl print:max-w-none print:shadow-none print:border-none print:p-0">
        
        @include('partials.company-letterhead')
        <h1 class="mt-5 text-3xl font-extrabold text-brandSky">{{ $invoiceDocumentTitle }}</h1>

        <!-- Sender Info & Metadata Block (Kentucky style layout) -->
        <div class="mt-8 grid grid-cols-12 gap-6 text-[11px] leading-relaxed">
            <!-- Left Info (Sender) -->
            <div class="col-span-6 font-semibold text-slate-700 space-y-0.5">
                <p class="text-xs font-black text-slate-950">{{ __('messages.company_name') }}</p>
                <p class="font-bold text-slate-500 uppercase text-[10px]">{{ __('messages.invoice_doc_freight_forwarder') }}</p>
                <p>{{ __('messages.account_statement_company_address') }}</p>
                <p class="{{ $textStart }}"><bdi dir="ltr">{{ config('company.phones.0.display') }} - {{ config('company.phones.1.display') }}</bdi></p>
                <p>{{ config('company.email') }}</p>
                <p>{{ config('company.website') }}</p>
            </div>

            <!-- Right Info (Metadata matching image) -->
            <div class="col-span-6 grid grid-cols-12 gap-x-2 gap-y-1">
                <!-- Date -->
                <div class="col-span-4 font-bold text-brandSky {{ $textEnd }}">{{ __('messages.invoice_doc_date_label') }}</div>
                <div class="col-span-8 font-semibold text-slate-950">{{ $invoice->issued_at->toDateString() }}</div>
                
                <!-- Invoice -->
                <div class="col-span-4 font-bold text-brandSky {{ $textEnd }}">{{ __('messages.invoice_doc_invoice_label') }}</div>
                <div class="col-span-8 font-mono font-bold text-slate-950">{{ $invoice->id }}</div>
                
                <!-- Customer Name -->
                <div class="col-span-4 font-bold text-brandSky {{ $textEnd }}">{{ __('messages.invoice_doc_customer_name_label') }}</div>
                <div class="col-span-8 font-bold text-slate-950">{{ $booking->customer_company }}</div>

                <!-- Bill to -->
                <div class="col-span-4 font-bold text-brandSky {{ $textEnd }}">{{ __('messages.invoice_doc_bill_to_label') }}</div>
                <div class="col-span-8 font-semibold text-slate-800">{{ $booking->customer_contact_name }}</div>

                <!-- Shipping term -->
                <div class="col-span-4 font-bold text-brandSky {{ $textEnd }}">{{ __('messages.invoice_doc_shipping_term_label') }}</div>
                <div class="col-span-8 font-semibold text-slate-800">{{ data_get($booking->route_details, 'mode') ?: '—' }}</div>

                <!-- POL -->
                <div class="col-span-4 font-bold text-brandSky {{ $textEnd }}">{{ __('messages.invoice_doc_pol_label') }}</div>
                <div class="col-span-8 font-semibold text-slate-800">{{ $booking->pol }}</div>

                <!-- POD -->
                <div class="col-span-4 font-bold text-brandSky {{ $textEnd }}">{{ __('messages.invoice_doc_pod_label') }}</div>
                <div class="col-span-8 font-semibold text-slate-800">{{ $booking->pod }}</div>
            </div>
        </div>

        <!-- Shipping Category Header -->
        <div class="mt-8">
            <h3 class="text-sm font-black text-slate-950 border-b-2 border-slate-950 pb-1 mb-2">
                @if($isCustomsInvoice)
                    {{ __('messages.invoice_doc_customs_services_section') }}
                @elseif($isAirInvoice)
                    {{ __('messages.invoice_doc_air_shipping') }}
                @elseif($isLandInvoice)
                    {{ __('messages.invoice_doc_land_shipping') }}
                @else
                    {{ __('messages.invoice_doc_sea_shipping') }}
                @endif
            </h3>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-[10px] font-bold text-slate-600">
                <span>{{ __('messages.invoice_doc_pricing_basis') }}: <strong class="text-brandSky">{{ $invoicePricingUnit }}</strong></span>
                <span>{{ __('messages.invoice_doc_pricing_quantity') }}: <strong class="text-slate-950" dir="ltr">{{ $formatInvoiceQuantity($invoiceQuantity) }} {{ $invoicePricingUnit }}</strong></span>
            </div>
        </div>

        <!-- Grid Table matching exactly the image -->
        <table class="w-full invoice-table border-collapse border border-slate-200 text-[11px] mt-4">
            <thead>
                <tr class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                    <th class="px-4 py-2 border-r border-slate-200">{{ __('messages.invoice_doc_description_header') }}</th>
                    <th class="px-4 py-2 border-r border-slate-200 text-center">{{ __('messages.invoice_doc_quantity_header') }}</th>
                    <th class="px-4 py-2">{{ __('messages.invoice_doc_unit_price_header') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoiceChargeRows as $invoiceChargeRow)
                <tr class="h-10">
                    <td class="px-4 py-2 text-slate-800 font-semibold border-r border-slate-200">
                        {{ $invoiceChargeRow['label'] }}
                        @if(filled($invoiceChargeRow['hs_code'] ?? null))
                            <span class="text-slate-500" dir="ltr">(HS: {{ $invoiceChargeRow['hs_code'] }})</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center font-bold text-slate-900 border-r border-slate-200" dir="ltr">
                        {{ $formatInvoiceQuantity($invoiceChargeRow['quantity'] ?? 0) }}
                        @if(filled($invoiceChargeRow['unit'] ?? null))
                            <span class="text-slate-500">{{ \App\Models\Booking::cargoUnitLabel($invoiceChargeRow['unit']) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right font-bold text-slate-900" dir="ltr">{{ $formatInvoiceMoney($invoiceChargeRow['unit_price'] ?? 0) }}</td>
                </tr>
                @endforeach
                @php
                    $renderedRows = count($invoiceChargeRows);
                    $emptyLinesNeeded = max(10 - $renderedRows, 3);
                @endphp
                @for($i = 0; $i < $emptyLinesNeeded; $i++)
                <tr class="invoice-spacer h-8">
                    <td class="border-r border-slate-200"></td>
                    <td class="border-r border-slate-200"></td>
                    <td></td>
                </tr>
                @endfor

                <!-- Total Row -->
                <tr class="bg-slate-50 font-black text-slate-950 border-t-2 border-slate-350">
                    <td colspan="2" class="px-4 py-3 {{ $textEnd }} uppercase border-r border-slate-200 text-xs">{{ __('messages.invoice_doc_total_label') }}</td>
                    <td class="px-4 py-3 text-right text-base text-brandSky" dir="ltr">{{ $formatInvoiceMoney($invoice->amount) }}</td>
                </tr>
                <tr class="bg-emerald-50 font-black text-slate-950">
                    <td colspan="2" class="px-4 py-2 text-right uppercase border-r border-slate-200 text-xs">{{ __('messages.paid') }}</td>
                    <td class="px-4 py-2 text-right text-sm text-emerald-700" dir="ltr">{{ $formatInvoiceMoney($invoice->paid_amount) }}</td>
                </tr>
                <tr class="bg-red-50 font-black text-slate-950">
                    <td colspan="2" class="px-4 py-2 text-right uppercase border-r border-slate-200 text-xs">{{ __('messages.balance_due') }}</td>
                    <td class="px-4 py-2 text-right text-sm text-red-600" dir="ltr">{{ $formatInvoiceMoney($invoice->remaining_amount) }}</td>
                </tr>
            </tbody>
        </table>



        <!-- Footer notes matching image -->
        <footer class="mt-8 {{ $textStart }} text-[10px] text-slate-500 font-medium space-y-1 {{ $spaceStart }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
            <p>{{ __('messages.invoice_doc_footer_contact') }}</p>
            <p>{{ __('messages.invoice_doc_footer_assist') }}</p>
            <p>{{ __('messages.invoice_doc_footer_thanks') }}</p>
            <p>{{ __('messages.invoice_doc_footer_future') }}</p>
            <p class="font-extrabold text-slate-900 mt-2 text-[10.5px]">{{ __('messages.invoice_doc_electronic_note') }}</p>
            
            <div class="pt-2">
                <p class="font-extrabold text-slate-950 text-[10.5px]">{{ __('messages.invoice_doc_disclaimer_label') }}</p>
                <p class="font-extrabold text-slate-950 text-[10.5px] mt-0.5">{{ __('messages.invoice_doc_payment_warning') }}</p>
            </div>
        </footer>
        @include('partials.company-document-footer')
    </article>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
