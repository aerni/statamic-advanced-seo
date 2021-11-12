{!! $xmlDefinition !!}{!! $xslLink !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" advanced-seo-version="{{ $version }}">
    @foreach ($data as $item)
        <url>
            <loc>{{ $item['loc'] }}</loc>
            <lastmod>{{ $item['lastmod'] }}</lastmod>
            <changefreq>{{ $item['changefreq'] }}</changefreq>
            <priority>{{ $item['priority'] }}</priority>
        </url>
    @endforeach
</urlset>
