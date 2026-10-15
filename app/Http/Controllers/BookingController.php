<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Port;
use App\Models\Voucher;
use App\Support\BookingDetailPayload;
use App\Support\BookingStatus;
use App\Support\CarrierVesselOptions;
use App\Support\PortCatalog;
use App\Support\SystemId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    private const DEFAULT_AIR_VOLUMETRIC_DIVISOR = 6000;

    public function create(Request $request)
    {
        $parentBooking = null;
        if ($request->filled('parent_id')) {
            $parentBooking = Booking::find($request->query('parent_id'));
        }

        $customers = Customer::orderBy('company')->get();
        $ports = Port::where('is_active', true)
            ->orderBy('country')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $portCountries = $ports
            ->map(fn (Port $port) => PortCatalog::countryOptionFromPort($port))
            ->filter()
            ->unique('name')
            ->sortBy('name')
            ->values();

        $portsForBooking = $ports->map(function (Port $port) {
            $country = PortCatalog::resolveCountry($port->country, $port->country_code, $port->country_flag);

            return [
                'name' => $port->name,
                'code' => $port->code,
                'country' => $country['country'],
                'country_code' => $country['country_code'],
                'country_flag' => $country['country_flag'],
            ];
        })->values();
        $airportsForBooking = $this->airportsForBooking();
        $airportCountriesForBooking = $this->airportCountriesForBooking($airportsForBooking, $portCountries);

        $customersData = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'company' => $customer->company,
                'contact_name' => $customer->contact_name,
                'phone' => $customer->phone,
                'email' => $customer->email,
            ];
        })->values();

        return view('booking', compact('customers', 'customersData', 'ports', 'portCountries', 'portsForBooking', 'airportsForBooking', 'airportCountriesForBooking', 'parentBooking'));
    }

    private function airportsForBooking()
    {
        $airports = Schema::hasTable('airports')
            ? Airport::where('is_active', true)
                ->orderBy('country')
                ->orderBy('city')
                ->orderBy('iata_code')
                ->get()
            : collect(config('airports.airports', []));

        return $airports->map(function ($airport) {
            $code = strtoupper((string) data_get($airport, 'iata_code'));
            $name = (string) data_get($airport, 'name', $code);
            $city = (string) data_get($airport, 'city', '');
            $country = (string) data_get($airport, 'country', '');
            $countryCode = strtoupper((string) data_get($airport, 'country_code', ''));

            return [
                'iata_code' => $code,
                'code' => $code,
                'name' => $name,
                'city' => $city,
                'country' => $country,
                'country_code' => $countryCode,
                'display_name' => trim($city . ($city && $code ? " ({$code})" : $code) . ($name ? " - {$name}" : '')),
            ];
        })->values();
    }

    private function airportCountriesForBooking($airportsForBooking, $portCountries)
    {
        $airportCountries = collect($airportsForBooking)
            ->map(function (array $airport) {
                $country = PortCatalog::resolveCountry(
                    $airport['country'] ?? null,
                    $airport['country_code'] ?? null
                );

                if (blank($country['country'])) {
                    return null;
                }

                return [
                    'name' => $country['country'],
                    'code' => $country['country_code'],
                    'flag' => $country['country_flag'],
                ];
            })
            ->filter();

        if ($airportCountries->isEmpty()) {
            return collect($portCountries)->values();
        }

        return $airportCountries
            ->filter(fn (array $country) => filled($country['name'] ?? null))
            ->unique(fn (array $country) => ($country['code'] ?? null) ?: $country['name'])
            ->sortBy('name')
            ->values();
    }

    private function normalizeBookingRequest(Request $request): void
    {
        $category = strtolower(str_replace('-', '_', (string) $request->input('shipping_category', 'sea')));
        if (in_array($category, ['contracting', 'door', 'door_to_door'], true)) {
            $category = 'door_to_door';
        }

        $routeMode = strtoupper(str_replace('-', '_', (string) $request->input('route_mode')));
        if (in_array($routeMode, ['DTD', 'DOOR', 'DOOR_TO_DOOR'], true)) {
            $routeMode = 'DOOR_TO_DOOR';
        }

        if ($routeMode === '') {
            $routeMode = match ($category) {
                'land' => 'LAND',
                'air' => 'AIR',
                'door_to_door' => 'DOOR_TO_DOOR',
                default => '',
            };
        }

        $grossWeight = $request->input('gross_weight_kg', $request->input('weightKg'));
        $volumeCbm = $request->input('volume_cbm', $request->input('volumeCbm'));
        $packages = $this->extractPackages($request);
        if ($packages !== []) {
            $packageMetrics = $this->packageMetrics($packages, $request, $routeMode === 'AIR');
            if ($grossWeight === null || $grossWeight === '') {
                $grossWeight = $packageMetrics['gross_weight_kg'];
            }
            if ($volumeCbm === null || $volumeCbm === '') {
                $volumeCbm = $packageMetrics['volume_cbm'];
            }
        }
        $merge = [
            'shipping_category' => $category,
            'route_mode' => $routeMode,
            'gross_weight_kg' => $grossWeight,
            'volume_cbm' => $volumeCbm,
            'weightKg' => $grossWeight,
            'volumeCbm' => $volumeCbm,
            'commercial_value' => $request->input('commercial_value', $request->input('declared_value')),
        ];

        if ($routeMode === 'AIR') {
            $originAirport = $this->airportCode($request->input('origin_airport', $request->input('pol')));
            $destinationAirport = $this->airportCode($request->input('destination_airport', $request->input('pod')));

            $merge['origin_airport'] = $originAirport;
            $merge['destination_airport'] = $destinationAirport;
            $merge['pol'] = $originAirport;
            $merge['pod'] = $destinationAirport;
        }

        if ($routeMode === 'DOOR_TO_DOOR') {
            $pickupAddress = $this->cleanString($request->input('pickup_address', $request->input('pol')));
            $deliveryAddress = $this->cleanString($request->input('delivery_address', $request->input('pod')));

            $merge['pickup_address'] = $pickupAddress;
            $merge['delivery_address'] = $deliveryAddress;
            $merge['main_transport_mode'] = $this->normalizeMainTransportMode($request->input('main_transport_mode'));
            $merge['pol'] = $pickupAddress;
            $merge['pod'] = $deliveryAddress;
        }

        $request->merge([
            ...$merge,
        ]);
    }

    private function cleanString(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function airportCode(mixed $value): string
    {
        return strtoupper(preg_replace('/\s+/', '', $this->cleanString($value)) ?? '');
    }

    private function normalizeMainTransportMode(mixed $value): string
    {
        $mode = strtoupper(str_replace('-', '_', $this->cleanString($value)));

        return in_array($mode, ['AUTO', 'AIR', 'SEA', 'LAND', 'MULTIMODAL'], true)
            ? $mode
            : 'AUTO';
    }

    private function numericInput(Request $request, string $key, float $default = 0): float
    {
        $value = $request->input($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return round((float) $value, 4);
    }

    private function integerInput(Request $request, string $key, int $default = 0): int
    {
        $value = $request->input($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return max(0, (int) $value);
    }

    private function extractFclContainers(Request $request): array
    {
        $rows = $request->input('containers', []);

        if (! is_array($rows) || empty($rows)) {
            $rows = [[
                'type' => $request->input('containerType'),
                'qty' => $request->input('containerQty'),
            ]];
        }

        $containers = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = trim((string) ($row['type'] ?? ''));
            $qty = max(1, (int) ($row['qty'] ?? 1));

            if ($type === '') {
                continue;
            }

            if (isset($containers[$type])) {
                $containers[$type]['qty'] += $qty;
                continue;
            }

            $containers[$type] = [
                'type' => $type,
                'qty' => $qty,
            ];
        }

        return array_values($containers);
    }

    private function fclContainerSummary(array $containers): string
    {
        return collect($containers)
            ->map(fn (array $container) => "{$container['qty']}x{$container['type']}")
            ->implode(' + ');
    }

    private function primaryFclContainer(array $cargoDetails, Request $request): array
    {
        $containers = $cargoDetails['containers'] ?? [];

        if (is_array($containers) && isset($containers[0]) && is_array($containers[0])) {
            return [
                'type' => (string) ($containers[0]['type'] ?? $request->input('containerType')),
                'qty' => (int) ($containers[0]['qty'] ?? $request->input('containerQty', 1)),
            ];
        }

        return [
            'type' => (string) $request->input('containerType'),
            'qty' => (int) $request->input('containerQty', 1),
        ];
    }

    private function extractLandShipments(Request $request): array
    {
        $rows = $request->input('land_shipments', []);

        if (! is_array($rows) || $rows === []) {
            $rows = [[
                'qty' => $request->input('land_truck_qty', $request->input('containerQty', 1)),
                'from_city' => $request->input('land_from_city', $request->input('pol')),
                'to_city' => $request->input('land_to_city', $request->input('pod')),
            ]];
        }

        $shipments = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $qty = max(1, (int) ($row['qty'] ?? $row['truck_qty'] ?? 1));
            $fromCity = trim((string) ($row['from_city'] ?? $row['origin_city'] ?? ''));
            $toCity = trim((string) ($row['to_city'] ?? $row['destination_city'] ?? ''));

            if ($fromCity === '' || $toCity === '') {
                continue;
            }

            $key = strtolower($fromCity) . '|' . strtolower($toCity);
            if (isset($shipments[$key])) {
                $shipments[$key]['qty'] += $qty;
                continue;
            }

            $shipments[$key] = [
                'qty' => $qty,
                'from_city' => $fromCity,
                'to_city' => $toCity,
            ];
        }

        return array_values($shipments);
    }

    private function landShipmentSummary(array $shipments): string
    {
        return collect($shipments)
            ->map(function (array $shipment) {
                $qty = (int) ($shipment['qty'] ?? $shipment['truck_qty'] ?? 0);
                $from = trim((string) ($shipment['from_city'] ?? $shipment['origin_city'] ?? ''));
                $to = trim((string) ($shipment['to_city'] ?? $shipment['destination_city'] ?? ''));

                if ($qty <= 0 || $from === '' || $to === '') {
                    return null;
                }

                return "{$qty}x{$from} → {$to}";
            })
            ->filter()
            ->implode(' + ');
    }

    private function extractPackages(Request $request): array
    {
        $rawPackages = $request->input('packages', []);
        if (is_string($rawPackages)) {
            $decoded = json_decode($rawPackages, true);
            $rawPackages = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($rawPackages)) {
            return [];
        }

        $dimensionFactor = $request->input('shipmentType') === 'DOOR_TO_DOOR'
            && $request->input('package_dimension_unit') === 'm' ? 100 : 1;
        $packages = [];
        foreach ($rawPackages as $package) {
            if (! is_array($package)) {
                continue;
            }

            $quantity = max(0, (int) ($package['quantity'] ?? 0));
            $length = (float) ($package['length_cm'] ?? 0) * $dimensionFactor;
            $width = (float) ($package['width_cm'] ?? 0) * $dimensionFactor;
            $height = (float) ($package['height_cm'] ?? 0) * $dimensionFactor;
            $weight = (float) ($package['weight_kg'] ?? 0);

            if ($quantity <= 0 && $length <= 0 && $width <= 0 && $height <= 0 && $weight <= 0) {
                continue;
            }

            $packages[] = [
                'quantity' => max(1, $quantity),
                'package_type' => $this->cleanString($package['package_type'] ?? 'carton') ?: 'carton',
                'length_cm' => round(max(0, $length), 2),
                'width_cm' => round(max(0, $width), 2),
                'height_cm' => round(max(0, $height), 2),
                'weight_kg' => round(max(0, $weight), 2),
            ];
        }

        return $packages;
    }

    private function airVolumetricDivisor(?Request $request = null): int
    {
        $requestDivisor = $request ? (int) $request->input('air_volumetric_divisor', 0) : 0;
        if ($requestDivisor > 0) {
            return $requestDivisor;
        }

        return max(1, (int) config('shipping.air.volumetric_divisor', self::DEFAULT_AIR_VOLUMETRIC_DIVISOR));
    }

    private function packageMetrics(array $packages, Request $request, bool $air = false): array
    {
        $pieces = 0;
        $grossWeight = 0.0;
        $volumeCbm = 0.0;

        foreach ($packages as $package) {
            $quantity = (int) $package['quantity'];
            $pieces += $quantity;
            $grossWeight += $quantity * (float) $package['weight_kg'];
            $volumeCbm += $quantity
                * (float) $package['length_cm']
                * (float) $package['width_cm']
                * (float) $package['height_cm']
                / 1000000;
        }

        if ($pieces === 0) {
            $pieces = max(
                $this->integerInput($request, 'pieces_count'),
                $this->integerInput($request, 'packages_count')
            );
        }

        if ($grossWeight <= 0) {
            $grossWeight = $this->numericInput($request, 'gross_weight_kg', $this->numericInput($request, 'weightKg'));
        }

        if ($volumeCbm <= 0) {
            $volumeCbm = $this->numericInput($request, 'volume_cbm', $this->numericInput($request, 'volumeCbm'));
        }

        $volumetricDivisor = $air ? $this->airVolumetricDivisor($request) : null;
        $volumetricWeight = $air && $volumeCbm > 0 && $volumetricDivisor
            ? ($volumeCbm * 1000000) / $volumetricDivisor
            : 0.0;

        return [
            'pieces_count' => $pieces,
            'gross_weight_kg' => round($grossWeight, 2),
            'volume_cbm' => round($volumeCbm, 4),
            'volumetric_weight_kg' => round($volumetricWeight, 2),
            'chargeable_weight_kg' => $air ? round(max($grossWeight, $volumetricWeight), 2) : null,
            'volumetric_divisor' => $volumetricDivisor,
        ];
    }

    private function cargoBaseDetails(Request $request): array
    {
        $cargoType = $this->cleanString($request->input('cargo_type', 'general')) ?: 'general';
        if ($cargoType !== 'dangerous' && $this->isDangerousCargo($request, $cargoType)) {
            $cargoType = 'dangerous';
        }

        $currency = $this->cleanString($request->input('currency', 'USD')) ?: 'USD';

        return [
            'cargo_type' => $cargoType,
            'hs_code' => $this->cleanString($request->input('hs_code')),
            'country_of_origin' => $this->cleanString($request->input('country_of_origin')),
            'net_weight_kg' => $request->filled('net_weight_kg') ? $this->numericInput($request, 'net_weight_kg') : null,
            'package_type' => $this->cleanString($request->input('package_type')),
            'stackable' => $request->boolean('stackable', true),
            'declared_value' => $this->numericInput($request, 'declared_value'),
            'currency' => $currency,
            'dangerous_goods' => $this->dangerousGoodsDetails($request, $cargoType),
            'trade' => $this->tradeDetails($request, $currency),
        ];
    }

    private function tradeDetails(Request $request, string $currency): array
    {
        return [
            'incoterm' => $this->cleanString($request->input('incoterm')),
            'commercial_value' => $this->numericInput($request, 'commercial_value', $this->numericInput($request, 'declared_value')),
            'currency' => $currency,
        ];
    }

    private function airServiceDetails(Request $request): array
    {
        return [
            'service_level' => $request->input('air_service_level', 'standard'),
            'ready_date' => $request->input('ready_date'),
            'direct_flight_preferred' => $request->boolean('direct_flight_preferred'),
            'insurance_required' => $request->boolean('insurance_required'),
            'customs_clearance_origin' => $request->boolean('customs_clearance_origin'),
            'customs_clearance_destination' => $request->boolean('customs_clearance_destination'),
            'pickup_required' => $request->boolean('pickup_required'),
            'delivery_required' => $request->boolean('delivery_required'),
        ];
    }

    private function doorServiceDetails(Request $request): array
    {
        return [
            'pickup_ready_date' => $request->input('pickup_ready_date'),
            'pickup_time_from' => $request->input('pickup_time_from'),
            'pickup_time_to' => $request->input('pickup_time_to'),
            'delivery_required_date' => $request->input('delivery_required_date'),
            'preferred_pickup_time' => $request->input('preferred_pickup_time', 'anytime'),
            'insurance_required' => $request->boolean('insurance_required'),
            'export_customs_clearance' => $request->boolean('export_customs_clearance'),
            'import_customs_clearance' => $request->boolean('import_customs_clearance'),
            'delivery_notes' => $request->input('delivery_notes'),
        ];
    }

    private function dangerousGoodsDetails(Request $request, string $cargoType): ?array
    {
        $isDangerous = $this->isDangerousCargo($request, $cargoType);
        $msdsAvailable = $request->boolean(
            'dangerous_msds_available',
            $request->boolean('dangerous_msds_required', false)
        );
        $transportRegulation = strtoupper($this->cleanString($request->input('dangerous_transport_regulation')));
        if ($transportRegulation === '') {
            $transportRegulation = $this->dangerousGoodsRegulation($request);
        }

        return [
            'is_dangerous' => $isDangerous,
            'transport_regulation' => $transportRegulation,
            'regulation' => $transportRegulation,
            'un_number' => $this->cleanString($request->input('dangerous_un_number')),
            'iata_class' => $this->cleanString($request->input('dangerous_iata_class')),
            'imdg_class' => $this->cleanString($request->input('dangerous_imdg_class')),
            'adr_class' => $this->cleanString($request->input('dangerous_adr_class')),
            'packing_group' => $this->cleanString($request->input('dangerous_packing_group')),
            'proper_shipping_name' => $this->cleanString($request->input('dangerous_proper_shipping_name')),
            'msds_available' => $msdsAvailable,
            'msds_required' => $request->boolean('dangerous_msds_required', $isDangerous),
        ];
    }

    private function dangerousGoodsRegulation(Request $request): string
    {
        $routeMode = (string) $request->input('route_mode');
        $transportMode = $routeMode === 'DOOR_TO_DOOR'
            ? $this->normalizeMainTransportMode($request->input('main_transport_mode'))
            : $routeMode;

        $map = config('shipping.dangerous_goods_regulations', []);

        return $map[$transportMode] ?? 'MULTIMODAL';
    }

    private function isDangerousCargo(Request $request, ?string $cargoType = null): bool
    {
        $cargoType = Str::lower($this->cleanString($cargoType ?? $request->input('cargo_type')));
        if ($cargoType === 'dangerous' || $request->boolean('dangerous')) {
            return true;
        }

        $dangerousGoods = $request->input('dangerous_goods', []);
        if (is_array($dangerousGoods) && filter_var(data_get($dangerousGoods, 'is_dangerous'), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        return collect([
            'dangerous_un_number',
            'dangerous_iata_class',
            'dangerous_imdg_class',
            'dangerous_adr_class',
            'dangerous_packing_group',
            'dangerous_proper_shipping_name',
            'dangerous_msds_available',
        ])->contains(fn (string $key) => $request->filled($key));
    }

    private function validateBookingRequest(Request $request, ?Booking $booking = null): void
    {
        $validator = validator($request->all(), $this->bookingValidationRules($request));

        $validator->after(function ($validator) use ($request, $booking) {
            if (! $this->isDangerousCargo($request)) {
                return;
            }

            if ($this->hasRequiredDangerousGoodsFile($request, $booking)) {
                return;
            }

            $validator->errors()->add('attachments', __('messages.booking_dg_attachment_required'));
        });

        $validator->validate();
    }

    private function hasRequiredDangerousGoodsFile(Request $request, ?Booking $booking = null): bool
    {
        $files = $request->file('attachments', []);
        if ($files instanceof \Illuminate\Http\UploadedFile) {
            $files = [$files];
        }

        foreach ((array) $files as $file) {
            if ($file && $file->isValid()) {
                return true;
            }
        }

        if (! $booking) {
            return false;
        }

        $removedAttachments = collect($request->input('remove_attachments', []))
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->map(fn (string $path) => trim($path))
            ->unique()
            ->values()
            ->all();

        return collect($this->attachmentList($booking->attachments))
            ->reject(fn (string $path) => in_array($path, $removedAttachments, true))
            ->isNotEmpty();
    }

    private function bookingGrossWeight(Request $request, array $cargoDetails): float
    {
        return (float) ($cargoDetails['gross_weight_kg'] ?? $this->numericInput($request, 'weightKg'));
    }

    private function bookingVolumeCbm(Request $request, array $cargoDetails): float
    {
        return (float) ($cargoDetails['volume_cbm'] ?? $this->numericInput($request, 'volumeCbm'));
    }

    private function routeDisplayName(string $city, string $codeOrAddress): string
    {
        if ($city !== '' && $codeOrAddress !== '') {
            return str_contains($codeOrAddress, ' ')
                ? "{$codeOrAddress} - {$city}"
                : "{$city} ({$codeOrAddress})";
        }

        return $codeOrAddress ?: $city;
    }

    private function countryMeta(?string $countryName, ?string $countryCode = null): array
    {
        $countryName = $this->cleanString($countryName);
        $countryCode = strtoupper($this->cleanString($countryCode));

        if ($countryCode === '' && preg_match('/^[A-Za-z]{2}$/', $countryName)) {
            $countryCode = strtoupper($countryName);
            $countryName = '';
        }

        $resolved = PortCatalog::resolveCountry($countryName, $countryCode);

        return [
            'country' => $resolved['country'] ?: ($countryName ?: null),
            'country_code' => $resolved['country_code'],
        ];
    }

    private function bookingValidationRules(Request $request): array
    {
        $routeMode = $request->input('route_mode');

        $rules = [
            'shipping_category' => 'nullable|in:sea,land,air,door_to_door',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'company' => 'required',
            'contactName' => 'required',
            'phone' => 'required',
            'email' => 'required|email',
            'goodsDescription' => 'required',
            'notes' => 'nullable|string|max:2000',
            'documents' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array|max:12',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'nullable|string',
            'weightKg' => 'required|numeric',
            'gross_weight_kg' => 'nullable|numeric|min:0',
            'volume_cbm' => 'nullable|numeric|min:0',
            'pol' => 'required|string|max:255',
            'pod' => 'required|string|max:255',
            'shipmentType' => 'required',
            'containerType' => 'nullable|string|max:50',
            'containerQty' => 'nullable|integer|min:1',
            'containers' => 'nullable|array',
            'containers.*.type' => 'nullable|string|max:50',
            'containers.*.qty' => 'nullable|integer|min:1',
            'origin_country_code' => 'nullable|string|size:2',
            'destination_country_code' => 'nullable|string|size:2',
            'incoterm' => 'nullable|string|max:20',
            'cargo_type' => 'nullable|in:general,dangerous,perishable,pharmaceutical,valuable,fragile,live_animals,temperature_controlled,oversized',
            'hs_code' => 'nullable|string|max:40',
            'cargo_items' => 'nullable|array|max:50',
            'cargo_items.*.name' => 'required_with:cargo_items|string|max:255',
            'cargo_items.*.hs_code' => 'nullable|string|max:40',
            'cargo_items.*.quantity' => 'required_with:cargo_items|numeric|min:0.01',
            'cargo_items.*.unit' => 'nullable|in:piece,vehicle,cbm,kg,ton,container',
            'cargo_items.*.weight_kg' => 'nullable|numeric|min:0',
            'cargo_items.*.volume_cbm' => 'nullable|numeric|min:0',
            'country_of_origin' => 'nullable|string|max:255',
            'net_weight_kg' => 'nullable|numeric|min:0',
            'package_type' => 'nullable|string|max:80',
            'declared_value' => 'nullable|numeric|min:0',
            'commercial_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'packages' => 'nullable|array',
            'package_dimension_unit' => 'nullable|in:cm,m',
            'packages.*.quantity' => 'nullable|integer|min:1',
            'packages.*.package_type' => 'nullable|string|max:80',
            'packages.*.length_cm' => 'nullable|numeric|min:0',
            'packages.*.width_cm' => 'nullable|numeric|min:0',
            'packages.*.height_cm' => 'nullable|numeric|min:0',
            'packages.*.weight_kg' => 'nullable|numeric|min:0',
            'lcl_length_m' => 'nullable|numeric|min:0.01',
            'lcl_width_m' => 'nullable|numeric|min:0.01',
            'lcl_height_m' => 'nullable|numeric|min:0.01',
            'bulk_pieces' => 'nullable|required_if:shipmentType,Bulk|integer|min:1',
            'bulk_piece_weight' => 'nullable|required_if:shipmentType,Bulk|numeric|min:0.01',
            'bulk_total_weight' => 'nullable|numeric|min:0',
            'bulk_length' => 'nullable|required_if:shipmentType,Bulk|numeric|min:0.01',
            'bulk_width' => 'nullable|required_if:shipmentType,Bulk|numeric|min:0.01',
            'bulk_height' => 'nullable|required_if:shipmentType,Bulk|numeric|min:0.01',
            'bulk_cbm' => 'nullable|numeric|min:0',
            'dangerous_transport_regulation' => 'nullable|in:IATA,IMDG,ADR,MULTIMODAL',
            'dangerous_un_number' => 'nullable|string|max:40',
            'dangerous_iata_class' => 'nullable|string|max:40',
            'dangerous_imdg_class' => 'nullable|string|max:40',
            'dangerous_adr_class' => 'nullable|string|max:40',
            'dangerous_packing_group' => 'nullable|string|max:40',
            'dangerous_proper_shipping_name' => 'nullable|string|max:255',
        ];

        if ($request->filled('route_mode')) {
            $rules['route_mode'] = 'in:FOB,EXW,LAND,AIR,DOOR_TO_DOOR';
        }

        if (in_array($routeMode, ['FOB', 'EXW', 'AIR', 'DOOR_TO_DOOR'], true)) {
            $rules['origin_country'] = 'required|string|max:255';
            $rules['destination_country'] = 'required|string|max:255';
        }

        if (in_array($routeMode, ['EXW', 'AIR', 'DOOR_TO_DOOR'], true)) {
            $rules['origin_city'] = 'required|string|max:255';
            $rules['destination_city'] = 'required|string|max:255';
        }

        if ($routeMode === 'AIR') {
            $rules['origin_airport'] = 'required|string|max:255';
            $rules['destination_airport'] = 'required|string|max:255';
            $rules['origin_airport_name'] = 'nullable|string|max:255';
            $rules['destination_airport_name'] = 'nullable|string|max:255';
            $rules['air_service_level'] = 'nullable|in:standard,express,economy';
            $rules['ready_date'] = 'nullable|date';
            $rules['air_volumetric_divisor'] = 'nullable|integer|min:1';
        }

        if ($routeMode === 'DOOR_TO_DOOR') {
            $rules['origin_postal_code'] = 'nullable|string|max:40';
            $rules['destination_postal_code'] = 'nullable|string|max:40';
            $rules['pickup_address'] = 'required|string|max:500';
            $rules['pickup_contact_name'] = 'nullable|string|max:255';
            $rules['pickup_phone_country_code'] = 'nullable|string|max:10';
            $rules['pickup_phone'] = 'nullable|string|max:50';
            $rules['delivery_address'] = 'required|string|max:500';
            $rules['delivery_contact_name'] = 'nullable|string|max:255';
            $rules['delivery_phone_country_code'] = 'nullable|string|max:10';
            $rules['delivery_phone'] = 'nullable|string|max:50';
            $rules['main_transport_mode'] = 'required|in:AUTO,AIR,SEA,LAND,MULTIMODAL';
            $rules['pickup_ready_date'] = 'nullable|date';
            $rules['pickup_time_from'] = 'nullable|date_format:H:i';
            $rules['pickup_time_to'] = 'nullable|date_format:H:i';
            $rules['delivery_required_date'] = 'nullable|date';
            $rules['preferred_pickup_time'] = 'nullable|in:morning,afternoon,evening,anytime';
            $rules['delivery_notes'] = 'nullable|string|max:1000';
        }

        if ($routeMode === 'LAND' || $request->input('shipping_category') === 'land') {
            $rules['land_carton_count'] = 'nullable|integer|min:0';
            $rules['land_shipments'] = 'nullable|array';
            $rules['land_shipments.*.qty'] = 'nullable|integer|min:1';
            $rules['land_shipments.*.truck_qty'] = 'nullable|integer|min:1';
            $rules['land_shipments.*.from_city'] = 'nullable|string|max:255';
            $rules['land_shipments.*.to_city'] = 'nullable|string|max:255';
            $rules['land_shipments.*.origin_city'] = 'nullable|string|max:255';
            $rules['land_shipments.*.destination_city'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    private function rememberRouteStops(Request $request): void
    {
        $category = $request->input('shipping_category', 'sea');
        $routeMode = $request->input('route_mode');
        $isSeaPortRoute = in_array($routeMode, ['FOB', 'EXW'], true) || ($category === 'sea' && blank($routeMode));

        if (! $isSeaPortRoute) {
            return;
        }

        $portsToRemember = [
            ['name' => trim((string) $request->input('pol')), 'country' => $request->input('origin_country'), 'country_code' => $request->input('origin_country_code')],
            ['name' => trim((string) $request->input('pod')), 'country' => $request->input('destination_country'), 'country_code' => $request->input('destination_country_code')],
        ];

        foreach ($portsToRemember as $portData) {
            $portName = $portData['name'];
            if ($portName === '') {
                continue;
            }

            $countryMeta = $this->countryMeta($portData['country'] ?? null, $portData['country_code'] ?? null);
            $country = PortCatalog::resolveCountry($countryMeta['country'], $countryMeta['country_code']);
            $port = Port::where('name', $portName)
                ->when(filled($country['country']), function ($query) use ($country) {
                    $query->where(function ($countryQuery) use ($country) {
                        $countryQuery->where('country', $country['country'])
                            ->orWhereNull('country')
                            ->orWhere('country', '');
                    });
                })
                ->first();

            if (! $port) {
                $port = new Port(['name' => $portName]);
            }

            if (! $port->exists) {
                $port->sort_order = Port::count();
                $port->is_active = true;
            }

            if (blank($port->country) && filled($country['country'])) {
                $port->country = $country['country'];
                $port->country_code = $country['country_code'];
                $port->country_flag = $country['country_flag'];
            }

            $port->save();
        }
    }

    private function airportByCode(?string $code): ?Airport
    {
        $code = $this->airportCode($code);
        if ($code === '' || ! Schema::hasTable('airports')) {
            return null;
        }

        return Airport::where('iata_code', $code)->first();
    }

    private function rememberAirports(Request $request): void
    {
        if ($request->input('route_mode') !== 'AIR' || ! Schema::hasTable('airports')) {
            return;
        }

        $airportsToRemember = [
            [
                'iata_code' => $request->input('origin_airport'),
                'name' => $request->input('origin_airport_name'),
                'city' => $request->input('origin_city'),
                'country' => $request->input('origin_country'),
                'country_code' => $request->input('origin_country_code'),
            ],
            [
                'iata_code' => $request->input('destination_airport'),
                'name' => $request->input('destination_airport_name'),
                'city' => $request->input('destination_city'),
                'country' => $request->input('destination_country'),
                'country_code' => $request->input('destination_country_code'),
            ],
        ];

        foreach ($airportsToRemember as $index => $airportData) {
            $code = $this->airportCode($airportData['iata_code'] ?? null);
            if ($code === '') {
                continue;
            }

            $country = $this->countryMeta($airportData['country'] ?? null, $airportData['country_code'] ?? null);
            $airport = Airport::firstOrNew(['iata_code' => $code]);

            if (! $airport->exists) {
                $airport->sort_order = Airport::count() + $index;
                $airport->is_active = true;
            }

            $payload = [
                'name' => $this->cleanString($airportData['name'] ?? '') ?: $airport->name ?: $code,
                'city' => $this->cleanString($airportData['city'] ?? '') ?: $airport->city,
                'country' => $country['country'] ?: $airport->country,
                'country_code' => $country['country_code'] ?: $airport->country_code,
                'is_active' => true,
            ];

            $airport->fill($payload)->save();
        }
    }

    private function buildCargoDetails(Request $request): array
    {
        $shipmentType = (string) $request->input('shipmentType', 'FCL');
        $baseDetails = $this->cargoBaseDetails($request);
        $cargoItems = $this->extractCargoItems($request);
        $baseDetails['cargo_items'] = $cargoItems;

        if (blank($baseDetails['hs_code']) && $cargoItems !== []) {
            $baseDetails['hs_code'] = $cargoItems[0]['hs_code'] ?? null;
        }

        if ($shipmentType === 'AIR') {
            $packages = $this->extractPackages($request);
            $metrics = $this->packageMetrics($packages, $request, true);
            $service = $this->airServiceDetails($request);
            $trade = $baseDetails['trade'];
            $dangerousGoods = $baseDetails['dangerous_goods'];
            $cargo = [
                'goods_description' => $request->input('goodsDescription'),
                'cargo_type' => $baseDetails['cargo_type'],
                'hs_code' => $baseDetails['hs_code'],
                'country_of_origin' => $baseDetails['country_of_origin'],
                'pieces_count' => $metrics['pieces_count'],
                'package_type' => $baseDetails['package_type'],
                'gross_weight_kg' => $metrics['gross_weight_kg'],
                'net_weight_kg' => $baseDetails['net_weight_kg'],
                'volume_cbm' => $metrics['volume_cbm'],
                'volumetric_weight_kg' => $metrics['volumetric_weight_kg'],
                'chargeable_weight_kg' => $metrics['chargeable_weight_kg'],
                'stackable' => $baseDetails['stackable'],
                'declared_value' => $baseDetails['declared_value'],
                'currency' => $baseDetails['currency'],
            ];

            return [
                'cargo' => $cargo,
                'cargo_items' => $cargoItems,
                'packages' => $packages,
                'service' => $service,
                'trade' => $trade,
                'dangerous_goods' => $dangerousGoods,
                'service_type' => 'air_freight',
                'service_details' => $service,
                'cargo_type' => $baseDetails['cargo_type'],
                'hs_code' => $baseDetails['hs_code'],
                'country_of_origin' => $baseDetails['country_of_origin'],
                'package_type' => $baseDetails['package_type'],
                'stackable' => $baseDetails['stackable'],
                'pieces_count' => $metrics['pieces_count'],
                'gross_weight_kg' => $metrics['gross_weight_kg'],
                'net_weight_kg' => $baseDetails['net_weight_kg'],
                'volume_cbm' => $metrics['volume_cbm'],
                'volumetric_weight_kg' => $metrics['volumetric_weight_kg'],
                'chargeable_weight_kg' => $metrics['chargeable_weight_kg'],
                'volumetric_divisor' => $metrics['volumetric_divisor'],
                'service_level' => $service['service_level'],
                'ready_date' => $service['ready_date'],
                'declared_value' => $baseDetails['declared_value'],
                'currency' => $baseDetails['currency'],
                'commercial_value' => $trade['commercial_value'],
                'incoterm' => $trade['incoterm'],
            ];
        }

        if ($shipmentType === 'DOOR_TO_DOOR') {
            $packages = $this->extractPackages($request);
            $metrics = $this->packageMetrics($packages, $request);
            $service = $this->doorServiceDetails($request);
            $trade = $baseDetails['trade'];
            $dangerousGoods = $baseDetails['dangerous_goods'];
            $cargo = [
                'goods_description' => $request->input('goodsDescription'),
                'cargo_type' => $baseDetails['cargo_type'],
                'hs_code' => $baseDetails['hs_code'],
                'country_of_origin' => $baseDetails['country_of_origin'],
                'packages_count' => $metrics['pieces_count'],
                'package_type' => $baseDetails['package_type'],
                'gross_weight_kg' => $metrics['gross_weight_kg'],
                'net_weight_kg' => $baseDetails['net_weight_kg'],
                'volume_cbm' => $metrics['volume_cbm'],
                'stackable' => $baseDetails['stackable'],
                'declared_value' => $baseDetails['declared_value'],
                'currency' => $baseDetails['currency'],
            ];

            return [
                'cargo' => $cargo,
                'packages' => $packages,
                'service' => $service,
                'service_details' => $service,
                'trade' => $trade,
                'dangerous_goods' => $dangerousGoods,
                'cargo_type' => $baseDetails['cargo_type'],
                'hs_code' => $baseDetails['hs_code'],
                'country_of_origin' => $baseDetails['country_of_origin'],
                'package_type' => $baseDetails['package_type'],
                'stackable' => $baseDetails['stackable'],
                'service_type' => 'door_to_door',
                'package_dimension_unit' => $request->input('package_dimension_unit') ?: 'cm',
                'main_transport_mode' => $this->normalizeMainTransportMode($request->input('main_transport_mode')),
                'packages_count' => $metrics['pieces_count'],
                'gross_weight_kg' => $metrics['gross_weight_kg'],
                'net_weight_kg' => $baseDetails['net_weight_kg'],
                'volume_cbm' => $metrics['volume_cbm'],
                'pickup_ready_date' => $service['pickup_ready_date'],
                'preferred_pickup_time' => $service['preferred_pickup_time'],
                'pickup_contact' => [
                    'name' => $request->input('pickup_contact_name'),
                    'phone_country_code' => $request->input('pickup_phone_country_code'),
                    'phone' => $request->input('pickup_phone'),
                ],
                'delivery_contact' => [
                    'name' => $request->input('delivery_contact_name'),
                    'phone_country_code' => $request->input('delivery_phone_country_code'),
                    'phone' => $request->input('delivery_phone'),
                ],
                'delivery_notes' => $service['delivery_notes'],
                'declared_value' => $baseDetails['declared_value'],
                'currency' => $baseDetails['currency'],
                'commercial_value' => $trade['commercial_value'],
                'incoterm' => $trade['incoterm'],
            ];
        }

        if ($shipmentType === 'LCL') {
            $length = $this->numericInput($request, 'lcl_length_m');
            $width = $this->numericInput($request, 'lcl_width_m');
            $height = $this->numericInput($request, 'lcl_height_m');
            $volumeCbm = $length > 0 && $width > 0 && $height > 0
                ? ceil($length * $width * $height)
                : ceil($this->numericInput($request, 'volumeCbm'));

            return array_merge($baseDetails, [
                'service_type' => 'lcl',
                'dimensions' => [
                    'unit' => 'm',
                    'length_m' => $length,
                    'width_m' => $width,
                    'height_m' => $height,
                ],
                'volume_cbm' => $volumeCbm,
            ]);
        }

        if ($shipmentType === 'LAND') {
            $shipments = $this->extractLandShipments($request);
            $totalQty = collect($shipments)->sum('qty');
            $cartonCount = $this->integerInput($request, 'land_carton_count');

            return array_filter(array_merge($baseDetails, [
                'service_type' => 'land',
                'land_carton_count' => $cartonCount > 0 ? $cartonCount : null,
                'land_shipments' => $shipments,
                'land_shipment_summary' => $this->landShipmentSummary($shipments),
                'land_shipment_total_qty' => $totalQty,
            ]), fn ($value) => $value !== null && $value !== '' && $value !== []);
        }

        if ($shipmentType === 'Bulk') {
            $pieces = max(1, (int) $this->numericInput($request, 'bulk_pieces', 1));
            $pieceWeight = $this->numericInput($request, 'bulk_piece_weight');
            $totalWeight = $pieceWeight > 0
                ? round($pieceWeight * $pieces, 2)
                : $this->numericInput($request, 'bulk_total_weight', $this->numericInput($request, 'weightKg'));
            $length = $this->numericInput($request, 'bulk_length');
            $width = $this->numericInput($request, 'bulk_width');
            $height = $this->numericInput($request, 'bulk_height');
            $rawPieceCbm = $length > 0 && $width > 0 && $height > 0
                ? $height * $width * $length
                : $this->numericInput($request, 'bulk_cbm', $this->numericInput($request, 'volumeCbm'));
            $pieceCbm = round($rawPieceCbm, 1);
            $totalCbm = round($rawPieceCbm * $pieces, 1);

            return array_merge($baseDetails, [
                'service_type' => 'bulk',
                'cargo_name' => $request->input('bulk_cargo_name'),
                'pieces' => $pieces,
                'piece_weight' => $pieceWeight,
                'total_weight' => $totalWeight,
                'gross_weight_kg' => $totalWeight,
                'length' => $length,
                'width' => $width,
                'height' => $height,
                'dimension_unit' => 'cm',
                'cbm' => $pieceCbm,
                'piece_cbm' => $pieceCbm,
                'total_cbm' => $totalCbm,
                'volume_cbm' => $totalCbm,
                'packaging_type' => $request->input('bulk_packaging'),
                'lifting_method' => $request->input('bulk_lifting'),
                'stackable' => $request->has('bulk_stackable'),
            ]);
        }

        if (in_array($shipmentType, ['RoRo', 'Flat Container'], true)) {
            return array_merge($baseDetails, [
            'service_type' => strtolower(str_replace(' ', '_', $shipmentType)),
                'vehicle_type' => $request->input('roro_vehicle_type'),
                'manufacturer' => $request->input('roro_manufacturer'),
                'model' => $request->input('roro_model'),
                'year' => $request->input('roro_year'),
                'color' => $request->input('roro_color'),
                'vin' => $request->input('roro_vin'),
                'chassis_number' => $request->input('roro_chassis_number'),
                'engine_number' => $request->input('roro_engine_number'),
                'plate_number' => $request->input('roro_plate_number'),
                'length' => $request->input('roro_length'),
                'width' => $request->input('roro_width'),
                'height' => $request->input('roro_height'),
                'weight' => $request->input('roro_weight'),
                'running' => $request->has('roro_running'),
                'tow_required' => $request->has('roro_tow_required'),
                'fuel_level' => $request->input('roro_fuel_level'),
                'has_key' => $request->has('roro_has_key'),
                'has_items' => $request->has('roro_has_items'),
                'value' => $request->input('roro_value'),
            ]);
        }

        if ($shipmentType === 'FCL') {
            $containers = $this->extractFclContainers($request);
            $totalQty = collect($containers)->sum('qty');
            $hasReefer = collect($containers)
                ->contains(fn (array $container) => in_array($container['type'], ['Reefer 20', 'Reefer 40'], true));

            return array_filter(array_merge($baseDetails, [
                'service_type' => 'fcl',
                'containers' => $containers,
                'container_summary' => $this->fclContainerSummary($containers),
                'container_total_qty' => $totalQty,
                'reefer_temperature' => $hasReefer ? $request->input('reefer_temperature') : null,
            ]), fn ($value) => $value !== null && $value !== '' && $value !== []);
        }

        return [];
    }

    private function extractCargoItems(Request $request): array
    {
        $rows = $request->input('cargo_items', []);

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                if (! is_array($row)) {
                    return null;
                }

                $name = $this->cleanString($row['name'] ?? null);
                $quantity = max(0, (float) ($row['quantity'] ?? 0));

                if ($name === '' || $quantity <= 0) {
                    return null;
                }

                $unit = strtolower($this->cleanString($row['unit'] ?? 'piece'));
                if (! in_array($unit, ['piece', 'vehicle', 'cbm', 'kg', 'ton', 'container'], true)) {
                    $unit = 'piece';
                }

                return array_filter([
                    'name' => mb_substr($name, 0, 255),
                    'hs_code' => mb_substr($this->cleanString($row['hs_code'] ?? null), 0, 40),
                    'quantity' => round($quantity, 4),
                    'unit' => $unit,
                    'weight_kg' => isset($row['weight_kg']) && $row['weight_kg'] !== '' ? round(max(0, (float) $row['weight_kg']), 2) : null,
                    'volume_cbm' => isset($row['volume_cbm']) && $row['volume_cbm'] !== '' ? round(max(0, (float) $row['volume_cbm']), 4) : null,
                ], fn ($value) => $value !== null && $value !== '');
            })
            ->filter()
            ->values()
            ->all();
    }

    private function buildRouteDetails(Request $request): ?array
    {
        $routeMode = $request->input('route_mode');
        if (! in_array($routeMode, ['FOB', 'EXW', 'LAND', 'AIR', 'DOOR_TO_DOOR'], true)) {
            return null;
        }

        $category = $request->input('shipping_category', 'sea');
        $originCountry = $request->input('origin_country');
        $destinationCountry = $request->input('destination_country');
        $originCity = $request->input('origin_city');
        $destinationCity = $request->input('destination_city');
        $pol = trim((string) $request->input('pol'));
        $pod = trim((string) $request->input('pod'));
        $incoterm = $this->cleanString($request->input('incoterm'));
        $originMeta = $this->countryMeta($originCountry, $request->input('origin_country_code'));
        $destinationMeta = $this->countryMeta($destinationCountry, $request->input('destination_country_code'));

        if ($routeMode === 'AIR') {
            $originAirport = $this->cleanString($request->input('origin_airport', $pol));
            $destinationAirport = $this->cleanString($request->input('destination_airport', $pod));
            $originAirportName = $this->cleanString($request->input('origin_airport_name'));
            $destinationAirportName = $this->cleanString($request->input('destination_airport_name'));
            $originAirportRecord = $this->airportByCode($originAirport);
            $destinationAirportRecord = $this->airportByCode($destinationAirport);

            if ($originAirportRecord) {
                $originAirportName = $originAirportName ?: (string) $originAirportRecord->name;
                $originCity = $originCity ?: $originAirportRecord->city;
                if (blank($originMeta['country_code'])) {
                    $originMeta = $this->countryMeta($originAirportRecord->country, $originAirportRecord->country_code);
                }
            }

            if ($destinationAirportRecord) {
                $destinationAirportName = $destinationAirportName ?: (string) $destinationAirportRecord->name;
                $destinationCity = $destinationCity ?: $destinationAirportRecord->city;
                if (blank($destinationMeta['country_code'])) {
                    $destinationMeta = $this->countryMeta($destinationAirportRecord->country, $destinationAirportRecord->country_code);
                }
            }

            return [
                'category' => 'air',
                'mode' => 'AIR',
                'service' => 'air_freight',
                'incoterm' => $incoterm ?: null,
                'origin' => [
                    'type' => 'airport',
                    'country' => $originMeta['country'],
                    'country_code' => $originMeta['country_code'],
                    'city' => $originCity,
                    'code' => $originAirport,
                    'airport_code' => $originAirport,
                    'airport' => $originAirport,
                    'airport_name' => $originAirportName ?: null,
                    'address' => null,
                    'port' => null,
                    'display_name' => $this->routeDisplayName((string) $originCity, $originAirport),
                ],
                'destination' => [
                    'type' => 'airport',
                    'country' => $destinationMeta['country'],
                    'country_code' => $destinationMeta['country_code'],
                    'city' => $destinationCity,
                    'code' => $destinationAirport,
                    'airport_code' => $destinationAirport,
                    'airport' => $destinationAirport,
                    'airport_name' => $destinationAirportName ?: null,
                    'address' => null,
                    'port' => null,
                    'display_name' => $this->routeDisplayName((string) $destinationCity, $destinationAirport),
                ],
            ];
        }

        if ($routeMode === 'DOOR_TO_DOOR') {
            $pickupAddress = $this->cleanString($request->input('pickup_address', $pol));
            $deliveryAddress = $this->cleanString($request->input('delivery_address', $pod));

            return [
                'category' => 'door_to_door',
                'mode' => 'DOOR_TO_DOOR',
                'service' => 'door_to_door',
                'main_transport_mode' => $this->normalizeMainTransportMode($request->input('main_transport_mode')),
                'incoterm' => $incoterm ?: null,
                'origin' => [
                    'type' => 'address',
                    'country' => $originMeta['country'],
                    'country_code' => $originMeta['country_code'],
                    'city' => $originCity,
                    'postal_code' => $request->input('origin_postal_code'),
                    'code' => null,
                    'airport' => null,
                    'port' => null,
                    'address' => $pickupAddress,
                    'contact_name' => $request->input('pickup_contact_name'),
                    'phone_country_code' => $request->input('pickup_phone_country_code'),
                    'phone' => $request->input('pickup_phone'),
                    'display_name' => $this->routeDisplayName((string) $originCity, $pickupAddress),
                ],
                'destination' => [
                    'type' => 'address',
                    'country' => $destinationMeta['country'],
                    'country_code' => $destinationMeta['country_code'],
                    'city' => $destinationCity,
                    'postal_code' => $request->input('destination_postal_code'),
                    'code' => null,
                    'airport' => null,
                    'port' => null,
                    'address' => $deliveryAddress,
                    'contact_name' => $request->input('delivery_contact_name'),
                    'phone_country_code' => $request->input('delivery_phone_country_code'),
                    'phone' => $request->input('delivery_phone'),
                    'display_name' => $this->routeDisplayName((string) $destinationCity, $deliveryAddress),
                ],
            ];
        }

        if ($routeMode === 'LAND') {
            return [
                'category' => 'land',
                'mode' => 'LAND',
                'incoterm' => $incoterm ?: null,
                'origin' => [
                    'type' => 'city',
                    'country' => $originMeta['country'],
                    'country_code' => $originMeta['country_code'],
                    'city' => $originCity,
                    'code' => null,
                    'airport' => null,
                    'address' => null,
                    'port' => $pol,
                    'display_name' => $this->routeDisplayName((string) $originCity, $pol),
                ],
                'destination' => [
                    'type' => 'city',
                    'country' => $destinationMeta['country'],
                    'country_code' => $destinationMeta['country_code'],
                    'city' => $destinationCity,
                    'code' => null,
                    'airport' => null,
                    'address' => null,
                    'port' => $pod,
                    'display_name' => $this->routeDisplayName((string) $destinationCity, $pod),
                ],
            ];
        }

        return [
            'category' => $category ?: 'sea',
            'mode' => $routeMode,
            'incoterm' => $incoterm ?: ($routeMode ?: null),
            'origin' => [
                'type' => 'port',
                'country' => $originMeta['country'],
                'country_code' => $originMeta['country_code'],
                'city' => $routeMode === 'EXW' ? $originCity : null,
                'code' => null,
                'airport' => null,
                'address' => null,
                'port' => $pol,
                'display_name' => $this->routeDisplayName((string) ($routeMode === 'EXW' ? $originCity : ''), $pol),
            ],
            'destination' => [
                'type' => 'port',
                'country' => $destinationMeta['country'],
                'country_code' => $destinationMeta['country_code'],
                'city' => $routeMode === 'EXW' ? $destinationCity : null,
                'code' => null,
                'airport' => null,
                'address' => null,
                'port' => $pod,
                'display_name' => $this->routeDisplayName((string) ($routeMode === 'EXW' ? $destinationCity : ''), $pod),
            ],
        ];
    }

    public function store(Request $request)
    {
        $this->normalizeBookingRequest($request);
        $this->validateBookingRequest($request);
        $this->rememberRouteStops($request);
        $this->rememberAirports($request);

        $uploadedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('bookings/attachments', 'public');
                    $uploadedAttachments[] = $path;
                }
            }
        }

        $shipmentType = (string) $request->input('shipmentType', 'FCL');
        $cargoDetails = $this->buildCargoDetails($request);
        $routeDetails = $this->buildRouteDetails($request);
        $primaryContainer = $this->primaryFclContainer($cargoDetails, $request);

        $customerId = $request->input('customer_id');
        $customer = $customerId ? Customer::find($customerId) : null;

        if (! $customer) {
            $customer = Customer::firstOrCreate(
                [
                    'company' => $request->input('company'),
                    'email' => $request->input('email'),
                    'phone' => $request->input('phone'),
                ],
                [
                    'contact_name' => $request->input('contactName'),
                    'notes' => $request->input('notes'),
                ]
            );
        }

        $id = SystemId::makeSequential(Booking::class, 'BK');

        $booking = Booking::create([
            'id' => $id,
            'parent_booking_id' => $request->input('parent_booking_id'),
            'customer_id' => $customer->id,
            'customer_company' => $request->input('company'),
            'customer_contact_name' => $request->input('contactName'),
            'customer_phone' => $request->input('phone'),
            'customer_email' => $request->input('email'),
            'shipment_type' => $shipmentType,
            'container_type' => $shipmentType === 'FCL' ? $primaryContainer['type'] : null,
            'container_qty' => $shipmentType === 'FCL' ? $primaryContainer['qty'] : null,
            'goods_description' => $request->input('goodsDescription'),
            'notes' => $request->input('notes'),
            'weight_kg' => $this->bookingGrossWeight($request, $cargoDetails),
            'volume_cbm' => $this->bookingVolumeCbm($request, $cargoDetails),
            'dangerous' => $this->isDangerousCargo($request),
            'documents' => $request->input('documents'),
            'attachments' => !empty($uploadedAttachments) ? json_encode($uploadedAttachments) : null,
            'cargo_details' => !empty($cargoDetails) ? $cargoDetails : null,
            'pol' => $request->input('pol'),
            'pod' => $request->input('pod'),
            'route_details' => $routeDetails,
            'status' => 'new'
        ]);

        AuditLog::logAction('CREATE', 'bookings', $booking->id, [], [
            'customer_company' => $booking->customer_company,
            'pol' => $booking->pol,
            'pod' => $booking->pod,
            'status' => $booking->status,
        ]);

        $this->createNotification([
            'message' => "طلب جديد {$id} من {$booking->customer_company} بانتظار التسعير",
            'booking_id' => $id,
            'read' => false,
            'target' => 'pricing'
        ]);

        $this->createNotification([
            'message' => "طلب جديد {$id} من {$booking->customer_company} تمت إضافته إلى سجل الطلبات",
            'booking_id' => $id,
            'read' => false,
            'target' => 'orders'
        ]);

        $this->createNotification([
            'message' => "تم تسجيل طلب الشحن {$id} لحسابك وإحالته إلى قسم التسعير",
            'booking_id' => $id,
            'read' => false,
            'target' => 'customer',
        ]);

        return redirect()->route('orders.index')->with('success', "تم استلام طلبك {$id} وإحالته إلى قسم التسعير");
    }

    public function storeChild(Request $request, $parentId)
    {
        $parent = Booking::findOrFail($parentId);

        $request->merge([
            'company' => $parent->customer_company,
            'contactName' => $parent->customer_contact_name,
            'phone' => $parent->customer_phone,
            'email' => $parent->customer_email,
            'goodsDescription' => $parent->goods_description,
            'weightKg' => $parent->weight_kg,
            'volumeCbm' => $parent->volume_cbm,
            'shipmentType' => $parent->shipment_type,
            'containerType' => $parent->container_type,
            'containerQty' => $parent->container_qty,
            'pol' => $parent->pol,
            'pod' => $parent->pod,
            'notes' => $parent->notes,
            'documents' => $parent->documents,
            'cargo_details' => $parent->cargo_details,
            'route_mode' => !empty($parent->route_details['mode']) ? $parent->route_details['mode'] : null,
            'origin_country' => data_get($parent->route_details, 'origin.country'),
            'origin_country_code' => data_get($parent->route_details, 'origin.country_code'),
            'destination_country' => data_get($parent->route_details, 'destination.country'),
            'destination_country_code' => data_get($parent->route_details, 'destination.country_code'),
            'origin_city' => data_get($parent->route_details, 'origin.city'),
            'destination_city' => data_get($parent->route_details, 'destination.city'),
            'origin_airport' => data_get($parent->route_details, 'origin.airport'),
            'origin_airport_name' => data_get($parent->route_details, 'origin.airport_name'),
            'destination_airport' => data_get($parent->route_details, 'destination.airport'),
            'destination_airport_name' => data_get($parent->route_details, 'destination.airport_name'),
            'main_transport_mode' => data_get($parent->route_details, 'main_transport_mode'),
            'parent_booking_id' => $parent->id,
        ]);

        return $this->store($request);
    }

    private function excelColumnName(int $index): string
    {
        $letters = '';
        while ($index >= 0) {
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26) - 1;
        }

        return $letters;
    }

    private function orderExportColumns(?array $selectedColumns = null): array
    {
        $columns = [
            'رقم الطلب', 'الشركة', 'الشخص المسؤول', 'الهاتف', 'البريد',
            'نوع الشحن', 'نوع الحاوية', 'عدد الحاويات', 'وصف البضاعة',
            'POL', 'POD', 'الوزن كجم', 'الحجم CBM', 'بضاعة خطرة',
            'الملاحظات', 'المستندات', 'الحالة', 'حالة الطلب', 'الشحن البحري',
            'رسوم المنشأ', 'أجور EXW', 'DTHC', 'مخاطر الحرب', 'رسوم أخرى',
            'رسوم تغيير ميناء الوجهة', 'غرامات أخرى', 'رسوم التخليص', 'الإجمالي', 'تاريخ الإنشاء', 'ملاحظات التسعير',
            'الشاحن', 'المستلم', 'الطرف المبلّغ', 'نوع بوليصة الشحن', 'نوع الحركة',
            'رقم الوظيفة', 'الوكيل الخارجي', 'مكان التسليم', 'عدد الطرود', 'القياس CBM',
            'رقم ختم الحاوية', 'ملاحظات الشحن', 'السفينة', 'الرحلة', 'مرجع حجز الناقل',
            'ETD', 'ETA', 'تم الحجز بواسطة', 'تاريخ حجز الناقل', 'رقم الطلب الرئيسي',
            'تفاصيل المسار JSON', 'تفاصيل الحمولة JSON', 'المرفقات JSON', 'ملاحظات المبيعات',
            'قرار العميل', 'ملاحظة قرار العميل', 'تاريخ قرار العميل', 'تاريخ القرار',
            'أيام العبور', 'صلاحية السعر', 'تم التسعير بواسطة', 'تاريخ التسعير',
            'الوكيل', 'حالة الفاتورة', 'قيمة الفاتورة',
            'تاريخ إصدار الفاتورة', 'تاريخ استحقاق الفاتورة', 'حالة التتبع', 'نسبة التتبع',
            'تسعير النقل البري JSON', 'كمية التسعير البري',
            'معرف الفاتورة', 'معرف التتبع',
        ];

        if ($selectedColumns === null) {
            return $columns;
        }

        $selected = array_values(array_unique(array_filter(
            $selectedColumns,
            static fn ($column) => is_string($column) && trim($column) !== '' && in_array($column, $columns, true)
        )));

        return $selected !== [] ? $selected : $columns;
    }

    private function orderExportRow(Booking $booking, ?array $selectedColumns = null): array
    {
        $fullRow = [
            $booking->id, $booking->customer_company, $booking->customer_contact_name,
            $booking->customer_phone, $booking->customer_email, $booking->shipmentDisplay(),
            $booking->container_type, $booking->container_qty, $booking->goods_description,
            $booking->pol, $booking->pod, $booking->weight_kg, $booking->volume_cbm,
            $booking->dangerous ? 'نعم' : 'لا', $booking->notes, $booking->documents,
            $booking->status, BookingStatus::resolve($booking->status)['label'],
            $booking->ocean_freight, $booking->origin_charges, $booking->exw_charges, $booking->dthc,
            $booking->war_risk_surcharge, $booking->other_charges, $booking->destination_port_change_fee,
            $booking->other_penalties, $booking->custom_charge_amount,
            $booking->pricingTotal(), optional($booking->created_at)->toDateString(),
            $booking->pricing_notes,
            $booking->shipper, $booking->consignee, $booking->notify_party, $booking->bill_type,
            $booking->type_of_move, $booking->job_no, $booking->overseas_agent,
            $booking->place_of_delivery, $booking->quantity_packages, $booking->measurement_cbm,
            $booking->container_seal_no, $booking->custom_notes, $booking->carrier_vessel,
            $booking->carrier_voyage, $booking->carrier_booking_ref,
            optional($booking->carrier_etd)->toDateString(), optional($booking->carrier_eta)->toDateString(),
            $booking->carrier_booked_by, optional($booking->carrier_booked_at)->toISOString(),
            $booking->parent_booking_id, $this->jsonExportValue($booking->route_details),
            $this->jsonExportValue($this->sanitizeOrderExportDetails($booking->cargo_details)), $this->jsonExportValue($booking->attachments),
            $booking->sales_notes, $booking->customer_decision, $booking->customer_decision_note,
            optional($booking->customer_decided_at)->toISOString(), optional($booking->decided_at)->toISOString(),
            $booking->transit_days, optional($booking->validity_date)->toDateString(), $booking->priced_by,
            optional($booking->priced_at)->toISOString(), $booking->agent?->name,
            $booking->invoices->first()?->status, $booking->invoices->first()?->amount,
            optional($booking->invoices->first()?->issued_at)->toDateString(), optional($booking->invoices->first()?->due_at)->toDateString(),
            $booking->shipmentTrack?->status, $booking->shipmentTrack?->progress,
            $this->jsonExportValue($booking->landPricingLines()),
            $booking->landPricingQuantity(), $booking->invoices->first()?->id, $booking->shipmentTrack?->id,
        ];

        $columnAliases = $this->orderExportColumns();
        $selectedColumns = $selectedColumns ?? $columnAliases;
        $indexMap = array_flip($columnAliases);

        $row = [];
        foreach ($selectedColumns as $column) {
            if (! array_key_exists($column, $indexMap)) {
                continue;
            }

            $row[] = $fullRow[$indexMap[$column]] ?? '';
        }

        return $row;
    }

    private function sanitizeOrderExportDetails($value)
    {
        if (! is_array($value)) {
            return $value;
        }

        $hiddenKeys = [
            'agent_purchase_amount',
            'agent_purchase_currency',
            'agent_purchase_lines',
            'agent_purchase_notes',
            'agent_purchase_quantity',
            'agent_purchase_unit_price',
            'currency',
            'currency_symbol',
            'pricing_currency',
            'pricing_currency_symbol',
        ];

        $clean = [];

        foreach ($value as $key => $item) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, $hiddenKeys, true)) {
                continue;
            }

            $clean[$key] = is_array($item) ? $this->sanitizeOrderExportDetails($item) : $item;
        }

        return $clean;
    }

    private function jsonExportValue($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return json_encode($value ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function excelXmlText($value): string
    {
        $value = (string) ($value ?? '');
        $value = preg_replace('/[^\P{C}\t\r\n]/u', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function index(Request $request)
    {
        $q = $request->query('q', '');
        $status = $request->query('status', 'all');
        $showArchived = filter_var($request->query('archived', '0'), FILTER_VALIDATE_BOOLEAN);
        $hasArchivedColumn = Schema::hasColumn('bookings', 'is_archived');
        $transportMode = $request->query('transport_mode', 'all');
        $seaType = $request->query('sea_type', 'all');
        $export = $request->query('export');
        $transportModes = ['all', 'sea', 'air', 'land'];
        $seaTypes = ['all', 'FCL', 'LCL', 'Bulk', 'RoRo'];

        if (! in_array($transportMode, $transportModes, true)) {
            $transportMode = 'all';
        }

        if (! in_array($seaType, $seaTypes, true)) {
            $seaType = 'all';
        }

        if ($seaType !== 'all' && $transportMode === 'all') {
            $transportMode = 'sea';
        }

        $query = $showArchived
            ? Booking::query()->withoutGlobalScope('archived_visibility')->where('is_archived', true)
            : Booking::query()->active();

        if ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('id', 'LIKE', "%{$q}%")
                    ->orWhere('customer_company', 'LIKE', "%{$q}%")
                    ->orWhere('pol', 'LIKE', "%{$q}%")
                    ->orWhere('pod', 'LIKE', "%{$q}%");
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($transportMode === 'sea') {
            $query->whereIn('shipment_type', ['FCL', 'LCL', 'Bulk', 'RoRo']);

            if ($seaType !== 'all') {
                $query->where('shipment_type', $seaType);
            }
        } elseif ($transportMode === 'air') {
            $query->where('shipment_type', 'AIR');
        } elseif ($transportMode === 'land') {
            $query->where('shipment_type', 'LAND');
        }

        $bookings = $query->latest()->get();

        if (Schema::hasColumn('bookings', 'parent_booking_id')) {
            $bookings->load('children');
        }

        $bookings = $bookings->filter(function ($booking) {
            return blank($booking->parent_booking_id);
        })->values();
        $bookings->load(['agent', 'invoices', 'shipmentTrack']);

        $selectedOrderColumns = $this->selectedOrderColumns($request);

        if ($export === 'csv') {
            $filename = 'bookings-' . date('Y-m-d-H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8; charset=binary',
                'Content-Disposition' => "attachment; filename={$filename}",
            ];

            $callback = function () use ($bookings, $selectedOrderColumns) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($handle, $this->orderExportColumns($selectedOrderColumns));

                foreach ($bookings as $booking) {
                    fputcsv($handle, $this->orderExportRow($booking, $selectedOrderColumns));
                }

                fclose($handle);
            };

            return new StreamedResponse($callback, 200, $headers);
        }

        if ($export === 'xlsx') {
            $filename = 'bookings-' . date('Y-m-d-H-i-s') . '.xlsx';
            $excelTempDirectory = storage_path('app/excel-temp');
            if (! is_dir($excelTempDirectory) && ! mkdir($excelTempDirectory, 0755, true) && ! is_dir($excelTempDirectory)) {
                abort(500, 'تعذر إنشاء مجلد ملفات Excel.');
            }
            if (! is_writable($excelTempDirectory)) {
                abort(500, 'مجلد ملفات Excel غير قابل للكتابة.');
            }

            $tempFile = tempnam($excelTempDirectory, 'bookings');
            $zip = new \ZipArchive();
            if ($tempFile === false || $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                abort(500, 'تعذر إنشاء ملف Excel.');
            }

            $rows = [$this->orderExportColumns($selectedOrderColumns)];

            foreach ($bookings as $booking) {
                $rows[] = $this->orderExportRow($booking, $selectedOrderColumns);
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
            $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . PHP_EOL;
            $xml .= '<sheetViews><sheetView workbookViewId="0" rightToLeft="1"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A2" sqref="A2"/></sheetView></sheetViews>' . PHP_EOL;
            $xml .= '<cols><col min="1" max="1" width="16" customWidth="1"/><col min="2" max="' . count($rows[0]) . '" width="22" customWidth="1"/></cols>' . PHP_EOL;
            $xml .= '<sheetData>' . PHP_EOL;
            foreach ($rows as $rowIndex => $row) {
                $xml .= '<row r="' . ($rowIndex + 1) . '">' . PHP_EOL;
                foreach ($row as $cellIndex => $value) {
                    $cellRef = $this->excelColumnName($cellIndex) . ($rowIndex + 1);
                    $xml .= '<c r="' . $cellRef . '" t="inlineStr"><is><t xml:space="preserve">' . $this->excelXmlText($value) . '</t></is></c>' . PHP_EOL;
                }
                $xml .= '</row>' . PHP_EOL;
            }
            $lastColumn = $this->excelColumnName(count($rows[0]) - 1);
            $xml .= '</sheetData><autoFilter ref="A1:' . $lastColumn . count($rows) . '"/></worksheet>';

            $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Orders" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
            $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>ShipMate Orders</dc:title><dc:creator>ShipMate</dc:creator><cp:lastModifiedBy>ShipMate</cp:lastModifiedBy></cp:coreProperties>');
            $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>ShipMate</Application></Properties>');
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');
            $zip->close();

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "sandbox; default-src 'none'",
            ])->deleteFileAfterSend(true);
        }

        // Statistics
        $baseQuery = Booking::query();
        if ($hasArchivedColumn) {
            $baseQuery->where(function ($query) {
                $query->where('is_archived', false)->orWhereNull('is_archived');
            });
        }

        $totalCount = (clone $baseQuery)->count();
        $pendingPricingCount = (clone $baseQuery)->whereIn('status', ['new', 'needs_pricing'])->count();
        $pendingSalesCount = (clone $baseQuery)->where('status', 'priced')->count();
        $approvedCount = (clone $baseQuery)->where(function ($query) {
            $query->where('status', 'approved')->orWhere('status', 'booked');
        })->count();
        $carrierVesselOptions = CarrierVesselOptions::all();

        return view('orders', compact('bookings', 'q', 'status', 'transportMode', 'seaType', 'showArchived', 'totalCount', 'pendingPricingCount', 'pendingSalesCount', 'approvedCount', 'carrierVesselOptions'));
    }

    private function selectedOrderColumns(Request $request): array
    {
        $columnParam = $request->query('columns');
        if (blank($columnParam)) {
            return $this->orderExportColumns();
        }

        $columns = array_values(array_filter(array_map('trim', explode(',', (string) $columnParam)), static fn ($column) => $column !== ''));

        return $this->orderExportColumns($columns);
    }

    public function show($id)
    {
        $booking = Booking::with(['agent', 'invoices', 'shipmentTrack'])->findOrFail($id);
        $resolvedCustomer = $this->resolveCustomerForBooking($booking);
        $resolvedCargo = $this->resolveCargoForBooking($booking);
        $resolvedRoute = $this->resolveRouteForBooking($booking);

        if (request()->expectsJson() || request()->header('Accept') === 'application/json') {
            return response()->json(BookingDetailPayload::fromBooking(
                $booking,
                request()->boolean('hide_stale_pricing')
            ));
        }

        return view('booking-details', compact('booking', 'resolvedCustomer', 'resolvedCargo', 'resolvedRoute'));
    }

    private function resolveCustomerForBooking(Booking $booking): array
    {
        if (!blank($booking->customer_company) || !blank($booking->customer_contact_name) || !blank($booking->customer_phone) || !blank($booking->customer_email)) {
            return [
                'company' => $booking->customer_company,
                'contactName' => $booking->customer_contact_name,
                'phone' => $booking->customer_phone,
                'email' => $booking->customer_email,
            ];
        }

        if ($booking->parent_booking_id) {
            $parent = Booking::find($booking->parent_booking_id);
            if ($parent) {
                return [
                    'company' => $parent->customer_company,
                    'contactName' => $parent->customer_contact_name,
                    'phone' => $parent->customer_phone,
                    'email' => $parent->customer_email,
                ];
            }
        }

        return [
            'company' => null,
            'contactName' => null,
            'phone' => null,
            'email' => null,
        ];
    }

    private function resolveCargoForBooking(Booking $booking): array
    {
        $fallback = null;
        if ($booking->parent_booking_id) {
            $fallback = Booking::find($booking->parent_booking_id);
        }

        $cargoDetails = $booking->cargo_details ?: ($fallback?->cargo_details ?? []);

        return [
            'shipmentType' => $booking->shipment_type ?: ($fallback?->shipment_type ?? null),
            'containerType' => $booking->container_type ?? ($fallback?->container_type ?? null),
            'containerQty' => $booking->container_qty ?? ($fallback?->container_qty ?? null),
            'goodsDescription' => $booking->goods_description ?: ($fallback?->goods_description ?? null),
            'notes' => $booking->notes ?: ($fallback?->notes ?? null),
            'weightKg' => $booking->weight_kg ?? ($fallback?->weight_kg ?? null),
            'volumeCbm' => $booking->volume_cbm ?? ($fallback?->volume_cbm ?? null),
            'dangerous' => $booking->dangerous ?? ($fallback?->dangerous ?? false),
            'documents' => $booking->documents ?: ($fallback?->documents ?? null),
            'attachments' => $booking->attachments ? json_decode($booking->attachments, true) : ($fallback?->attachments ? json_decode($fallback->attachments, true) : []),
            'cargoDetails' => is_array($cargoDetails) ? $cargoDetails : [],
        ];
    }

    private function resolveRouteForBooking(Booking $booking): array
    {
        $fallback = null;
        if ($booking->parent_booking_id) {
            $fallback = Booking::find($booking->parent_booking_id);
        }

        return [
            'pol' => $booking->pol ?: ($fallback?->pol ?? null),
            'pod' => $booking->pod ?: ($fallback?->pod ?? null),
            'details' => $booking->route_details ?: ($fallback?->route_details ?? null),
        ];
    }

    public function edit($id)
    {
        $booking = Booking::findOrFail($id);
        $parentBooking = null;

        if ($booking->parent_booking_id) {
            $parentBooking = Booking::find($booking->parent_booking_id);
        }

        $customers = Customer::orderBy('company')->get();
        $ports = Port::where('is_active', true)
            ->orderBy('country')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $portCountries = $ports
            ->map(fn (Port $port) => PortCatalog::countryOptionFromPort($port))
            ->filter()
            ->unique('name')
            ->sortBy('name')
            ->values();

        $portsForBooking = $ports->map(function (Port $port) {
            $country = PortCatalog::resolveCountry($port->country, $port->country_code, $port->country_flag);

            return [
                'name' => $port->name,
                'code' => $port->code,
                'country' => $country['country'],
                'country_code' => $country['country_code'],
                'country_flag' => $country['country_flag'],
            ];
        })->values();
        $airportsForBooking = $this->airportsForBooking();
        $airportCountriesForBooking = $this->airportCountriesForBooking($airportsForBooking, $portCountries);

        $customersData = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'company' => $customer->company,
                'contact_name' => $customer->contact_name,
                'phone' => $customer->phone,
                'email' => $customer->email,
            ];
        })->values();

        return view('booking', compact('booking', 'parentBooking', 'customers', 'customersData', 'ports', 'portCountries', 'portsForBooking', 'airportsForBooking', 'airportCountriesForBooking'));
    }

    public function showParent($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->parent_booking_id) {
            return redirect()->route('orders.edit', $booking->parent_booking_id);
        }

        return redirect()->route('orders.edit', $booking->id);
    }

    public function archive(Request $request, $id)
    {
        if (! $request->user()?->isManager()) {
            return redirect()->route('orders.index')->with('error', __('messages.orders_archive_forbidden'));
        }

        if (! Schema::hasColumn('bookings', 'is_archived')) {
            return redirect()->route('orders.index')->with('error', 'ميزة الأرشفة غير مفعّلة في قاعدة البيانات.');
        }

        $booking = Booking::withoutGlobalScope('archived_visibility')->findOrFail($id);
        $booking->update(['is_archived' => true]);

        return redirect()->route('orders.index')->with('success', __('messages.orders_archive_success', ['booking_id' => $booking->id]));
    }

    public function restore(Request $request, $id)
    {
        if (! $request->user()?->isManager()) {
            return redirect()->route('orders.index')->with('error', __('messages.orders_restore_forbidden'));
        }

        if (! Schema::hasColumn('bookings', 'is_archived')) {
            return redirect()->route('orders.index')->with('error', 'ميزة الأرشفة غير مفعّلة في قاعدة البيانات.');
        }

        $booking = Booking::withoutGlobalScope('archived_visibility')->findOrFail($id);
        $booking->update(['is_archived' => false]);

        return redirect()->route('orders.index')->with('success', __('messages.orders_restore_success', ['booking_id' => $booking->id]));
    }

    public function destroy(Request $request, $id)
    {
        if (! $request->user()?->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => __('messages.orders_delete_forbidden')], 403);
            }

            return redirect()
                ->route('orders.index')
                ->with('error', __('messages.orders_delete_forbidden'));
        }

        $booking = Booking::findOrFail($id);
        $bookingIds = $this->bookingIdsForDeletion($booking);
        $deletedCount = count($bookingIds);

        DB::transaction(function () use ($bookingIds) {
            $bookings = Booking::whereIn('id', $bookingIds)->get();
            $invoiceIds = DB::table('invoices')
                ->whereIn('booking_id', $bookingIds)
                ->pluck('id')
                ->all();

            Voucher::where(function ($query) use ($bookingIds, $invoiceIds) {
                $query->whereIn('booking_id', $bookingIds);

                if ($invoiceIds) {
                    $query->orWhereIn('invoice_id', $invoiceIds);
                }
            })->delete();

            Notification::whereIn('booking_id', $bookingIds)
                ->get()
                ->each(fn (Notification $notification) => $notification->delete());

            foreach ($bookings as $deletedBooking) {
                AuditLog::logAction('DELETE', 'bookings', $deletedBooking->id, $deletedBooking->toArray(), [
                    'deleted_with_order_ids' => $bookingIds,
                ]);
            }

            Booking::whereIn('id', $bookingIds)->delete();
        });

        $message = $deletedCount > 1
            ? __('messages.orders_delete_success_with_children', ['count' => $deletedCount])
            : __('messages.orders_delete_success');

        return redirect()->route('orders.index')->with('success', $message);
    }

    private function bookingIdsForDeletion(Booking $booking): array
    {
        $ids = [$booking->id];
        $pending = [$booking->id];

        while ($pending !== []) {
            $childIds = Booking::whereIn('parent_booking_id', $pending)
                ->pluck('id')
                ->all();

            $childIds = array_values(array_diff($childIds, $ids));

            if ($childIds === []) {
                break;
            }

            $ids = array_merge($ids, $childIds);
            $pending = $childIds;
        }

        return array_values(array_unique($ids));
    }

    private function attachmentList(mixed $attachments): array
    {
        if (is_array($attachments)) {
            $decoded = $attachments;
        } elseif (is_string($attachments) && trim($attachments) !== '') {
            $json = json_decode($attachments, true);
            $decoded = is_array($json) ? $json : [$attachments];
        } else {
            $decoded = [];
        }

        return collect($decoded)
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->map(fn (string $path) => trim($path))
            ->unique()
            ->values()
            ->all();
    }

    private function shouldDeleteBookingAttachment(string $path): bool
    {
        $normalizedPath = str_replace('\\', '/', $path);

        return Str::startsWith($normalizedPath, 'bookings/attachments/')
            && ! str_contains($normalizedPath, '../')
            && ! str_starts_with($normalizedPath, '/');
    }

    public function downloadAttachment(Request $request, Booking $booking, string $path)
    {
        abort_unless($this->canViewBookingAttachment($request, $booking), 403);

        $normalizedPath = $this->normalizeStoredAttachmentPath($path);
        abort_if($normalizedPath === '' || str_contains($normalizedPath, '../'), 404);

        $availablePaths = $this->bookingDownloadablePaths($booking);
        abort_unless(in_array($normalizedPath, $availablePaths, true), 404);
        abort_unless(Storage::disk('public')->exists($normalizedPath), 404);

        $fileName = basename($normalizedPath);
        $safeFileName = str_replace(['"', "\r", "\n"], '', $fileName);

        return response()->file(Storage::disk('public')->path($normalizedPath), [
            'Content-Disposition' => 'inline; filename="' . $safeFileName . '"',
        ]);
    }

    private function canViewBookingAttachment(Request $request, Booking $booking): bool
    {
        $user = auth('web')->user();

        if ($user) {
            return collect([
                'manage_bookings',
                'manage_pricing',
                'manage_sales',
                'manage_carriers',
                'manage_customs',
                'manage_accounts',
            ])->contains(fn (string $permission) => $user->hasPermission($permission));
        }

        $customer = auth('customer')->user();

        return $customer !== null && (int) $booking->customer_id === (int) $customer->id;
    }

    private function bookingDownloadablePaths(Booking $booking): array
    {
        $paths = $this->attachmentList($booking->attachments);
        $customsDocuments = $booking->customs_documents;

        if (is_string($customsDocuments) && trim($customsDocuments) !== '') {
            $decoded = json_decode($customsDocuments, true);
            $customsDocuments = is_array($decoded) ? $decoded : [$customsDocuments];
        }

        if (is_array($customsDocuments)) {
            foreach ($customsDocuments as $document) {
                $path = is_array($document)
                    ? ($document['path'] ?? $document['file'] ?? null)
                    : $document;

                if (is_string($path) && trim($path) !== '') {
                    $paths[] = $path;
                }
            }
        }

        return collect($paths)
            ->map(fn (string $path) => $this->normalizeStoredAttachmentPath($path))
            ->filter(fn (string $path) => $path !== '' && ! str_contains($path, '../'))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeStoredAttachmentPath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        return Str::startsWith($path, 'storage/')
            ? Str::after($path, 'storage/')
            : $path;
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        $this->normalizeBookingRequest($request);
        $this->validateBookingRequest($request, $booking);
        $this->rememberRouteStops($request);
        $this->rememberAirports($request);

        $pol = trim((string) $request->input('pol'));
        $pod = trim((string) $request->input('pod'));
        $routeDetails = $this->buildRouteDetails($request);

        $customerId = $request->input('customer_id');
        $customer = $customerId ? Customer::find($customerId) : null;

        if (! $customer) {
            $customer = Customer::firstOrCreate(
                [
                    'company' => $request->input('company'),
                    'email' => $request->input('email'),
                    'phone' => $request->input('phone'),
                ],
                [
                    'contact_name' => $request->input('contactName'),
                    'notes' => $request->input('notes'),
                ]
            );
        }

        // Keep existing files unless the user explicitly marks them for removal.
        $currentAttachments = $this->attachmentList($booking->attachments);
        $removeAttachments = collect($request->input('remove_attachments', []))
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->map(fn (string $path) => trim($path))
            ->unique()
            ->values()
            ->all();

        $uploadedAttachments = collect($currentAttachments)
            ->reject(fn (string $path) => in_array($path, $removeAttachments, true))
            ->values()
            ->all();

        foreach ($removeAttachments as $removedPath) {
            if (in_array($removedPath, $currentAttachments, true) && $this->shouldDeleteBookingAttachment($removedPath)) {
                Storage::disk('public')->delete($removedPath);
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('bookings/attachments', 'public');
                    $uploadedAttachments[] = $path;
                }
            }
        }
        
        $attachments = !empty($uploadedAttachments)
            ? json_encode(array_values($uploadedAttachments))
            : null;

        $shipmentType = (string) $request->input('shipmentType', 'FCL');
        $cargoDetails = $this->buildCargoDetails($request);
        $primaryContainer = $this->primaryFclContainer($cargoDetails, $request);

        $old = $booking->only([
            'customer_company',
            'customer_contact_name',
            'customer_phone',
            'customer_email',
            'shipment_type',
            'container_type',
            'container_qty',
            'goods_description',
            'notes',
            'weight_kg',
            'volume_cbm',
            'dangerous',
            'documents',
            'attachments',
            'cargo_details',
            'pol',
            'pod',
            'route_details',
        ]);

        $booking->update([
            'customer_id' => $customer->id,
            'customer_company' => $request->input('company'),
            'customer_contact_name' => $request->input('contactName'),
            'customer_phone' => $request->input('phone'),
            'customer_email' => $request->input('email'),
            'shipment_type' => $shipmentType,
            'container_type' => $shipmentType === 'FCL' ? $primaryContainer['type'] : null,
            'container_qty' => $shipmentType === 'FCL' ? $primaryContainer['qty'] : null,
            'goods_description' => $request->input('goodsDescription'),
            'notes' => $request->input('notes'),
            'weight_kg' => $this->bookingGrossWeight($request, $cargoDetails),
            'volume_cbm' => $this->bookingVolumeCbm($request, $cargoDetails),
            'dangerous' => $this->isDangerousCargo($request),
            'documents' => $request->input('documents'),
            'attachments' => $attachments,
            'cargo_details' => !empty($cargoDetails) ? $cargoDetails : null,
            'pol' => $pol,
            'pod' => $pod,
            'route_details' => $routeDetails,
        ]);

        AuditLog::logAction('UPDATE', 'bookings', $booking->id, $old, [
            'customer_company' => $booking->customer_company,
            'pol' => $booking->pol,
            'pod' => $booking->pod,
            'route_details' => $booking->route_details,
        ]);

        return redirect()->route('orders.index')->with('success', "تم تحديث بيانات الطلب {$booking->id} بنجاح");
    }
}
