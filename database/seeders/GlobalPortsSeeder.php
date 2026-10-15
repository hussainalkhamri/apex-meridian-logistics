<?php

namespace Database\Seeders;

use App\Models\Port;
use Illuminate\Database\Seeder;

class GlobalPortsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('ports.ports', []) as $index => $portData) {
            $port = null;

            if (! empty($portData['code'])) {
                $port = Port::where('code', $portData['code'])->first();
            }

            if (! $port) {
                $port = Port::where('name', $portData['name'])->first();
            }

            $payload = [
                'name' => $portData['name'],
                'code' => $portData['code'] ?? null,
                'country' => $portData['country'] ?? null,
                'country_code' => $portData['country_code'] ?? null,
                'country_flag' => $portData['country_flag'] ?? null,
                'is_active' => true,
                'sort_order' => $index,
            ];

            if ($port) {
                $port->fill($payload)->save();
                continue;
            }

            Port::create($payload);
        }
    }
}
