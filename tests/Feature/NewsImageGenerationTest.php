<?php

namespace Tests\Feature;

use App\Jobs\GenerateNewsArticleImage;
use App\Models\AiSetting;
use App\Models\NewsArticle;
use App\Models\User;
use App\Services\NewsImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsImageGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_draft_queues_automatic_image_generation(): void
    {
        Queue::fake();
        AiSetting::create([
            'active_provider' => 'openai',
            'auto_generate_news_images' => true,
            'openai_api_key' => 'openai-test-key',
            'openai_model' => 'gpt-image-2.5-flare',
            'google_model' => 'gemini-3.1-flash-image',
        ]);
        $author = User::factory()->create([
            'name' => 'Redação Futebol na TV',
            'bio' => 'Equipe responsável por revisar as notícias e as imagens editoriais.',
        ]);

        $response = $this->actingAs($author)->post(route('admin.news.store'), [
            'type' => 'daily_schedule',
            'status' => 'draft',
            'coverage_date' => today()->format('Y-m-d'),
            'title' => 'Jogos na TV ganham destaque nesta quarta-feira',
            'excerpt' => 'Confira os campeonatos, horários e canais presentes na programação do dia.',
            'body' => str_repeat('Conteúdo editorial revisável sobre a programação de futebol na televisão. ', 5),
            'created_by' => $author->id,
            'author_name' => $author->name,
            'author_bio' => $author->bio,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $article = NewsArticle::firstOrFail();
        $response->assertRedirect(route('admin.news.edit', $article))
            ->assertSessionHas('status', 'Notícia criada. A imagem está sendo gerada em segundo plano.');
        $this->assertSame('queued', $article->fresh()->hero_image_generation_status);
        Queue::assertPushedOn('ai', GenerateNewsArticleImage::class, fn (GenerateNewsArticleImage $job) => $job->articleId === $article->id
            && $job->connection === 'redis_ai');
    }

    public function test_openai_job_stores_image_and_editorial_metadata(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://api.openai.com/v1/images/generations' => Http::response([
                'data' => [['b64_json' => base64_encode($this->png(1600, 900))]],
            ]),
        ]);
        [$settings, $article] = $this->articleWithSettings('openai');

        (new GenerateNewsArticleImage($article->id))->handle(app(NewsImageGenerator::class));

        $article->refresh();
        $this->assertSame('completed', $article->hero_image_generation_status);
        $this->assertSame('openai', $article->hero_image_generation_provider);
        $this->assertSame($settings->openai_model, $article->hero_image_generation_model);
        $this->assertSame('Imagem gerada por IA · OpenAI', $article->hero_image_credit);
        $this->assertStringContainsString($article->title, $article->hero_image_alt);
        $this->assertNotNull($article->hero_image_generated_at);
        Storage::disk('public')->assertExists($article->hero_image_path);

        Http::assertSent(function (Request $request): bool {
            return $request->hasHeader('Authorization', 'Bearer openai-test-key')
                && $request['size'] === '1536x864'
                && $request['output_format'] === 'webp'
                && $request['safety_identifier'] !== ''
                && str_contains($request['prompt'], 'não inserir palavras');
        });
    }

    public function test_google_job_reads_inline_image_data(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'inlineData' => [
                                'mimeType' => 'image/png',
                                'data' => base64_encode($this->png(1376, 768)),
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);
        [, $article] = $this->articleWithSettings('google');

        (new GenerateNewsArticleImage($article->id))->handle(app(NewsImageGenerator::class));

        $article->refresh();
        $this->assertSame('completed', $article->hero_image_generation_status);
        $this->assertSame('Imagem gerada por IA · Google Gemini', $article->hero_image_credit);
        $this->assertStringEndsWith('.png', $article->hero_image_path);
        Storage::disk('public')->assertExists($article->hero_image_path);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', 'google-test-key')
            && data_get($request->data(), 'generationConfig.responseFormat.image.aspectRatio') === '16:9');
    }

    /** @return array{AiSetting, NewsArticle} */
    private function articleWithSettings(string $provider): array
    {
        $settings = AiSetting::create([
            'active_provider' => $provider,
            'auto_generate_news_images' => true,
            'openai_api_key' => 'openai-test-key',
            'openai_model' => 'gpt-image-2.5-flare',
            'google_api_key' => 'google-test-key',
            'google_model' => 'gemini-3.1-flash-image',
        ]);
        $author = User::factory()->create();
        $article = NewsArticle::create([
            'type' => 'daily_schedule',
            'status' => 'draft',
            'title' => 'Programação de futebol na TV nesta quarta-feira',
            'slug' => 'programacao-futebol-tv-quarta-feira',
            'excerpt' => 'Levantamento dos campeonatos, canais e principais jogos televisionados.',
            'body' => str_repeat('Conteúdo editorial sobre futebol na televisão. ', 8),
            'created_by' => $author->id,
            'hero_image_generation_status' => 'queued',
        ]);

        return [$settings, $article];
    }

    private function png(int $width, int $height): string
    {
        $row = "\0".str_repeat("\0", $width * 3);
        $compressed = gzcompress(str_repeat($row, $height), 9);

        return "\x89PNG\r\n\x1a\n"
            .$this->pngChunk('IHDR', pack('NNC5', $width, $height, 8, 2, 0, 0, 0))
            .$this->pngChunk('IDAT', $compressed === false ? '' : $compressed)
            .$this->pngChunk('IEND', '');
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }
}
