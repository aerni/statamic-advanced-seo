<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>

<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    advanced-seo-version="{{ $version }}"
>
    @foreach ($urls as $url)
        <url>
            <loc>{{ $url['loc'] }}</loc>

            @foreach ($url['alternates'] as $alternate)
                <xhtml:link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['href'] }}"/>
            @endforeach

            @isset($url['lastmod'])<lastmod>{{ $url['lastmod'] }}</lastmod>@endisset
            @isset($url['changefreq'])<changefreq>{{ $url['changefreq'] }}</changefreq>@endisset
            @isset($url['priority'])<priority>{{ $url['priority'] }}</priority>@endisset
        </url>
    @endforeach
</urlset>
