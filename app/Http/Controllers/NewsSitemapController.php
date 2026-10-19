<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\Http\Response;

class NewsSitemapController extends Controller
{
    public function __invoke(): Response
    {
        $articles = NewsArticle::query()
            ->published()
            ->where(function ($query): void {
                $query->where('published_at', '>=', now()->subDays(2))
                    ->orWhere('scheduled_for', '>=', now()->subDays(2));
            })
            ->latest('published_at')
            ->limit(1000)
            ->get();

        return response()
            ->view('news.sitemap', compact('articles'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=300');
    }
}
