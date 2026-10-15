<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Port;
use Illuminate\Support\Str;

class TrackingGeoCatalog
{
    private array $datasets = [];

    public static function normalize(string $name): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower(Str::ascii($name))));
    }

    public function endpoint(Booking $booking, string $side, string $mode): ?array
    {
        $details = data_get($booking->route_details, $side, []);
        $details = is_array($details) ? $details : [];
        $legacy = (string) ($side === 'origin' ? $booking->pol : $booking->pod);
        $country = strtoupper((string) ($details['country_code'] ?? ''));
        $type = $details['type'] ?? ($mode === 'AIR' ? 'airport' : ($mode === 'SEA' ? 'port' : 'city'));

        if ($type === 'airport') {
            $code = strtoupper(trim((string) ($details['airport_code'] ?? $details['airport'] ?? $details['code'] ?? $legacy)));
            $row = $this->dataset('airports')[$code] ?? null;
            if ($row && ($country === '' || $row['country'] === $country)) {
                return $row + ['label' => $code, 'precision' => 'airport', 'source' => 'OurAirports'];
            }

            return null;
        }

        if ($type === 'port') {
            $name = (string) ($details['port'] ?? $legacy);
            $port = Port::query()->where(fn ($query) => $query->where('name', $name)->orWhere('code', $name))
                ->when($country !== '', fn ($query) => $query->where('country_code', $country))->first();
            $catalogPort = collect(config('ports.ports', []))->first(fn ($row) =>
                ($row['name'] === $name || ($row['code'] ?? null) === $name)
                && ($country === '' || ($row['country_code'] ?? '') === $country));
            $code = strtoupper((string) ($port?->code ?: ($catalogPort['code'] ?? $details['code'] ?? $name)));
            $row = $this->dataset('ports')[$code] ?? null;
            if ($row && ($country === '' || $row['country'] === $country)) {
                return $row + ['label' => $name, 'precision' => 'port_locality', 'source' => 'UN/LOCODE'];
            }

            // Some UN/LOCODE ports have no coordinates; use the known port's city, never an arbitrary match.
            $portCountry = $country ?: ($port?->country_code ?: ($catalogPort['country_code'] ?? ''));
            $portName = $port?->name ?: ($catalogPort['name'] ?? null);
            return $portName && $portCountry ? $this->city($portName, $portCountry) : null;
        }

        // Address shipments use city-level geography; private street addresses never leave the app.
        $name = trim((string) ($details['city'] ?? ($type === 'address' ? '' : $legacy)));
        return $this->city($name, $country);
    }

    private function city(string $name, string $country): ?array
    {
        $key = self::normalize($name);
        $country = $country ?: ($this->dataset('city-countries')[$key] ?? null);
        if (! is_string($country) || ! preg_match('/^[A-Z]{2}$/', $country)) {
            return null;
        }
        $cities = $this->dataset('cities/'.$country);
        $ids = $cities['names'][$key] ?? [];
        if (count($ids) !== 1 || ! isset($cities['places'][$ids[0]])) return null;

        return $cities['places'][$ids[0]] + ['label' => $name, 'precision' => 'city', 'source' => 'GeoNames'];
    }

    private function dataset(string $name): array
    {
        if (! isset($this->datasets[$name])) {
            $path = resource_path('data/tracking/'.$name.'.json');
            $this->datasets[$name] = is_file($path) ? json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) : [];
        }

        return $this->datasets[$name];
    }
}
