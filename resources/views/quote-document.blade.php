<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $isLandQuote = ($booking->shipment_type === 'LAND' || $booking->shipping_category === 'land');
        $isAirQuote = ($booking->shipment_type === 'AIR' || strtolower((string) data_get($booking->route_details, 'category')) === 'air');
        $isDoorToDoorQuote = ($booking->shipment_type === 'DOOR_TO_DOOR'
            || data_get($booking->route_details, 'category') === 'door_to_door'
            || data_get($booking->route_details, 'mode') === 'DOOR_TO_DOOR'
            || data_get($booking->cargo_details, 'service_type') === 'door_to_door');
        $isBulkQuote = strtoupper((string) $booking->shipment_type) === 'BULK'
            || strtolower((string) data_get($booking->cargo_details, 'service_type')) === 'bulk';
        $isRoRoQuote = in_array(strtoupper((string) $booking->shipment_type), ['RORO', 'RO-RO'], true)
            || in_array(strtolower((string) data_get($booking->cargo_details, 'service_type')), ['roro', 'ro-ro'], true);
        $quoteTitle = $isLandQuote
            ? __('messages.official_quote_title_land')
            : ($isAirQuote ? __('messages.official_quote_title_air') : ($isDoorToDoorQuote ? __('messages.official_quote_title_door_to_door') : __('messages.official_quote_title')));
    @endphp
    <title>{{ $quoteTitle }} {{ $booking->id }} — {{ __('messages.site_name') }}</title>
    
    <!-- Cairo Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons CDN -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    
    <!-- Tailwind CSS CDN for print preview simplicity -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#26376b', // custom blue
                        accent: '#c6192b',  // custom cyan
                    },
                    fontFamily: {
                        sans: ['Cairo', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        @media print {
            @page { size: A4 portrait; margin: 6mm; }
            html, body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
            body { font-size: 9px; line-height: 1.2; }
            .print-hidden { display: none !important; }
            .print-single-page { border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 auto !important; width: 100% !important; max-width: 100% !important; overflow: hidden !important; page-break-inside: avoid !important; }
            .quote-paper { border: none !important; box-shadow: none !important; padding: 0 !important; }
            .land-quote-paper { font-size: 8.6px !important; line-height: 1.16 !important; }
            .land-quote-paper header { padding-bottom: 0.6rem !important; }
            .land-quote-paper .land-print-logo { height: 2.35rem !important; width: 2.35rem !important; }
            .land-quote-paper .land-print-title { font-size: 1.35rem !important; line-height: 1.05 !important; }
            .land-quote-paper .land-print-quote-id { margin-top: 0.9rem !important; margin-bottom: 0.9rem !important; }
            .land-quote-paper .land-print-party-grid { gap: 0.7rem !important; }
            .land-quote-paper .land-print-party-grid section { padding: 0.6rem 0.7rem !important; }
            .land-quote-paper .land-print-compact-section { margin-top: 0.7rem !important; padding: 0.55rem 0.7rem !important; }
            .land-quote-paper .land-print-compact-section h3 { margin-bottom: 0.25rem !important; padding-bottom: 0.2rem !important; }
            .land-quote-paper .land-print-compact-table th,
            .land-quote-paper .land-print-compact-table td { padding-top: 0.25rem !important; padding-bottom: 0.25rem !important; }
            .land-quote-paper .land-print-pricing { margin-top: 0.8rem !important; }
            .land-quote-paper .land-print-pricing h3 { margin-bottom: 0.3rem !important; }
            .land-quote-paper .land-print-compact-transfer { margin-top: 0.55rem !important; gap: 0.75rem !important; page-break-inside: avoid !important; break-inside: avoid !important; }
            .land-quote-paper .land-print-compact-transfer > div { padding: 0.4rem 0.6rem !important; }
            .land-quote-paper .land-print-signature-line { height: 1.15rem !important; }
        }
    </style>
    @include('partials.company-document-style')
</head>
<body class="bg-background text-foreground py-8 font-sans">
    @php
        $doorToDoorPricing = $booking->doorToDoorPricing();
        $doorToDoorGrossWeightKg = (float) $doorToDoorPricing['gross_weight_kg'];
        $doorToDoorTons = (float) $doorToDoorPricing['tons'];
        $doorToDoorPricingBasis = $doorToDoorPricing['basis'];
        $doorToDoorQuantity = (float) $doorToDoorPricing['quantity'];
        $doorToDoorUnitPrice = (float) $doorToDoorPricing['unit_price'];
        $doorToDoorUnitPriceLabel = $doorToDoorPricingBasis === 'cbm'
            ? __('messages.door_to_door_unit_price_per_cbm_label')
            : __('messages.door_to_door_unit_price_label');
        $doorToDoorQuantityLabel = $doorToDoorPricingBasis === 'cbm'
            ? __('messages.door_to_door_cbm_label')
            : __('messages.door_to_door_tons_label');
        $quoteRouteOriginLabel = $isLandQuote ? __('messages.booking_land_origin_label') : ($isDoorToDoorQuote ? 'من' : ($isAirQuote ? 'مطار الشحن' : 'POL'));
        $quoteRouteDestinationLabel = $isLandQuote ? __('messages.booking_land_destination_label') : ($isDoorToDoorQuote ? 'إلى' : ($isAirQuote ? 'مطار الوصول' : 'POD'));
        $quoteVolumeFieldLabel = $isLandQuote ? __('messages.booking_land_volume_label') : __('messages.volume_cbm_header');
        $quoteVolumeDisplayValue = $isLandQuote
            ? rtrim(rtrim(number_format((float) ($booking->volume_cbm ?? 0), 2, '.', ','), '0'), '.')
            : $booking->volume_cbm . ' CBM';

        $displayLabel = static function ($value): string {
            $value = trim((string) $value);

            if ($value === '') {
                return '—';
            }

            $translationKey = 'messages.' . $value;

            return trans()->has($translationKey) ? __($translationKey) : $value;
        };

        $quotePackageRows = data_get($booking->cargo_details, 'packages', []);
        $quotePackageRows = is_array($quotePackageRows) ? $quotePackageRows : [];
        $quotePackageSummary = collect($quotePackageRows)
            ->map(function ($package) {
                if (! is_array($package)) {
                    return null;
                }

                $quantity = (int) data_get($package, 'quantity', 0);
                $type = trim((string) data_get($package, 'package_type', ''));

                return $quantity > 0 && $type !== '' ? "{$quantity}x {$type}" : null;
            })
            ->filter()
            ->implode(' + ');
        $quotePackageType = trim((string) (data_get($booking->cargo_details, 'cargo.package_type') ?: data_get($booking->cargo_details, 'package_type')));
        $quotePackageCount = (int) (
            data_get($booking->cargo_details, 'cargo.pieces_count')
            ?: data_get($booking->cargo_details, 'pieces_count')
            ?: data_get($booking->cargo_details, 'cargo.packages_count')
            ?: data_get($booking->cargo_details, 'packages_count')
            ?: 0
        );

        if ($quotePackageSummary === '' && $quotePackageType !== '' && $quotePackageCount > 0) {
            $quotePackageSummary = "{$quotePackageCount}x {$quotePackageType}";
        }

        $quoteRoRoDetails = collect([
            data_get($booking->cargo_details, 'vehicle_type'),
            data_get($booking->cargo_details, 'manufacturer'),
            data_get($booking->cargo_details, 'model'),
            data_get($booking->cargo_details, 'year'),
        ])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->implode(' ');
        $quoteRoRoSummary = $quoteRoRoDetails !== '' ? "RoRo - {$quoteRoRoDetails}" : 'RoRo';

        $quoteBulkPieces = (int) data_get($booking->cargo_details, 'pieces', 0);
        $quoteBulkPackaging = trim((string) data_get($booking->cargo_details, 'packaging_type', ''));
        $quoteBulkPackageSummary = $quoteBulkPieces > 0 && $quoteBulkPackaging !== ''
            ? "{$quoteBulkPieces}x {$quoteBulkPackaging}"
            : ($quoteBulkPieces > 0 ? "{$quoteBulkPieces} pcs" : $quoteBulkPackaging);
        $quoteBulkTotalCbm = (float) (
            data_get($booking->cargo_details, 'total_cbm')
            ?: data_get($booking->cargo_details, 'volume_cbm')
            ?: $booking->volume_cbm
            ?: 0
        );
        $quoteBulkCbmSummary = $quoteBulkTotalCbm > 0
            ? rtrim(rtrim(number_format($quoteBulkTotalCbm, 1, '.', ','), '0'), '.') . ' CBM'
            : null;
        $quoteBulkDetails = collect([
            data_get($booking->cargo_details, 'cargo_name'),
            $quoteBulkPackageSummary,
            $quoteBulkCbmSummary,
        ])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->implode(' - ');
        $quoteBulkSummary = $quoteBulkDetails !== '' ? "Bulk - {$quoteBulkDetails}" : 'Bulk';

        $quoteContainerSummary = $isAirQuote
            ? ($quotePackageSummary !== '' ? $quotePackageSummary : 'AIR')
            : ($isDoorToDoorQuote
                ? ($quotePackageSummary !== '' ? $quotePackageSummary : __('messages.door_to_door_service_label'))
                : ($isRoRoQuote
                    ? $quoteRoRoSummary
                    : ($isBulkQuote
                        ? $quoteBulkSummary
                        : ($booking->containerSummary() ?: 'LCL'))));
        $landPricingRows = $isLandQuote ? $booking->landPricingLines() : [];
        $quoteItemPricingRows = $booking->itemPricingLines();
        $quoteFclBreakdownRows = $booking->fclPricingBreakdown();
        $fclStandardCharges = is_array(data_get($booking->cargo_details, 'fcl_standard_charges')) ? data_get($booking->cargo_details, 'fcl_standard_charges') : [];
        $quoteBreakdownHeader = __('messages.charges_breakdown_header');
        $fclBreakdownQuantityTotal = $quoteFclBreakdownRows !== []
            ? collect($quoteFclBreakdownRows)->sum(fn (array $row) => (float) ($row['quantity'] ?? 0))
            : 0;
        $quoteQuantity = (float) (
            $quoteItemPricingRows !== []
                ? collect($quoteItemPricingRows)->sum(fn (array $row) => (float) ($row['quantity'] ?? 0))
                : ($isLandQuote && $booking->landPricingQuantity() > 0
                ? $booking->landPricingQuantity()
                : ($fclBreakdownQuantityTotal > 0
                    ? $fclBreakdownQuantityTotal
                    : ($booking->agent_purchase_quantity ?: $booking->containerTotalQuantity() ?: 1)))
        );
        $quoteQuantity = $quoteQuantity > 0 ? $quoteQuantity : 1;
        $formatQuoteMoney = fn ($value): string => $booking->formatPricingMoney($value);
        $formatQuoteQuantity = static fn ($value): string => rtrim(rtrim(number_format((float) $value, 2, '.', ','), '0'), '.');
        $quoteUnitAmount = static fn ($amount): float => ((float) $amount) / $quoteQuantity;
        $quoteLandPalletCount = $isLandQuote ? (float) ($booking->volume_cbm ?? 0) : 0;
        $quoteLandPalletCount = $quoteLandPalletCount > 0 ? $quoteLandPalletCount : $quoteQuantity;
        $quoteLandPackageCount = $isLandQuote
            ? $booking->landPricingQuantity()
            : (is_numeric($booking->quantity_packages) && (float) $booking->quantity_packages > 0
                ? (float) $booking->quantity_packages
                : $quoteQuantity);
        $quoteLandCartonCount = $isLandQuote ? (int) data_get($booking->cargo_details, 'land_carton_count', 0) : 0;
        $quoteChargeRows = $quoteItemPricingRows !== []
            ? collect($quoteItemPricingRows)->map(function (array $line) {
                $hsCode = trim((string) ($line['hs_code'] ?? ''));
                $unit = \App\Models\Booking::cargoUnitLabel($line['unit'] ?? null);

                return [
                    'label' => $line['name'] . ($hsCode !== '' ? ' | HS: ' . $hsCode : '') . ' | ' . $unit,
                    'amount' => (float) ($line['sale_total'] ?? 0),
                    'unit_price' => (float) ($line['sale_unit_price'] ?? 0),
                    'quantity' => (float) ($line['quantity'] ?? 0),
                    'class' => '',
                    'isWar' => false,
                    'show' => true,
                ];
            })->all()
            : ($isAirQuote
            ? [
                ['label' => 'الشحن الجوي لكل CBM', 'amount' => (float) ($booking->ocean_freight ?? 0), 'unit_price' => (float) data_get($booking->cargo_details, 'air_pricing.unit_prices.air_freight', 0), 'quantity' => (float) ($booking->volume_cbm ?? 0), 'class' => '', 'isWar' => false, 'show' => true],
                ['label' => 'رسوم المنشأ لكل CBM', 'amount' => (float) ($booking->origin_charges ?? 0), 'unit_price' => (float) data_get($booking->cargo_details, 'air_pricing.unit_prices.origin_charges', 0), 'quantity' => (float) ($booking->volume_cbm ?? 0), 'class' => '', 'isWar' => false, 'show' => (float) ($booking->origin_charges ?? 0) > 0],
                ['label' => 'رسوم الوصول لكل CBM', 'amount' => (float) ($booking->dthc ?? 0), 'unit_price' => (float) data_get($booking->cargo_details, 'air_pricing.unit_prices.dthc', 0), 'quantity' => (float) ($booking->volume_cbm ?? 0), 'class' => '', 'isWar' => false, 'show' => (float) ($booking->dthc ?? 0) > 0],
            ]
            : ($isDoorToDoorQuote
            ? [[
                'label' => __('messages.door_to_door_service_label'),
                'amount' => (float) $doorToDoorPricing['total'],
                'unit_price' => $doorToDoorUnitPrice,
                'quantity' => $doorToDoorQuantity,
                'class' => '',
                'isWar' => false,
                'show' => true,
            ]]
            : ($isLandQuote && count($landPricingRows)
                ? collect($landPricingRows)->map(function (array $line) {
                    $plate = ! empty($line['plate_number']) ? ' | لوحة: ' . $line['plate_number'] : '';

                    return [
                        'label' => 'نقل بري: ' . $line['from_city'] . ' → ' . $line['to_city'] . $plate,
                        'amount' => (float) ($line['total'] ?? 0),
                        'unit_price' => (float) ($line['unit_price'] ?? 0),
                        'quantity' => (float) ($line['qty'] ?? 0),
                        'class' => '',
                        'isWar' => false,
                        'show' => true,
                    ];
                })->all()
                : ($quoteFclBreakdownRows !== []
                    ? array_merge($quoteFclBreakdownRows, collect($fclStandardCharges)->filter(fn (array $line) => ! empty($line['amount']) && ! empty($line['unit_price']))->map(fn (array $line) => [
                        'label' => $line['label'] ?? 'Charge',
                        'amount' => (float) ($line['amount'] ?? 0),
                        'unit_price' => (float) ($line['unit_price'] ?? 0),
                        'quantity' => (float) ($line['quantity'] ?? $quoteQuantity),
                        'class' => '',
                        'isWar' => false,
                        'show' => true,
                    ])->all())
                    : [
                        ['label' => __('messages.base_ocean_freight_details'), 'amount' => (float) ($booking->ocean_freight ?? 0), 'class' => '', 'isWar' => false, 'show' => (float) ($booking->ocean_freight ?? 0) > 0],
                        ['label' => __('messages.origin_charges_details'), 'amount' => (float) ($booking->origin_charges ?? 0), 'class' => '', 'isWar' => false, 'show' => (float) ($booking->origin_charges ?? 0) > 0],
                        ['label' => __('messages.exw_charges_details'), 'amount' => (float) ($booking->exw_charges ?? 0), 'class' => '', 'isWar' => false, 'show' => $booking->shouldShowExwCharges()],
                        ['label' => __('messages.dthc_details'), 'amount' => (float) ($booking->dthc ?? 0), 'class' => '', 'isWar' => false, 'show' => (float) ($booking->dthc ?? 0) > 0],
                        ['label' => __('messages.war_risk_details'), 'amount' => (float) ($booking->war_risk_surcharge ?? 0), 'class' => ($booking->war_risk_surcharge > 300 ? 'bg-destructive/10' : ''), 'isWar' => true, 'show' => (float) ($booking->war_risk_surcharge ?? 0) > 0],
                        ['label' => __('messages.other_charges_details'), 'amount' => (float) ($booking->other_charges ?? 0), 'class' => '', 'isWar' => false, 'show' => (float) ($booking->other_charges ?? 0) > 0],
                        ['label' => __('messages.destination_port_change_fee_details'), 'amount' => (float) ($booking->destination_port_change_fee ?? 0), 'class' => '', 'isWar' => false, 'show' => (float) ($booking->destination_port_change_fee ?? 0) > 0],
                        ['label' => __('messages.other_penalties_details'), 'amount' => (float) ($booking->other_penalties ?? 0), 'class' => '', 'isWar' => false, 'show' => (float) ($booking->other_penalties ?? 0) > 0],
                    ]))));
        $quoteChargeRows = array_merge($quoteChargeRows, collect($booking->customPricingCharges())->map(fn (array $line) => [
            'label' => $line['label'],
            'amount' => (float) ($line['amount'] ?? 0),
            'unit_price' => (float) ($line['amount'] ?? 0),
            'quantity' => 1,
            'class' => '',
            'isWar' => false,
            'show' => true,
        ])->all());
        $quoteValidityDate = $booking->validity_date
            ? $booking->validity_date->format('d-m-Y')
            : '';
        $quoteValidityDisplayDate = $quoteValidityDate !== '' ? $quoteValidityDate : '—';
        $quoteTerms = $isLandQuote
            ? [
                __('messages.land_term_1'),
                __('messages.land_term_2', ['date' => $quoteValidityDate]),
                __('messages.land_term_4'),
            ]
            : ($isDoorToDoorQuote ? [
                __('messages.door_to_door_term_1'),
                __('messages.door_to_door_term_2'),
                __('messages.door_to_door_term_3'),
                __('messages.door_to_door_term_6'),
                __('messages.door_to_door_term_7'),
                __('messages.door_to_door_term_8'),
                __('messages.door_to_door_term_10'),
                __('messages.door_to_door_term_11'),
                __('messages.door_to_door_term_12'),
            ] : [
                __('messages.term_1'),
                __('messages.term_2', ['date' => $quoteValidityDate]),
                __('messages.term_4'),
            ]);
    @endphp
    
    <!-- Action bar -->
    <div class="max-w-[210mm] mx-auto mb-6 flex items-center justify-end gap-2 px-4 print-hidden">
            <div class="mr-auto flex items-center gap-1.5 bg-secondary/50 p-1 rounded-lg border">
            <span class="text-[10px] font-bold text-muted-foreground px-2">{{ __('messages.theme_label') }}</span>
            <a href="{{ route('quote.show', ['id' => $booking->id]) }}" class="inline-flex h-8 items-center justify-center rounded-md bg-primary text-white text-[11px] font-bold px-3 shadow-sm">{{ __('messages.theme_default') }}</a>
            <a href="{{ route('quote.show', ['id' => $booking->id]) }}?template=modern" class="inline-flex h-8 items-center justify-center rounded-md hover:bg-card text-foreground text-[11px] font-bold px-3 transition">{{ __('messages.theme_modern') }}</a>
        </div>
        <button onclick="printQuote(true)" class="inline-flex h-10 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold text-foreground hover:bg-secondary/40 shadow-sm transition-all">
            <i data-lucide="printer" class="ml-2 h-4 w-4"></i>
            <span>{{ __('messages.print_quote_summary') }}</span>
        </button>
        <button onclick="printQuote(false)" class="inline-flex h-10 items-center justify-center rounded-lg bg-primary text-white px-4 text-xs font-bold shadow hover:opacity-95 transition-all">
            <i data-lucide="printer" class="ml-2 h-4 w-4"></i>
            <span>{{ __('messages.print_quote_detailed') }}</span>
        </button>
        <button onclick="window.close()" class="inline-flex h-10 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold text-foreground hover:bg-secondary/40 shadow-sm transition-all">
            <span>{{ __('messages.close_page') }}</span>
        </button>
    </div>

    <!-- Official Document Sheet -->
    <article class="quote-paper print-single-page {{ $isLandQuote ? 'land-quote-paper' : '' }} mx-auto max-w-[210mm] bg-card p-12 text-[12px] leading-relaxed text-foreground shadow-md border border-border rounded-xl print:max-w-none print:shadow-none print:border-none print:p-0">
        
        <!-- Header -->
        @include('partials.company-letterhead')

        <!-- Document Identity -->
        <div class="my-6 {{ $isLandQuote ? 'land-print-quote-id' : '' }} flex items-end justify-between">
            <div>
                <h2 class="land-print-title text-2xl font-black text-foreground tracking-tight">{{ $quoteTitle }}</h2>
                <p class="text-xs text-muted-foreground font-bold uppercase tracking-wider">OFFICIAL FREIGHT QUOTATION</p>
            </div>
            <table class="text-xs">
                <tbody>
                    <tr><td class="px-2 py-1 text-muted-foreground">{{ __('messages.quote_number') }}:</td><td class="px-2 py-1 font-bold">{{ $booking->id }}</td></tr>
                    <tr><td class="px-2 py-1 text-muted-foreground">{{ __('messages.date') }}:</td><td class="px-2 py-1">{{ $booking->priced_at ? $booking->priced_at->toDateString() : date('Y-m-d') }}</td></tr>
                    <tr><td class="px-2 py-1 text-muted-foreground">{{ __('messages.valid_until') }}:</td><td class="px-2 py-1 font-bold text-red-650">{{ $quoteValidityDisplayDate }}</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Customer & Route -->
        <div class="land-print-party-grid grid gap-4 sm:grid-cols-2 print:grid-cols-2">
            <section class="rounded-xl border border-border/80 p-4 bg-secondary/30">
                <h3 class="mb-2 text-[10px] font-bold uppercase tracking-wider text-muted-foreground border-b pb-1">{{ __('messages.recipient_client') }}</h3>
                <p class="font-extrabold text-foreground">{{ $booking->customer_company }}</p>
                <p>{{ __('messages.salutation') }} {{ $booking->customer_contact_name }}</p>
                <p dir="ltr" class="text-right text-xs text-muted-foreground mt-1 font-semibold">{{ __('messages.phone_label') }}: {{ $booking->customer_phone }}</p>
                <p dir="ltr" class="text-right text-xs text-muted-foreground font-semibold">{{ __('messages.email_label') }}: {{ $booking->customer_email }}</p>
            </section>
            
            <section class="rounded-xl border border-border/80 p-4 bg-secondary/30">
                <h3 class="mb-2 text-[10px] font-bold uppercase tracking-wider text-muted-foreground border-b pb-1">{{ __('messages.route_details_header') }}</h3>
                <div class="grid gap-2 sm:grid-cols-2 print:grid-cols-2">
                    <div><span class="text-muted-foreground font-medium">{{ $quoteRouteOriginLabel }}:</span> <strong class="text-foreground">{{ $displayLabel($booking->pol) }}</strong></div>
                    <div><span class="text-muted-foreground font-medium">{{ $quoteRouteDestinationLabel }}:</span> <strong class="text-foreground">{{ $displayLabel($booking->pod) }}</strong></div>
                    <div><span class="text-muted-foreground font-medium">{{ __('messages.transit_duration') }}:</span> <strong class="text-foreground">{{ $booking->transit_days }} {{ __('messages.days') }}</strong></div>
                    <div><span class="text-muted-foreground font-medium">{{ __('messages.type_label') }}:</span> <strong class="text-foreground">{{ $booking->shipment_type }}</strong></div>
                </div>
            </section>
        </div>

        <!-- Cargo info -->
        <section class="land-print-compact-section mt-4 rounded-xl border border-border p-4">
            <h3 class="mb-2 text-[10px] font-bold uppercase tracking-wider text-muted-foreground border-b pb-1">{{ __('messages.declared_cargo_details') }}</h3>
            <table class="land-print-compact-table w-full text-right text-xs">
                <thead>
                    <tr class="text-muted-foreground border-b">
                        <th class="py-1">{{ __('messages.cargo_description') }}</th>
                        @if($isLandQuote)
                            <th class="py-1">{{ __('messages.total_weight') }}</th>
                            <th class="py-1">{{ __('messages.booking_land_volume_label') }}</th>
                            @if($quoteLandCartonCount > 0)
                                <th class="py-1">{{ __('messages.booking_land_carton_count_label') }}</th>
                            @endif
                            <th class="py-1">{{ __('messages.door_to_door_tons_label') }}</th>
                        @else
                            <th class="py-1">{{ __('messages.packaging_size') }}</th>
                            <th class="py-1">{{ __('messages.total_weight') }}</th>
                            <th class="py-1">{{ $quoteVolumeFieldLabel }}</th>
                        @endif
                        <th class="py-1">{{ __('messages.dangerous_dg_status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="py-2 font-bold">{{ $booking->goods_description }}</td>
                        @if($isLandQuote)
                            <td class="py-2">{{ number_format($booking->weight_kg) }} {{ __('messages.kg') }}</td>
                            <td class="py-2 font-bold" dir="ltr">{{ $formatQuoteQuantity($quoteLandPalletCount) }}</td>
                            @if($quoteLandCartonCount > 0)
                                <td class="py-2 font-bold" dir="ltr">{{ $formatQuoteQuantity($quoteLandCartonCount) }}</td>
                            @endif
                            <td class="py-2 font-bold" dir="ltr">{{ $formatQuoteQuantity($quoteLandPackageCount) }}</td>
                        @else
                            <td class="py-2">{{ $quoteContainerSummary }}</td>
                            <td class="py-2">{{ number_format($booking->weight_kg) }} {{ __('messages.kg') }}</td>
                            <td class="py-2">{{ $quoteVolumeDisplayValue }}</td>
                        @endif
                        <td class="py-2">{{ $booking->dangerous ? __('messages.yes_dangerous') : __('messages.no_dangerous') }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Pricing details -->
        <section class="land-print-pricing mt-6">
            <h3 id="breakdown-header" class="mb-2 text-xs font-bold text-foreground">{{ $quoteBreakdownHeader }}</h3>
            <table class="land-print-compact-table w-full overflow-hidden rounded-xl border border-border text-xs">
                <thead class="bg-primary text-white">
                    <tr class="breakdown-row">
                        <th class="px-3 py-2 text-right">{{ $isLandQuote ? 'المسار' : ($isDoorToDoorQuote ? __('messages.door_to_door_service_label') : __('messages.charge_item_label')) }}</th>
                        <th class="px-3 py-2 text-left">{{ $isDoorToDoorQuote ? $doorToDoorUnitPriceLabel : ($isLandQuote ? __('messages.door_to_door_unit_price_label') : __('messages.quote_unit_price_label')) }}</th>
                        <th class="px-3 py-2 text-center">{{ $isDoorToDoorQuote ? $doorToDoorQuantityLabel : ($isLandQuote ? __('messages.door_to_door_tons_label') : __('messages.quote_quantity_label')) }}</th>
                        <th class="px-3 py-2 text-left">{{ __('messages.quote_total_label') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($quoteChargeRows as $row)
                        @continue(! ($row['show'] ?? true))
                        <tr class="breakdown-row {{ $row['class'] }}">
                            <td class="px-3 py-2 text-muted-foreground">{{ $row['label'] }}</td>
                            <td class="px-3 py-2 text-left font-bold {{ ($row['isWar'] ?? false) ? 'text-destructive' : '' }}" dir="ltr">{{ $formatQuoteMoney($row['unit_price'] ?? $quoteUnitAmount($row['amount'])) }}</td>
                            <td class="px-3 py-2 text-center font-bold" dir="ltr">{{ $formatQuoteQuantity($row['quantity'] ?? $quoteQuantity) }}</td>
                            <td class="px-3 py-2 text-left font-bold {{ ($row['isWar'] ?? false) ? 'text-destructive' : '' }}" dir="ltr">{{ $formatQuoteMoney($row['amount']) }}</td>
                        </tr>
                    @endforeach
                    @if($quoteFclBreakdownRows !== [])
                        <tr class="bg-secondary/30 font-black">
                            <td class="px-3 py-3 text-sm text-foreground">إجمالي الحاويات المختلطة</td>
                            <td class="px-3 py-3 text-left text-base text-foreground" dir="ltr">-</td>
                            <td class="px-3 py-3 text-center text-base text-foreground" dir="ltr">{{ $formatQuoteQuantity($quoteQuantity) }}</td>
                            <td class="px-3 py-3 text-left text-base text-foreground" dir="ltr">{{ $formatQuoteMoney($booking->pricingTotal()) }}</td>
                        </tr>
                    @endif
                    <tr class="bg-secondary/40 font-black">
                        <td class="px-3 py-3 text-sm text-foreground">{{ __('messages.final_total_freight') }}</td>
                        <td class="px-3 py-3 text-left text-base text-foreground" dir="ltr">{{ $isDoorToDoorQuote ? $formatQuoteMoney($doorToDoorUnitPrice) : ($quoteFclBreakdownRows !== [] ? '-' : $formatQuoteMoney($quoteUnitAmount($booking->pricingTotal()))) }}</td>
                        <td class="px-3 py-3 text-center text-base text-foreground" dir="ltr">{{ $isDoorToDoorQuote ? $formatQuoteQuantity($doorToDoorQuantity) : $formatQuoteQuantity($quoteQuantity) }}</td>
                        <td class="px-3 py-3 text-left text-base text-foreground" dir="ltr">{{ $formatQuoteMoney($booking->pricingTotal()) }}</td>
                    </tr>
                </tbody>
            </table>
            @if($booking->pricing_notes)
                <p class="mt-3 text-[11px] text-muted-foreground"><strong>{{ __('messages.quote_notes') }}:</strong> {{ $booking->pricing_notes }}</p>
            @endif
        </section>

        <!-- Terms and conditions -->
        <section class="land-print-compact-section mt-6 rounded-xl bg-secondary/40 p-4 text-[10px] leading-5 text-muted-foreground border border-border">
            <h3 class="mb-1 text-xs font-bold text-foreground">{{ __('messages.terms_conditions_header') }}</h3>
            <ul class="list-disc space-y-0.5 pr-5">
                @foreach($quoteTerms as $term)
                    <li>{{ $term }}</li>
                @endforeach
            </ul>
        </section>

        <!-- Wire transfer info & Stamp signature -->
        <section class="land-print-compact-transfer mt-6 grid gap-4 sm:grid-cols-2 print:grid-cols-2">
            <div class="rounded-xl border border-border/80 p-3 text-[10px] bg-secondary/30 leading-relaxed">
                <h4 class="mb-1 font-bold text-foreground">{{ __('messages.wire_transfer_details') }}</h4>
                    <p class="font-extrabold text-foreground">{{ __('messages.company_name') }}</p>
                <p dir="ltr" class="font-semibold text-slate-600">+20 1553818989</p>
            </div>
            <div class="flex flex-col items-end justify-between">
                <div class="text-center">
                    <div class="land-print-signature-line h-14 w-40 border-b border-border"></div>
                    <p class="mt-1 text-[10px] text-muted-foreground font-bold">{{ __('messages.signature_stamp_ops') }}</p>
                </div>
            </div>
        </section>


        @include('partials.company-document-footer')
    </article>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });

        function printQuote(hideDetails) {
            const breakdownRows = document.querySelectorAll('.breakdown-row');
            const breakdownHeader = document.getElementById('breakdown-header');
            
            if (hideDetails) {
                breakdownRows.forEach(row => row.style.display = 'none');
                if (breakdownHeader) breakdownHeader.style.display = 'none';
            } else {
                breakdownRows.forEach(row => row.style.display = '');
                if (breakdownHeader) breakdownHeader.style.display = '';
            }
            
            window.print();
            
            // Restore visibility after printing starts (gives time for browser spooling)
            setTimeout(() => {
                breakdownRows.forEach(row => row.style.display = '');
                if (breakdownHeader) breakdownHeader.style.display = '';
            }, 1000);
        }
    </script>
</body>
</html>
