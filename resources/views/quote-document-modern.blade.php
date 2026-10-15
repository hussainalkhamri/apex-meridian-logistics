<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $quoteTitle = ($booking->shipment_type === 'LAND' || $booking->shipping_category === 'land')
            ? __('messages.official_quote_title_land')
            : __('messages.official_quote_title');
    @endphp
    <title>{{ $quoteTitle }} {{ $booking->id }} — {{ __('messages.site_name') }}</title>
    
    <!-- Fonts: Inter & Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0f172a',    // Slate 900
                        brand: '#26376b',      // Blue 600
                        accent: '#c6192b',     // Teal 600
                        success: '#16a34a',    // Green 600
                    },
                    fontFamily: {
                        sans: ['Cairo', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Cairo', 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        @media print {
            @page { size: A4 portrait; margin: 6mm; }
            body { background: #ffffff !important; color: #0f172a !important; padding: 0 !important; margin: 0; font-size: 9px; line-height: 1.2; }
            .print-hidden { display: none !important; }
            .print-single-page { border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 auto !important; width: 100% !important; max-width: 100% !important; overflow: hidden !important; page-break-inside: avoid !important; }
            .quote-paper { border: none !important; box-shadow: none !important; padding: 0 !important; max-width: 100% !important; }
            .badge-print { border: 1px solid #e2e8f0 !important; background: transparent !important; color: #000 !important; }
            .modern-land-quote-paper { font-size: 8.2px !important; line-height: 1.12 !important; }
            .modern-land-quote-paper .modern-land-strip { margin: 0 0 0.45rem 0 !important; height: 0.2rem !important; border-radius: 0 !important; }
            .modern-land-quote-paper header { padding-top: 0.25rem !important; padding-bottom: 0.65rem !important; }
            .modern-land-quote-paper .modern-land-identity { margin-top: 0.8rem !important; margin-bottom: 0.75rem !important; padding: 0.6rem 0.8rem !important; }
            .modern-land-quote-paper .modern-land-card-grid { gap: 0.7rem !important; }
            .modern-land-quote-paper .modern-land-card-grid section { padding: 0.6rem 0.7rem !important; }
            .modern-land-quote-paper .modern-land-section { margin-top: 0.7rem !important; padding: 0.55rem 0.7rem !important; }
            .modern-land-quote-paper .modern-land-section > div:first-child { margin-bottom: 0.25rem !important; padding-bottom: 0.2rem !important; }
            .modern-land-quote-paper .modern-land-table th,
            .modern-land-quote-paper .modern-land-table td { padding-top: 0.25rem !important; padding-bottom: 0.25rem !important; }
            .modern-land-quote-paper .modern-land-pricing { margin-top: 0.75rem !important; }
            .modern-land-quote-paper .modern-land-note { margin-top: 0.45rem !important; padding: 0.45rem 0.6rem !important; }
            .modern-land-quote-paper .modern-land-footer { margin-top: 0.4rem !important; padding-top: 0.35rem !important; gap: 0.55rem !important; page-break-inside: avoid !important; break-inside: avoid !important; }
            .modern-land-quote-paper .modern-land-footer > div { padding: 0.35rem 0.55rem !important; }
            .modern-land-quote-paper .modern-land-signature-line { height: 0.75rem !important; }
        }
    </style>
    @include('partials.company-document-style')
</head>
<body class="text-slate-800 py-8 px-4">
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
                ['label' => 'رسوم إضافية', 'amount' => (float) (($booking->war_risk_surcharge ?? 0) + ($booking->other_charges ?? 0) + ($booking->destination_port_change_fee ?? 0) + ($booking->other_penalties ?? 0)), 'class' => '', 'isWar' => false, 'show' => false],
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
                        ['label' => __('messages.base_ocean_freight_details'), 'amount' => (float) ($booking->ocean_freight ?? 0), 'class' => '', 'isWar' => false, 'show' => true],
                        ['label' => __('messages.origin_charges_details'), 'amount' => (float) ($booking->origin_charges ?? 0), 'class' => '', 'isWar' => false, 'show' => true],
                        ['label' => __('messages.exw_charges_details'), 'amount' => (float) ($booking->exw_charges ?? 0), 'class' => '', 'isWar' => false, 'show' => $booking->shouldShowExwCharges()],
                        ['label' => __('messages.dthc_details'), 'amount' => (float) ($booking->dthc ?? 0), 'class' => '', 'isWar' => false, 'show' => true],
                        ['label' => __('messages.war_risk_details'), 'amount' => (float) ($booking->war_risk_surcharge ?? 0), 'class' => 'bg-red-50/20', 'isWar' => true, 'show' => (float) ($booking->war_risk_surcharge ?? 0) > 0],
                        ['label' => __('messages.other_charges_details'), 'amount' => (float) ($booking->other_charges ?? 0), 'class' => '', 'isWar' => false, 'show' => true],
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
        <div class="mr-auto flex items-center gap-1.5 bg-white p-1 rounded-lg border shadow-sm">
            <span class="text-[10px] font-bold text-slate-400 px-2">{{ __('messages.theme_label') }}</span>
            <a href="{{ route('quote.show', ['id' => $booking->id]) }}" class="inline-flex h-8 items-center justify-center rounded-md hover:bg-slate-50 text-slate-700 text-[11px] font-bold px-3 transition">{{ __('messages.theme_default') }}</a>
            <a href="{{ route('quote.show', ['id' => $booking->id]) }}?template=modern" class="inline-flex h-8 items-center justify-center rounded-md bg-brand text-white text-[11px] font-bold px-3 shadow-sm">{{ __('messages.theme_modern') }}</a>
        </div>
        <button onclick="printQuote(true)" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all">
            <i data-lucide="printer" class="ml-2 h-4 w-4"></i>
            <span>{{ __('messages.print_quote_summary') }}</span>
        </button>
        <button onclick="printQuote(false)" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand text-white px-4 text-xs font-bold shadow hover:opacity-95 transition-all">
            <i data-lucide="printer" class="ml-2 h-4 w-4"></i>
            <span>{{ __('messages.print_quote_detailed') }}</span>
        </button>
        <button onclick="window.close()" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all">
            <span>{{ __('messages.close_page') }}</span>
        </button>
    </div>

    <!-- Modern Compact Sheet -->
    <article class="quote-paper print-single-page {{ $isLandQuote ? 'modern-land-quote-paper' : '' }} mx-auto max-w-[210mm] bg-white p-8 md:p-10 text-[11px] leading-relaxed shadow-lg border border-slate-200 rounded-2xl">
        <!-- Colored top border -->
        <div class="modern-land-strip -mt-8 md:-mt-10 -mx-8 md:-mx-10 h-1.5 bg-gradient-to-r from-brand via-brand to-accent rounded-t-2xl mb-8"></div>
        
        <!-- Header -->
        @include('partials.company-letterhead')

        <!-- Identity & Status Badges -->
        <div class="modern-land-identity my-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50 p-4 rounded-xl border border-slate-150">
            <div>
                <span class="inline-flex items-center rounded-full bg-brand/10 px-2.5 py-0.5 text-[9px] font-bold text-brand badge-print">OFFICIAL FREIGHT QUOTATION</span>
                <h2 class="text-xl font-extrabold text-slate-900 tracking-tight mt-1">{{ $quoteTitle }}</h2>
            </div>
            <div class="grid grid-cols-3 gap-x-6 gap-y-1 text-slate-600 text-xs">
                <div>
                    <span class="block text-[9px] text-slate-400 font-bold uppercase">{{ __('messages.quote_number') }}</span>
                    <strong class="text-slate-800 font-mono">#{{ $booking->id }}</strong>
                </div>
                <div>
                    <span class="block text-[9px] text-slate-400 font-bold uppercase">{{ __('messages.date') }}</span>
                    <strong class="text-slate-800">{{ $booking->priced_at ? $booking->priced_at->toDateString() : date('Y-m-d') }}</strong>
                </div>
                <div>
                    <span class="block text-[9px] text-slate-400 font-bold uppercase text-red-500">{{ __('messages.valid_until') }}</span>
                    <strong class="text-red-650 font-bold">{{ $quoteValidityDisplayDate }}</strong>
                </div>
            </div>
        </div>

        <!-- Party details -->
        <div class="modern-land-card-grid grid gap-4 sm:grid-cols-2">
            <section class="rounded-xl border border-slate-100 p-4 bg-white shadow-sm">
                <div class="flex items-center gap-1.5 border-b border-slate-100 pb-2 mb-2">
                    <i data-lucide="user" class="h-3.5 w-3.5 text-brand"></i>
                    <h3 class="text-[9px] font-extrabold uppercase tracking-wider text-slate-500">{{ __('messages.recipient_client') }}</h3>
                </div>
                <p class="font-black text-slate-900 text-xs">{{ $booking->customer_company }}</p>
                <p class="text-slate-600">{{ __('messages.general_manager') === 'General Manager' ? 'Mr/Ms' : 'السيد/ة' }} {{ $booking->customer_contact_name }}</p>
                <div class="mt-2 space-y-0.5 text-slate-500 font-semibold">
                    <p dir="ltr" class="text-right">{{ __('messages.phone_label') }}: {{ $booking->customer_phone }}</p>
                    <p dir="ltr" class="text-right">{{ __('messages.email_label') }}: {{ $booking->customer_email }}</p>
                </div>
            </section>
            
            <section class="rounded-xl border border-slate-100 p-4 bg-white shadow-sm">
                <div class="flex items-center gap-1.5 border-b border-slate-100 pb-2 mb-2">
                    <i data-lucide="map-pin" class="h-3.5 w-3.5 text-accent"></i>
                    <h3 class="text-[9px] font-extrabold uppercase tracking-wider text-slate-500">{{ __('messages.route_details_header') }}</h3>
                </div>
                <div class="grid grid-cols-2 gap-y-2 gap-x-4 text-slate-600">
                    <div>
                        <span class="block text-[9px] text-slate-400 font-bold">{{ $quoteRouteOriginLabel }}</span>
                        <strong class="text-slate-950 font-bold text-xs">{{ $displayLabel($booking->pol) }}</strong>
                    </div>
                    <div>
                        <span class="block text-[9px] text-slate-400 font-bold">{{ $quoteRouteDestinationLabel }}</span>
                        <strong class="text-slate-950 font-bold text-xs">{{ $displayLabel($booking->pod) }}</strong>
                    </div>
                    <div>
                        <span class="block text-[9px] text-slate-400 font-bold">{{ __('messages.transit_duration') }}</span>
                        <strong class="text-slate-800">{{ $booking->transit_days }} {{ __('messages.days') }}</strong>
                    </div>
                    <div>
                        <span class="block text-[9px] text-slate-400 font-bold">{{ __('messages.type_label') }}</span>
                        <strong class="text-slate-800">{{ $booking->shipment_type }}</strong>
                    </div>
                </div>
            </section>
        </div>

        <!-- Cargo specifications -->
        <section class="modern-land-section mt-4 rounded-xl border border-slate-100 p-4 bg-slate-50">
            <div class="flex items-center gap-1.5 border-b border-slate-150 pb-2 mb-2">
                <i data-lucide="package" class="h-3.5 w-3.5 text-success"></i>
                <h3 class="text-[9px] font-extrabold uppercase tracking-wider text-slate-500">{{ __('messages.declared_cargo_details') }}</h3>
            </div>
            <table class="modern-land-table w-full text-right text-[11px] text-slate-600">
                <thead>
                    <tr class="text-slate-400 font-bold border-b border-slate-200">
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
                    <tr class="font-semibold text-slate-850">
                        <td class="py-2 text-slate-900 font-extrabold">{{ $booking->goods_description }}</td>
                        @if($isLandQuote)
                            <td class="py-2">{{ number_format($booking->weight_kg) }} {{ __('messages.kg') }}</td>
                            <td class="py-2 text-slate-900 font-extrabold" dir="ltr">{{ $formatQuoteQuantity($quoteLandPalletCount) }}</td>
                            @if($quoteLandCartonCount > 0)
                                <td class="py-2 text-slate-900 font-extrabold" dir="ltr">{{ $formatQuoteQuantity($quoteLandCartonCount) }}</td>
                            @endif
                            <td class="py-2 text-slate-900 font-extrabold" dir="ltr">{{ $formatQuoteQuantity($quoteLandPackageCount) }}</td>
                        @else
                            <td class="py-2">{{ $quoteContainerSummary }}</td>
                            <td class="py-2">{{ number_format($booking->weight_kg) }} {{ __('messages.kg') }}</td>
                            <td class="py-2">{{ $quoteVolumeDisplayValue }}</td>
                        @endif
                        <td class="py-2">
                            @if($booking->dangerous)
                                <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-[9px] font-bold text-red-700 border border-red-100 badge-print">{{ __('messages.yes_dangerous') }}</span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-[9px] font-bold text-green-700 border border-green-100 badge-print">{{ __('messages.no_dangerous') }}</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Pricing details -->
        <section class="modern-land-pricing mt-6">
            <h3 id="breakdown-header" class="mb-2 text-xs font-black text-slate-900 border-r-4 border-brand pr-2">{{ $quoteBreakdownHeader }}</h3>
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <table class="modern-land-table w-full text-[11px] text-slate-650">
                    <thead class="bg-slate-900 text-white font-bold">
                        <tr class="breakdown-row">
                            <th class="px-4 py-2.5 text-right">{{ $isLandQuote ? 'المسار' : ($isDoorToDoorQuote ? __('messages.door_to_door_service_label') : __('messages.charge_item_label')) }}</th>
                            <th class="px-4 py-2.5 text-left">{{ $isDoorToDoorQuote ? $doorToDoorUnitPriceLabel : ($isLandQuote ? __('messages.door_to_door_unit_price_label') : __('messages.quote_unit_price_label')) }}</th>
                            <th class="px-4 py-2.5 text-center">{{ $isDoorToDoorQuote ? $doorToDoorQuantityLabel : ($isLandQuote ? __('messages.door_to_door_tons_label') : __('messages.quote_quantity_label')) }}</th>
                            <th class="px-4 py-2.5 text-left">{{ __('messages.quote_total_label') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($quoteChargeRows as $row)
                            @continue(! $row['show'])
                            <tr class="hover:bg-slate-50 transition-colors breakdown-row {{ $row['class'] }}">
                                <td class="px-4 py-2 text-slate-500 font-semibold">{{ $row['label'] }}</td>
                                <td class="px-4 py-2 text-left font-bold {{ $row['isWar'] ? 'text-red-600' : 'text-slate-900' }}" dir="ltr">{{ $formatQuoteMoney($row['unit_price'] ?? $quoteUnitAmount($row['amount'])) }}</td>
                                <td class="px-4 py-2 text-center font-bold text-slate-900" dir="ltr">{{ $formatQuoteQuantity($row['quantity'] ?? $quoteQuantity) }}</td>
                                <td class="px-4 py-2 text-left font-bold {{ $row['isWar'] ? 'text-red-600' : 'text-slate-900' }}" dir="ltr">{{ $formatQuoteMoney($row['amount']) }}</td>
                            </tr>
                        @endforeach
                        @if($quoteFclBreakdownRows !== [])
                            <tr class="bg-slate-100/80 font-black text-slate-900">
                                <td class="px-4 py-3 text-xs">إجمالي الحاويات المختلطة</td>
                                <td class="px-4 py-3 text-left text-base text-brand" dir="ltr">-</td>
                                <td class="px-4 py-3 text-center text-base text-brand" dir="ltr">{{ $formatQuoteQuantity($quoteQuantity) }}</td>
                                <td class="px-4 py-3 text-left text-base text-brand" dir="ltr">{{ $formatQuoteMoney($booking->pricingTotal()) }}</td>
                            </tr>
                        @endif
                        <tr class="bg-slate-50 font-black text-slate-900">
                            <td class="px-4 py-3 text-xs">{{ __('messages.final_total_freight') }}</td>
                            <td class="px-4 py-3 text-left text-base text-brand" dir="ltr">{{ $isDoorToDoorQuote ? $formatQuoteMoney($doorToDoorUnitPrice) : ($quoteFclBreakdownRows !== [] ? '-' : $formatQuoteMoney($quoteUnitAmount($booking->pricingTotal()))) }}</td>
                            <td class="px-4 py-3 text-center text-base text-brand" dir="ltr">{{ $isDoorToDoorQuote ? $formatQuoteQuantity($doorToDoorQuantity) : $formatQuoteQuantity($quoteQuantity) }}</td>
                            <td class="px-4 py-3 text-left text-base text-brand" dir="ltr">{{ $formatQuoteMoney($booking->pricingTotal()) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @if($booking->pricing_notes)
                <div class="modern-land-note mt-2.5 p-3 rounded-lg border border-slate-100 bg-slate-50 text-[10px] text-slate-500 leading-relaxed">
                    <strong class="text-slate-700">{{ __('messages.quote_notes') }}:</strong> {{ $booking->pricing_notes }}
                </div>
            @endif
        </section>

        <!-- Terms and conditions -->
        <section class="modern-land-section mt-6 rounded-xl bg-slate-50 p-4 text-[9.5px] leading-relaxed text-slate-500 border border-slate-100">
            <h3 class="mb-1 text-xs font-black text-slate-950 flex items-center gap-1.5">
                <i data-lucide="info" class="h-3.5 w-3.5 text-slate-500"></i>
                <span>{{ __('messages.terms_conditions_header') }}</span>
            </h3>
            <ul class="list-decimal space-y-0.5 pr-5">
                @foreach($quoteTerms as $term)
                    <li>{{ $term }}</li>
                @endforeach
            </ul>
        </section>

        <!-- Wire transfer info & Stamp signature -->
        @if($isLandQuote)
            <footer class="modern-land-footer mt-3 grid gap-3 sm:grid-cols-2 border-t border-slate-100 pt-3">
                <div class="rounded-lg border border-slate-150 p-2 text-[9px] bg-slate-50/50 leading-tight text-slate-500">
                    <h4 class="mb-0.5 font-bold text-slate-700">{{ __('messages.wire_transfer_details') }}</h4>
                    <p class="font-extrabold text-slate-800">{{ __('messages.company_name') }}</p>
                    <p dir="ltr" class="font-semibold text-slate-600">+20 1553818989</p>
                </div>

                <div class="rounded-lg border border-dashed border-slate-200 p-2 text-center bg-white">
                    <div class="modern-land-signature-line mx-auto h-6 w-36 border-b border-slate-300"></div>
                    <span class="mt-1 block text-[9px] uppercase tracking-wider font-bold text-slate-400">{{ __('messages.signature_stamp_ops') }}</span>
                </div>
            </footer>
        @else
            <footer class="mt-6 grid gap-4 sm:grid-cols-2 border-t border-slate-100 pt-6">
                <div class="rounded-xl border border-slate-150 p-4 text-[9.5px] bg-slate-50/50 leading-relaxed text-slate-500">
                    <h4 class="mb-1 font-bold text-slate-700">{{ __('messages.wire_transfer_details') }}</h4>
                    <p class="font-extrabold text-slate-800">{{ __('messages.company_name') }}</p>
                    <p dir="ltr" class="font-semibold text-slate-600">+20 1553818989</p>
                </div>
                
                <div class="flex flex-col items-center justify-center text-center relative border border-dashed border-slate-200 rounded-xl p-3 bg-white">
                    <span class="text-[9px] uppercase tracking-wider font-bold text-slate-400">{{ __('messages.signature_stamp_ops') }}</span>
                    <p class="font-extrabold text-slate-800 mt-0.5">{{ __('messages.quote_doc_header_subtitle') }}</p>
                    <div class="h-20 w-28 opacity-90 select-none pointer-events-none mt-2 relative flex items-center justify-center">
                        <img src="{{ asset(config('company.logo')) }}" alt="{{ __('messages.site_name') }}" class="h-full w-full object-contain">
                    </div>
                </div>
            </footer>
        @endif
        @include('partials.company-document-footer')
    </article>

    <script>
        if (window.lucide) window.lucide.createIcons();

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
