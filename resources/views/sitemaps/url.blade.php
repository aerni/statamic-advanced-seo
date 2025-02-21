<url id="{{ $url['id'] }}">
    <loc>{{ $url['loc'] }}</loc>

    @isset($url['alternates'])
        @foreach ($url['alternates'] as $alternate)
            <xhtml:link rel="alternate" hreflang="{{ $alternate['hreflang'] }}" href="{{ $alternate['href'] }}"/>
        @endforeach
    @endisset

    @isset($url['lastmod'])
        <lastmod>{{ $url['lastmod'] }}</lastmod>
    @endisset

    @isset($url['changefreq'])
        <changefreq>{{ $url['changefreq'] }}</changefreq>
    @endisset

    @isset($url['priority'])
        <priority>{{ $url['priority'] }}</priority>
    @endisset
</url>
