<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncWostiEventsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_only_events_with_a_broadcast_channel(): void
    {
        config()->set('services.wosti.key', 'test-key');
        config()->set('services.wosti.host', 'wosti.example');
        config()->set('services.wosti.base_url', 'https://wosti.example');

        Http::fake([
            'https://wosti.example/api/Events' => Http::response([
                $this->event(774345, [['Id' => 5034, 'Name' => 'Disney+ Premium', 'Image' => 'disney.png']]),
                $this->event(774346, []),
            ]),
        ]);

        $this->artisan('wosti:sync-events')
            ->expectsOutputToContain('2 recebidos, 1 importados, 1 ignorados')
            ->assertSuccessful();

        $this->assertDatabaseCount('fixtures', 1);
        $this->assertDatabaseHas('broadcast_channels', ['wosti_id' => 5034, 'name' => 'Disney+ Premium']);
        $this->assertDatabaseHas('fixtures', ['wosti_id' => 774345, 'is_listed' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Londrina')
            ->assertSee('Juventude')
            ->assertSee('Disney+ Premium');
    }

    /** @param list<array<string, mixed>> $channels */
    private function event(int $id, array $channels): array
    {
        return [
            'LocalTeam' => ['Id' => 8322, 'Name' => 'Londrina', 'Image' => 'londrina.png'],
            'AwayTeam' => ['Id' => 8909, 'Name' => 'Juventude', 'Image' => 'juventude.png'],
            'Competition' => ['Id' => 3531, 'Name' => 'Brasileirão Série B', 'Image' => 'brasil.png'],
            'Date' => now()->addDay()->utc()->toIso8601String(),
            'Channels' => $channels,
            'Id' => $id,
        ];
    }
}
