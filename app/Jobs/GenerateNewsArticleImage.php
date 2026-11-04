<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\NewsArticle;
use App\Services\NewsImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GenerateNewsArticleImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $articleId)
    {
        $this->onConnection('redis_ai')->onQueue('ai');
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(NewsImageGenerator $generator): void
    {
        $article = NewsArticle::find($this->articleId);
        if (! $article || $article->hero_image_path) {
            $article?->update(['hero_image_generation_status' => null]);

            return;
        }

        $settings = AiSetting::query()->first();
        if (! $settings?->auto_generate_news_images || blank($settings->activeApiKey())) {
            throw new RuntimeException('Configure o provedor de IA antes de gerar imagens.');
        }

        $article->update([
            'hero_image_generation_status' => 'processing',
            'hero_image_generation_provider' => $settings->active_provider,
            'hero_image_generation_model' => $settings->activeModel(),
            'hero_image_generation_error' => null,
        ]);

        $image = $generator->generate($article, $settings);
        $article->refresh();
        if ($article->hero_image_path) {
            $article->update(['hero_image_generation_status' => null]);

            return;
        }

        $path = 'news/'.Str::slug($article->slug).'-ia-'.now()->format('YmdHis').'.'.$image['extension'];
        if (! Storage::disk('public')->put($path, $image['bytes'])) {
            throw new RuntimeException('Não foi possível armazenar a imagem gerada.');
        }

        $article->update([
            'hero_image_path' => $path,
            'hero_image_alt' => Str::limit('Ilustração editorial sobre '.$article->title, 180, ''),
            'hero_image_caption' => Str::limit('Imagem ilustrativa produzida para a notícia “'.$article->title.'”.', 240, ''),
            'hero_image_credit' => 'Imagem gerada por IA · '.$image['provider_label'],
            'hero_image_generation_status' => 'completed',
            'hero_image_generation_error' => null,
            'hero_image_generated_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        NewsArticle::query()->whereKey($this->articleId)->update([
            'hero_image_generation_status' => 'failed',
            'hero_image_generation_error' => Str::limit($exception?->getMessage() ?: 'Falha inesperada ao gerar a imagem.', 500, ''),
        ]);
    }
}
