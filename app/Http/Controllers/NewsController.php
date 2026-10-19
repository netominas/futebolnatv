<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Contracts\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        $articles = NewsArticle::query()
            ->published()
            ->with('author:id,name,slug')
            ->latest('published_at')
            ->latest('scheduled_for')
            ->paginate(12);

        return view('news.index', compact('articles'));
    }

    public function show(string $article): View
    {
        $article = NewsArticle::query()
            ->published()
            ->where('slug', $article)
            ->with([
                'author:id,name,slug,bio',
                'fixtures' => fn ($query) => $query
                    ->with(['competition:id,name,slug', 'homeTeam:id,name,slug', 'awayTeam:id,name,slug', 'channels:id,name,slug'])
                    ->orderBy('starts_at'),
            ])
            ->firstOrFail();

        $relatedArticles = NewsArticle::query()
            ->published()
            ->whereKeyNot($article->id)
            ->with('author:id,name,slug')
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('news.show', compact('article', 'relatedArticles'));
    }

    public function author(string $author): View
    {
        $author = User::query()->where('slug', $author)->firstOrFail();
        $articles = $author->articles()
            ->published()
            ->latest('published_at')
            ->latest('scheduled_for')
            ->paginate(12);

        return view('news.author', compact('articles', 'author'));
    }
}
