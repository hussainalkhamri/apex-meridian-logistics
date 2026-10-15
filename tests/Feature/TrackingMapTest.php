<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\ShipmentTrack;
use App\Models\User;
use App\Support\TrackingGeoCatalog;
use App\Support\TrackingMapData;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackingMapTest extends TestCase
{
    use RefreshDatabase;

    private function track(array $booking = [], array $tracking = []): ShipmentTrack
    {
        $shipment = Booking::create(array_merge([
            'id' => 'BK-MAP-'.random_int(100000, 999999),
            'customer_company' => 'Map Customer', 'customer_contact_name' => 'Private Contact',
            'customer_phone' => '+967700000222', 'customer_email' => 'private@example.test',
            'shipment_type' => 'FCL', 'pol' => 'Jeddah Islamic Port', 'pod' => 'Aden',
            'goods_description' => 'Cargo', 'weight_kg' => 100, 'volume_cbm' => 1,
            'status' => 'booked', 'attachments' => json_encode(['bookings/attachments/private-invoice.pdf']),
            'carrier_vessel' => 'Recorded Vessel', 'carrier_voyage' => 'VY-21',
            'shipper' => 'Private Shipper',
        ], $booking));

        return ShipmentTrack::create(array_merge([
            'id' => 'BHRL-MAP-'.random_int(100000, 999999), 'booking_id' => $shipment->id,
            'status' => 'In transit', 'progress' => 15, 'etd' => '2026-09-01', 'eta' => '2026-09-11',
        ], $tracking))->load('booking');
    }

    public function test_time_estimate_is_clamped_and_does_not_confirm_delivery_or_change_recorded_progress(): void
    {
        $track = $this->track();
        foreach ([['2026-08-31', 0.0, 'scheduled'], ['2026-09-06', 50.0, 'in_transit'], ['2026-09-20', 100.0, 'eta_passed']] as [$date, $percent, $state]) {
            $result = TrackingMapData::schedule($track, Carbon::parse($date));
            $this->assertEquals($percent, $result['percent']);
            $this->assertSame($state, $result['state']);
            $this->assertTrue($result['is_estimate']);
        }
        $this->assertSame(15, $track->fresh()->progress);
        $this->assertSame('In transit', $track->fresh()->status);
    }

    public function test_missing_equal_and_reversed_dates_have_no_estimated_position(): void
    {
        foreach ([[null, null], ['2026-09-01', '2026-09-01'], ['2026-09-11', '2026-09-01']] as [$etd, $eta]) {
            $result = TrackingMapData::schedule($this->track([], ['etd' => $etd, 'eta' => $eta]));
            $this->assertNull($result['fraction']);
            $this->assertSame('unavailable', $result['state']);
        }
    }

    public function test_estimate_uses_latest_carrier_dates_and_partial_elapsed_days(): void
    {
        $track = $this->track(['carrier_etd' => '2026-09-02', 'carrier_eta' => '2026-09-04']);
        $result = TrackingMapData::schedule($track, Carbon::parse('2026-09-03 12:00:00'));
        $this->assertEquals(75, $result['percent']);
        $this->assertSame(2, $result['total_days']);
        $this->assertSame(1, $result['remaining_days']);
    }

    public function test_mode_resolution_supports_sea_air_land_and_door_to_door(): void
    {
        foreach ([['FCL', 'SEA'], ['LCL', 'SEA'], ['Bulk', 'SEA'], ['RoRo', 'SEA'], ['AIR', 'AIR'], ['LAND', 'LAND'], ['DOOR_TO_DOOR', 'MULTIMODAL']] as [$shipmentType, $expected]) {
            $this->assertSame($expected, TrackingMapData::mode(new Booking(['shipment_type' => $shipmentType])));
        }
        $this->assertSame('AIR', TrackingMapData::mode(new Booking(['shipment_type' => 'DOOR_TO_DOOR', 'route_details' => ['main_transport_mode' => 'AIR']])));
    }

    public function test_public_map_contains_route_and_estimate_but_no_private_documents_or_financial_fields(): void
    {
        $this->travelTo(Carbon::parse('2026-09-06'));
        $track = $this->track();
        $this->getJson(route('tracking.map', $track->id))->assertOk()
            ->assertJsonPath('mode', 'SEA')->assertJsonPath('schedule.percent', 50)
            ->assertJsonPath('origin.country', 'SA')->assertJsonPath('destination.country', 'YE')
            ->assertDontSee('private-invoice')->assertDontSee('private@example.test');
        $this->get(route('tracking', ['q' => $track->id]))->assertOk()
            ->assertSee('data-tracking-map-open', false)->assertSee('Recorded Vessel')->assertSee('VY-21')
            ->assertDontSee('private-invoice.pdf')->assertDontSee('Private Shipper');
    }

    public function test_map_documents_are_visible_to_authorized_staff_and_shipment_owner_only(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $track = $this->track();
        $this->actingAs(User::where('username', 'admin')->first())->get(route('tracking', ['q' => $track->id]))
            ->assertOk()->assertSee('private-invoice.pdf')->assertSee('Private Shipper');
    }

    public function test_customer_map_preserves_ownership_scope(): void
    {
        $customer = Customer::create(['company' => 'Owner', 'contact_name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'secret123', 'portal_enabled' => true]);
        $owned = $this->track(['customer_id' => $customer->id]);
        $other = $this->track();
        $this->actingAs($customer, 'customer')->getJson(route('customer.tracking.map', $owned->id))->assertOk();
        $this->getJson(route('customer.tracking.map', $other->id))->assertNotFound();
        $this->get(route('customer.tracking', ['q' => $owned->id]))->assertOk()->assertSee('private-invoice.pdf');
    }

    public function test_unknown_or_archived_track_cannot_be_loaded(): void
    {
        $this->getJson(route('tracking.map', 'BHRL-UNKNOWN'))->assertNotFound();
        $track = $this->track(['is_archived' => true]);
        $this->getJson(route('tracking.map', $track->id))->assertNotFound();
    }

    public function test_airports_are_resolved_by_iata_and_country(): void
    {
        $track = $this->track(['shipment_type' => 'AIR', 'pol' => 'SAH', 'pod' => 'DXB', 'route_details' => [
            'origin' => ['type' => 'airport', 'airport_code' => 'SAH', 'country_code' => 'YE'],
            'destination' => ['type' => 'airport', 'airport_code' => 'DXB', 'country_code' => 'AE'],
        ]]);
        $this->getJson(route('tracking.map', $track->id))->assertOk()->assertJsonPath('origin.precision', 'airport')->assertJsonPath('destination.country', 'AE');
        $track->booking->route_details = ['origin' => ['type' => 'airport', 'airport_code' => 'DXB', 'country_code' => 'YE']];
        $this->assertNull(app(TrackingGeoCatalog::class)->endpoint($track->booking, 'origin', 'AIR'));
    }

    public function test_road_geometry_is_cached_and_provider_failures_do_not_invent_routes(): void
    {
        Http::preventStrayRequests();
        $track = $this->track(['shipment_type' => 'LAND', 'pol' => 'Riyadh', 'pod' => 'Jeddah', 'route_details' => [
            'origin' => ['type' => 'city', 'city' => 'Riyadh', 'country_code' => 'SA'],
            'destination' => ['type' => 'city', 'city' => 'Jeddah', 'country_code' => 'SA'],
        ]]);
        $geometry = ['type' => 'LineString', 'coordinates' => [[46.7, 24.7], [44, 23], [39.2, 21.5]]];
        Http::fake(['router.project-osrm.org/*' => Http::response(['code' => 'Ok', 'routes' => [['geometry' => $geometry]]])]);
        $this->getJson(route('tracking.map', $track->id))->assertOk()->assertJsonPath('road_geometry', $geometry);
        $this->getJson(route('tracking.map', $track->id))->assertOk();
        Http::assertSentCount(1);
    }

    public function test_missing_locations_are_not_guessed_and_do_not_call_external_services(): void
    {
        Http::preventStrayRequests();
        $track = $this->track(['pol' => 'Unknown private loading location', 'pod' => 'Unknown private destination']);
        $this->getJson(route('tracking.map', $track->id))->assertOk()->assertJsonPath('origin', null)->assertJsonPath('destination', null);
        Http::assertNothingSent();
    }

    public function test_road_provider_failure_keeps_dates_but_returns_no_invented_geometry(): void
    {
        Http::fake(['router.project-osrm.org/*' => Http::response(['code' => 'NoRoute'], 400)]);
        $track = $this->track(['shipment_type' => 'LAND', 'route_details' => [
            'origin' => ['type' => 'city', 'city' => 'Dubai', 'country_code' => 'AE'],
            'destination' => ['type' => 'city', 'city' => 'Riyadh', 'country_code' => 'SA'],
        ]]);
        $this->getJson(route('tracking.map', $track->id))->assertOk()
            ->assertJsonPath('road_geometry', null)->assertJsonPath('schedule.etd', '2026-09-01');
        Http::assertSentCount(1);
    }

    public function test_known_port_without_coordinates_can_use_its_country_scoped_city(): void
    {
        $point = app(TrackingGeoCatalog::class)->endpoint(new Booking(['pod' => 'Aden']), 'destination', 'SEA');
        $this->assertSame('YE', $point['country']);
        $this->assertSame('city', $point['precision']);
        $this->assertSame('GeoNames', $point['source']);
    }

    public function test_other_customer_cannot_see_documents_on_public_tracking_page(): void
    {
        $customer = Customer::create(['company' => 'Other', 'contact_name' => 'Other', 'email' => 'other@example.test', 'password' => 'secret123', 'portal_enabled' => true]);
        $track = $this->track();
        $this->actingAs($customer, 'customer')->get(route('tracking', ['q' => $track->id]))
            ->assertOk()->assertDontSee('private-invoice.pdf')->assertDontSee('Private Shipper');
    }
}
