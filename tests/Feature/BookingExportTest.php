<?php

namespace Tests\Feature;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_export_xlsx_returns_excel_file(): void
    {
        Booking::create([
            'id' => 'BK-TEST-1',
            'customer_company' => 'شركة اختبارات',
            'customer_contact_name' => 'أحمد',
            'customer_phone' => '123456',
            'customer_email' => 'test@example.com',
            'shipment_type' => 'FCL',
            'goods_description' => 'أجهزة',
            'notes' => 'ملاحظة عربية',
            'weight_kg' => 10,
            'volume_cbm' => 2,
            'documents' => 'فاتورة',
            'pol' => 'Qingdao',
            'pod' => 'Salalah',
            'status' => 'new',
        ]);

        $response = $this->withoutMiddleware()->get('/orders?export=xlsx');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_orders_export_xlsx_uses_current_filters(): void
    {
        Booking::create([
            'id' => 'BK-FILTERED-XLSX',
            'customer_company' => 'Filtered Export Customer',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '111111',
            'customer_email' => 'filtered@example.com',
            'shipment_type' => 'FCL',
            'goods_description' => 'Filtered goods',
            'weight_kg' => 10,
            'volume_cbm' => 2,
            'pol' => 'Qingdao',
            'pod' => 'Aden',
            'status' => 'approved',
        ]);

        Booking::create([
            'id' => 'BK-APPROVED-OTHER',
            'customer_company' => 'Other Approved Customer',
            'customer_contact_name' => 'Saleh',
            'customer_phone' => '222222',
            'customer_email' => 'other-approved@example.com',
            'shipment_type' => 'FCL',
            'goods_description' => 'Other approved goods',
            'weight_kg' => 20,
            'volume_cbm' => 3,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'approved',
        ]);

        Booking::create([
            'id' => 'BK-FILTERED-REJECTED',
            'customer_company' => 'Filtered Export Rejected Customer',
            'customer_contact_name' => 'Ali',
            'customer_phone' => '333333',
            'customer_email' => 'filtered-rejected@example.com',
            'shipment_type' => 'FCL',
            'goods_description' => 'Rejected filtered goods',
            'weight_kg' => 30,
            'volume_cbm' => 4,
            'pol' => 'Ningbo',
            'pod' => 'Aden',
            'status' => 'rejected',
        ]);

        $response = $this->withoutMiddleware()->get('/orders?export=xlsx&q=Filtered%20Export&status=approved');

        $response->assertOk();

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertIsString($sheetXml);
        $this->assertStringContainsString('BK-FILTERED-XLSX', $sheetXml);
        $this->assertStringContainsString('Filtered Export Customer', $sheetXml);
        $this->assertStringNotContainsString('BK-APPROVED-OTHER', $sheetXml);
        $this->assertStringNotContainsString('BK-FILTERED-REJECTED', $sheetXml);
    }

    public function test_orders_export_xlsx_hides_purchase_price_and_currency_columns(): void
    {
        Booking::create([
            'id' => 'BK-HIDDEN-PURCHASE',
            'customer_company' => 'Hidden Purchase Customer',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '444444',
            'customer_email' => 'hidden-purchase@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40GP',
            'container_qty' => 1,
            'goods_description' => 'Sensitive purchase data',
            'weight_kg' => 10,
            'volume_cbm' => 2,
            'pol' => 'Qingdao',
            'pod' => 'Aden',
            'status' => 'approved',
            'ocean_freight' => 12345,
            'agent_purchase_amount' => 8765.43,
            'agent_purchase_currency' => 'USD',
            'agent_purchase_notes' => 'Hidden purchase note',
            'cargo_details' => [
                'pricing_currency' => 'USD',
                'currency' => 'USD',
                'agent_purchase_lines' => [
                    ['qty' => 1, 'unit_price' => 777.77, 'total' => 777.77],
                ],
                'trade' => [
                    'currency' => 'USD',
                ],
            ],
        ]);

        $response = $this->withoutMiddleware()->get('/orders?export=xlsx&q=BK-HIDDEN-PURCHASE');

        $response->assertOk();

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertIsString($sheetXml);
        $this->assertStringContainsString('BK-HIDDEN-PURCHASE', $sheetXml);
        $this->assertStringNotContainsString('عملة شراء الوكيل', $sheetXml);
        $this->assertStringNotContainsString('قيمة شراء الوكيل USD', $sheetXml);
        $this->assertStringNotContainsString('ربح الطلب USD', $sheetXml);
        $this->assertStringNotContainsString('عملة التسعير', $sheetXml);
        $this->assertStringNotContainsString('رمز العملة', $sheetXml);
        $this->assertStringNotContainsString('كمية شراء الوكيل', $sheetXml);
        $this->assertStringNotContainsString('ملاحظات شراء الوكيل', $sheetXml);
        $this->assertStringNotContainsString('8765.43', $sheetXml);
        $this->assertStringNotContainsString('3579.57', $sheetXml);
        $this->assertStringNotContainsString('777.77', $sheetXml);
        $this->assertStringNotContainsString('Hidden purchase note', $sheetXml);
        $this->assertStringNotContainsString('USD', $sheetXml);
    }

    public function test_orders_export_buttons_use_explicit_export_action_without_submitting_search_on_enter(): void
    {
        $response = $this->withoutMiddleware()->get('/orders?q=Filtered%20Export&status=approved');

        $response->assertOk();
        $response->assertSee('id="orders-filter-form"', false);
        $response->assertSee('name="transport_mode"', false);
        $response->assertSee('name="sea_type"', false);
        $response->assertSee('data-orders-search-input', false);
        $response->assertSee('data-orders-export-button="xlsx"', false);
        $response->assertSee('data-orders-export-button="csv"', false);
        $response->assertDontSee('form="orders-filter-form" name="export"', false);
        $response->assertDontSee('triggerImport()', false);
        $response->assertDontSee('orders.import', false);
        $response->assertSee('data-orders-export-modal', false);
    }

    public function test_orders_export_accepts_selected_columns_for_xlsx(): void
    {
        Booking::create([
            'id' => 'BK-SELECTED-COLS',
            'customer_company' => 'Column Select Customer',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '999999',
            'customer_email' => 'columns@example.com',
            'shipment_type' => 'FCL',
            'goods_description' => 'Selected columns export',
            'weight_kg' => 10,
            'volume_cbm' => 2,
            'pol' => 'Qingdao',
            'pod' => 'Aden',
            'status' => 'approved',
            'ocean_freight' => 2500,
        ]);

        $response = $this->withoutMiddleware()->get('/orders?export=xlsx&columns=رقم الطلب,الشركة');

        $response->assertOk();

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertIsString($sheetXml);
        $this->assertStringContainsString('رقم الطلب', $sheetXml);
        $this->assertStringContainsString('الشركة', $sheetXml);
        $this->assertStringContainsString('BK-SELECTED-COLS', $sheetXml);
        $this->assertStringContainsString('Column Select Customer', $sheetXml);
        $this->assertStringNotContainsString('الشخص المسؤول', $sheetXml);
    }

    public function test_orders_page_searches_customer_name_without_transport_type_filter(): void
    {
        Booking::create([
            'id' => 'BK-CUSTOMER-SEARCH-MATCH',
            'customer_company' => 'Immediate Search Customer',
            'customer_contact_name' => 'Search Contact',
            'customer_phone' => '777000001',
            'customer_email' => 'search-match@example.com',
            'shipment_type' => 'AIR',
            'goods_description' => 'Search goods',
            'weight_kg' => 10,
            'volume_cbm' => 2,
            'pol' => 'Origin',
            'pod' => 'Destination',
            'status' => 'new',
        ]);

        Booking::create([
            'id' => 'BK-CUSTOMER-SEARCH-OTHER',
            'customer_company' => 'Other Orders Customer',
            'customer_contact_name' => 'Other Contact',
            'customer_phone' => '777000002',
            'customer_email' => 'search-other@example.com',
            'shipment_type' => 'FCL',
            'goods_description' => 'Other goods',
            'weight_kg' => 10,
            'volume_cbm' => 2,
            'pol' => 'Origin',
            'pod' => 'Destination',
            'status' => 'new',
        ]);

        $response = $this->withoutMiddleware()->get('/orders?q=Immediate%20Search');

        $response->assertOk();
        $response->assertSee('BK-CUSTOMER-SEARCH-MATCH');
        $response->assertSee('Immediate Search Customer');
        $response->assertDontSee('BK-CUSTOMER-SEARCH-OTHER');
    }

    public function test_orders_detail_modal_has_arabic_labels_for_extra_cargo_details(): void
    {
        $response = $this->withoutMiddleware()->get('/orders');

        $response->assertOk();
        $response->assertSee('السحب مطلوب');
        $response->assertSee('رافعة شوكية');
        $response->assertSee('من الباب إلى الباب');
        $response->assertDontSee('Tow Required');
    }

    public function test_orders_detail_modal_has_organized_land_cargo_details(): void
    {
        Booking::create([
            'id' => 'BK-LAND-DETAILS',
            'customer_company' => 'Land Detail Customer',
            'customer_contact_name' => 'Saleh',
            'customer_phone' => '777000333',
            'customer_email' => 'land-details@example.com',
            'shipment_type' => 'LAND',
            'goods_description' => 'Land cargo details',
            'weight_kg' => 2500,
            'volume_cbm' => 7,
            'pol' => 'Sanaa',
            'pod' => 'Aden',
            'status' => 'new',
            'route_details' => [
                'category' => 'land',
                'mode' => 'LAND',
            ],
            'cargo_details' => [
                'service_type' => 'land',
                'land_carton_count' => 44,
                'land_shipments' => [
                    ['qty' => 2, 'from_city' => 'Sanaa', 'to_city' => 'Aden'],
                    ['qty' => 1, 'from_city' => 'Taiz', 'to_city' => 'Aden'],
                ],
                'land_shipment_summary' => '2xSanaa → Aden + 1xTaiz → Aden',
                'land_shipment_total_qty' => 3,
            ],
        ]);

        $detailResponse = $this->withoutMiddleware()->getJson(route('orders.show', 'BK-LAND-DETAILS'));
        $detailResponse->assertOk();

        $payload = $detailResponse->json();
        $this->assertTrue($payload['cargo']['isLand']);
        $this->assertSame('Sanaa', $payload['cargo']['landShipmentLines'][0]['from_city']);
        $this->assertSame('Aden', $payload['cargo']['landShipmentLines'][0]['to_city']);
        $this->assertEquals(3, $payload['cargo']['landShipmentTotalQty']);
        $this->assertEquals(7, $payload['cargo']['landPalletCount']);
        $this->assertSame(44, $payload['cargo']['landCartonCount']);
        $this->assertSame('2xSanaa → Aden + 1xTaiz → Aden', $payload['cargo']['landShipmentSummary']);

        $pageResponse = $this->withoutMiddleware()->get('/orders');
        $pageResponse->assertOk();
        $pageResponse->assertSee('مسارات الشحن البري');
        $pageResponse->assertSee('إجمالي الشاحنات');
        $pageResponse->assertSee('عدد الطبليات');
        $pageResponse->assertSee('عدد الكراتين');
    }

    public function test_orders_page_hides_old_price_when_repricing_is_required(): void
    {
        Booking::create([
            'id' => 'BK-REPRICE-OLD-TOTAL',
            'customer_company' => 'Repricing Customer',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '777000111',
            'customer_email' => 'repricing@example.com',
            'shipment_type' => 'FCL',
            'goods_description' => 'Goods need updated pricing',
            'weight_kg' => 10,
            'volume_cbm' => 2,
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'status' => 'needs_pricing',
            'ocean_freight' => 12345,
        ]);

        $response = $this->withoutMiddleware()->get('/orders');

        $response->assertOk();
        $response->assertSee(__('messages.orders_status_needs_pricing'));
        $response->assertDontSee('$12,345');
        $response->assertSee('hide_stale_pricing=1');

        $detailResponse = $this->withoutMiddleware()->getJson(route('orders.show', 'BK-REPRICE-OLD-TOTAL'));
        $detailResponse->assertOk();
        $detailResponse->assertJsonPath('repricingRequired', true);
        $detailResponse->assertJsonPath('pricing.total', 12345);

        $ordersDetailResponse = $this->withoutMiddleware()->getJson(route('orders.show', 'BK-REPRICE-OLD-TOTAL') . '?hide_stale_pricing=1');
        $ordersDetailResponse->assertOk();
        $ordersDetailResponse->assertJsonPath('repricingRequired', true);
        $ordersDetailResponse->assertJsonPath('pricing', null);
    }

    public function test_orders_page_filters_by_transport_mode_and_sea_type(): void
    {
        foreach ([
            ['id' => 'BK-FILTER-FCL', 'company' => 'Sea FCL Customer', 'type' => 'FCL'],
            ['id' => 'BK-FILTER-BULK', 'company' => 'Sea Bulk Customer', 'type' => 'Bulk'],
            ['id' => 'BK-FILTER-RORO', 'company' => 'Sea RoRo Customer', 'type' => 'RoRo'],
            ['id' => 'BK-FILTER-AIR', 'company' => 'Air Filter Customer', 'type' => 'AIR'],
            ['id' => 'BK-FILTER-LAND', 'company' => 'Land Filter Customer', 'type' => 'LAND'],
        ] as $row) {
            Booking::create([
                'id' => $row['id'],
                'customer_company' => $row['company'],
                'customer_contact_name' => 'Filter Contact',
                'customer_phone' => '777000000',
                'customer_email' => strtolower($row['id']) . '@example.com',
                'shipment_type' => $row['type'],
                'goods_description' => 'Filter goods',
                'weight_kg' => 10,
                'volume_cbm' => 2,
                'pol' => 'Origin',
                'pod' => 'Destination',
                'status' => 'new',
            ]);
        }

        $bulkResponse = $this->withoutMiddleware()->get('/orders?transport_mode=sea&sea_type=Bulk');
        $bulkResponse->assertOk();
        $bulkResponse->assertSee('BK-FILTER-BULK');
        $bulkResponse->assertDontSee('BK-FILTER-FCL');
        $bulkResponse->assertDontSee('BK-FILTER-RORO');
        $bulkResponse->assertDontSee('BK-FILTER-AIR');
        $bulkResponse->assertDontSee('BK-FILTER-LAND');

        $airResponse = $this->withoutMiddleware()->get('/orders?transport_mode=air');
        $airResponse->assertOk();
        $airResponse->assertSee('BK-FILTER-AIR');
        $airResponse->assertDontSee('BK-FILTER-BULK');
        $airResponse->assertDontSee('BK-FILTER-LAND');

        $landResponse = $this->withoutMiddleware()->get('/orders?transport_mode=land');
        $landResponse->assertOk();
        $landResponse->assertSee('BK-FILTER-LAND');
        $landResponse->assertDontSee('BK-FILTER-AIR');
        $landResponse->assertDontSee('BK-FILTER-BULK');
    }
}
