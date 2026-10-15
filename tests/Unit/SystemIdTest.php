<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Support\SystemId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_id_uses_prefix_year_and_avoids_existing_records(): void
    {
        $firstId = SystemId::make(Booking::class, 'BK', 2026);

        $this->assertMatchesRegularExpression('/^BK-2026-\d{5}$/', $firstId);

        Booking::create([
            'id' => $firstId,
            'customer_company' => 'ID Test Customer',
            'customer_contact_name' => 'ID Test Contact',
            'customer_phone' => '+967700000001',
            'customer_email' => 'id-test@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'ID test goods',
            'weight_kg' => 1000,
            'volume_cbm' => 10,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
        ]);

        $secondId = SystemId::make(Booking::class, 'BK', 2026);

        $this->assertMatchesRegularExpression('/^BK-2026-\d{5}$/', $secondId);
        $this->assertNotSame($firstId, $secondId);
    }

    public function test_sequential_booking_ids_start_at_one_and_increment_per_year(): void
    {
        $firstId = SystemId::makeSequential(Booking::class, 'BK', 2026);

        $this->assertSame('BK-2026-1', $firstId);

        Booking::create([
            'id' => $firstId,
            'customer_company' => 'Sequential ID Customer',
            'customer_contact_name' => 'Sequential ID Contact',
            'customer_phone' => '+967700000002',
            'customer_email' => 'sequential-id@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Sequential ID test goods',
            'weight_kg' => 1000,
            'volume_cbm' => 10,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
        ]);

        $this->assertSame('BK-2026-2', SystemId::makeSequential(Booking::class, 'BK', 2026));
        $this->assertSame('BK-2027-1', SystemId::makeSequential(Booking::class, 'BK', 2027));
    }
}
