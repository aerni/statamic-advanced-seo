@php
echo '<?xml version="1.0" encoding="utf-8"?>';
echo '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>';
@endphp

<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml"
    advanced-seo-version="{{ $version }}"
>
    @foreach ($urls as $url)
        @include('advanced-seo::sitemaps.url')
    @endforeach
</urlset>
