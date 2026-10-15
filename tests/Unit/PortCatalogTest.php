<?php

namespace Tests\Unit;

use App\Support\PortCatalog;
use Tests\TestCase;

class PortCatalogTest extends TestCase
{
    public function test_port_catalog_includes_taiwan_with_flag(): void
    {
        $countries = PortCatalog::countries();

        $taiwan = $countries->firstWhere('code', 'TW');

        $this->assertNotNull($taiwan);
        $this->assertSame('Taiwan', $taiwan['name']);
        $this->assertSame('🇹🇼', $taiwan['flag']);
    }

    public function test_port_catalog_includes_portugal_with_flag(): void
    {
        $countries = PortCatalog::countries();

        $portugal = $countries->firstWhere('code', 'PT');

        $this->assertNotNull($portugal);
        $this->assertSame('Portugal', $portugal['name']);
        $this->assertSame('البرتغال', $portugal['name_ar']);
        $this->assertSame('🇵🇹', $portugal['flag']);
        $this->assertFileExists(public_path('flags/pt.svg'));
    }

    public function test_portugal_ports_and_airports_are_available(): void
    {
        $ports = collect(config('ports.ports'));
        $airports = collect(config('airports.airports'));

        $this->assertTrue($ports->contains(fn (array $port) => ($port['country_code'] ?? null) === 'PT' && ($port['code'] ?? null) === 'PTLIS'));
        $this->assertTrue($ports->contains(fn (array $port) => ($port['country_code'] ?? null) === 'PT' && ($port['code'] ?? null) === 'PTSIE'));
        $this->assertTrue($airports->contains(fn (array $airport) => ($airport['country_code'] ?? null) === 'PT' && ($airport['iata_code'] ?? null) === 'LIS'));
        $this->assertTrue($airports->contains(fn (array $airport) => ($airport['country_code'] ?? null) === 'PT' && ($airport['iata_code'] ?? null) === 'OPO'));
    }
}
