<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomsCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndUsersSeeder::class);
    }

    public function test_customs_calculator_matches_rasaq_reference_example(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(route('customs.calculate'), [
            'customs_exchange_rate' => 125.25,
            'tax_exchange_rate' => 554.607,
            'other_services_fee' => 498250,
            'items' => [
                [
                    'description' => '27101939',
                    'hs' => '27101939',
                    'packages' => 7000,
                    'weight_kg' => 105000,
                    'value_usd' => 208000,
                    'cv_rate' => 10,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('totals.customs_base', 26052000)
            ->assertJsonPath('totals.grand_total', 14140300)
            ->assertJsonPath('breakdown.0.code', 'CV')
            ->assertJsonPath('breakdown.0.base', 26052000)
            ->assertJsonPath('breakdown.0.amount', 2605200)
            ->assertJsonPath('breakdown.1.code', 'ST')
            ->assertJsonPath('breakdown.1.base', 115358256)
            ->assertJsonPath('breakdown.1.amount', 5767913)
            ->assertJsonPath('breakdown.6.code', 'TEF')
            ->assertJsonPath('breakdown.6.amount', 547092)
            ->assertJsonPath('breakdown.9.code', 'HT')
            ->assertJsonPath('breakdown.9.amount', 70000)
            ->assertJsonPath('breakdown.10.code', 'FD')
            ->assertJsonPath('breakdown.10.amount', 260520)
            ->assertJsonPath('breakdown.11.code', 'OS')
            ->assertJsonPath('breakdown.11.amount', 498250);
    }
}
