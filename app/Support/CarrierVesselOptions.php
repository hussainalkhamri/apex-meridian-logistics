<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Vessel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CarrierVesselOptions
{
    public static function all(): Collection
    {
        $defaultVessels = collect([
            'MV Maritime Star',
            'MV Bahriya Pioneer',
            'MV Ocean Voyager',
            'MV Red Sea Explorer',
            'MV Asian Bridge',
        ]);

        $fleetVessels = Vessel::query()
            ->orderBy('name')
            ->pluck('name');

        $previouslyUsedVessels = Booking::query()
            ->whereNotNull('carrier_vessel')
            ->where('carrier_vessel', '<>', '')
            ->orderBy('carrier_vessel')
            ->pluck('carrier_vessel');

        return $defaultVessels
            ->merge($fleetVessels)
            ->merge($previouslyUsedVessels)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => Str::lower($name))
            ->sort(fn ($first, $second) => strnatcasecmp($first, $second))
            ->values();
    }
}
