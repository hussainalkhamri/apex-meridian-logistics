<?php

namespace Tests\Feature;

use App\Models\PortCountry;
use App\Models\User;
use App\Http\Middleware\CheckPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_view_ports(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->withoutMiddleware(CheckPermission::class);

        $response = $this->post(route('ports.store'), [
            'name' => 'Jeddah Islamic Port',
            'code' => 'SAJED',
            'country' => 'Saudi Arabia',
            'country_code' => 'SA',
            'country_flag' => '🇸🇦',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('ports.index'));
        $this->assertDatabaseHas('ports', [
            'name' => 'Jeddah Islamic Port',
            'code' => 'SAJED',
            'country' => 'Saudi Arabia',
            'country_code' => 'SA',
        ]);

        $viewResponse = $this->get(route('ports.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Jeddah Islamic Port');
    }

    public function test_admin_can_filter_ports_by_country(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->withoutMiddleware(CheckPermission::class);

        $this->post(route('ports.store'), [
            'name' => 'Shanghai',
            'code' => 'CNSHA',
            'country' => 'China',
            'country_code' => 'CN',
            'country_flag' => '🇨🇳',
            'is_active' => true,
        ]);

        $this->post(route('ports.store'), [
            'name' => 'Aden',
            'code' => 'YEADE',
            'country' => 'Yemen',
            'country_code' => 'YE',
            'country_flag' => '🇾🇪',
            'is_active' => true,
        ]);

        $response = $this->get(route('ports.index', ['country' => 'China']));

        $response->assertOk();
        $response->assertSee('Shanghai');
        $response->assertDontSee('Aden');
    }

    public function test_ports_page_shows_newly_cataloged_countries(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->withoutMiddleware(CheckPermission::class);

        $response = $this->get(route('ports.index'));

        $response->assertOk();
        $response->assertSee('Russia');
        $response->assertSee('Taiwan');
        $response->assertSee('Kazakhstan');
    }

    public function test_admin_can_add_country_and_use_it_when_creating_port(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->withoutMiddleware(CheckPermission::class);

        $response = $this->post(route('ports.countries.store'), [
            'name' => 'Romania',
            'name_ar' => 'رومانيا',
            'code' => 'ro',
            'flag' => '🇷🇴',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('ports.index'));
        $this->assertDatabaseHas('port_countries', [
            'name' => 'Romania',
            'name_ar' => 'رومانيا',
            'code' => 'RO',
            'flag' => '🇷🇴',
        ]);

        $this->get(route('ports.index'))
            ->assertOk()
            ->assertSee('رومانيا')
            ->assertSee('Romania');

        $this->post(route('ports.store'), [
            'name' => 'Constanta',
            'code' => 'ROCND',
            'country' => 'Romania',
            'country_code' => 'RO',
            'country_flag' => '🇷🇴',
            'is_active' => true,
        ])->assertRedirect(route('ports.index'));

        $this->assertDatabaseHas('ports', [
            'name' => 'Constanta',
            'code' => 'ROCND',
            'country' => 'Romania',
            'country_code' => 'RO',
            'country_flag' => '🇷🇴',
        ]);
    }

    public function test_admin_cannot_duplicate_configured_port_country(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->withoutMiddleware(CheckPermission::class);

        $response = $this->from(route('ports.index'))->post(route('ports.countries.store'), [
            'name' => 'Portugal',
            'name_ar' => 'البرتغال',
            'code' => 'PT',
            'flag' => '🇵🇹',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('ports.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('port_countries', [
            'name' => 'Portugal',
            'code' => 'PT',
        ]);
    }

    public function test_updating_managed_country_updates_related_ports_metadata(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->withoutMiddleware(CheckPermission::class);

        $country = PortCountry::create([
            'name' => 'Testland',
            'name_ar' => 'تستلاند',
            'code' => 'TL',
            'flag' => '🏳',
            'is_active' => true,
        ]);

        $this->post(route('ports.store'), [
            'name' => 'Testland Port',
            'code' => 'TLTST',
            'country' => 'Testland',
            'country_code' => 'TL',
            'country_flag' => '🏳',
            'is_active' => true,
        ]);

        $this->put(route('ports.countries.update', $country), [
            'name' => 'New Testland',
            'name_ar' => 'تستلاند الجديدة',
            'code' => 'NT',
            'flag' => '🏁',
            'is_active' => true,
        ])->assertRedirect(route('ports.index'));

        $this->assertDatabaseHas('port_countries', [
            'id' => $country->id,
            'name' => 'New Testland',
            'name_ar' => 'تستلاند الجديدة',
            'code' => 'NT',
            'flag' => '🏁',
        ]);
        $this->assertDatabaseHas('ports', [
            'name' => 'Testland Port',
            'country' => 'New Testland',
            'country_code' => 'NT',
            'country_flag' => '🏁',
        ]);
    }
}
