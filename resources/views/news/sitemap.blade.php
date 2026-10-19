{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
@foreach($articles as $article)
<url><loc>{{ $article->publicUrl() }}</loc><news:news><news:publication><news:name>Futebol na TV</news:name><news:language>pt</news:language></news:publication><news:publication_date>{{ $article->publicationDate()->toIso8601String() }}</news:publication_date><news:title>{{ $article->title }}</news:title></news:news></url>
@endforeach
</urlset>
