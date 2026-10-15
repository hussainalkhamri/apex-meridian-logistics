<?php

namespace App\Support;

class RasaqCustomsCalculator
{
    public const DEFAULT_CUSTOMS_EXCHANGE_RATE = 125.25;
    public const DEFAULT_TAX_EXCHANGE_RATE = 554.607;
    public const DEFAULT_OTHER_SERVICES_FEE = 498250;

    private const FEE_RATES = [
        'ST' => 5,
        'VAT' => 1,
        'PT' => 1,
        'VF' => 2,
        'CND' => 0,
        'TEF' => 1,
        'MMF' => 2,
        'CED' => 0,
    ];

    public function calculate(array $payload): array
    {
        $customsExchangeRate = (float) ($payload['customs_exchange_rate'] ?? self::DEFAULT_CUSTOMS_EXCHANGE_RATE);
        $taxExchangeRate = (float) ($payload['tax_exchange_rate'] ?? self::DEFAULT_TAX_EXCHANGE_RATE);
        $otherServicesFee = (float) ($payload['other_services_fee'] ?? self::DEFAULT_OTHER_SERVICES_FEE);
        $items = collect($payload['items'] ?? [])
            ->filter(fn (array $item) => (float) ($item['value_usd'] ?? 0) > 0)
            ->values();

        $breakdown = [];
        $totals = [
            'usd_value' => 0.0,
            'customs_base' => 0.0,
            'tax_base' => 0.0,
            'fees_base' => 0.0,
            'cv' => 0.0,
            'duties' => 0.0,
            'grand_total' => 0.0,
        ];

        foreach ($items as $index => $item) {
            $itemNumber = $index + 1;
            $valueUsd = (float) ($item['value_usd'] ?? 0);
            $packages = (float) ($item['packages'] ?? 0);
            $weightKg = (float) ($item['weight_kg'] ?? 0);
            $cvRate = (float) ($item['cv_rate'] ?? 0);
            $hs = trim((string) ($item['hs'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));

            $customsBase = round($valueUsd * $customsExchangeRate, 3);
            $taxBase = round($valueUsd * $taxExchangeRate, 3);
            $feesBase = round($customsBase * 2, 3);
            $cvAmount = $customsBase * $cvRate / 100;

            $totals['usd_value'] += $valueUsd;
            $totals['customs_base'] += $customsBase;
            $totals['tax_base'] += $taxBase;
            $totals['fees_base'] += $feesBase;
            $totals['cv'] += $cvAmount;

            $this->pushFeeRow($breakdown, $itemNumber, $hs, $description, 'CV', $customsBase, $cvRate, $cvAmount);

            foreach (self::FEE_RATES as $code => $rate) {
                $base = in_array($code, ['ST', 'VAT', 'PT'], true) ? $taxBase : $feesBase;
                $amountBase = $code === 'TEF' ? $base + $cvAmount : $base;
                $amount = $amountBase * $rate / 100;
                $this->pushFeeRow($breakdown, $itemNumber, $hs, $description, $code, $base, $rate, $amount);
            }

            $htAmount = $packages * 10;
            $fdAmount = $cvAmount * 10 / 100;

            $this->pushFeeRow($breakdown, $itemNumber, $hs, $description, 'HT', $packages, 10, $htAmount);
            $this->pushFeeRow($breakdown, $itemNumber, $hs, $description, 'FD', null, null, $fdAmount);
        }

        if ($items->isNotEmpty()) {
            $this->pushFeeRow($breakdown, null, null, 'خدمات أخرى', 'OS', null, null, $otherServicesFee);
        }

        $rawGrandTotal = collect($breakdown)->sum('raw_amount');
        $totals['duties'] = $rawGrandTotal;
        $totals['grand_total'] = round($rawGrandTotal);

        return [
            'settings' => [
                'customs_exchange_rate' => $customsExchangeRate,
                'tax_exchange_rate' => $taxExchangeRate,
                'other_services_fee' => $otherServicesFee,
            ],
            'items' => $items->map(fn (array $item, int $index) => [
                'item_number' => $index + 1,
                'hs' => trim((string) ($item['hs'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
                'packages' => (float) ($item['packages'] ?? 0),
                'weight_kg' => (float) ($item['weight_kg'] ?? 0),
                'value_usd' => (float) ($item['value_usd'] ?? 0),
                'cv_rate' => (float) ($item['cv_rate'] ?? 0),
            ])->all(),
            'breakdown' => $breakdown,
            'totals' => array_map(fn ($value) => round($value, 3), $totals),
        ];
    }

    private function pushFeeRow(array &$breakdown, ?int $itemNumber, ?string $hs, string $description, string $code, ?float $base, ?float $rate, float $rawAmount): void
    {
        $breakdown[] = [
            'item_number' => $itemNumber,
            'hs' => $hs ?: '-',
            'description' => $description ?: '-',
            'code' => $code,
            'base' => $base === null ? null : round($base),
            'rate' => $rate,
            'raw_amount' => $rawAmount,
            'amount' => round($rawAmount),
        ];
    }
}
