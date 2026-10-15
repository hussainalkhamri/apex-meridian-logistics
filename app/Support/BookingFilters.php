<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class BookingFilters
{
    public const TRANSPORT_MODES = ['all', 'sea', 'air', 'land'];

    public const SEA_TYPES = ['all', 'FCL', 'LCL', 'Bulk', 'RoRo', 'Flat Container'];

    public const STATUSES = [
        'all',
        'new',
        'needs_pricing',
        'priced',
        'sent',
        'customer_approved',
        'customer_rejected',
        'approved',
        'booked',
        'rejected',
    ];

    public static function fromRequest(Request $request): array
    {
        $query = $request->query('q', '');
        $status = $request->query('status', 'all');
        $transportMode = $request->query('transport_mode', 'all');
        $seaType = $request->query('sea_type', 'all');

        $query = is_scalar($query) ? trim((string) $query) : '';
        $status = is_scalar($status) ? (string) $status : 'all';
        $transportMode = is_scalar($transportMode) ? (string) $transportMode : 'all';
        $seaType = is_scalar($seaType) ? (string) $seaType : 'all';

        if (! in_array($status, self::STATUSES, true)) {
            $status = 'all';
        }

        if (! in_array($transportMode, self::TRANSPORT_MODES, true)) {
            $transportMode = 'all';
        }

        if (! in_array($seaType, self::SEA_TYPES, true)) {
            $seaType = 'all';
        }

        if ($seaType !== 'all' && $transportMode === 'all') {
            $transportMode = 'sea';
        }

        return [
            'q' => mb_substr($query, 0, 120),
            'status' => $status,
            'transport_mode' => $transportMode,
            'sea_type' => $seaType,
        ];
    }

    public static function apply(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $status = $filters['status'] ?? 'all';
        $transportMode = $filters['transport_mode'] ?? 'all';
        $seaType = $filters['sea_type'] ?? 'all';

        if ($search !== '') {
            $query->where(function (Builder $subQuery) use ($search): void {
                $subQuery->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('customer_company', 'LIKE', "%{$search}%")
                    ->orWhere('customer_contact_name', 'LIKE', "%{$search}%")
                    ->orWhere('customer_phone', 'LIKE', "%{$search}%")
                    ->orWhere('customer_email', 'LIKE', "%{$search}%")
                    ->orWhere('goods_description', 'LIKE', "%{$search}%")
                    ->orWhere('pol', 'LIKE', "%{$search}%")
                    ->orWhere('pod', 'LIKE', "%{$search}%");
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($transportMode === 'sea') {
            $query->whereIn('shipment_type', ['FCL', 'LCL', 'Bulk', 'RoRo', 'Flat Container']);

            if ($seaType !== 'all') {
                $query->where('shipment_type', $seaType);
            }
        } elseif ($transportMode === 'air') {
            $query->where('shipment_type', 'AIR');
        } elseif ($transportMode === 'land') {
            $query->where('shipment_type', 'LAND');
        }

        return $query;
    }
}
