@php
    $publishedAt = $article->publicationDate();
    $imageUrl = $article->heroImageUrl() ?: asset('images/futebol-na-tv-social.jpg');
    $description = $article->meta_description ?: $article->excerpt;
    $pageTitle = $article->meta_title ?: $article->title;
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $article->publicUrl()],
        'headline' => $article->title,
        'description' => $description,
        'image' => [$imageUrl],
        'datePublished' => $publishedAt?->toIso8601String(),
        'dateModified' => $article->updated_at->toIso8601String(),
        'author' => ['@type' => 'Person', 'name' => $article->author->name, 'url' => $article->author->publicUrl()],
        'publisher' => ['@type' => 'Organization', 'name' => 'Futebol na TV', 'url' => route('home'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('images/pwa/icon-512.png')]],
        'isAccessibleForFree' => true,
    ];
@endphp
<!DOCTYPE html>
<html lang="pt-BR"><head>@include('partials.google-tag-head')
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0756b9">
<title>{{ $pageTitle }} | Futebol na TV</title><meta name="description" content="{{ $description }}"><meta name="robots" content="index,follow,max-image-preview:large"><link rel="canonical" href="{{ $article->publicUrl() }}">
<meta property="og:type" content="article"><meta property="og:title" content="{{ $article->title }}"><meta property="og:description" content="{{ $description }}"><meta property="og:url" content="{{ $article->publicUrl() }}"><meta property="og:image" content="{{ $imageUrl }}"><meta property="og:image:width" content="1200"><meta property="og:image:height" content="675"><meta property="article:published_time" content="{{ $publishedAt?->toIso8601String() }}"><meta property="article:modified_time" content="{{ $article->updated_at->toIso8601String() }}">
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="{{ $article->title }}"><meta name="twitter:description" content="{{ $description }}"><meta name="twitter:image" content="{{ $imageUrl }}">
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@include('partials.pwa-head')@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="min-h-screen bg-[#f4f7fb] text-slate-950 antialiased">@include('partials.google-tag-body')@include('partials.site-header')
<main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
<nav class="mb-7 text-sm font-bold text-blue-700"><a href="{{ route('home') }}">Início</a><span class="mx-2 text-slate-300">/</span><a href="{{ route('news.index') }}">Notícias</a><span class="mx-2 text-slate-300">/</span><span class="text-slate-500">{{ Str::limit($article->title, 44) }}</span></nav>
<article class="mx-auto max-w-4xl"><header><p class="text-xs font-extrabold uppercase tracking-[.18em] text-blue-700">Programação e transmissões</p><h1 class="mt-3 text-4xl font-black leading-[1.08] tracking-[-.05em] sm:text-5xl">{{ $article->title }}</h1>@if($article->subtitle)<p class="mt-5 text-lg leading-8 text-slate-600 sm:text-xl">{{ $article->subtitle }}</p>@endif<div class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-slate-500"><span>Por <a href="{{ $article->author->publicUrl() }}" rel="author" class="font-extrabold text-slate-800 hover:text-blue-700">{{ $article->author->name }}</a></span><span aria-hidden="true">•</span><time datetime="{{ $publishedAt?->toIso8601String() }}">Publicado em {{ $publishedAt?->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i') }}</time>@if($article->updated_at->gt($publishedAt))<span aria-hidden="true">•</span><time datetime="{{ $article->updated_at->toIso8601String() }}">Atualizado em {{ $article->updated_at->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i') }}</time>@endif</div></header>
<figure class="mt-8 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-900/[.07]"><img src="{{ $imageUrl }}" alt="{{ $article->hero_image_alt ?: $article->title }}" class="aspect-video w-full object-cover" width="1200" height="675">@if($article->hero_image_caption || $article->hero_image_credit)<figcaption class="px-4 py-3 text-xs leading-5 text-slate-500">{{ $article->hero_image_caption }}@if($article->hero_image_credit)<span class="font-bold"> Crédito: {{ $article->hero_image_credit }}</span>@endif</figcaption>@endif</figure>
<div class="article-content mt-9 rounded-3xl border border-slate-200 bg-white px-6 py-8 shadow-sm sm:px-10 sm:py-10">{!! $article->bodyHtml() !!}</div>
@if($article->fixtures->isNotEmpty())<section class="mt-8 rounded-3xl border border-blue-100 bg-blue-50/60 p-6" aria-labelledby="jogos-citados"><h2 id="jogos-citados" class="text-xl font-black">Jogos citados nesta notícia</h2><div class="mt-4 grid gap-3 sm:grid-cols-2">@foreach($article->fixtures->take(8) as $fixture)<a href="{{ $fixture->publicUrl() }}" class="rounded-2xl border border-blue-100 bg-white p-4 transition hover:border-blue-300"><span class="text-xs font-extrabold uppercase tracking-wider text-blue-700">{{ $fixture->competition->name }} · {{ $fixture->starts_at->format('H:i') }}</span><strong class="mt-1 block leading-tight">{{ $fixture->homeTeam->name }} x {{ $fixture->awayTeam->name }}</strong><span class="mt-2 block text-xs text-slate-500">{{ $fixture->channels->pluck('name')->join(', ', ' e ') }}</span></a>@endforeach</div></section>@endif
<aside class="mt-8 rounded-3xl border border-slate-200 bg-white p-6"><p class="text-xs font-extrabold uppercase tracking-wider text-blue-700">Sobre o autor</p><h2 class="mt-1 text-xl font-black"><a href="{{ $article->author->publicUrl() }}" class="hover:text-blue-700">{{ $article->author->name }}</a></h2><p class="mt-2 leading-7 text-slate-600">{{ $article->author->bio }}</p></aside>
</article>
@if($relatedArticles->isNotEmpty())<section class="mx-auto mt-12 max-w-5xl border-t border-slate-200 pt-9"><h2 class="text-2xl font-black tracking-tight">Leia também</h2><div class="mt-5 grid gap-4 md:grid-cols-3">@foreach($relatedArticles as $related)<article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs font-bold text-blue-700">{{ $related->publicationDate()?->translatedFormat('d \d\e F') }}</p><h3 class="mt-2 font-black leading-snug"><a href="{{ $related->publicUrl() }}" class="hover:text-blue-700">{{ $related->title }}</a></h3></article>@endforeach</div></section>@endif
</main>@include('partials.legal-footer')</body></html>
