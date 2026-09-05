<?php

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_searches_only_entities_with_televised_fixtures(): void
    {
        $competition = Competition::create(['wosti_id' => 1, 'name' => 'Copa Nacional', 'slug' => 'copa-nacional-1']);
        $home = Team::create(['wosti_id' => 1, 'name' => 'Clube Azul', 'slug' => 'clube-azul-1']);
        $away = Team::create(['wosti_id' => 2, 'name' => 'Clube Branco', 'slug' => 'clube-branco-2']);
        $channel = BroadcastChannel::create(['wosti_id' => 1, 'name' => 'Canal Azul', 'slug' => 'canal-azul-1']);
        $hiddenTeam = Team::create(['wosti_id' => 3, 'name' => 'Azul Sem TV', 'slug' => 'azul-sem-tv-3']);
        $fixture = Fixture::create(['wosti_id' => 1, 'competition_id' => $competition->id, 'home_team_id' => $home->id, 'away_team_id' => $away->id, 'starts_at' => now()->addDay(), 'is_listed' => true, 'last_seen_at' => now()]);
        $fixture->channels()->attach($channel);

        $this->get(route('search', ['q' => 'Azul']))
            ->assertOk()
            ->assertSee('Clube Azul')
            ->assertSee('Canal Azul')
            ->assertDontSee($hiddenTeam->name)
            ->assertSee('noindex,follow', false);
    }

    public function test_header_contains_global_search_form(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('search'), false)
            ->assertSee('Buscar time, campeonato ou canal');
    }
}
