<?php

namespace App\Support;

use App\Models\PortCountry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class PortCatalog
{
    public static function countries(bool $includeManagedCountries = true): Collection
    {
        $countries = collect(config('ports.countries', []))
            ->map(fn (array $country) => [
                'code' => strtoupper((string) ($country['code'] ?? '')),
                'name' => (string) ($country['name'] ?? ''),
                'name_ar' => (string) ($country['name_ar'] ?? ($country['name'] ?? '')),
                'flag' => (string) ($country['flag'] ?? ''),
            ])
            ->filter(fn (array $country) => $country['name'] !== '');

        if ($includeManagedCountries) {
            $countries = $countries->merge(self::managedCountries());
        }

        return $countries
            ->unique(fn (array $country) => $country['code'] !== '' ? $country['code'] : mb_strtolower($country['name']))
            ->sortBy('name')
            ->values();
    }

    private static function managedCountries(): Collection
    {
        try {
            if (! Schema::hasTable('port_countries')) {
                return collect();
            }

            return PortCountry::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->toBase()
                ->map(fn (PortCountry $country) => self::countryOptionFromManagedCountry($country))
                ->filter();
        } catch (Throwable) {
            return collect();
        }
    }

    public static function resolveCountry(?string $countryName = null, ?string $countryCode = null, ?string $countryFlag = null): array
    {
        $countryName = trim((string) $countryName);
        $countryCode = strtoupper(trim((string) $countryCode));
        $countryFlag = trim((string) $countryFlag);

        $matched = null;
        $countries = self::countries();

        if ($countryCode !== '') {
            $matched = $countries->firstWhere('code', $countryCode);
        }

        if (! $matched && $countryName !== '') {
            $matched = $countries->first(function (array $country) use ($countryName) {
                return strcasecmp($country['name'], $countryName) === 0
                    || $country['name_ar'] === $countryName;
            });
        }

        return [
            'country' => $countryName !== '' ? $countryName : ($matched['name'] ?? null),
            'country_code' => $countryCode !== '' ? $countryCode : ($matched['code'] ?? null),
            'country_flag' => $countryFlag !== '' ? $countryFlag : ($matched['flag'] ?? null),
        ];
    }

    public static function countryOptionFromPort(object $port): ?array
    {
        if (blank($port->country)) {
            return null;
        }

        $resolved = self::resolveCountry($port->country, $port->country_code, $port->country_flag);

        return [
            'name' => $resolved['country'] ?: $port->country,
            'code' => $resolved['country_code'],
            'flag' => $resolved['country_flag'],
        ];
    }

    public static function countryOptionFromManagedCountry(object $country): ?array
    {
        if (blank($country->name)) {
            return null;
        }

        return [
            'name' => (string) $country->name,
            'name_ar' => (string) ($country->name_ar ?: $country->name),
            'code' => strtoupper((string) ($country->code ?: '')),
            'flag' => (string) ($country->flag ?: ''),
        ];
    }
}
