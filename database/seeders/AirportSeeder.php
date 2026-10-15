<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;

class AirportSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('airports.airports', []) as $index => $airportData) {
            $code = strtoupper((string) ($airportData['iata_code'] ?? ''));
            if ($code === '') {
                continue;
            }

            Airport::updateOrCreate(
                ['iata_code' => $code],
                [
                    'name' => $airportData['name'] ?? $code,
                    'city' => $airportData['city'] ?? null,
                    'country' => $airportData['country'] ?? null,
                    'country_code' => isset($airportData['country_code']) ? strtoupper((string) $airportData['country_code']) : null,
                    'is_active' => true,
                    'sort_order' => $index,
                ]
            );
        }
    }
}
