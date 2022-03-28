{!! $xmlDefinition !!}
{!! $xslLink !!}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" advanced-seo-version="{{ $version }}">
    @foreach ($sitemaps as $sitemap)
        @if (count($sitemap->urls()) >= 1)
            <sitemap>
                <loc>{{ $sitemap->url() }}</loc>
                @if($sitemap->lastmod())<lastmod>{{ $sitemap->lastmod() }}</lastmod>@endif
            </sitemap>
        @endif
    @endforeach
</sitemapindex>
