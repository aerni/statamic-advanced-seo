<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>

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
