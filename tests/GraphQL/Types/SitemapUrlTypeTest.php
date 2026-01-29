<?php

use Aerni\AdvancedSeo\GraphQL\Types\SitemapUrlType;

it('has the correct name', function () {
    expect(SitemapUrlType::NAME)->toBe('sitemapUrl');
});

it('exposes all expected fields', function () {
    expect((new SitemapUrlType)->fields())->toHaveKeys([
        'loc',
        'lastmod',
        'changefreq',
        'priority',
        'alternates',
    ]);
});

it('resolves data', function () {
    $fields = (new SitemapUrlType)->fields();

    $url = [
        'loc' => 'https://example.com/page',
        'lastmod' => '2024-01-01',
        'changefreq' => 'weekly',
        'priority' => '0.8',
        'alternates' => [['href' => 'https://example.com/de/page', 'hreflang' => 'de']],
    ];

    expect($fields['loc']['resolve']($url))->toBe('https://example.com/page');
    expect($fields['lastmod']['resolve']($url))->toBe('2024-01-01');
    expect($fields['changefreq']['resolve']($url))->toBe('weekly');
    expect($fields['priority']['resolve']($url))->toBe('0.8');
    expect($fields['alternates']['resolve']($url))->toBe([['href' => 'https://example.com/de/page', 'hreflang' => 'de']]);
});
