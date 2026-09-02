<?php

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_channel_page_lists_upcoming_and_last_ten_games(): void
    {
        $competition = Competition::create(['wosti_id' => 1, 'name' => 'Liga', 'slug' => 'liga']);
        $home = Team::create(['wosti_id' => 1, 'name' => 'Azul', 'slug' => 'azul']);
        $away = Team::create(['wosti_id' => 2, 'name' => 'Branco', 'slug' => 'branco']);
        $channel = BroadcastChannel::create(['wosti_id' => 1, 'name' => 'TV Azul', 'slug' => 'tv-azul']);
        foreach (range(1, 12) as $index) {
            $fixture = Fixture::create(['wosti_id' => $index, 'competition_id' => $competition->id, 'home_team_id' => $home->id, 'away_team_id' => $away->id, 'starts_at' => now()->subDays($index), 'is_listed' => true, 'last_seen_at' => now()]);
            $fixture->channels()->attach($channel);
        }
        $next = Fixture::create(['wosti_id' => 20, 'competition_id' => $competition->id, 'home_team_id' => $home->id, 'away_team_id' => $away->id, 'starts_at' => now()->addDay(), 'is_listed' => true, 'last_seen_at' => now()]);
        $next->channels()->attach($channel);

        $this->get(route('channels.index'))->assertOk()->assertSee('TV Azul');
        $this->get($channel->publicUrl())->assertOk()->assertSee('Jogos no TV Azul')->assertViewHas('pastFixtures', fn ($fixtures) => $fixtures->count() === 10);
        $this->get(route('fixtures.by-date', ['date' => $next->starts_at->format('Y-m-d')]))->assertSee($channel->publicUrl());
        $this->get('/sitemap.xml')->assertSee($channel->publicUrl());
    }
}
