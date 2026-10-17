<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Master Bill of Lading - Booking #{{ $booking->id }}</title>
    
    <!-- Cairo / Inter Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandSky: '#c6192b',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['Courier Prime', 'Courier New', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #f1f5f9;
        }
        @media print {
            @page { size: A4 portrait; margin: 6mm; }
            body { background: #ffffff !important; color: #000000 !important; padding: 0 !important; margin: 0; font-size: 9px; line-height: 1.2; }
            .print-hidden { display: none !important; }
            .print-single-page { border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 auto !important; width: 100% !important; max-width: 100% !important; overflow: hidden !important; page-break-inside: avoid !important; }
            .mbl-document { border: 1.5px solid #000000 !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; max-width: 100% !important; }
            .border-tbl { border-color: #000000 !important; }
        }
        /* Style adjustments for authentic shipping paper feel */
        .mbl-cell-title {
            font-size: 8px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }
        .mbl-text {
            font-family: 'Courier Prime', 'Courier New', monospace;
            font-size: 10.5px;
            line-height: 1.35;
            color: #0f172a;
        }
    </style>
    @include('partials.company-document-style')
</head>
<body class="py-8 px-4 text-slate-900 font-sans">

    <!-- Action bar -->
    <div class="max-w-[210mm] mx-auto mb-6 flex items-center justify-end gap-2 px-4 print-hidden">
        <div class="mr-auto text-xs text-slate-500 font-bold">
            <i data-lucide="info" class="inline-block mr-1 h-4 w-4 align-text-bottom"></i>
            Master Bill of Lading Draft (Non-Negotiable / Logo-Free / Price-Free)
        </div>
        <button onclick="window.print()" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all">
            <i data-lucide="printer" class="ml-2 h-4 w-4"></i>
            <span>Print Draft B/L</span>
        </button>
        <button onclick="window.close()" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all">
            <span>Close</span>
        </button>
    </div>

    <!-- Bill of Lading Sheet -->
    <article class="mbl-document print-single-page mx-auto max-w-[210mm] bg-white text-[10px] leading-relaxed shadow-lg border border-slate-300 rounded-lg overflow-hidden print:shadow-none print:border-black print:rounded-none">
        
        <!-- TOP META & HEADER GRID -->
        <div class="grid grid-cols-12 border-b-2 border-brandSky">
            <!-- Left Header -->
            <div class="col-span-7 p-4 border-r border-slate-300 flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 tracking-tight leading-none">{{ __('messages.company_name') }}</h2>
                        <h1 class="text-base font-extrabold tracking-widest text-brandSky uppercase mt-2 leading-none">BILL OF LADING</h1>
                        <p class="text-[8px] font-bold text-red-600 uppercase tracking-widest mt-1">DRAFT ONLY — NOT NEGOTIABLE</p>
                    </div>
                </div>
                <div class="text-[8px] text-slate-400 font-bold uppercase tracking-wider self-end mb-1">
                    Multimodal Transport B/L
                </div>
            </div>
            
            <!-- Right Meta Block -->
            <div class="col-span-5 grid grid-rows-2">
                <div class="p-3 border-b border-slate-350 bg-slate-50/20">
                    <div class="mbl-cell-title text-brandSky">B/L Number</div>
                    <div class="mbl-text font-bold text-xs uppercase">{{ $booking->carrier_booking_ref ?? 'DRAFT-' . $booking->id }}</div>
                </div>
                <div class="grid grid-cols-2">
                    <div class="p-3 border-r border-slate-350">
                        <div class="mbl-cell-title">Booking Number</div>
                        <div class="mbl-text font-bold">{{ $booking->id }}</div>
                    </div>
                    <div class="p-3">
                        <div class="mbl-cell-title">Export References</div>
                    <div class="mbl-text font-semibold">{{ filled($booking->job_no) ? $booking->job_no : '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PARTY DETAILS SECTION -->
        <div class="grid grid-cols-12 border-b border-slate-300">
            <!-- Shipper details -->
            <div class="col-span-7 p-4 border-r border-slate-300 min-h-[100px]">
                <div class="mbl-cell-title">Shipper / Exporter (Name & Address)</div>
                <div class="mbl-text whitespace-pre-wrap">{{ filled($booking->shipper) ? $booking->shipper : '-' }}</div>
            </div>
            <!-- Document control notes -->
            <div class="col-span-5 p-4 bg-slate-50/50">
                <div class="mbl-cell-title">Overseas Forwarding Agent / Representative</div>
                <div class="mbl-text font-semibold">{{ filled($booking->overseas_agent) ? $booking->overseas_agent : '-' }}</div>
                
                <div class="mt-4">
                    <div class="mbl-cell-title">Point of Origin</div>
                    <div class="mbl-text">{{ $booking->pol }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 border-b border-slate-300">
            <!-- Consignee details -->
            <div class="col-span-7 p-4 border-r border-slate-300 min-h-[100px]">
                <div class="mbl-cell-title">Consignee (Name, Address & Contact Details)</div>
                <div class="mbl-text whitespace-pre-wrap">{{ filled($booking->consignee) ? $booking->consignee : '-' }}</div>
            </div>
            <!-- Move status notes -->
            <div class="col-span-5 p-4 bg-slate-50/50">
                <div class="mbl-cell-title">Instructions / Movement Type</div>
                <div class="mbl-text font-bold text-xs uppercase">{{ filled($booking->bill_type) ? $booking->bill_type : '-' }}</div>
                <div class="mbl-text mt-1">TypeOfMove: <span class="font-bold text-slate-800">{{ filled($booking->type_of_move) ? $booking->type_of_move : '-' }}</span></div>
                
                <div class="mt-3">
                    <div class="mbl-cell-title">Place of Delivery</div>
                    <div class="mbl-text">{{ filled($booking->place_of_delivery) ? $booking->place_of_delivery : '-' }}</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 border-b-2 border-slate-900">
            <!-- Notify Party details -->
            <div class="col-span-7 p-4 border-r border-slate-300 min-h-[100px]">
                <div class="mbl-cell-title">Notify Party (Name, Address & Arrival Notification Notice)</div>
                <div class="mbl-text whitespace-pre-wrap">{{ filled($booking->notify_party) ? $booking->notify_party : '-' }}</div>
            </div>
            <!-- Carrier references -->
            <div class="col-span-5 p-4 bg-slate-50/50 flex flex-col justify-between">
                <div>
                    <div class="mbl-cell-title">Pre-Carriage By</div>
                    <div class="mbl-text">-</div>
                </div>
                <div class="mt-2">
                    <div class="mbl-cell-title">Place of Receipt</div>
                    <div class="mbl-text font-semibold">{{ $booking->pol }}</div>
                </div>
            </div>
        </div>

        <!-- ROUTING GRID SECTION -->
        <div class="grid grid-cols-12 border-b-2 border-slate-900">
            <div class="col-span-3 p-3 border-r border-slate-300">
                <div class="mbl-cell-title">Vessel & Voyage Number</div>
                @php
                    $vesselVoyage = collect([$booking->carrier_vessel, $booking->carrier_voyage])
                        ->filter(fn ($value) => filled($value))
                        ->implode(' / ');
                @endphp
                <div class="mbl-text font-bold uppercase">{{ $vesselVoyage ?: '-' }}</div>
            </div>
            <div class="col-span-3 p-3 border-r border-slate-300">
                <div class="mbl-cell-title">Port of Loading (POL)</div>
                <div class="mbl-text font-bold uppercase">{{ $booking->pol }}</div>
            </div>
            <div class="col-span-3 p-3 border-r border-slate-300">
                <div class="mbl-cell-title">Port of Discharge (POD)</div>
                <div class="mbl-text font-bold uppercase">{{ $booking->pod }}</div>
            </div>
            <div class="col-span-3 p-3">
                <div class="mbl-cell-title">Estimated Date of Departure (ETD)</div>
                <div class="mbl-text font-bold font-mono">{{ $booking->carrier_etd ? $booking->carrier_etd->toDateString() : '-' }}</div>
            </div>
        </div>

        <!-- CARGO SPECIFICATIONS TABLE -->
        <div class="border-b-2 border-slate-900 min-h-[300px]">
            <table class="w-full text-[10px] mbl-text">
                <thead class="bg-slate-50 border-b border-slate-300 font-bold uppercase">
                    <tr>
                        <th class="px-4 py-2 border-r border-slate-300 w-1/4 text-left">Container No. / Seal No. / Marks</th>
                        <th class="px-4 py-2 border-r border-slate-300 w-1/6 text-right">No. of Pkgs</th>
                        <th class="px-4 py-2 border-r border-slate-300 w-1/3 text-left">Description of Goods</th>
                        <th class="px-4 py-2 border-r border-slate-300 w-1/8 text-right">Gross Weight</th>
                        <th class="px-4 py-2 text-right">Measurement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr class="align-top">
                        <!-- Containers and Seals matrix -->
                        <td class="px-4 py-3 border-r border-slate-300 font-mono whitespace-pre-wrap leading-loose">
{{ filled($booking->container_seal_no) ? $booking->container_seal_no : '-' }}
                        </td>
                        <!-- Packages count and container details -->
                        <td class="px-4 py-3 border-r border-slate-300 text-right font-bold whitespace-pre-wrap leading-loose">
@php
    $containerSpec = $booking->containerSummary();
@endphp
@if(filled($booking->quantity_packages))
{{ $booking->quantity_packages }}
@if($containerSpec)
<br>{{ $containerSpec }}
@endif
@elseif($containerSpec)
{{ $containerSpec }}
@else
-
@endif
                        </td>
                        <!-- Description of cargo -->
                        <td class="px-4 py-3 border-r border-slate-300 font-mono leading-relaxed whitespace-pre-wrap">
{{ filled($booking->goods_description) ? $booking->goods_description : '-' }}
                        </td>
                        <!-- Weight -->
                        <td class="px-4 py-3 border-r border-slate-300 text-right font-bold whitespace-pre-wrap leading-loose">
{{ filled($booking->weight_kg) ? number_format((float) $booking->weight_kg) . ' KG' : '-' }}
                        </td>
                        <!-- Volume -->
                        <td class="px-4 py-3 text-right font-bold whitespace-pre-wrap leading-loose">
@php
    $measurementCbm = filled($booking->measurement_cbm) ? $booking->measurement_cbm : $booking->volume_cbm;
@endphp
{{ filled($measurementCbm) ? $measurementCbm . ' CBM' : '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- FOOTER & SHIPPED ON BOARD SIGNATURE BLOCK -->
        <div class="grid grid-cols-12 text-[9px] text-slate-500 font-semibold p-4 gap-4 bg-slate-50/20">
            <div class="col-span-8 space-y-2 border-r border-slate-200 pr-4 leading-relaxed text-slate-500">
                <p>
                    RECEIVED by the Carrier in outward apparent good order and condition, unless otherwise stated herein, the Goods to be carried to the Port of Discharge or Place of Delivery.
                </p>
                <p>
                    The details of the cargo listed above have been declared by the shipper. All counts, weights, seals, and descriptions are checked and certified by the exporter. 
                </p>
                <div class="pt-4 grid grid-cols-2 text-[10px] font-bold text-slate-800">
                    <div>Place and Date of Issue: <span class="font-normal font-mono">{{ $booking->pol }}, {{ date('Y-m-d') }}</span></div>
                    <div>Number of Original B/Ls: <span class="font-normal">{{ filled($booking->bill_type) ? '0 (' . $booking->bill_type . ' / DRAFT)' : '0 (DRAFT)' }}</span></div>
                </div>
            </div>
            
            <div class="col-span-4 flex flex-col justify-between items-center text-center pt-2 min-h-[110px]">
                <div class="w-full">
                    <span class="mbl-cell-title block">Signature of Carrier / Agent</span>
                    <div class="text-[9px] font-bold text-slate-900 mt-1">AS AGENTS FOR THE CARRIER</div>
                </div>
                <div class="border-t border-slate-300 border-dashed w-full pt-2 font-mono text-[9px] text-slate-400">
                    DRAFT ONLY - FOR VERIFICATION
                </div>
            </div>
        </div>

        <!-- Terms at bottom -->
        @if($booking->custom_notes)
        <div class="border-t border-slate-350 p-4 bg-slate-50 text-[9px] text-slate-650 leading-relaxed font-mono">
            <strong>DOCUMENT REMARKS:</strong> {{ $booking->custom_notes }}
        </div>
        @endif
        @include('partials.company-document-footer')
    </article>

    <script>
        if (window.lucide) window.lucide.createIcons();
    </script>
</body>
</html>
