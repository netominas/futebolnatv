{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('home') }}</loc><changefreq>hourly</changefreq><priority>1.0</priority></url>
    <url><loc>{{ route('fixtures.live') }}</loc><changefreq>always</changefreq><priority>0.9</priority></url>
    <url><loc>{{ route('fixtures.tomorrow') }}</loc><changefreq>hourly</changefreq><priority>0.9</priority></url>
    <url><loc>{{ route('news.index') }}</loc><changefreq>daily</changefreq><priority>0.9</priority></url>
@foreach($newsArticles as $article)
    <url><loc>{{ $article->publicUrl() }}</loc><lastmod>{{ $article->updated_at->toAtomString() }}</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>
@endforeach
@foreach($newsArticles->pluck('author')->filter()->unique('id') as $author)
    <url><loc>{{ $author->publicUrl() }}</loc><changefreq>weekly</changefreq><priority>0.6</priority></url>
@endforeach
@foreach(['pages.about','pages.contact','pages.privacy','pages.cookies','pages.terms','pages.editorial'] as $pageRoute)
    <url><loc>{{ route($pageRoute) }}</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
@endforeach
    <url><loc>{{ route('teams.index') }}</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
@foreach($teams as $team)
    <url><loc>{{ $team->publicUrl() }}</loc><lastmod>{{ $team->updated_at->toAtomString() }}</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>
@endforeach
    <url><loc>{{ route('channels.index') }}</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
@foreach($channels as $channel)
    <url><loc>{{ $channel->publicUrl() }}</loc><lastmod>{{ $channel->updated_at->toAtomString() }}</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>
@endforeach
    <url><loc>{{ route('competitions.index') }}</loc><changefreq>daily</changefreq><priority>0.8</priority></url>
@foreach($competitions as $competition)
    <url><loc>{{ $competition->publicUrl() }}</loc><lastmod>{{ $competition->updated_at->toAtomString() }}</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>
@endforeach
@foreach($fixtures as $fixture)
    <url><loc>{{ $fixture->publicUrl() }}</loc><lastmod>{{ $fixture->updated_at->toAtomString() }}</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>
@endforeach
</urlset>
