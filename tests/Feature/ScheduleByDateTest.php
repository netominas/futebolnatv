<?php

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Team;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleByDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_displays_only_todays_televised_fixtures(): void
    {
        $this->fixture('Jogo de Hoje', today()->setHour(20));
        $this->fixture('Outro Jogo de Hoje', today()->setHour(21));
        $tomorrow = $this->fixture('Jogo de Amanhã', today()->addDay()->setHour(20));

        $this->get('/')
            ->assertOk()
            ->assertSee('Jogo de Hoje')
            ->assertSee('Futebol na TV: saiba onde assistir aos jogos de hoje')
            ->assertSee('Jogos na TV e no streaming')
            ->assertDontSee('Jogo de Amanhã')
            ->assertViewHas('fixturesByCompetition', fn ($groups): bool => $groups->count() === 1
                && $groups->first()->count() === 2);

        $this->get(route('fixtures.by-date', ['date' => today()->addDay()->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('Jogo de Amanhã')
            ->assertDontSee('Jogo de Hoje');

        $this->get(route('fixtures.redirect-to-date', ['data' => $tomorrow->starts_at->format('Y-m-d')]))
            ->assertRedirect(route('fixtures.by-date', ['date' => today()->addDay()->format('Y-m-d')]));
    }

    private function fixture(string $homeName, CarbonInterface $startsAt): Fixture
    {
        $competition = Competition::firstOrCreate(
            ['wosti_id' => 1],
            ['name' => 'Campeonato Teste', 'slug' => 'campeonato-teste-1'],
        );
        $home = Team::create([
            'wosti_id' => Team::count() + 1,
            'name' => $homeName,
            'slug' => str($homeName)->slug().'-'.(Team::count() + 1),
        ]);
        $away = Team::create([
            'wosti_id' => Team::count() + 1,
            'name' => 'Visitante '.$home->wosti_id,
            'slug' => 'visitante-'.$home->wosti_id,
        ]);
        $channel = BroadcastChannel::firstOrCreate(
            ['wosti_id' => 1],
            ['name' => 'Canal Teste', 'slug' => 'canal-teste-1'],
        );
        $fixture = Fixture::create([
            'wosti_id' => Fixture::count() + 1,
            'competition_id' => $competition->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'starts_at' => $startsAt,
            'is_listed' => true,
            'last_seen_at' => now(),
        ]);
        $fixture->channels()->attach($channel);

        return $fixture;
    }
}
