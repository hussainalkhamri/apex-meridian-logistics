<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Booking extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('archived_visibility', function (Builder $builder) {
            if (! Schema::hasColumn((new self)->getTable(), 'is_archived')) {
                return;
            }

            $builder->where(function (Builder $query) {
                $query->where('is_archived', false)
                    ->orWhereNull('is_archived');
            });
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->withoutGlobalScope('archived_visibility')
            ->where(function (Builder $builder) {
                $builder->where('is_archived', false)
                    ->orWhereNull('is_archived');
            });
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->withoutGlobalScope('archived_visibility')
            ->where('is_archived', true);
    }

    public function scopeApprovedForAccounting(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACCOUNTING_APPROVED_STATUSES);
    }

    public function isApprovedForAccounting(): bool
    {
        return in_array((string) $this->status, self::ACCOUNTING_APPROVED_STATUSES, true);
    }

    public const PRICING_CURRENCIES = [
        'USD' => ['symbol' => '$', 'name_ar' => 'الدولار الأمريكي', 'name_en' => 'US Dollar'],
    ];

    public const ACCOUNTING_APPROVED_STATUSES = ['approved', 'booked'];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'parent_booking_id',
        'customer_id',
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
        'status',
        'is_archived',
        'sales_notes',
        'customer_decision',
        'customer_decision_note',
        'customer_decided_at',
        'decided_at',
        
        // Pricing
        'ocean_freight',
        'origin_charges',
        'exw_charges',
        'dthc',
        'war_risk_surcharge',
        'other_charges',
        'destination_port_change_fee',
        'other_penalties',
        'transit_days',
        'validity_date',
        'pricing_notes',
        'agent_id',
        'agent_purchase_amount',
        'agent_purchase_quantity',
        'agent_purchase_currency',
        'profit_amount',
        'agent_purchase_notes',
        'priced_by',
        'priced_at',
        
        // Carrier
        'carrier_vessel',
        'carrier_voyage',
        'carrier_booking_ref',
        'carrier_etd',
        'carrier_eta',
        'carrier_booked_by',
        'carrier_booked_at',

        // Shipping Details
        'shipper',
        'consignee',
        'notify_party',
        'bill_type',
        'type_of_move',
        'job_no',
        'overseas_agent',
        'place_of_delivery',
        'quantity_packages',
        'measurement_cbm',
        'container_seal_no',
        'custom_notes',
        'custom_charge_desc',
        'custom_charge_amount',
        'customs_status',
        'customs_reference',
        'customs_declaration_no',
        'customs_bayan_no',
        'customs_fees',
        'customs_documents',
        'customs_cleared_at',
        'customs_notes',
    ];

    protected $casts = [
        'dangerous' => 'boolean',
        'is_archived' => 'boolean',
        'container_qty' => 'integer',
        'weight_kg' => 'double',
        'volume_cbm' => 'double',
        'route_details' => 'array',
        'decided_at' => 'datetime',
        'customer_decided_at' => 'datetime',
        'validity_date' => 'date',
        'priced_at' => 'datetime',
        'carrier_etd' => 'date',
        'carrier_eta' => 'date',
        'carrier_booked_at' => 'datetime',
        'measurement_cbm' => 'double',
        'custom_charge_amount' => 'double',
        'exw_charges' => 'double',
        'destination_port_change_fee' => 'double',
        'other_penalties' => 'double',
        'customs_fees' => 'double',
        'customs_documents' => 'array',
        'cargo_details' => 'array',
        'customs_cleared_at' => 'date',
        'agent_purchase_amount' => 'double',
        'agent_purchase_quantity' => 'double',
        'profit_amount' => 'double',
    ];

    public function pricingTotal(): float
    {
        $itemPricingTotal = $this->itemPricingTotal();
        if ($itemPricingTotal > 0) {
            return $itemPricingTotal +
                ($this->custom_charge_amount ?? 0) +
                $this->customPricingChargesTotal();
        }

        $landPricingTotal = $this->landPricingTotal();
        if ($this->isLandShipment() && $landPricingTotal > 0) {
            return $landPricingTotal +
                ($this->destination_port_change_fee ?? 0) +
                ($this->other_penalties ?? 0) +
                ($this->custom_charge_amount ?? 0) +
                $this->customPricingChargesTotal();
        }

        return ($this->ocean_freight ?? 0) +
               ($this->origin_charges ?? 0) +
               ($this->shouldShowExwCharges() ? ($this->exw_charges ?? 0) : 0) +
               ($this->dthc ?? 0) +
               ($this->war_risk_surcharge ?? 0) +
               ($this->other_charges ?? 0) +
               ($this->destination_port_change_fee ?? 0) +
               ($this->other_penalties ?? 0) +
               ($this->custom_charge_amount ?? 0) +
               $this->customPricingChargesTotal();
    }

    public function customPricingCharges(): array
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $rows = data_get($cargoDetails, 'custom_pricing_charges', []);

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                if (! is_array($row)) {
                    return null;
                }

                $label = trim((string) data_get($row, 'label', ''));
                $amount = max(0, (float) data_get($row, 'amount', 0));

                if ($label === '' || $amount <= 0) {
                    return null;
                }

                return [
                    'label' => $label,
                    'amount' => round($amount, 2),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function customPricingChargesTotal(): float
    {
        return collect($this->customPricingCharges())
            ->sum(fn (array $line) => (float) ($line['amount'] ?? 0));
    }

    public function hsCode(): ?string
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $code = trim((string) (
            data_get($cargoDetails, 'hs_code')
            ?: data_get($cargoDetails, 'cargo.hs_code')
            ?: data_get($cargoDetails, 'cargo_items.0.hs_code')
        ));

        return $code !== '' ? $code : null;
    }

    public function cargoItems(): array
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $rows = data_get($cargoDetails, 'cargo_items', []);

        if (is_array($rows) && $rows !== []) {
            return collect($rows)
                ->map(function ($row) {
                    if (! is_array($row)) {
                        return null;
                    }

                    $name = trim((string) data_get($row, 'name', ''));
                    $quantity = max(0, (float) data_get($row, 'quantity', 0));

                    if ($name === '' || $quantity <= 0) {
                        return null;
                    }

                    return array_filter([
                        'name' => $name,
                        'hs_code' => trim((string) data_get($row, 'hs_code', '')),
                        'quantity' => $quantity,
                        'unit' => trim((string) data_get($row, 'unit', 'piece')) ?: 'piece',
                        'weight_kg' => data_get($row, 'weight_kg'),
                        'volume_cbm' => data_get($row, 'volume_cbm'),
                    ], fn ($value) => $value !== null && $value !== '');
                })
                ->filter()
                ->values()
                ->all();
        }

        $shipmentType = strtoupper((string) $this->shipment_type);
        if (! in_array($shipmentType, ['AIR', 'BULK', 'RORO', 'RO-RO'], true)) {
            return [];
        }

        $name = match ($shipmentType) {
            'BULK' => trim((string) data_get($cargoDetails, 'cargo_name', $this->goods_description)),
            'RORO', 'RO-RO' => collect([
                data_get($cargoDetails, 'vehicle_type'),
                data_get($cargoDetails, 'manufacturer'),
                data_get($cargoDetails, 'model'),
                data_get($cargoDetails, 'year'),
            ])->filter()->implode(' '),
            default => trim((string) data_get($cargoDetails, 'cargo.goods_description', $this->goods_description)),
        };
        $name = $name !== '' ? $name : $this->shipmentDisplay();
        $quantity = match ($shipmentType) {
            'AIR' => max(0.01, (float) ($this->volume_cbm ?: data_get($cargoDetails, 'volume_cbm', 1))),
            'BULK' => max(1, (float) data_get($cargoDetails, 'pieces', 1)),
            default => 1,
        };
        $unit = match ($shipmentType) {
            'AIR' => 'cbm',
            'RORO', 'RO-RO', 'FLAT CONTAINER' => 'vehicle',
            default => 'piece',
        };

        return [[
            'name' => $name,
            'hs_code' => $this->hsCode() ?: '',
            'quantity' => $quantity,
            'unit' => $unit,
            'weight_kg' => (float) ($this->weight_kg ?? data_get($cargoDetails, 'gross_weight_kg', 0)),
            'volume_cbm' => (float) ($this->volume_cbm ?? data_get($cargoDetails, 'volume_cbm', 0)),
        ]];
    }

    public function itemPricingLines(): array
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $rows = data_get($cargoDetails, 'item_pricing_lines', []);

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                if (! is_array($row)) {
                    return null;
                }

                $name = trim((string) data_get($row, 'name', ''));
                $quantity = max(0, (float) data_get($row, 'quantity', data_get($row, 'qty', 0)));
                $saleUnitPrice = max(0, (float) data_get($row, 'sale_unit_price', 0));
                $purchaseUnitPrice = max(0, (float) data_get($row, 'purchase_unit_price', 0));

                if ($name === '' || $quantity <= 0) {
                    return null;
                }

                return [
                    'name' => $name,
                    'hs_code' => trim((string) data_get($row, 'hs_code', '')),
                    'quantity' => $quantity,
                    'unit' => trim((string) data_get($row, 'unit', 'piece')) ?: 'piece',
                    'sale_unit_price' => $saleUnitPrice,
                    'purchase_unit_price' => $purchaseUnitPrice,
                    'sale_total' => max(0, (float) data_get($row, 'sale_total', round($quantity * $saleUnitPrice, 2))),
                    'purchase_total' => max(0, (float) data_get($row, 'purchase_total', round($quantity * $purchaseUnitPrice, 2))),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function itemPricingTotal(): float
    {
        return collect($this->itemPricingLines())->sum(fn (array $line) => (float) ($line['sale_total'] ?? 0));
    }

    public static function cargoUnitLabel(?string $unit): string
    {
        $unitKey = match (strtolower(trim((string) $unit))) {
            'vehicle' => 'vehicle',
            'cbm' => 'cbm',
            'kg' => 'kg',
            'ton' => 'ton',
            'container' => 'container',
            'mixed' => 'mixed',
            'service' => 'service',
            default => 'piece',
        };

        return __('messages.cargo_unit_' . $unitKey);
    }

    public function pricingBasis(): string
    {
        $itemPricingLines = $this->itemPricingLines();

        if ($itemPricingLines !== []) {
            $units = collect($itemPricingLines)
                ->map(fn (array $line) => strtolower(trim((string) ($line['unit'] ?? 'piece'))))
                ->unique()
                ->values();

            return $units->count() === 1 ? (string) $units->first() : 'mixed';
        }

        if ($this->isLandShipment()) {
            return 'ton';
        }

        if ($this->isDoorToDoorShipment()) {
            return $this->doorToDoorPricing()['basis'];
        }

        $routeCategory = strtolower((string) data_get($this->route_details, 'category'));
        if (strtoupper((string) $this->shipment_type) === 'AIR' || $routeCategory === 'air') {
            return 'cbm';
        }

        if ($this->isFclShipment() || ! blank($this->container_type) || $this->containerTotalQuantity()) {
            return 'container';
        }

        return 'piece';
    }

    public function pricingQuantity(): float
    {
        $itemPricingQuantity = collect($this->itemPricingLines())
            ->sum(fn (array $line) => (float) ($line['quantity'] ?? 0));

        if ($itemPricingQuantity > 0) {
            return (float) $itemPricingQuantity;
        }

        if ($this->isLandShipment()) {
            $landQuantity = $this->landPricingQuantity();

            if ($landQuantity > 0) {
                return (float) $landQuantity;
            }
        }

        if ($this->isDoorToDoorShipment()) {
            $doorToDoorQuantity = (float) $this->doorToDoorPricing()['quantity'];

            if ($doorToDoorQuantity > 0) {
                return $doorToDoorQuantity;
            }
        }

        $routeCategory = strtolower((string) data_get($this->route_details, 'category'));
        if (strtoupper((string) $this->shipment_type) === 'AIR' || $routeCategory === 'air') {
            $volumeCbm = (float) ($this->volume_cbm ?: data_get($this->cargo_details, 'volume_cbm', 0));

            if ($volumeCbm > 0) {
                return $volumeCbm;
            }
        }

        if ($this->isFclShipment()) {
            $fclQuantity = collect($this->fclPricingLines())
                ->sum(fn (array $line) => (float) ($line['qty'] ?? 0));

            if ($fclQuantity > 0) {
                return (float) $fclQuantity;
            }
        }

        $containerQuantity = $this->containerTotalQuantity();
        if ($containerQuantity !== null && $containerQuantity > 0) {
            return (float) $containerQuantity;
        }

        if ((float) $this->agent_purchase_quantity > 0) {
            return (float) $this->agent_purchase_quantity;
        }

        return 1.0;
    }

    public function doorToDoorPricing(): array
    {
        $pricing = is_array(data_get($this->cargo_details, 'door_to_door_pricing'))
            ? data_get($this->cargo_details, 'door_to_door_pricing')
            : [];
        $basis = strtolower(trim((string) data_get($pricing, 'basis', 'ton')));
        $basis = in_array($basis, ['ton', 'cbm'], true) ? $basis : 'ton';
        $grossWeightKg = (float) (
            $this->weight_kg
            ?? data_get($this->cargo_details, 'cargo.gross_weight_kg')
            ?? data_get($this->cargo_details, 'gross_weight_kg')
            ?? 0
        );
        $tons = (float) data_get($pricing, 'tons', $grossWeightKg > 0 ? $grossWeightKg / 1000 : 0);
        $volumeCbm = (float) (
            $this->volume_cbm
            ?: data_get($pricing, 'volume_cbm')
            ?: data_get($this->cargo_details, 'cargo.volume_cbm')
            ?: data_get($this->cargo_details, 'volume_cbm')
            ?: 0
        );
        $quantity = (float) data_get($pricing, 'quantity', 0);

        if ($quantity <= 0) {
            $quantity = $basis === 'cbm' ? $volumeCbm : $tons;
        }

        $unitPrice = data_get($pricing, 'unit_price');
        if ($unitPrice === null || $unitPrice === '') {
            $unitPrice = data_get($pricing, $basis === 'cbm' ? 'unit_price_per_cbm' : 'unit_price_per_ton', 0);
        }

        $total = data_get($pricing, 'total');
        if ($total === null || $total === '') {
            $total = $this->ocean_freight ?? 0;
        }

        return [
            'basis' => $basis,
            'quantity' => round(max(0, $quantity), 4),
            'unit_price' => round(max(0, (float) $unitPrice), 2),
            'total' => round(max(0, (float) $total), 2),
            'gross_weight_kg' => round(max(0, $grossWeightKg), 2),
            'tons' => round(max(0, $tons), 4),
            'volume_cbm' => round(max(0, $volumeCbm), 4),
        ];
    }

    public function agentPurchaseTotal(): float
    {
        return (float) ($this->agent_purchase_amount ?? 0);
    }

    public function profitAmount(): float
    {
        return (float) $this->pricingTotal() - $this->agentPurchaseTotal();
    }

    public function isExwSeaShipment(): bool
    {
        $routeDetails = is_array($this->route_details) ? $this->route_details : [];
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $shipmentType = strtoupper((string) $this->shipment_type);
        $routeCategory = strtolower((string) data_get($routeDetails, 'category'));
        $cargoServiceType = strtolower((string) data_get($cargoDetails, 'service_type'));
        $mode = strtoupper((string) (
            data_get($routeDetails, 'mode')
            ?: data_get($routeDetails, 'incoterm')
            ?: data_get($cargoDetails, 'trade.incoterm')
            ?: data_get($cargoDetails, 'incoterm')
        ));

        $isSea = $routeCategory === 'sea'
            || in_array($shipmentType, ['FCL', 'LCL', 'BULK', 'RORO', 'RO-RO'], true)
            || in_array($cargoServiceType, ['fcl', 'lcl', 'bulk', 'roro', 'ro-ro'], true);

        return $isSea && $mode === 'EXW';
    }

    public function shouldShowExwCharges(): bool
    {
        return $this->isExwSeaShipment() && (float) ($this->exw_charges ?? 0) > 0;
    }

    public static function pricingCurrencyOptions(): array
    {
        return self::PRICING_CURRENCIES;
    }

    public static function normalizePricingCurrency(?string $currency): string
    {
        return 'USD';
    }

    public static function pricingCurrencySymbolFor(?string $currency): string
    {
        return self::PRICING_CURRENCIES[self::normalizePricingCurrency($currency)]['symbol'];
    }

    public static function pricingCurrencyNameFor(?string $currency, ?string $locale = null): string
    {
        $currency = self::normalizePricingCurrency($currency);
        $nameKey = ($locale ?? app()->getLocale()) === 'ar' ? 'name_ar' : 'name_en';

        return self::PRICING_CURRENCIES[$currency][$nameKey];
    }

    public function pricingCurrency(): string
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];

        return self::normalizePricingCurrency(
            data_get($cargoDetails, 'pricing_currency')
                ?: $this->agent_purchase_currency
                ?: data_get($cargoDetails, 'currency')
                ?: 'USD'
        );
    }

    public function pricingCurrencySymbol(): string
    {
        return self::pricingCurrencySymbolFor($this->pricingCurrency());
    }

    public function pricingCurrencyName(?string $locale = null): string
    {
        return self::pricingCurrencyNameFor($this->pricingCurrency(), $locale);
    }

    public function formatPricingMoney(float|int|null $amount, int $decimals = 2): string
    {
        return $this->pricingCurrencySymbol() . ' ' . number_format((float) $amount, $decimals);
    }

    public static function formatMoneyTotalsByCurrency(iterable $items, callable $amountResolver, ?callable $currencyResolver = null, int $decimals = 2): string
    {
        $totals = [];

        foreach ($items as $item) {
            $amount = (float) $amountResolver($item);
            $currency = self::normalizePricingCurrency(
                $currencyResolver
                    ? $currencyResolver($item)
                    : ($item instanceof self ? $item->pricingCurrency() : 'USD')
            );

            $totals[$currency] = ($totals[$currency] ?? 0) + $amount;
        }

        if ($totals === []) {
            return self::pricingCurrencySymbolFor('USD') . ' ' . number_format(0, $decimals);
        }

        return collect($totals)
            ->filter(fn ($amount) => abs((float) $amount) > 0.004)
            ->whenEmpty(fn ($totalsCollection) => $totalsCollection->put('USD', 0))
            ->map(fn ($amount, $currency) => self::pricingCurrencySymbolFor($currency) . ' ' . number_format((float) $amount, $decimals))
            ->implode(' / ');
    }

    public function containerSummary(): ?string
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];

        $savedSummary = trim((string) data_get($cargoDetails, 'container_summary', ''));
        if ($savedSummary !== '') {
            return $savedSummary;
        }

        $landSummary = $this->landShipmentSummary();
        if ($landSummary !== null) {
            return $landSummary;
        }

        $containers = data_get($cargoDetails, 'containers');
        if (is_array($containers)) {
            $summary = collect($containers)
                ->map(function ($container) {
                    $qty = (int) data_get($container, 'qty', 0);
                    $type = trim((string) data_get($container, 'type', ''));

                    return $qty > 0 && $type !== '' ? "{$qty} × {$type}" : null;
                })
                ->filter()
                ->implode(' + ');

            if ($summary !== '') {
                return $summary;
            }
        }

        if (! blank($this->container_type)) {
            $qty = (int) ($this->container_qty ?: 1);

            return "{$qty}x{$this->container_type}";
        }

        return null;
    }

    public function containerTotalQuantity(): ?int
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];

        $savedTotal = (int) data_get($cargoDetails, 'container_total_qty', 0);
        if ($savedTotal > 0) {
            return $savedTotal;
        }

        $landTotal = $this->landShipmentTotalQuantity();
        if ($landTotal > 0) {
            return $landTotal;
        }

        $containers = data_get($cargoDetails, 'containers');
        if (is_array($containers)) {
            $total = collect($containers)->sum(fn ($container) => (int) data_get($container, 'qty', 0));
            if ($total > 0) {
                return (int) $total;
            }
        }

        if (! blank($this->container_type) && (int) $this->container_qty > 0) {
            return (int) $this->container_qty;
        }

        return null;
    }

    public function isFclShipment(): bool
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];

        return strtoupper((string) $this->shipment_type) === 'FCL'
            || data_get($cargoDetails, 'service_type') === 'fcl';
    }

    public function fclPricingLines(): array
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $pricingLines = data_get($cargoDetails, 'fcl_pricing_lines', []);

        if (! is_array($pricingLines) || $pricingLines === []) {
            return [];
        }

        return collect($pricingLines)
            ->map(function ($line) {
                if (! is_array($line)) {
                    return null;
                }

                $qty = max(0, (float) data_get($line, 'qty', 0));
                $type = trim((string) data_get($line, 'type', data_get($line, 'container_type', '')));
                $unitPrice = max(0, (float) data_get($line, 'unit_price', data_get($line, 'price', 0)));
                $total = data_get($line, 'total');
                $total = $total === null || $total === '' ? round($qty * $unitPrice, 2) : max(0, (float) $total);

                if ($qty <= 0 || $type === '') {
                    return null;
                }

                return [
                    'type' => $type,
                    'qty' => $qty,
                    'unit_price' => round($unitPrice, 2),
                    'total' => round($total, 2),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function fclPricingBreakdown(): array
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];

        if (! $this->isFclShipment()) {
            return [];
        }

        $pricingLines = $this->fclPricingLines();
        if ($pricingLines !== []) {
            return collect($pricingLines)
                ->map(function (array $line) {
                    $qty = (float) $line['qty'];
                    $type = (string) $line['type'];
                    $amount = (float) ($line['total'] ?? round($qty * ($line['unit_price'] ?? 0), 2));

                    return [
                        'label' => sprintf('%s × %s', $qty, $type),
                        'amount' => round($amount, 2),
                        'unit_price' => round((float) ($line['unit_price'] ?? ($amount / max($qty, 1))), 2),
                        'quantity' => $qty,
                        'class' => '',
                        'isWar' => false,
                        'show' => true,
                    ];
                })
                ->all();
        }

        $containers = data_get($cargoDetails, 'containers', []);
        if (! is_array($containers) || count($containers) < 2) {
            return [];
        }

        $rows = collect($containers)
            ->map(function ($container) {
                if (! is_array($container)) {
                    return null;
                }

                $qty = max(0, (int) data_get($container, 'qty', 0));
                $type = trim((string) data_get($container, 'type', ''));

                if ($qty <= 0 || $type === '') {
                    return null;
                }

                return [
                    'qty' => $qty,
                    'type' => $type,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($rows === []) {
            return [];
        }

        $totalQty = collect($rows)->sum(fn (array $row) => (float) $row['qty']);
        $totalAmount = max(0, (float) $this->pricingTotal());

        if ($totalQty <= 0 || $totalAmount <= 0) {
            return [];
        }

        $unitPrice = round($totalAmount / $totalQty, 2);

        return collect($rows)
            ->map(function (array $row) use ($unitPrice) {
                $qty = (float) $row['qty'];
                $type = (string) $row['type'];

                return [
                    'label' => sprintf('%s × %s', $qty, $type),
                    'amount' => round($qty * $unitPrice, 2),
                    'unit_price' => $unitPrice,
                    'quantity' => $qty,
                    'class' => '',
                    'isWar' => false,
                    'show' => true,
                ];
            })
            ->all();
    }

    protected function landShipmentSummary(): ?string
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $shipments = data_get($cargoDetails, 'land_shipments', []);

        if (! is_array($shipments) || $shipments === []) {
            return null;
        }

        $summary = collect($shipments)
            ->map(function ($shipment) {
                $qty = (int) data_get($shipment, 'qty', data_get($shipment, 'truck_qty', 0));
                $fromCity = trim((string) data_get($shipment, 'from_city', data_get($shipment, 'origin_city', '')));
                $toCity = trim((string) data_get($shipment, 'to_city', data_get($shipment, 'destination_city', '')));

                if ($qty <= 0 || $fromCity === '' || $toCity === '') {
                    return null;
                }

                return "{$qty}x{$fromCity} → {$toCity}";
            })
            ->filter()
            ->implode(' + ');

        return $summary !== '' ? $summary : null;
    }

    protected function landShipmentTotalQuantity(): int
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $shipments = data_get($cargoDetails, 'land_shipments', []);

        if (! is_array($shipments) || $shipments === []) {
            return 0;
        }

        return collect($shipments)->sum(fn ($shipment) => (int) data_get($shipment, 'qty', data_get($shipment, 'truck_qty', 0)));
    }

    public function shipmentDisplay(): string
    {
        $shipmentType = trim((string) $this->shipment_type);
        $containerSummary = $this->containerSummary();

        return trim($shipmentType . ($containerSummary ? ' ' . $containerSummary : ''));
    }

    public function isLandShipment(): bool
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $routeDetails = is_array($this->route_details) ? $this->route_details : [];

        return strtoupper((string) $this->shipment_type) === 'LAND'
            || data_get($cargoDetails, 'service_type') === 'land'
            || data_get($routeDetails, 'category') === 'land'
            || data_get($routeDetails, 'mode') === 'LAND';
    }

    public function isDoorToDoorShipment(): bool
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $routeDetails = is_array($this->route_details) ? $this->route_details : [];

        return strtoupper((string) $this->shipment_type) === 'DOOR_TO_DOOR'
            || data_get($cargoDetails, 'service_type') === 'door_to_door'
            || data_get($routeDetails, 'category') === 'door_to_door'
            || data_get($routeDetails, 'mode') === 'DOOR_TO_DOOR';
    }

    public function landShipmentLines(): array
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $shipments = data_get($cargoDetails, 'land_shipments', []);

        if (! is_array($shipments)) {
            $shipments = [];
        }

        $lines = collect($shipments)
            ->map(function ($shipment) {
                if (! is_array($shipment)) {
                    return null;
                }

                $qty = max(0, (float) data_get($shipment, 'qty', data_get($shipment, 'truck_qty', 0)));
                $fromCity = trim((string) data_get($shipment, 'from_city', data_get($shipment, 'origin_city', '')));
                $toCity = trim((string) data_get($shipment, 'to_city', data_get($shipment, 'destination_city', '')));

                if ($qty <= 0 || $fromCity === '' || $toCity === '') {
                    return null;
                }

                return [
                    'qty' => $qty,
                    'from_city' => $fromCity,
                    'to_city' => $toCity,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($lines === [] && $this->isLandShipment()) {
            $qty = max(1, (float) ($this->container_qty ?: $this->agent_purchase_quantity ?: 1));
            $fromCity = trim((string) $this->pol);
            $toCity = trim((string) $this->pod);

            if ($fromCity !== '' && $toCity !== '') {
                $lines[] = [
                    'qty' => $qty,
                    'from_city' => $fromCity,
                    'to_city' => $toCity,
                ];
            }
        }

        return $lines;
    }

    public function landPricingLines(): array
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $pricingLines = data_get($cargoDetails, 'land_pricing_lines', []);

        if (! is_array($pricingLines) || $pricingLines === []) {
            return $this->fallbackLandPricingLines();
        }

        return collect($pricingLines)
            ->map(fn ($line) => $this->normalizeLandPricingLine(is_array($line) ? $line : []))
            ->filter()
            ->values()
            ->all();
    }

    public function landPricingQuantity(): float
    {
        $pricedQty = collect($this->landPricingLines())->sum(fn (array $line) => (float) ($line['qty'] ?? 0));

        if ($pricedQty > 0) {
            return $pricedQty;
        }

        $tons = $this->landPricingWeightTons();

        return $tons > 0 ? $tons : (float) $this->landShipmentTotalQuantity();
    }

    public function landPricingWeightTons(): float
    {
        $cargoDetails = is_array($this->cargo_details) ? $this->cargo_details : [];
        $weightKg = (float) (
            $this->weight_kg
            ?? data_get($cargoDetails, 'cargo.gross_weight_kg')
            ?? data_get($cargoDetails, 'gross_weight_kg')
            ?? 0
        );

        return $weightKg > 0 ? round($weightKg / 1000, 4) : 0.0;
    }

    public function landPricingTotal(): float
    {
        return collect($this->landPricingLines())->sum(fn (array $line) => (float) ($line['total'] ?? 0));
    }

    private function fallbackLandPricingLines(): array
    {
        if (! $this->isLandShipment()) {
            return [];
        }

        $shipmentLines = $this->landShipmentLines();
        $legacyTotal = (float) ($this->ocean_freight ?? 0)
            + (float) ($this->origin_charges ?? 0)
            + (float) ($this->dthc ?? 0)
            + (float) ($this->war_risk_surcharge ?? 0)
            + (float) ($this->other_charges ?? 0);

        if ($shipmentLines === []) {
            return [];
        }

        $totalRouteQty = collect($shipmentLines)->sum(fn (array $line) => (float) ($line['qty'] ?? 0));
        $totalTons = $this->landPricingWeightTons();
        $pricingQuantity = $totalTons > 0 ? $totalTons : $totalRouteQty;
        $fallbackUnitPrice = $legacyTotal > 0 && $pricingQuantity > 0
            ? round($legacyTotal / $pricingQuantity, 2)
            : 0;

        return collect($shipmentLines)
            ->map(function (array $line) use ($fallbackUnitPrice, $totalRouteQty, $totalTons) {
                $routeQty = max(0, (float) ($line['qty'] ?? 0));
                $qty = $totalTons > 0 && $totalRouteQty > 0
                    ? round($totalTons * $routeQty / $totalRouteQty, 4)
                    : $routeQty;
                $line['truck_qty'] = $routeQty;
                $line['unit_price'] = $fallbackUnitPrice;
                $line['unit'] = 'ton';
                $line['qty'] = $qty;
                $line['total'] = round($qty * $fallbackUnitPrice, 2);

                return $line;
            })
            ->all();
    }

    private function normalizeLandPricingLine(array $line): ?array
    {
        $qty = max(0, (float) data_get($line, 'qty', data_get($line, 'tons', data_get($line, 'weight_tons', 0))));
        $truckQty = max(0, (float) data_get($line, 'truck_qty', data_get($line, 'route_qty', 0)));
        $fromCity = trim((string) data_get($line, 'from_city', data_get($line, 'origin_city', '')));
        $toCity = trim((string) data_get($line, 'to_city', data_get($line, 'destination_city', '')));
        $unitPrice = max(0, (float) data_get($line, 'unit_price', data_get($line, 'price', 0)));
        $total = data_get($line, 'total');
        $total = $total === null || $total === ''
            ? round($qty * $unitPrice, 2)
            : max(0, (float) $total);

        if ($qty <= 0 || $fromCity === '' || $toCity === '') {
            return null;
        }

        return array_filter([
            'qty' => $qty,
            'truck_qty' => $truckQty > 0 ? $truckQty : null,
            'unit' => data_get($line, 'unit', 'ton'),
            'from_city' => $fromCity,
            'to_city' => $toCity,
            'unit_price' => $unitPrice,
            'total' => $total,
            'plate_number' => trim((string) data_get($line, 'plate_number', '')),
            'notes' => trim((string) data_get($line, 'notes', '')),
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function shipmentTrack(): HasOne
    {
        return $this->hasOne(ShipmentTrack::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_booking_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_booking_id');
    }
}
