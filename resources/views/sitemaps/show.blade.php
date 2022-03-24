{!! $xmlDefinition !!}{!! $xslLink !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" advanced-seo-version="{{ $version }}">
    @foreach ($data as $item)
        <url>
            <loc>{{ $item['loc'] }}</loc>
            @isset($item['lastmod'])<lastmod>{{ $item['lastmod'] }}</lastmod>@endisset
            @isset($item['changefreq'])<changefreq>{{ $item['changefreq'] }}</changefreq>@endisset
            @isset($item['priority'])<priority>{{ $item['priority'] }}</priority>@endisset
        </url>
    @endforeach
</urlset>
