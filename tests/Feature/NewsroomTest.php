<?php

namespace Tests\Feature;

use App\Models\BroadcastChannel;
use App\Models\Competition;
use App\Models\Fixture;
use App\Models\NewsArticle;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsroomTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_a_daily_draft_from_televised_fixtures(): void
    {
        $user = User::factory()->create(['name' => 'Redação Futebol na TV', 'bio' => 'Equipe responsável pelos levantamentos editoriais.']);
        $competition = Competition::create(['wosti_id' => 1, 'name' => 'Copa do Brasil', 'slug' => 'copa-do-brasil', 'display_priority' => 10]);
        $channel = BroadcastChannel::create(['wosti_id' => 1, 'name' => 'Premiere', 'slug' => 'premiere']);

        $this->fixture($competition, $channel, 1, 'Time Azul', 'Time Branco', 18);
        $this->fixture($competition, $channel, 2, 'Time Verde', 'Time Amarelo', 21);

        $this->get(route('admin.news.index'))->assertRedirect(route('admin.login'));

        $response = $this->actingAs($user)->post(route('admin.news.generate'), [
            'coverage_date' => today()->format('Y-m-d'),
        ]);

        $article = NewsArticle::firstOrFail();
        $response->assertRedirect(route('admin.news.edit', $article));
        $this->assertSame('draft', $article->status);
        $this->assertStringContainsString('Premiere concentra 2 dos 2 jogos', $article->title);
        $this->assertStringNotContainsString('Horários com partidas simultâneas', $article->body);
        $this->assertSame(2, $article->source_snapshot['fixture_count']);
        $this->assertCount(2, $article->fixtures);
        $this->get($article->publicUrl())->assertNotFound();
    }

    public function test_published_article_has_news_seo_author_and_sitemaps(): void
    {
        $author = User::factory()->create([
            'name' => 'Equipe Futebol na TV',
            'bio' => 'Equipe dedicada à análise da programação esportiva brasileira.',
        ]);
        $article = NewsArticle::create([
            'type' => 'daily_schedule',
            'status' => 'published',
            'coverage_date' => today(),
            'title' => 'Premiere concentra os jogos na TV desta quarta-feira',
            'slug' => 'premiere-concentra-os-jogos-na-tv',
            'subtitle' => 'Um levantamento original da grade de transmissões.',
            'excerpt' => 'Confira os canais, campeonatos e horários de maior movimento na programação.',
            'body' => str_repeat('Conteúdo editorial verificado sobre a programação dos jogos na TV. ', 8),
            'meta_title' => 'Jogos na TV e canais em destaque',
            'meta_description' => 'Levantamento dos jogos na TV, canais e campeonatos em destaque.',
            'hero_image_path' => 'news/destaque.jpg',
            'hero_image_alt' => 'Televisão e bola representando os jogos do dia',
            'created_by' => $author->id,
            'reviewed_by' => $author->id,
            'published_at' => now()->subHour(),
            'last_fact_checked_at' => now(),
        ]);

        $this->get(route('news.index'))->assertOk()->assertSee($article->title);
        $this->get($article->publicUrl())
            ->assertOk()
            ->assertSee($article->title)
            ->assertSee('NewsArticle')
            ->assertSee($author->name)
            ->assertSee('max-image-preview:large', false);
        $this->get($author->publicUrl())->assertOk()->assertSee($article->title);
        $this->get(route('news.sitemap'))->assertOk()->assertSee($article->publicUrl())->assertSee('<news:news>', false);
        $this->get(route('sitemap'))->assertOk()->assertSee($article->publicUrl())->assertSee($author->publicUrl());
    }

    public function test_article_cannot_be_published_without_an_editorial_image(): void
    {
        $author = User::factory()->create(['bio' => 'Autor responsável pela revisão da programação.']);
        $article = NewsArticle::create([
            'type' => 'daily_schedule',
            'status' => 'draft',
            'coverage_date' => today(),
            'title' => 'Resumo da programação',
            'slug' => 'resumo-da-programacao',
            'excerpt' => 'Resumo dos jogos e canais da programação de hoje.',
            'body' => str_repeat('Informação verificada sobre jogos, canais e horários. ', 8),
            'created_by' => $author->id,
        ]);

        $this->actingAs($author)->put(route('admin.news.update', $article), [
            'type' => 'daily_schedule',
            'status' => 'published',
            'coverage_date' => today()->format('Y-m-d'),
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'hero_image_alt' => 'Imagem da programação de futebol',
            'created_by' => $author->id,
            'author_name' => $author->name,
            'author_bio' => $author->bio,
        ])->assertSessionHasErrors('hero_image');

        $this->assertSame('draft', $article->fresh()->status);
    }

    public function test_article_cannot_be_published_without_complete_image_metadata(): void
    {
        $author = User::factory()->create(['bio' => 'Autor responsável pela revisão da programação.']);
        $article = NewsArticle::create([
            'type' => 'daily_schedule',
            'status' => 'draft',
            'coverage_date' => today(),
            'title' => 'Guia dos jogos na televisão',
            'slug' => 'guia-dos-jogos-na-televisao',
            'excerpt' => 'Resumo dos jogos e canais da programação de hoje.',
            'body' => str_repeat('Informação verificada sobre jogos, canais e horários. ', 8),
            'hero_image_path' => 'news/guia.jpg',
            'hero_image_alt' => 'Televisão exibindo uma partida de futebol',
            'created_by' => $author->id,
        ]);

        $this->actingAs($author)->put(route('admin.news.update', $article), [
            'type' => 'daily_schedule',
            'status' => 'published',
            'coverage_date' => today()->format('Y-m-d'),
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'hero_image_alt' => $article->hero_image_alt,
            'created_by' => $author->id,
            'author_name' => $author->name,
            'author_bio' => $author->bio,
        ])->assertSessionHasErrors(['hero_image_caption', 'hero_image_credit']);

        $this->assertSame('draft', $article->fresh()->status);
    }

    private function fixture(Competition $competition, BroadcastChannel $channel, int $id, string $homeName, string $awayName, int $hour): void
    {
        $home = Team::create(['wosti_id' => $id * 10, 'name' => $homeName, 'slug' => str($homeName)->slug()]);
        $away = Team::create(['wosti_id' => $id * 10 + 1, 'name' => $awayName, 'slug' => str($awayName)->slug()]);
        $fixture = Fixture::create([
            'wosti_id' => $id,
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
