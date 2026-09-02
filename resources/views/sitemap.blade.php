{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ route('home') }}</loc><changefreq>hourly</changefreq><priority>1.0</priority></url>
@foreach($fixtures as $fixture)
    <url><loc>{{ $fixture->publicUrl() }}</loc><lastmod>{{ $fixture->updated_at->toAtomString() }}</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>
@endforeach
</urlset>
