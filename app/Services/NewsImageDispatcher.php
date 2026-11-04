<?php

namespace App\Services;

use App\Jobs\GenerateNewsArticleImage;
use App\Models\AiSetting;
use App\Models\NewsArticle;

class NewsImageDispatcher
{
    public function dispatch(NewsArticle $article): bool
    {
        if ($article->hero_image_path || in_array($article->hero_image_generation_status, ['queued', 'processing'], true)) {
            return false;
        }

        $settings = AiSetting::query()->first();
        if (! $settings?->auto_generate_news_images || blank($settings->activeApiKey())) {
            return false;
        }

        $article->update([
            'hero_image_generation_status' => 'queued',
            'hero_image_generation_provider' => $settings->active_provider,
            'hero_image_generation_model' => $settings->activeModel(),
            'hero_image_generation_error' => null,
        ]);

        GenerateNewsArticleImage::dispatch($article->id)->afterCommit();

        return true;
    }
}
