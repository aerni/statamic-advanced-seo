<?php

use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapType;

it('has the correct name', function () {
    expect(SeoSitemapType::NAME)->toBe('seoSitemap');
});

it('exposes all expected fields', function () {
    expect((new SeoSitemapType)->fields())->toHaveKeys([
        'alternates',
        'changefreq',
        'lastmod',
        'loc',
        'priority',
    ]);
});

it('resolves data', function () {
    $fields = (new SeoSitemapType)->fields();

    $sitemap = [
        'alternates' => [['href' => 'https://example.com/de', 'hreflang' => 'de']],
        'changefreq' => 'weekly',
        'lastmod' => '2024-01-01',
        'loc' => 'https://example.com/page',
        'priority' => '0.8',
    ];

    expect($fields['alternates']['resolve']($sitemap))->toBe($sitemap['alternates']);
    expect($fields['changefreq']['resolve']($sitemap))->toBe('weekly');
    expect($fields['lastmod']['resolve']($sitemap))->toBe('2024-01-01');
    expect($fields['loc']['resolve']($sitemap))->toBe('https://example.com/page');
    expect($fields['priority']['resolve']($sitemap))->toBe('0.8');
});
