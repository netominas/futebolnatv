<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SyncWostiLogosCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_downloads_competition_and_team_logos_to_public_storage(): void
    {
        Storage::fake('public');
        config()->set('services.wosti.logo_base_url', 'https://static.example/img/32');

        $competition = Competition::create([
            'wosti_id' => 3531,
            'name' => 'Brasileirão Série B',
            'slug' => 'brasileirao-serie-b-3531',
            'image' => '20130805105145-Brasil.png',
        ]);
        $team = Team::create([
            'wosti_id' => 8322,
            'name' => 'Londrina',
            'slug' => 'londrina-8322',
            'image' => '20150220125822-londrina.png',
        ]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        Http::fake(['https://static.example/img/32/*' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);

        $this->artisan('wosti:sync-logos')
            ->expectsOutputToContain('2 baixadas, 0 existentes e 0 falhas')
            ->assertSuccessful();

        Storage::disk('public')->assertExists('wosti/competitions/3531.png');
        Storage::disk('public')->assertExists('wosti/teams/8322.png');
        $this->assertSame('wosti/competitions/3531.png', $competition->fresh()->local_logo_path);
        $this->assertSame('wosti/teams/8322.png', $team->fresh()->local_logo_path);
    }
}
