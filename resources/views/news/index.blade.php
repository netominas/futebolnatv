<!DOCTYPE html>
<html lang="pt-BR">
<head>
@include('partials.google-tag-head')
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0756b9">
<title>Notícias de futebol na TV, canais e transmissões</title>
<meta name="description" content="Notícias e levantamentos sobre jogos de futebol na TV, canais, streaming, campeonatos e horários de transmissão no Brasil.">
<meta name="robots" content="index,follow,max-image-preview:large"><link rel="canonical" href="{{ route('news.index') }}">
<meta property="og:type" content="website"><meta property="og:title" content="Notícias de futebol na TV"><meta property="og:description" content="Análises da programação, canais e campeonatos com transmissão confirmada no Brasil."><meta property="og:url" content="{{ route('news.index') }}">
@include('partials.social-image-meta')@include('partials.pwa-head')@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f4f7fb] text-slate-950 antialiased">@include('partials.google-tag-body')@include('partials.site-header')
<main>
<section class="hero-panel border-b border-blue-100"><div class="mx-auto max-w-6xl px-4 py-9 sm:px-6 sm:py-14"><p class="text-xs font-extrabold uppercase tracking-[.18em] text-blue-700">Redação Futebol na TV</p><h1 class="mt-2 max-w-4xl text-4xl font-black tracking-[-.045em] sm:text-5xl">Notícias sobre jogos, canais e transmissões</h1><p class="mt-4 max-w-3xl text-base leading-7 text-slate-600 sm:text-lg">Levantamentos próprios da grade de futebol na TV, com os destaques do dia, partidas simultâneas e a distribuição dos jogos por canal e campeonato.</p></div></section>
<div class="mx-auto max-w-6xl px-4 py-9 sm:px-6 sm:py-12">
@if($articles->isNotEmpty())
<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
@foreach($articles as $article)
<article class="group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-900/[.07]">
<a href="{{ $article->publicUrl() }}" class="block">@if($article->heroImageUrl())<img src="{{ $article->heroImageUrl() }}" alt="{{ $article->hero_image_alt }}" class="aspect-video w-full object-cover" width="1200" height="675" loading="lazy">@else<div class="aspect-video bg-gradient-to-br from-blue-700 to-cyan-400"></div>@endif</a>
<div class="p-5"><div class="flex items-center gap-2 text-xs font-extrabold uppercase tracking-wider text-blue-700"><span>{{ $article->coverage_date?->translatedFormat('d M') ?? 'Notícia' }}</span><span class="text-slate-300">•</span><time datetime="{{ $article->publicationDate()?->toIso8601String() }}">{{ $article->publicationDate()?->diffForHumans() }}</time></div><h2 class="mt-3 text-xl font-black leading-tight tracking-[-.025em]"><a href="{{ $article->publicUrl() }}" class="group-hover:text-blue-700">{{ $article->title }}</a></h2><p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ $article->excerpt }}</p><div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-xs"><a href="{{ $article->author->publicUrl() }}" class="font-bold text-slate-600 hover:text-blue-700">Por {{ $article->author->name }}</a><a href="{{ $article->publicUrl() }}" class="font-extrabold text-blue-700">Ler notícia →</a></div></div>
</article>
@endforeach
</div>
<div class="mt-9">{{ $articles->links() }}</div>
@else
<div class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm"><h2 class="text-xl font-black">As primeiras notícias estão em preparação</h2><p class="mt-2 text-slate-600">Enquanto isso, consulte a programação completa dos jogos na TV.</p><a href="{{ route('home') }}" class="mt-5 inline-flex rounded-xl bg-blue-600 px-5 py-3 font-extrabold text-white">Ver jogos de hoje</a></div>
@endif
</div>
</main>@include('partials.legal-footer')</body></html>
