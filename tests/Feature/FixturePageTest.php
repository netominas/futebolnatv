<?php

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixturePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_fixture_has_canonical_seo_page_and_is_listed_in_sitemap(): void
    {
        $competition = Competition::create(['wosti_id' => 1, 'name' => 'Copa Teste', 'slug' => 'copa-teste']);
        $home = Team::create(['wosti_id' => 1, 'name' => 'Time Azul', 'slug' => 'time-azul']);
        $away = Team::create(['wosti_id' => 2, 'name' => 'Time Branco', 'slug' => 'time-branco']);
        $channel = BroadcastChannel::create(['wosti_id' => 1, 'name' => 'Canal Teste', 'slug' => 'canal-teste']);
        $fixture = Fixture::create(['wosti_id' => 1, 'competition_id' => $competition->id, 'home_team_id' => $home->id, 'away_team_id' => $away->id, 'starts_at' => now()->addDay(), 'is_listed' => true, 'last_seen_at' => now()]);
        $fixture->channels()->attach($channel);

        $this->get($fixture->publicUrl())->assertOk()->assertSee('Time Azul x Time Branco')->assertSee('application/ld+json', false)->assertSee('Canal Teste');
        $this->get(route('fixtures.show', ['slug' => 'url-incorreta', 'fixture' => $fixture]))->assertRedirect($fixture->publicUrl())->assertStatus(301);
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee($fixture->publicUrl());
    }
}
