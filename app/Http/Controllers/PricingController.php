<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Notification;
use App\Support\BookingFilters;
use App\Support\NotificationVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PricingController extends Controller
{
    public function index(Request $request)
    {
        $filters = BookingFilters::fromRequest($request);

        // include fresh requests and those that were returned from sales for re-pricing
        $queueQuery = Booking::with('agent')->whereIn('status', ['new', 'needs_pricing']);
        BookingFilters::apply($queueQuery, $filters);
        $queue = $queueQuery->latest()->get();

        $recentQuery = Booking::with('agent')->whereIn('status', [
            'priced',
            'sent',
            'approved',
            'booked'
        ]);
        BookingFilters::apply($recentQuery, $filters);
        $recent = $recentQuery->latest()->take(5)->get();
        $agents = Agent::orderBy('name')->get();
        $unpricedCount = $queue->where('status', 'new')->count();
        $repricingCount = $queue->where('status', 'needs_pricing')->count();
        $oldestWaiting = $queue->sortBy('created_at')->first();

        // load pricing-targeted unread notifications for items in the queue
        $notificationMap = [];
        if ($queue->isNotEmpty()) {
            $notesQuery = Notification::whereIn('booking_id', $queue->pluck('id'))
                        ->where('target', 'pricing');
            NotificationVisibility::scopeUnreadFor($notesQuery, auth()->user());
            $notes = $notesQuery->get();
            foreach ($notes as $n) {
                $notificationMap[$n->booking_id] = $n;
            }
        }

        return view('pricing', compact(
            'queue',
            'recent',
            'notificationMap',
            'agents',
            'unpricedCount',
            'repricingCount',
            'oldestWaiting',
            'filters',
        ));
    }

    public function store(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $isLandShipment = $booking->isLandShipment();
        $isDoorToDoorShipment = $booking->isDoorToDoorShipment();
        $isAirShipment = strtoupper((string) $booking->shipment_type) === 'AIR'
            || strtolower((string) data_get($booking->route_details, 'category')) === 'air';
        $isBulkShipment = strtoupper((string) $booking->shipment_type) === 'BULK';
        $isRoRoShipment = in_array(strtoupper((string) $booking->shipment_type), ['RORO', 'RO-RO'], true);
        $isItemizedShipment = $isAirShipment || $isBulkShipment || $isRoRoShipment;
        $rawItemPricingLines = $request->input('itemPricingLines', []);
        $usesItemPricing = $isItemizedShipment && is_array($rawItemPricingLines) && $rawItemPricingLines !== [];
        $isExwSeaShipment = $booking->isExwSeaShipment();

        $rules = [
            'transitDays' => 'required|integer',
            'validityDate' => 'required|date',
            'agent_id' => 'required|exists:agents,id',
            'agentPurchaseUnitPrice' => 'nullable|numeric|min:0',
            'agentPurchaseAmount' => 'nullable|numeric|min:0',
            'agentPurchaseQuantity' => 'nullable|numeric|min:0.01',
            'agentPurchaseNotes' => 'nullable|string',
            'pricingEditMode' => 'nullable|boolean',
            'customPricingCharges' => 'nullable|array|max:20',
            'customPricingCharges.*.label' => 'nullable|string|max:120',
            'customPricingCharges.*.amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];

        if ($usesItemPricing) {
            $rules += [
                'itemPricingLines' => 'required|array|min:1|max:50',
                'itemPricingLines.*.name' => 'required|string|max:255',
                'itemPricingLines.*.hs_code' => 'nullable|string|max:40',
                'itemPricingLines.*.quantity' => 'required|numeric|min:0.01',
                'itemPricingLines.*.unit' => 'required|in:piece,vehicle,cbm,kg,ton,container',
                'itemPricingLines.*.sale_unit_price' => 'required|numeric|min:0',
                'itemPricingLines.*.purchase_unit_price' => 'required|numeric|min:0',
            ];
        } elseif ($isLandShipment) {
            $rules += [
                'landPricingLines' => 'required|array|min:1',
                'landPricingLines.*.qty' => 'required|numeric|min:0.01',
                'landPricingLines.*.truck_qty' => 'nullable|numeric|min:0',
                'landPricingLines.*.from_city' => 'required|string|max:255',
                'landPricingLines.*.to_city' => 'required|string|max:255',
                'landPricingLines.*.unit_price' => 'required|numeric|min:0',
                'landPricingLines.*.plate_number' => 'nullable|string|max:255',
                'landPricingLines.*.notes' => 'nullable|string|max:1000',
            ];
        } elseif ($isDoorToDoorShipment) {
            $rules += [
                'doorToDoorPricingBasis' => 'required|in:ton,cbm',
                'doorToDoorUnitPrice' => 'required|numeric|min:0',
            ];
        } else {
            $fclRows = data_get($booking->cargo_details, 'containers', []);
            $hasMultipleFclContainers = is_array($fclRows) && count($fclRows) > 1;

            if ($hasMultipleFclContainers) {
                $rules += [
                    'fclPricingLines' => 'required|array|min:1',
                    'fclPricingLines.*.qty' => 'required|numeric|min:0.01',
                    'fclPricingLines.*.type' => 'required|string|max:50',
                    'fclPricingLines.*.unit_price' => 'required|numeric|min:0',
                    'agentPurchaseLines' => 'required|array|min:1',
                    'agentPurchaseLines.*.qty' => 'required|numeric|min:0.01',
                    'agentPurchaseLines.*.type' => 'required|string|max:50',
                    'agentPurchaseLines.*.unit_price' => 'required|numeric|min:0',
                    'exwCharges' => 'nullable|numeric|min:0',
                    'destinationPortChangeFee' => 'nullable|numeric|min:0',
                    'otherPenalties' => 'nullable|numeric|min:0',
                ];
            } else {
                $rules += [
                    'oceanFreight' => 'required|numeric',
                    'originCharges' => 'required|numeric',
                    'exwCharges' => 'nullable|numeric|min:0',
                    'dthc' => 'required|numeric',
                    'warRiskSurcharge' => 'required|numeric',
                    'otherCharges' => 'required|numeric',
                    'destinationPortChangeFee' => 'nullable|numeric|min:0',
                    'otherPenalties' => 'nullable|numeric|min:0',
                ];
            }
        }

        if (! $usesItemPricing) {
            $rules['agentPurchaseUnitPrice'] = 'nullable|required_without:agentPurchaseAmount|numeric|min:0';
            $rules['agentPurchaseAmount'] = 'nullable|required_without:agentPurchaseUnitPrice|numeric|min:0';
            $rules['agentPurchaseQuantity'] = 'required|numeric|min:0.01';
        }

        $request->validate($rules);

        $isPricingEdit = $request->boolean('pricingEditMode')
            && in_array($booking->status, ['priced', 'sent'], true);

        if (! in_array($booking->status, ['new', 'needs_pricing'], true) && ! $isPricingEdit) {
            return redirect()
                ->route('pricing.index')
                ->with('error', "الطلب {$id} تم تسعيره أو نقله مسبقاً، لذلك لم يتم إنشاء إشعارات مكررة.");
        }

        $old = $booking->only([
            'status',
            'ocean_freight',
            'origin_charges',
            'exw_charges',
            'dthc',
            'war_risk_surcharge',
            'other_charges',
            'destination_port_change_fee',
            'other_penalties',
            'agent_id',
            'agent_purchase_amount',
            'agent_purchase_quantity',
            'agent_purchase_currency',
            'profit_amount',
            'cargo_details',
        ]);

        $agentPurchaseQuantity = (float) $request->input('agentPurchaseQuantity', 0);
        $cargoDetails = is_array($booking->cargo_details) ? $booking->cargo_details : [];
        $pricingCurrency = 'USD';
        $cargoDetails['pricing_currency'] = $pricingCurrency;
        $pricingAudit = [];
        $saleExwCharges = 0;
        $saleUnitExwCharges = 0;
        $itemizedPurchaseTotal = null;

        if ($isLandShipment) {
            $landPricingLines = $this->landPricingLinesFromRequest($request);
            $saleQuantity = collect($landPricingLines)->sum(fn (array $line) => (float) $line['qty']);
            $saleTotal = collect($landPricingLines)->sum(fn (array $line) => (float) $line['total']);
            $saleOceanFreight = $saleTotal;
            $saleOriginCharges = 0;
            $saleExwCharges = 0;
            $saleDthc = 0;
            $saleWarRiskSurcharge = 0;
            $saleOtherCharges = 0;
            $saleDestinationPortChangeFee = 0;
            $saleOtherPenalties = 0;
            $saleUnitOceanFreight = $saleQuantity > 0 ? $saleTotal / $saleQuantity : 0;
            $saleUnitOriginCharges = 0;
            $saleUnitExwCharges = 0;
            $saleUnitDthc = 0;
            $saleUnitWarRiskSurcharge = 0;
            $saleUnitOtherCharges = 0;
            $saleUnitDestinationPortChangeFee = 0;
            $saleUnitOtherPenalties = 0;

            $cargoDetails['land_pricing_lines'] = $landPricingLines;
            $cargoDetails['land_pricing_total'] = round($saleTotal, 2);
            $cargoDetails['land_pricing_quantity'] = round($saleQuantity, 2);
            $cargoDetails['land_pricing_basis'] = 'ton';

            $pricingAudit['land_pricing_lines'] = $landPricingLines;
            $pricingAudit['land_pricing_total'] = $saleTotal;
            $pricingAudit['land_pricing_quantity'] = $saleQuantity;
            $pricingAudit['land_pricing_basis'] = 'ton';
        } elseif ($isDoorToDoorShipment) {
            $grossWeightKg = (float) ($booking->weight_kg ?? data_get($booking->cargo_details, 'cargo.gross_weight_kg') ?? data_get($booking->cargo_details, 'gross_weight_kg') ?? 0);
            $doorToDoorUnitPrice = (float) $request->input('doorToDoorUnitPrice', 0);
            $doorToDoorPricingBasis = $request->input('doorToDoorPricingBasis', 'ton');
            $doorToDoorTons = $grossWeightKg > 0 ? ($grossWeightKg / 1000) : 0;
            $doorToDoorVolumeCbm = (float) (
                $booking->volume_cbm
                ?: data_get($booking->cargo_details, 'cargo.volume_cbm')
                ?: data_get($booking->cargo_details, 'volume_cbm')
                ?: 0
            );
            $doorToDoorQuantity = $doorToDoorPricingBasis === 'cbm'
                ? $doorToDoorVolumeCbm
                : $doorToDoorTons;

            if ($doorToDoorQuantity <= 0) {
                throw ValidationException::withMessages([
                    'doorToDoorPricingBasis' => $doorToDoorPricingBasis === 'cbm'
                        ? __('messages.door_to_door_cbm_required')
                        : __('messages.door_to_door_ton_required'),
                ]);
            }

            $saleTotal = $doorToDoorQuantity * $doorToDoorUnitPrice;

            $saleOceanFreight = $saleTotal;
            $saleOriginCharges = 0;
            $saleExwCharges = 0;
            $saleDthc = 0;
            $saleWarRiskSurcharge = 0;
            $saleOtherCharges = 0;
            $saleDestinationPortChangeFee = 0;
            $saleOtherPenalties = 0;
            $saleUnitOceanFreight = $doorToDoorUnitPrice;
            $saleUnitOriginCharges = 0;
            $saleUnitExwCharges = 0;
            $saleUnitDthc = 0;
            $saleUnitWarRiskSurcharge = 0;
            $saleUnitOtherCharges = 0;
            $saleUnitDestinationPortChangeFee = 0;
            $saleUnitOtherPenalties = 0;

            $cargoDetails['door_to_door_pricing'] = [
                'basis' => $doorToDoorPricingBasis,
                'quantity' => round($doorToDoorQuantity, 4),
                'unit_price' => round($doorToDoorUnitPrice, 2),
                'gross_weight_kg' => round($grossWeightKg, 2),
                'tons' => round($doorToDoorTons, 4),
                'volume_cbm' => round($doorToDoorVolumeCbm, 4),
                'unit_price_per_ton' => $doorToDoorPricingBasis === 'ton' ? round($doorToDoorUnitPrice, 2) : null,
                'unit_price_per_cbm' => $doorToDoorPricingBasis === 'cbm' ? round($doorToDoorUnitPrice, 2) : null,
                'total' => round($saleTotal, 2),
            ];

            $pricingAudit['door_to_door_pricing'] = $cargoDetails['door_to_door_pricing'];
        } elseif ($usesItemPricing) {
            $itemPricingLines = $this->itemPricingLinesFromRequest($request);
            $saleTotal = collect($itemPricingLines)->sum(fn (array $line) => (float) $line['sale_total']);
            $itemizedPurchaseTotal = collect($itemPricingLines)->sum(fn (array $line) => (float) $line['purchase_total']);
            $agentPurchaseQuantity = collect($itemPricingLines)->sum(fn (array $line) => (float) $line['quantity']);
            $saleOceanFreight = $saleTotal;
            $saleOriginCharges = 0;
            $saleExwCharges = 0;
            $saleDthc = 0;
            $saleWarRiskSurcharge = 0;
            $saleOtherCharges = 0;
            $saleDestinationPortChangeFee = 0;
            $saleOtherPenalties = 0;
            $saleUnitOceanFreight = $agentPurchaseQuantity > 0 ? $saleTotal / $agentPurchaseQuantity : 0;
            $saleUnitOriginCharges = 0;
            $saleUnitExwCharges = 0;
            $saleUnitDthc = 0;
            $saleUnitWarRiskSurcharge = 0;
            $saleUnitOtherCharges = 0;
            $saleUnitDestinationPortChangeFee = 0;
            $saleUnitOtherPenalties = 0;

            $cargoDetails['item_pricing_lines'] = $itemPricingLines;
            $pricingAudit['item_pricing_lines'] = $itemPricingLines;
            $pricingAudit['item_sale_total'] = $saleTotal;
            $pricingAudit['item_purchase_total'] = $itemizedPurchaseTotal;
        } elseif ($isAirShipment) {
            $volumeCbm = (float) ($booking->volume_cbm ?? data_get($booking->cargo_details, 'cargo.volume_cbm') ?? 0);
            $saleUnitOceanFreight = (float) $request->input('oceanFreight');
            $saleUnitOriginCharges = (float) $request->input('originCharges');
            $saleUnitExwCharges = 0;
            $saleUnitDthc = (float) $request->input('dthc');
            $saleUnitWarRiskSurcharge = (float) $request->input('warRiskSurcharge');
            $saleUnitOtherCharges = (float) $request->input('otherCharges');
            $saleUnitDestinationPortChangeFee = max(0, (float) $request->input('destinationPortChangeFee', 0));
            $saleUnitOtherPenalties = max(0, (float) $request->input('otherPenalties', 0));
            $saleOceanFreight = $saleUnitOceanFreight * $volumeCbm;
            $saleOriginCharges = $saleUnitOriginCharges * $volumeCbm;
            $saleExwCharges = 0;
            $saleDthc = $saleUnitDthc * $volumeCbm;
            $saleWarRiskSurcharge = $saleUnitWarRiskSurcharge * $volumeCbm;
            $saleOtherCharges = $saleUnitOtherCharges * $volumeCbm;
            $saleDestinationPortChangeFee = $saleUnitDestinationPortChangeFee * $volumeCbm;
            $saleOtherPenalties = $saleUnitOtherPenalties * $volumeCbm;
            $saleTotal = $saleOceanFreight
                + $saleOriginCharges
                + $saleDthc
                + $saleWarRiskSurcharge
                + $saleOtherCharges
                + $saleDestinationPortChangeFee
                + $saleOtherPenalties;
            $agentPurchaseQuantity = $volumeCbm > 0 ? $volumeCbm : $agentPurchaseQuantity;
            $cargoDetails['air_pricing'] = [
                'basis' => 'cbm',
                'volume_cbm' => round($volumeCbm, 4),
                'unit_prices' => [
                    'air_freight' => $saleUnitOceanFreight,
                    'origin_charges' => $saleUnitOriginCharges,
                    'exw_charges' => 0,
                    'dthc' => $saleUnitDthc,
                    'war_risk_surcharge' => $saleUnitWarRiskSurcharge,
                    'other_charges' => $saleUnitOtherCharges,
                    'destination_port_change_fee' => $saleUnitDestinationPortChangeFee,
                    'other_penalties' => $saleUnitOtherPenalties,
                ],
            ];
            $pricingAudit['air_pricing'] = $cargoDetails['air_pricing'];
        } else {
            $fclPricingLines = $this->fclPricingLinesFromRequest($request);
            $fclMixed = $fclPricingLines !== [];

            if ($fclMixed) {
                $fclTotalQuantity = max(0, (float) collect($fclPricingLines)->sum(fn (array $line) => (float) ($line['qty'] ?? 0)));
                $saleOceanFreight = collect($fclPricingLines)->sum(fn (array $line) => (float) ($line['total'] ?? 0));
                $saleOriginCharges = max(0, (float) $request->input('originCharges', 0)) * $fclTotalQuantity;
                $saleExwCharges = $isExwSeaShipment ? max(0, (float) $request->input('exwCharges', 0)) * $fclTotalQuantity : 0;
                $saleDthc = max(0, (float) $request->input('dthc', 0)) * $fclTotalQuantity;
                $saleWarRiskSurcharge = max(0, (float) $request->input('warRiskSurcharge', 0)) * $fclTotalQuantity;
                $saleOtherCharges = max(0, (float) $request->input('otherCharges', 0)) * $fclTotalQuantity;
                $saleDestinationPortChangeFee = max(0, (float) $request->input('destinationPortChangeFee', 0)) * $fclTotalQuantity;
                $saleOtherPenalties = max(0, (float) $request->input('otherPenalties', 0)) * $fclTotalQuantity;
                $saleTotal = $saleOceanFreight + $saleOriginCharges + $saleExwCharges + $saleDthc + $saleWarRiskSurcharge + $saleOtherCharges + $saleDestinationPortChangeFee + $saleOtherPenalties;
                $saleUnitOceanFreight = $saleTotal > 0 && $agentPurchaseQuantity > 0 ? $saleTotal / $agentPurchaseQuantity : 0;
                $saleUnitOriginCharges = $saleOriginCharges > 0 && $agentPurchaseQuantity > 0 ? $saleOriginCharges / $agentPurchaseQuantity : 0;
                $saleUnitExwCharges = $saleExwCharges > 0 && $agentPurchaseQuantity > 0 ? $saleExwCharges / $agentPurchaseQuantity : 0;
                $saleUnitDthc = $saleDthc > 0 && $agentPurchaseQuantity > 0 ? $saleDthc / $agentPurchaseQuantity : 0;
                $saleUnitWarRiskSurcharge = $saleWarRiskSurcharge > 0 && $agentPurchaseQuantity > 0 ? $saleWarRiskSurcharge / $agentPurchaseQuantity : 0;
                $saleUnitOtherCharges = $saleOtherCharges > 0 && $agentPurchaseQuantity > 0 ? $saleOtherCharges / $agentPurchaseQuantity : 0;
                $saleUnitDestinationPortChangeFee = $saleDestinationPortChangeFee > 0 && $agentPurchaseQuantity > 0 ? $saleDestinationPortChangeFee / $agentPurchaseQuantity : 0;
                $saleUnitOtherPenalties = $saleOtherPenalties > 0 && $agentPurchaseQuantity > 0 ? $saleOtherPenalties / $agentPurchaseQuantity : 0;
                $cargoDetails['fcl_pricing_lines'] = $fclPricingLines;
                $cargoDetails['fcl_standard_charges'] = [
                    ['label' => 'Ocean Freight', 'unit_price' => (float) $request->input('oceanFreight', 0), 'quantity' => $fclTotalQuantity, 'amount' => $saleOceanFreight],
                    ['label' => 'Origin Charges', 'unit_price' => (float) $request->input('originCharges', 0), 'quantity' => $fclTotalQuantity, 'amount' => $saleOriginCharges],
                    ['label' => __('messages.exw_charges_details'), 'unit_price' => $saleUnitExwCharges, 'quantity' => $fclTotalQuantity, 'amount' => $saleExwCharges],
                    ['label' => 'DTHC', 'unit_price' => (float) $request->input('dthc', 0), 'quantity' => $fclTotalQuantity, 'amount' => $saleDthc],
                    ['label' => 'War Risk & Fuel', 'unit_price' => (float) $request->input('warRiskSurcharge', 0), 'quantity' => $fclTotalQuantity, 'amount' => $saleWarRiskSurcharge],
                    ['label' => __('messages.other_charges_details'), 'unit_price' => (float) $request->input('otherCharges', 0), 'quantity' => $fclTotalQuantity, 'amount' => $saleOtherCharges],
                    ['label' => __('messages.destination_port_change_fee_details'), 'unit_price' => (float) $request->input('destinationPortChangeFee', 0), 'quantity' => $fclTotalQuantity, 'amount' => $saleDestinationPortChangeFee],
                    ['label' => __('messages.other_penalties_details'), 'unit_price' => (float) $request->input('otherPenalties', 0), 'quantity' => $fclTotalQuantity, 'amount' => $saleOtherPenalties],
                ];
                $pricingAudit['fcl_pricing_lines'] = $fclPricingLines;
                $pricingAudit['fcl_standard_charges'] = $cargoDetails['fcl_standard_charges'];
            } else {
                $saleUnitOceanFreight = (float) $request->input('oceanFreight');
                $saleUnitOriginCharges = (float) $request->input('originCharges');
                $saleUnitExwCharges = $isExwSeaShipment ? max(0, (float) $request->input('exwCharges', 0)) : 0;
                $saleUnitDthc = (float) $request->input('dthc');
                $saleUnitWarRiskSurcharge = (float) $request->input('warRiskSurcharge');
                $saleUnitOtherCharges = (float) $request->input('otherCharges');
                $saleUnitDestinationPortChangeFee = max(0, (float) $request->input('destinationPortChangeFee', 0));
                $saleUnitOtherPenalties = max(0, (float) $request->input('otherPenalties', 0));
                $saleOceanFreight = $saleUnitOceanFreight * $agentPurchaseQuantity;
                $saleOriginCharges = $saleUnitOriginCharges * $agentPurchaseQuantity;
                $saleExwCharges = $saleUnitExwCharges * $agentPurchaseQuantity;
                $saleDthc = $saleUnitDthc * $agentPurchaseQuantity;
                $saleWarRiskSurcharge = $saleUnitWarRiskSurcharge * $agentPurchaseQuantity;
                $saleOtherCharges = $saleUnitOtherCharges * $agentPurchaseQuantity;
                $saleDestinationPortChangeFee = $saleUnitDestinationPortChangeFee * $agentPurchaseQuantity;
                $saleOtherPenalties = $saleUnitOtherPenalties * $agentPurchaseQuantity;
                $saleTotal = $saleOceanFreight
                    + $saleOriginCharges
                    + $saleExwCharges
                    + $saleDthc
                    + $saleWarRiskSurcharge
                    + $saleOtherCharges
                    + $saleDestinationPortChangeFee
                    + $saleOtherPenalties;
            }
        }

        $customPricingCharges = $this->customPricingChargesFromRequest($request);
        $customPricingChargesTotal = collect($customPricingCharges)->sum(fn (array $line) => (float) ($line['amount'] ?? 0));
        $saleTotal += $customPricingChargesTotal;

        if ($customPricingCharges !== []) {
            $cargoDetails['custom_pricing_charges'] = $customPricingCharges;
            $pricingAudit['custom_pricing_charges'] = $customPricingCharges;
            $pricingAudit['custom_pricing_charges_total'] = $customPricingChargesTotal;
        } else {
            unset($cargoDetails['custom_pricing_charges']);
        }

        $agentPurchaseLines = $this->agentPurchaseLinesFromRequest($request);
        $agentPurchaseByTypeTotal = collect($agentPurchaseLines)->sum(fn (array $line) => (float) ($line['total'] ?? 0));
        $agentPurchaseQuantity = $agentPurchaseLines !== []
            ? collect($agentPurchaseLines)->sum(fn (array $line) => (float) ($line['qty'] ?? 0))
            : $agentPurchaseQuantity;

        if ($isAirShipment && $itemizedPurchaseTotal === null) {
            $agentPurchaseQuantity = (float) ($booking->volume_cbm ?? data_get($booking->cargo_details, 'cargo.volume_cbm') ?? 0);
        }

        $agentPurchaseUnitPrice = $itemizedPurchaseTotal !== null
            ? ($agentPurchaseQuantity > 0 ? $itemizedPurchaseTotal / $agentPurchaseQuantity : 0)
            : ($request->filled('agentPurchaseUnitPrice')
                ? (float) $request->input('agentPurchaseUnitPrice')
                : null);
        $agentPurchaseAmount = $itemizedPurchaseTotal !== null
            ? $itemizedPurchaseTotal
            : ($agentPurchaseLines !== []
            ? $agentPurchaseByTypeTotal
            : ($agentPurchaseUnitPrice !== null
                ? $agentPurchaseUnitPrice * $agentPurchaseQuantity
                : (float) $request->input('agentPurchaseAmount')));

        if ($agentPurchaseLines !== []) {
            $cargoDetails['agent_purchase_lines'] = $agentPurchaseLines;
            $pricingAudit['agent_purchase_lines'] = $agentPurchaseLines;
        }

        $booking->update([
            'status' => 'priced',
            'ocean_freight' => round($saleOceanFreight, 2),
            'origin_charges' => round($saleOriginCharges, 2),
            'exw_charges' => round($saleExwCharges, 2),
            'dthc' => round($saleDthc, 2),
            'war_risk_surcharge' => round($saleWarRiskSurcharge, 2),
            'other_charges' => round($saleOtherCharges, 2),
            'destination_port_change_fee' => round($saleDestinationPortChangeFee, 2),
            'other_penalties' => round($saleOtherPenalties, 2),
            'transit_days' => (int) $request->input('transitDays'),
            'validity_date' => $request->input('validityDate'),
            'pricing_notes' => $request->input('notes'),
            'agent_id' => $request->input('agent_id'),
            'agent_purchase_amount' => $agentPurchaseAmount,
            'agent_purchase_quantity' => $agentPurchaseQuantity,
            'agent_purchase_currency' => $pricingCurrency,
            'profit_amount' => $saleTotal - $agentPurchaseAmount,
            'agent_purchase_notes' => $request->input('agentPurchaseNotes'),
            'cargo_details' => $cargoDetails,
            'priced_by' => $request->input('pricedBy') ?: 'قسم التسعير',
            'priced_at' => Carbon::now(),
        ]);

        AuditLog::logAction($isPricingEdit ? 'PRICING_UPDATE' : 'PRICING', 'bookings', $booking->id, $old, [
            'sale_total' => $saleTotal,
            'sale_unit_total' => $saleUnitOceanFreight + $saleUnitOriginCharges + $saleUnitExwCharges + $saleUnitDthc + $saleUnitWarRiskSurcharge + $saleUnitOtherCharges + $saleUnitDestinationPortChangeFee + $saleUnitOtherPenalties,
            'exw_charges' => $saleExwCharges,
            'custom_pricing_charges_total' => $customPricingChargesTotal,
            'agent_id' => $booking->agent_id,
            'agent_purchase_unit_price' => $agentPurchaseUnitPrice,
            'agent_purchase_quantity' => $booking->agent_purchase_quantity,
            'agent_purchase_amount' => $booking->agent_purchase_amount,
            'profit_amount' => $booking->profit_amount,
            'status' => $booking->status,
        ] + $pricingAudit);

        $this->createNotification([
            'message' => $isPricingEdit
                ? "تم تعديل تسعير الطلب {$id}. يرجى مراجعة العرض المحدث قبل الإرسال أو الاعتماد."
                : "الطلب {$id} تم تسعيره وأُحيل إلى المبيعات",
            'booking_id' => $id,
            'read' => false,
            'target' => 'sales',
            'sound' => 'pricing',
        ]);

        $this->createNotification([
            'message' => $isPricingEdit
                ? "تم تحديث عرض سعر طلب الشحن {$id}. يرجى مراجعة العرض المحدث في صفحة تأكيد الطلب."
                : "تم تسعير طلب الشحن {$id}. يرجى مراجعة صفحة تأكيد الطلب لاعتماد العرض أو رفضه.",
            'booking_id' => $id,
            'read' => false,
            'target' => 'customer',
            'sound' => 'pricing',
        ]);

        return redirect()
            ->route('pricing.index')
            ->with('success', $isPricingEdit
                ? "تم تعديل تسعير الطلب {$id} بنجاح وإرجاعه للمبيعات للمراجعة"
                : "تم تسعير الطلب {$id} بنجاح وإرساله للمبيعات");
    }

    private function customPricingChargesFromRequest(Request $request): array
    {
        $rows = $request->input('customPricingCharges', []);

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                if (! is_array($row)) {
                    return null;
                }

                $label = trim((string) ($row['label'] ?? ''));
                $amount = max(0, (float) ($row['amount'] ?? 0));

                if ($label === '' || $amount <= 0) {
                    return null;
                }

                return [
                    'label' => mb_substr($label, 0, 120),
                    'amount' => round($amount, 2),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function itemPricingLinesFromRequest(Request $request): array
    {
        $rows = $request->input('itemPricingLines', []);

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                if (! is_array($row)) {
                    return null;
                }

                $name = trim((string) ($row['name'] ?? ''));
                $quantity = max(0, (float) ($row['quantity'] ?? 0));
                $saleUnitPrice = max(0, (float) ($row['sale_unit_price'] ?? 0));
                $purchaseUnitPrice = max(0, (float) ($row['purchase_unit_price'] ?? 0));

                if ($name === '' || $quantity <= 0) {
                    return null;
                }

                $unit = strtolower(trim((string) ($row['unit'] ?? 'piece')));
                if (! in_array($unit, ['piece', 'vehicle', 'cbm', 'kg', 'ton', 'container'], true)) {
                    $unit = 'piece';
                }

                return [
                    'name' => mb_substr($name, 0, 255),
                    'hs_code' => mb_substr(trim((string) ($row['hs_code'] ?? '')), 0, 40),
                    'quantity' => round($quantity, 4),
                    'unit' => $unit,
                    'sale_unit_price' => round($saleUnitPrice, 2),
                    'purchase_unit_price' => round($purchaseUnitPrice, 2),
                    'sale_total' => round($quantity * $saleUnitPrice, 2),
                    'purchase_total' => round($quantity * $purchaseUnitPrice, 2),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function fclPricingLinesFromRequest(Request $request): array
    {
        $rows = $request->input('fclPricingLines', []);

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                if (! is_array($row)) {
                    return null;
                }

                $qty = max(0.01, (float) ($row['qty'] ?? 0));
                $type = trim((string) ($row['type'] ?? ''));
                $unitPrice = max(0, (float) ($row['unit_price'] ?? 0));

                if ($type === '') {
                    return null;
                }

                $total = round($qty * $unitPrice, 2);

                return [
                    'type' => $type,
                    'qty' => $qty,
                    'unit_price' => round($unitPrice, 2),
                    'total' => $total,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function agentPurchaseLinesFromRequest(Request $request): array
    {
        $rows = $request->input('agentPurchaseLines', []);

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                if (! is_array($row)) {
                    return null;
                }

                $qty = max(0.01, (float) ($row['qty'] ?? 0));
                $type = trim((string) ($row['type'] ?? ''));
                $unitPrice = max(0, (float) ($row['unit_price'] ?? 0));

                if ($type === '') {
                    return null;
                }

                return [
                    'type' => $type,
                    'qty' => $qty,
                    'unit_price' => round($unitPrice, 2),
                    'total' => round($qty * $unitPrice, 2),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function landPricingLinesFromRequest(Request $request): array
    {
        $rows = $request->input('landPricingLines', $request->input('land_pricing_lines', []));

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                if (! is_array($row)) {
                    return null;
                }

                $qty = max(0, (float) ($row['qty'] ?? $row['tons'] ?? $row['weight_tons'] ?? 0));
                $truckQty = max(0, (float) ($row['truck_qty'] ?? $row['route_qty'] ?? 0));
                $fromCity = trim((string) ($row['from_city'] ?? $row['origin_city'] ?? ''));
                $toCity = trim((string) ($row['to_city'] ?? $row['destination_city'] ?? ''));
                $unitPrice = max(0, (float) ($row['unit_price'] ?? $row['price'] ?? 0));

                if ($qty <= 0 || $fromCity === '' || $toCity === '') {
                    return null;
                }

                return array_filter([
                    'qty' => $qty,
                    'truck_qty' => $truckQty > 0 ? $truckQty : null,
                    'unit' => 'ton',
                    'from_city' => $fromCity,
                    'to_city' => $toCity,
                    'unit_price' => $unitPrice,
                    'total' => round($qty * $unitPrice, 2),
                    'plate_number' => trim((string) ($row['plate_number'] ?? '')),
                    'notes' => trim((string) ($row['notes'] ?? '')),
                ], fn ($value) => $value !== null && $value !== '');
            })
            ->filter()
            ->values()
            ->all();
    }
}
