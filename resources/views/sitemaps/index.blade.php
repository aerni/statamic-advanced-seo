{!! $xmlDefinition !!}{!! $xslLink !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" advanced-seo-version="{{ $version }}">
    @foreach ($sitemaps as $sitemap)
        @if (count($sitemap->items()) >= 1)
            <sitemap>
                <loc>{{ $sitemap->url() }}</loc>
                <lastmod>{{ $sitemap->lastmod() }}</lastmod>
            </sitemap>
        @endif
    @endforeach
</sitemapindex>
