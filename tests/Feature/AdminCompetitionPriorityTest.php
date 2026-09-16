<?php

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Fixture;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompetitionPriorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_authenticated_user_can_manage_competition_priority(): void
    {
        $competition = $this->competition('Copa Libertadores', 1000);

        $this->get(route('admin.competitions.index'))->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create())
            ->put(route('admin.competitions.update', $competition), ['display_priority' => 10])
            ->assertRedirect();

        $this->assertDatabaseHas('competitions', ['id' => $competition->id, 'display_priority' => 10]);
    }

    public function test_home_groups_games_by_configured_competition_priority(): void
    {
        $laterPriority = $this->competition('Liga Secundária', 1000);
        $firstPriority = $this->competition('Copa Libertadores', 10);

        $this->fixture($laterPriority, 'Jogo da Liga Secundária', 12);
        $this->fixture($firstPriority, 'Jogo da Libertadores', 22);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Copa Libertadores', 'Jogo da Libertadores', 'Liga Secundária', 'Jogo da Liga Secundária']);
    }

    private function competition(string $name, int $priority): Competition
    {
        return Competition::create([
            'wosti_id' => Competition::count() + 1,
            'name' => $name,
            'slug' => str($name)->slug().'-'.(Competition::count() + 1),
            'display_priority' => $priority,
        ]);
    }

    private function fixture(Competition $competition, string $homeName, int $hour): void
    {
        $home = Team::create(['wosti_id' => Team::count() + 1, 'name' => $homeName, 'slug' => str($homeName)->slug()]);
        $away = Team::create(['wosti_id' => Team::count() + 1, 'name' => 'Visitante '.$homeName, 'slug' => 'visitante-'.str($homeName)->slug()]);
        $channel = BroadcastChannel::firstOrCreate(['wosti_id' => 1], ['name' => 'Canal Teste', 'slug' => 'canal-teste']);
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
    }
}
