<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\ShipmentTrack;
use Carbon\CarbonInterface;

class TrackingMapData
{
    public static function mode(Booking $booking): string
    {
        if ($booking->isDoorToDoorShipment()) {
            $mode = strtoupper((string) data_get($booking->route_details, 'main_transport_mode'));
            return in_array($mode, ['SEA', 'AIR', 'LAND'], true) ? $mode : 'MULTIMODAL';
        }
        if ($booking->isLandShipment()) {
            return 'LAND';
        }
        if (strtoupper((string) $booking->shipment_type) === 'AIR' || data_get($booking->route_details, 'category') === 'air') {
            return 'AIR';
        }

        return 'SEA';
    }

    public static function schedule(ShipmentTrack $track, ?CarbonInterface $at = null): array
    {
        $now = $at ?? now();
        $etd = $track->booking->carrier_etd ?? $track->etd;
        $eta = $track->booking->carrier_eta ?? $track->eta;
        $valid = $etd && $eta && $eta->greaterThan($etd);
        $fraction = $valid ? max(0, min(1, ($now->getTimestamp() - $etd->getTimestamp()) / ($eta->getTimestamp() - $etd->getTimestamp()))) : null;

        return [
            'etd' => $etd?->toDateString(),
            'eta' => $eta?->toDateString(),
            'fraction' => $fraction,
            'percent' => $fraction === null ? null : round($fraction * 100, 1),
            'state' => ! $valid ? 'unavailable' : ($now->lessThan($etd) ? 'scheduled' : ($now->greaterThanOrEqualTo($eta) ? 'eta_passed' : 'in_transit')),
            'total_days' => $valid ? (int) round(($eta->getTimestamp() - $etd->getTimestamp()) / 86400) : null,
            'remaining_days' => $valid ? (int) max(0, ceil(($eta->getTimestamp() - $now->getTimestamp()) / 86400)) : null,
            'calculated_at' => $now->toIso8601String(),
            'timezone' => config('app.timezone'),
            'is_estimate' => true,
        ];
    }
}
