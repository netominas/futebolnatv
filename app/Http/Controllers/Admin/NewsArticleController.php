<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use App\Models\User;
use App\Services\DailyNewsDraftGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NewsArticleController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $articles = NewsArticle::query()
            ->with('author:id,name')
            ->when(in_array($status, ['draft', 'scheduled', 'published'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $metrics = [
            'drafts' => NewsArticle::where('status', 'draft')->count(),
            'scheduled' => NewsArticle::where('status', 'scheduled')->count(),
            'published' => NewsArticle::where('status', 'published')->count(),
        ];

        return view('admin.news.index', compact('articles', 'metrics', 'status'));
    }

    public function create(): View
    {
        return view('admin.news.create', [
            'article' => new NewsArticle(['status' => 'draft', 'type' => 'daily_schedule']),
            'authors' => User::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateArticle($request);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['title']);
        $validated['created_by'] = (int) $validated['created_by'];
        $validated['reviewed_by'] = $validated['status'] === 'draft' ? null : $request->user()->id;
        $validated = $this->publicationDates($validated);
        $this->updateAuthor($validated);
        $validated['hero_image_path'] = $request->file('hero_image')?->store('news', 'public');
        unset($validated['hero_image'], $validated['author_name'], $validated['author_bio']);

        $article = NewsArticle::create($validated);

        return redirect()->route('admin.news.edit', $article)->with('status', 'Notícia criada com sucesso.');
    }

    public function edit(NewsArticle $article): View
    {
        return view('admin.news.edit', [
            'article' => $article->load('author'),
            'authors' => User::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, NewsArticle $article): RedirectResponse
    {
        $validated = $this->validateArticle($request, $article);
        $validated['slug'] = $this->uniqueSlug($validated['slug'] ?: $validated['title'], $article->id);
        $validated['reviewed_by'] = $validated['status'] === 'draft' ? null : $request->user()->id;
        $validated = $this->publicationDates($validated, $article);
        $this->updateAuthor($validated);

        if ($image = $request->file('hero_image')) {
            $newPath = $image->store('news', 'public');
            if ($article->hero_image_path) {
                Storage::disk('public')->delete($article->hero_image_path);
            }
            $validated['hero_image_path'] = $newPath;
        }

        unset($validated['hero_image'], $validated['author_name'], $validated['author_bio']);
        $article->update($validated);

        return back()->with('status', 'Notícia atualizada com sucesso.');
    }

    public function generate(Request $request, DailyNewsDraftGenerator $generator): RedirectResponse
    {
        $validated = $request->validate([
            'coverage_date' => ['required', 'date_format:Y-m-d'],
        ]);
        $date = CarbonImmutable::createFromFormat('Y-m-d', $validated['coverage_date'])->startOfDay();
        $article = $generator->generate($date, $request->user());

        return redirect()->route('admin.news.edit', $article)
            ->with('status', 'Rascunho diário gerado a partir da programação confirmada. Revise antes de publicar.');
    }

    /** @return array<string, mixed> */
    private function validateArticle(Request $request, ?NewsArticle $article = null): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['daily_schedule', 'channel_focus', 'competition_focus', 'time_slot', 'weekend_guide'])],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'coverage_date' => ['nullable', 'date_format:Y-m-d'],
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9-]+$/'],
            'subtitle' => ['nullable', 'string', 'max:240'],
            'excerpt' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string', 'min:200'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=1200,min_height=675,ratio=16/9'],
            'hero_image_alt' => ['nullable', 'string', 'max:180'],
            'hero_image_caption' => ['nullable', 'string', 'max:240'],
            'hero_image_credit' => ['nullable', 'string', 'max:160'],
            'created_by' => ['required', 'exists:users,id'],
            'author_name' => ['required', 'string', 'max:120'],
            'author_bio' => ['required', 'string', 'max:1000'],
            'scheduled_for' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
        ], [
            'hero_image.dimensions' => 'A imagem deve ter proporção 16:9 e pelo menos 1200 × 675 pixels.',
            'hero_image.max' => 'A imagem deve ter no máximo 5 MB.',
            'body.min' => 'O conteúdo precisa ter pelo menos 200 caracteres.',
            'slug.regex' => 'A URL pode conter apenas letras minúsculas, números e hífens.',
        ]);

        if ($validated['status'] !== 'draft') {
            if (! $request->hasFile('hero_image') && ! $article?->hero_image_path) {
                throw ValidationException::withMessages(['hero_image' => 'Adicione uma imagem editorial antes de publicar ou agendar.']);
            }
            $imageMetadataErrors = [];
            if (blank($validated['hero_image_alt'] ?? null)) {
                $imageMetadataErrors['hero_image_alt'] = 'Descreva a imagem para acessibilidade e SEO.';
            }
            if (blank($validated['hero_image_caption'] ?? null)) {
                $imageMetadataErrors['hero_image_caption'] = 'Adicione uma legenda contextual para a imagem.';
            }
            if (blank($validated['hero_image_credit'] ?? null)) {
                $imageMetadataErrors['hero_image_credit'] = 'Informe o crédito ou a origem da imagem.';
            }
            if ($imageMetadataErrors !== []) {
                throw ValidationException::withMessages($imageMetadataErrors);
            }
        }

        if ($validated['status'] === 'scheduled' && blank($validated['scheduled_for'] ?? null)) {
            throw ValidationException::withMessages(['scheduled_for' => 'Informe quando a matéria deve ser publicada.']);
        }

        return $validated;
    }

    /** @param array<string, mixed> $validated */
    private function updateAuthor(array $validated): void
    {
        $author = User::findOrFail($validated['created_by']);
        $author->update([
            'name' => $validated['author_name'],
            'bio' => $validated['author_bio'],
        ]);
    }

    /** @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function publicationDates(array $validated, ?NewsArticle $article = null): array
    {
        if ($validated['status'] === 'published') {
            $validated['published_at'] = ($validated['published_at'] ?? null) ?: $article?->published_at ?: now();
            $validated['scheduled_for'] = null;
        } elseif ($validated['status'] === 'scheduled') {
            $validated['published_at'] = null;
        } else {
            $validated['published_at'] = null;
            $validated['scheduled_for'] = null;
        }

        return $validated;
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $suffix = 2;

        while (NewsArticle::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
