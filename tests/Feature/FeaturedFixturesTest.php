<?php

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeaturedFixturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_highlights_up_to_four_priority_competitions(): void
    {
        $this->fixture('Premier League', 'Arsenal', 12);
        $this->fixture('Libertadores', 'Palmeiras', 20);
        $this->fixture('Champions League', 'Real Madrid', 16);
        $this->fixture('Copa do Brasil', 'Flamengo', 21);
        $this->fixture('Brasileirão Série A', 'Bahia', 19);
        $this->fixture('Campeonato Estadual', 'Clube Local', 18);

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Principais jogos na TV de hoje')
            ->assertSee('Real Madrid x Visitante Real Madrid')
            ->assertSee('Palmeiras x Visitante Palmeiras')
            ->assertSee('Bahia x Visitante Bahia')
            ->assertSee('Flamengo x Visitante Flamengo');

        $this->assertCount(4, $response->viewData('featuredFixtures'));
        $this->assertFalse($response->viewData('featuredFixtures')->contains(
            fn (Fixture $fixture): bool => $fixture->homeTeam->name === 'Clube Local'
        ));
    }

    public function test_home_highlights_only_one_fixture_from_each_competition(): void
    {
        $this->fixture('Premier League', 'Arsenal', 12);
        $this->fixture('Premier League', 'Liverpool', 15);
        $this->fixture('La Liga', 'Barcelona', 17);

        $featured = $this->get('/')->viewData('featuredFixtures');

        $this->assertCount(2, $featured);
        $this->assertSame(['Arsenal', 'Barcelona'], $featured->pluck('homeTeam.name')->all());
    }

    private function fixture(string $competitionName, string $homeName, int $hour): Fixture
    {
        $competition = Competition::firstOrCreate(
            ['name' => $competitionName],
            ['wosti_id' => Competition::count() + 1, 'slug' => str($competitionName)->slug().'-'.(Competition::count() + 1)],
        );
        $home = Team::create(['wosti_id' => Team::count() + 1, 'name' => $homeName, 'slug' => str($homeName)->slug().'-'.(Team::count() + 1)]);
        $awayName = 'Visitante '.$homeName;
        $away = Team::create(['wosti_id' => Team::count() + 1, 'name' => $awayName, 'slug' => str($awayName)->slug().'-'.(Team::count() + 1)]);
        $channel = BroadcastChannel::firstOrCreate(['wosti_id' => 1], ['name' => 'Canal Teste', 'slug' => 'canal-teste-1']);
        $fixture = Fixture::create([
            'wosti_id' => Fixture::count() + 1,
            'competition_id' => $competition->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'starts_at' => today()->setHour($hour),
            'is_listed' => true,
            'last_seen_at' => now(),
        ]);
        $fixture->channels()->attach($channel);

        return $fixture;
    }
}
