<?php

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_page_lists_upcoming_and_last_ten_televised_games(): void
    {
        $competition = Competition::create(['wosti_id' => 1, 'name' => 'Liga', 'slug' => 'liga']);
        $team = Team::create(['wosti_id' => 1, 'name' => 'Clube Azul', 'slug' => 'clube-azul']);
        $opponent = Team::create(['wosti_id' => 2, 'name' => 'Clube Branco', 'slug' => 'clube-branco']);
        $channel = BroadcastChannel::create(['wosti_id' => 1, 'name' => 'TV Teste', 'slug' => 'tv-teste']);

        foreach (range(1, 12) as $index) {
            $fixture = Fixture::create(['wosti_id' => $index, 'competition_id' => $competition->id, 'home_team_id' => $team->id, 'away_team_id' => $opponent->id, 'starts_at' => now()->subDays($index), 'is_listed' => true, 'last_seen_at' => now()]);
            $fixture->channels()->attach($channel);
        }
        $next = Fixture::create(['wosti_id' => 20, 'competition_id' => $competition->id, 'home_team_id' => $opponent->id, 'away_team_id' => $team->id, 'starts_at' => now()->addDay(), 'is_listed' => true, 'last_seen_at' => now()]);
        $next->channels()->attach($channel);

        $this->get(route('teams.index'))->assertOk()->assertSee('Clube Azul');
        $this->get($team->publicUrl())->assertOk()->assertSee('Jogos do Clube Azul')->assertSee('Próximos jogos na TV')->assertViewHas('pastFixtures', fn ($fixtures) => $fixtures->count() === 10);
        $this->get('/sitemap.xml')->assertSee($team->publicUrl());
    }
}
