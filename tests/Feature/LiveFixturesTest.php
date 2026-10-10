<?php

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveFixturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_page_only_displays_televised_fixtures_inside_the_two_hour_window(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-09 17:30:00'));

        $live = $this->fixture('Partida em Andamento', now()->setTime(16, 0));
        $this->fixture('Partida no Limite Encerrado', now()->setTime(15, 30));
        $this->fixture('Partida Antiga', now()->setTime(15, 29));
        $this->fixture('Partida Futura', now()->setTime(18, 0));
        $this->fixture('Partida Não Listada', now()->setTime(17, 0), false);
        $withoutChannel = $this->fixture('Partida Sem Canal', now()->setTime(16, 30));
        $withoutChannel->channels()->detach();

        $response = $this->get(route('fixtures.live'));

        $response->assertOk()
            ->assertSee('Jogos ao vivo agora na TV')
            ->assertSee('Partida em Andamento')
            ->assertSee('Desde 16:00')
            ->assertSee($live->publicUrl())
            ->assertDontSee('Partida no Limite Encerrado')
            ->assertDontSee('Partida Antiga')
            ->assertDontSee('Partida Futura')
            ->assertDontSee('Partida Não Listada')
            ->assertDontSee('Partida Sem Canal')
            ->assertSee('<link rel="canonical" href="'.route('fixtures.live').'">', false)
            ->assertHeader('X-Futebol-Cache', 'public')
            ->assertHeader('Cloudflare-CDN-Cache-Control', 'public, max-age=30, stale-if-error=300')
            ->assertViewHas('fixtures', fn ($fixtures): bool => $fixtures->count() === 1 && $fixtures->first()->is($live));

        $this->get(route('home'))->assertSee(route('fixtures.live'));
        $this->get(route('sitemap'))->assertSee(route('fixtures.live'));

        $this->travelBack();
    }

    public function test_live_page_has_a_useful_empty_state(): void
    {
        $this->get(route('fixtures.live'))
            ->assertOk()
            ->assertSee('Nenhum jogo ao vivo agora')
            ->assertSee(route('home'));
    }

    private function fixture(string $homeName, CarbonInterface $startsAt, bool $isListed = true): Fixture
    {
        $competition = Competition::firstOrCreate(
            ['wosti_id' => 900],
            ['name' => 'Campeonato ao Vivo', 'slug' => 'campeonato-ao-vivo-900'],
        );
        $homeId = Team::count() + 1;
        $home = Team::create([
            'wosti_id' => $homeId,
            'name' => $homeName,
            'slug' => str($homeName)->slug().'-'.$homeId,
        ]);
        $awayId = Team::count() + 1;
        $away = Team::create([
            'wosti_id' => $awayId,
            'name' => 'Visitante '.$homeId,
            'slug' => 'visitante-'.$homeId,
        ]);
        $channel = BroadcastChannel::firstOrCreate(
            ['wosti_id' => 900],
            ['name' => 'Canal ao Vivo', 'slug' => 'canal-ao-vivo-900'],
        );
        $fixture = Fixture::create([
            'wosti_id' => Fixture::count() + 900,
            'competition_id' => $competition->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'starts_at' => $startsAt,
            'is_listed' => $isListed,
            'last_seen_at' => now(),
        ]);
        $fixture->channels()->attach($channel);

        return $fixture;
    }
}
