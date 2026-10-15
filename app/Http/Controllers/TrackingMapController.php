<?php

namespace App\Http\Controllers;

use App\Models\ShipmentTrack;
use App\Support\TrackingGeoCatalog;
use App\Support\TrackingMapData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class TrackingMapController extends Controller
{
    public function show(Request $request, string $id, TrackingGeoCatalog $catalog)
    {
        $query = ShipmentTrack::with('booking')->whereHas('booking');
        if ($request->routeIs('customer.tracking.map')) {
            $query->whereHas('booking', fn ($query) => $query->where('customer_id', auth('customer')->id()));
        }
        $track = $query->findOrFail($id);
        $mode = TrackingMapData::mode($track->booking);
        $origin = $catalog->endpoint($track->booking, 'origin', $mode);
        $destination = $catalog->endpoint($track->booking, 'destination', $mode);

        $road = null;
        if ($mode === 'LAND' && $origin && $destination && config('tracking.osrm_url')) {
            $coordinates = implode(',', $origin['coordinates']).';'.implode(',', $destination['coordinates']);
            $key = 'tracking:road:v1:'.hash('sha256', config('tracking.osrm_url').$coordinates);
            $road = Cache::remember($key, now()->addDay(), function () use ($coordinates) {
                try {
                    $response = Http::connectTimeout(3)->timeout(8)->get(rtrim(config('tracking.osrm_url'), '/').'/route/v1/driving/'.$coordinates, [
                        'overview' => 'simplified', 'geometries' => 'geojson', 'steps' => 'false', 'radiuses' => '25000;25000',
                    ]);
                    $geometry = $response->successful() && $response->json('code') === 'Ok' ? $response->json('routes.0.geometry') : null;
                    return is_array($geometry) && ($geometry['type'] ?? '') === 'LineString' ? $geometry : [];
                } catch (Throwable) {
                    return [];
                }
            });
        }

        return response()->json([
            'id' => $track->id,
            'mode' => $mode,
            'origin' => $origin,
            'destination' => $destination,
            'schedule' => TrackingMapData::schedule($track),
            'road_geometry' => $road ?: null,
        ])->header('Cache-Control', 'private, no-store');
    }
}
