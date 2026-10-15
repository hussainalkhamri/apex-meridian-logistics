<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomsDeclarationXmlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndUsersSeeder::class);
    }

    public function test_customs_declaration_can_be_exported_as_rasaq_style_xml(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(route('customs.declaration.xml'), [
            'declaration_type' => 'import',
            'file_name' => 'إقرار_جمركي_20260722_175849',
            'serial_no' => '999',
            'shipment_no' => 'SHT-2026-1',
            'importer_code' => '1',
            'customer' => 'شركة الإمداد الذكية',
            'declarant_code' => '0165213',
            'declarant_name' => 'أحمد عبده المجيد محمد ماهر',
            'customs_office_code' => '0309',
            'declaration_no' => 'DEC-1',
            'bayan_no' => 'BAYAN-1',
            'customs_center' => 'عفار',
            'country' => 'اليمن',
            'destination' => 'صنعاء',
            'goods_type' => 'مشتقات نفطية',
            'driver' => 'سائق تجريبي',
            'truck' => 'TRK-101',
            'arrival_date' => '2026-07-22',
            'note' => 'إقرار من النظام',
            'items' => [
                [
                    'name' => 'ديزل',
                    'brand' => 'Rasaq',
                    'origin' => 'China',
                    'count' => 1,
                    'type' => 'برميل',
                    'packages' => 7000,
                    'price_unit' => 'USD',
                    'package_weight_kg' => 15,
                    'net_weight_kg' => 105000,
                    'value_usd' => 208000,
                    'price' => 208000,
                    'reference' => 'REF-1',
                    'hs' => '27101939',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();
        $this->assertStringContainsString('<CustomsDeclaration format="rasaq-compatible"', $xml);
        $this->assertStringContainsString('<FileName>إقرار_جمركي_20260722_175849</FileName>', $xml);
        $this->assertStringContainsString('<SerialNo>999</SerialNo>', $xml);
        $this->assertStringContainsString('<ImporterCode>1</ImporterCode>', $xml);
        $this->assertStringContainsString('<DeclarantCode>0165213</DeclarantCode>', $xml);
        $this->assertStringContainsString('<DeclarantName>أحمد عبده المجيد محمد ماهر</DeclarantName>', $xml);
        $this->assertStringContainsString('<CustomsOfficeCode>0309</CustomsOfficeCode>', $xml);
        $this->assertStringContainsString('<ShipmentNo>SHT-2026-1</ShipmentNo>', $xml);
        $this->assertStringContainsString('<Customer>شركة الإمداد الذكية</Customer>', $xml);
        $this->assertStringContainsString('<Country>اليمن</Country>', $xml);
        $this->assertStringContainsString('<Brand>Rasaq</Brand>', $xml);
        $this->assertStringContainsString('<HsCode>27101939</HsCode>', $xml);
        $this->assertStringContainsString('<Packages>7000</Packages>', $xml);
        $this->assertStringContainsString('<NetWeightKg>105000</NetWeightKg>', $xml);
        $this->assertStringContainsString('<ValueUsd>208000</ValueUsd>', $xml);
        $this->assertStringContainsString('<ItemsCount>1</ItemsCount>', $xml);
        $this->assertStringContainsString('<Quantity>1</Quantity>', $xml);
        $response->assertHeader('Content-Disposition', 'attachment; filename="customs-declaration.xml"; filename*=UTF-8\'\'' . rawurlencode('إقرار_جمركي_20260722_175849.xml'));
    }

    public function test_customs_declaration_can_be_exported_as_excel_file(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(route('customs.declaration.excel'), [
            'declaration_type' => 'import',
            'file_name' => 'إقرار_جمركي_20260722_175849',
            'shipment_no' => 'SHT-2026-1',
            'importer_code' => '1',
            'customer' => 'شركة الإمداد الذكية',
            'declarant_code' => '0165213',
            'declarant_name' => 'أحمد عبده المجيد محمد ماهر',
            'customs_office_code' => '0309',
            'country' => 'اليمن',
            'items' => [
                [
                    'name' => 'ديزل',
                    'brand' => 'Rasaq',
                    'origin' => 'China',
                    'count' => 2,
                    'type' => 'برميل',
                    'packages' => 7000,
                    'net_weight_kg' => 105000,
                    'value_usd' => 208000,
                    'price' => 104000,
                    'hs' => '27101939',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="customs-declaration.xls"; filename*=UTF-8\'\'' . rawurlencode('إقرار_جمركي_20260722_175849.xls'));

        $content = $response->getContent();
        $this->assertStringContainsString('إقرار جمركي', $content);
        $this->assertStringContainsString('شركة الإمداد الذكية', $content);
        $this->assertStringContainsString('27101939', $content);
        $this->assertStringContainsString('208000', $content);
    }

    public function test_customs_declaration_totals_match_entered_item_rows(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(route('customs.declaration.xml'), [
            'declaration_type' => 'import',
            'items' => [
                [
                    'name' => 'زيت 20w50',
                    'brand' => 'lukoil',
                    'origin' => 'تركيا',
                    'count' => 2520,
                    'type' => 'كرتون',
                    'packages' => 12,
                    'net_weight_kg' => 30240,
                    'value_usd' => 50400,
                    'price' => 20,
                    'hs' => '27101939',
                ],
                [
                    'name' => 'زيت 15w40',
                    'brand' => 'Lokoil',
                    'origin' => 'تركيا',
                    'count' => 720,
                    'type' => 'كرتون',
                    'packages' => 4,
                    'net_weight_kg' => 13456,
                    'value_usd' => 23760,
                    'price' => 33,
                    'hs' => '27101939',
                ],
            ],
        ]);

        $xml = $response->assertOk()->getContent();

        $this->assertStringContainsString('<ItemsCount>2</ItemsCount>', $xml);
        $this->assertStringContainsString('<Quantity>3240</Quantity>', $xml);
        $this->assertStringContainsString('<Packages>16</Packages>', $xml);
        $this->assertStringContainsString('<NetWeightKg>43696</NetWeightKg>', $xml);
        $this->assertStringContainsString('<ValueUsd>74160</ValueUsd>', $xml);
    }

    public function test_customs_declaration_page_contains_xml_export_controls(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('customs.index'))
            ->assertOk()
            ->assertSee('download-declaration-excel', false)
            ->assertSee('declaration-items-body', false)
            ->assertSee('declaration-customs-office-code', false)
            ->assertSee('declaration-total-packages', false)
            ->assertSee(route('customs.declaration.excel'), false)
            ->assertSee('shipmate.customs.declaration.draft', false)
            ->assertSee('restoreDeclarationDraft', false)
            ->assertDontSee('download-declaration-xml', false);
    }
}
