<?php

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SyncWostiLogosCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_downloads_competition_team_and_channel_logos_to_public_storage(): void
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
        $channel = BroadcastChannel::create([
            'wosti_id' => 3340,
            'name' => 'Amazon Prime Video',
            'slug' => 'amazon-prime-video-3340',
            'image' => '20210101120000-amazon-prime-video.png',
        ]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
        Http::fake(['https://static.example/img/32/*' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);

        $this->artisan('wosti:sync-logos')
            ->expectsOutputToContain('3 baixadas, 0 existentes e 0 falhas')
            ->assertSuccessful();

        Storage::disk('public')->assertExists('wosti/competitions/3531.png');
        Storage::disk('public')->assertExists('wosti/teams/8322.png');
        Storage::disk('public')->assertExists('wosti/channels/3340.png');
        $this->assertSame('wosti/competitions/3531.png', $competition->fresh()->local_logo_path);
        $this->assertSame('wosti/teams/8322.png', $team->fresh()->local_logo_path);
        $this->assertSame('wosti/channels/3340.png', $channel->fresh()->local_logo_path);
    }
}
