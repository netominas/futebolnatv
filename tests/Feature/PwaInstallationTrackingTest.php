<?php

namespace Tests\Feature;

use App\Models\PwaInstallation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaInstallationTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_each_confirmed_installation_only_once(): void
    {
        $payload = ['installation_id' => 'device-123', 'event' => 'installed'];
        $headers = ['User-Agent' => 'Mozilla/5.0 (Linux; Android 14; Mobile) Chrome/130'];

        $this->withHeaders($headers)->postJson(route('pwa-installations.store'), $payload)->assertOk();
        $this->withHeaders($headers)->postJson(route('pwa-installations.store'), $payload)->assertOk();

        $this->assertDatabaseCount('pwa_installations', 1);
        $this->assertDatabaseHas('pwa_installations', [
            'installation_key' => hash('sha256', 'device-123'),
            'status' => 'installed',
            'platform' => 'android',
            'device_type' => 'mobile',
        ]);
    }

    public function test_interest_is_not_counted_as_a_confirmed_installation(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)'])
            ->postJson(route('pwa-installations.store'), ['installation_id' => 'iphone-123', 'event' => 'interest'])
            ->assertOk();

        $this->assertSame(0, PwaInstallation::installed()->count());
        $this->assertDatabaseHas('pwa_installations', ['status' => 'interest', 'platform' => 'ios']);
    }

    public function test_installation_dashboard_requires_authentication_and_shows_metrics(): void
    {
        PwaInstallation::create([
            'installation_key' => hash('sha256', 'device-456'),
            'status' => 'installed',
            'platform' => 'android',
            'device_type' => 'mobile',
            'installed_at' => now(),
        ]);

        $this->get(route('admin.pwa-installations.index'))->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create())
            ->get(route('admin.pwa-installations.index'))
            ->assertOk()
            ->assertSee('Instalações do PWA')
            ->assertSee('Total instalado');
    }
}
