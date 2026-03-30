<?php

use Aerni\AdvancedSeo\Sitemaps\BaseSitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\Custom\SitemapBuilder;

it('throws when accessing sitemap before it is set', function () {
    $url = new TestSitemapUrl;

    $url->sitemap();
})->throws(Error::class);

it('can set and retrieve the parent sitemap', function () {
    $url = new TestSitemapUrl;
    $sitemap = new SitemapBuilder('test');

    $url->sitemap($sitemap);

    expect($url->sitemap())->toBe($sitemap);
});

it('converts to array with expected keys', function () {
    $url = new TestSitemapUrl;

    expect($url->toArray())->toHaveKeys(['loc', 'alternates', 'lastmod', 'changefreq', 'priority', 'site']);
});

it('converts to array with correct values from abstract methods', function () {
    $url = new TestSitemapUrl;

    $array = $url->toArray();

    expect($array['loc'])->toBe('https://example.com/test')
        ->and($array['alternates'])->toBeNull()
        ->and($array['lastmod'])->toBe('2025-06-15T12:00:00+00:00')
        ->and($array['changefreq'])->toBe('weekly')
        ->and($array['priority'])->toBe('0.5')
        ->and($array['site'])->toBe('english');
});

class TestSitemapUrl extends BaseSitemapUrl
{
    public function loc(): string
    {
        return 'https://example.com/test';
    }

    public function alternates(): ?array
    {
        return null;
    }

    public function lastmod(): ?string
    {
        return '2025-06-15T12:00:00+00:00';
    }

    public function changefreq(): ?string
    {
        return 'weekly';
    }

    public function priority(): ?string
    {
        return '0.5';
    }

    public function site(): string
    {
        return 'english';
    }
}
