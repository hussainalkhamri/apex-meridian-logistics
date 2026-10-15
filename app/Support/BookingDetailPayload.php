<?php

namespace App\Support;

use App\Models\Booking;

class BookingDetailPayload
{
    public static function fromBooking(Booking $booking, bool $hideStalePricing = false): array
    {
        $booking->loadMissing(['agent', 'invoices', 'shipmentTrack']);

        $parent = $booking->parent_booking_id
            ? Booking::find($booking->parent_booking_id)
            : null;

        $customer = self::resolveCustomer($booking, $parent);
        $cargo = self::resolveCargo($booking, $parent);
        $route = self::resolveRoute($booking, $parent);
        $shipping = self::resolveShipping($booking, $parent, $cargo, $route);
        $invoice = $booking->invoices->first();
        $repricingRequired = $booking->status === 'needs_pricing';
        $shouldHidePricing = $hideStalePricing && $repricingRequired;

        return [
            'id' => $booking->id,
            'parentBookingId' => $booking->parent_booking_id,
            'createdAt' => $booking->created_at->toISOString(),
            'customer' => $customer,
            'cargo' => $cargo,
            'route' => $route,
            'status' => $booking->status,
            'repricingRequired' => $repricingRequired,
            'salesNotes' => $booking->sales_notes,
            'decidedAt' => $booking->decided_at ? $booking->decided_at->toISOString() : null,
            'shipper' => $shipping['shipper'],
            'consignee' => $shipping['consignee'],
            'notifyParty' => $shipping['notifyParty'],
            'billType' => $shipping['billType'],
            'typeOfMove' => $shipping['typeOfMove'],
            'jobNo' => $shipping['jobNo'],
            'overseasAgent' => $shipping['overseasAgent'],
            'placeOfDelivery' => $shipping['placeOfDelivery'],
            'quantityPackages' => $shipping['quantityPackages'],
            'measurementCbm' => $shipping['measurementCbm'],
            'containerSealNo' => $shipping['containerSealNo'],
            'customNotes' => $shipping['customNotes'],
            'pricing' => ($booking->ocean_freight && ! $shouldHidePricing) ? [
                'oceanFreight' => $booking->ocean_freight,
                'originCharges' => $booking->origin_charges,
                'exwCharges' => $booking->exw_charges,
                'showExwCharges' => $booking->shouldShowExwCharges(),
                'dthc' => $booking->dthc,
                'warRiskSurcharge' => $booking->war_risk_surcharge,
                'otherCharges' => $booking->other_charges,
                'destinationPortChangeFee' => $booking->destination_port_change_fee,
                'otherPenalties' => $booking->other_penalties,
                'customPricingCharges' => $booking->customPricingCharges(),
                'customPricingChargesTotal' => $booking->customPricingChargesTotal(),
                'transitDays' => $booking->transit_days,
                'validityDate' => $booking->validity_date ? $booking->validity_date->toDateString() : null,
                'notes' => $booking->pricing_notes,
                'pricedBy' => $booking->priced_by,
                'pricedAt' => $booking->priced_at ? $booking->priced_at->toISOString() : null,
                'total' => $booking->pricingTotal(),
                'currency' => $booking->pricingCurrency(),
                'currencySymbol' => $booking->pricingCurrencySymbol(),
                'isLand' => $booking->isLandShipment(),
                'landPricingLines' => $booking->landPricingLines(),
                'landPricingQuantity' => $booking->landPricingQuantity(),
                'isDoorToDoor' => $booking->isDoorToDoorShipment(),
                'doorToDoorPricing' => $booking->doorToDoorPricing(),
                'itemPricingLines' => $booking->itemPricingLines(),
                'agent' => $booking->agent ? [
                    'id' => $booking->agent->id,
                    'name' => $booking->agent->name,
                    'type' => $booking->agent->type,
                ] : null,
                'agentPurchaseAmount' => $booking->agentPurchaseTotal(),
                'agentPurchaseQuantity' => $booking->agent_purchase_quantity,
                'profitAmount' => $booking->profitAmount(),
                'agentPurchaseNotes' => $booking->agent_purchase_notes,
            ] : null,
            'carrier' => $booking->carrier_vessel ? [
                'vessel' => $booking->carrier_vessel,
                'voyage' => $booking->carrier_voyage,
                'bookingRef' => $booking->carrier_booking_ref,
                'etd' => $booking->carrier_etd ? $booking->carrier_etd->toDateString() : null,
                'eta' => $booking->carrier_eta ? $booking->carrier_eta->toDateString() : null,
                'bookedBy' => $booking->carrier_booked_by,
                'bookedAt' => $booking->carrier_booked_at ? $booking->carrier_booked_at->toISOString() : null,
            ] : null,
            'invoice' => $invoice ? [
                'id' => $invoice->id,
                'amount' => $invoice->amount,
                'status' => $invoice->status,
                'issuedAt' => $invoice->issued_at->toDateString(),
                'dueAt' => $invoice->due_at->toDateString(),
            ] : null,
            'tracking' => $booking->shipmentTrack ? [
                'id' => $booking->shipmentTrack->id,
                'status' => $booking->shipmentTrack->status,
                'progress' => $booking->shipmentTrack->progress,
            ] : null,
        ];
    }

    private static function resolveCustomer(Booking $booking, ?Booking $parent): array
    {
        if (! blank($booking->customer_company) || ! blank($booking->customer_contact_name) || ! blank($booking->customer_phone) || ! blank($booking->customer_email)) {
            return [
                'company' => $booking->customer_company,
                'contactName' => $booking->customer_contact_name,
                'phone' => $booking->customer_phone,
                'email' => $booking->customer_email,
            ];
        }

        return [
            'company' => $parent?->customer_company,
            'contactName' => $parent?->customer_contact_name,
            'phone' => $parent?->customer_phone,
            'email' => $parent?->customer_email,
        ];
    }

    private static function resolveCargo(Booking $booking, ?Booking $parent): array
    {
        $cargoDetails = $booking->cargo_details ?: ($parent?->cargo_details ?? []);
        $attachments = $booking->attachments ?: $parent?->attachments;
        $isLandShipment = $booking->isLandShipment() || ($parent?->isLandShipment() ?? false);
        $landShipmentLines = $isLandShipment ? $booking->landShipmentLines() : [];

        if ($landShipmentLines === [] && $parent) {
            $landShipmentLines = $parent->landShipmentLines();
        }

        $landShipmentTotalQty = collect($landShipmentLines)->sum(fn (array $line) => (float) ($line['qty'] ?? 0));
        $landShipmentSummary = data_get($cargoDetails, 'land_shipment_summary');

        if (blank($landShipmentSummary) && $landShipmentLines !== []) {
            $landShipmentSummary = collect($landShipmentLines)
                ->map(function (array $line) {
                    $qty = (float) ($line['qty'] ?? 0);
                    $fromCity = trim((string) ($line['from_city'] ?? ''));
                    $toCity = trim((string) ($line['to_city'] ?? ''));

                    if ($qty <= 0 || $fromCity === '' || $toCity === '') {
                        return null;
                    }

                    $qtyLabel = fmod($qty, 1.0) === 0.0 ? (string) (int) $qty : (string) $qty;

                    return "{$qtyLabel}x{$fromCity} → {$toCity}";
                })
                ->filter()
                ->implode(' + ');
        }

        return [
            'shipmentType' => $booking->shipment_type ?: ($parent?->shipment_type ?? null),
            'containerType' => $booking->container_type ?? ($parent?->container_type ?? null),
            'containerQty' => $booking->container_qty ?? ($parent?->container_qty ?? null),
            'containerSummary' => $booking->containerSummary() ?: $parent?->containerSummary(),
            'containerTotalQty' => $booking->containerTotalQuantity() ?: $parent?->containerTotalQuantity(),
            'shipmentLabel' => $booking->shipmentDisplay() ?: $parent?->shipmentDisplay(),
            'goodsDescription' => $booking->goods_description ?: ($parent?->goods_description ?? null),
            'notes' => $booking->notes ?: ($parent?->notes ?? null),
            'weightKg' => $booking->weight_kg ?? ($parent?->weight_kg ?? null),
            'volumeCbm' => $booking->volume_cbm ?? ($parent?->volume_cbm ?? null),
            'dangerous' => $booking->dangerous ?? ($parent?->dangerous ?? false),
            'documents' => $booking->documents ?: ($parent?->documents ?? null),
            'attachments' => self::decodeList($attachments),
            'cargoDetails' => is_array($cargoDetails) ? $cargoDetails : [],
            'isLand' => $isLandShipment,
            'landShipmentLines' => $landShipmentLines,
            'landShipmentTotalQty' => $landShipmentTotalQty,
            'landShipmentSummary' => $landShipmentSummary ?: null,
            'landPalletCount' => $isLandShipment ? ($booking->volume_cbm ?? ($parent?->volume_cbm ?? null)) : null,
            'landCartonCount' => $isLandShipment ? data_get($cargoDetails, 'land_carton_count') : null,
        ];
    }

    private static function resolveRoute(Booking $booking, ?Booking $parent): array
    {
        return [
            'pol' => $booking->pol ?: ($parent?->pol ?? null),
            'pod' => $booking->pod ?: ($parent?->pod ?? null),
            'details' => $booking->route_details ?: ($parent?->route_details ?? null),
        ];
    }

    private static function resolveShipping(Booking $booking, ?Booking $parent, array $cargo, array $route): array
    {
        $quantityPackages = self::firstFilled($booking->quantity_packages, $parent?->quantity_packages);

        if (blank($quantityPackages)) {
            if (! blank($cargo['containerSummary'])) {
                $quantityPackages = $cargo['containerSummary'];
            } elseif (! blank($cargo['containerQty']) && ! blank($cargo['containerType'])) {
                $quantityPackages = "{$cargo['containerQty']} x {$cargo['containerType']}";
            } elseif (! blank($cargo['shipmentType'])) {
                $quantityPackages = $cargo['shipmentType'];
            }
        }

        return [
            'shipper' => self::firstFilled($booking->shipper, $parent?->shipper),
            'consignee' => self::firstFilled($booking->consignee, $parent?->consignee),
            'notifyParty' => self::firstFilled($booking->notify_party, $parent?->notify_party),
            'billType' => self::firstFilled($booking->bill_type, $parent?->bill_type, 'TELEX RELEASE'),
            'typeOfMove' => self::firstFilled($booking->type_of_move, $parent?->type_of_move, 'CY-CY'),
            'jobNo' => self::firstFilled($booking->job_no, $parent?->job_no),
            'overseasAgent' => self::firstFilled($booking->overseas_agent, $parent?->overseas_agent),
            'placeOfDelivery' => self::firstFilled($booking->place_of_delivery, $parent?->place_of_delivery, $route['pod'] ?? null),
            'quantityPackages' => $quantityPackages,
            'measurementCbm' => self::firstFilled($booking->measurement_cbm, $parent?->measurement_cbm, $cargo['volumeCbm'] ?? null),
            'containerSealNo' => self::firstFilled($booking->container_seal_no, $parent?->container_seal_no),
            'customNotes' => self::firstFilled($booking->custom_notes, $parent?->custom_notes),
        ];
    }

    private static function firstFilled(...$values): mixed
    {
        foreach ($values as $value) {
            if (is_string($value)) {
                $value = trim($value);
            }

            if (! blank($value)) {
                return $value;
            }
        }

        return null;
    }

    private static function decodeList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if (blank($value)) {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }
}
