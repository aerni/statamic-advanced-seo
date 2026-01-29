<?php

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\GraphQL\Types\SitemapType;

it('has the correct name', function () {
    expect(SitemapType::NAME)->toBe('sitemap');
});

it('exposes all expected fields', function () {
    expect((new SitemapType)->fields())->toHaveKeys([
        'id',
        'type',
        'handle',
        'lastmod',
        'urls',
    ]);
});

it('resolves data', function () {
    $fields = (new SitemapType)->fields();

    $sitemap = Mockery::mock(Sitemap::class);
    $sitemap->shouldReceive('id')->andReturn('collection-pages');
    $sitemap->shouldReceive('type')->andReturn('collection');
    $sitemap->shouldReceive('handle')->andReturn('pages');
    $sitemap->shouldReceive('lastmod')->andReturn('2024-01-01');
    $sitemap->shouldReceive('urls')->andReturn(collect([]));

    expect($fields['id']['resolve']($sitemap))->toBe('collection-pages');
    expect($fields['type']['resolve']($sitemap))->toBe('collection');
    expect($fields['handle']['resolve']($sitemap))->toBe('pages');
    expect($fields['lastmod']['resolve']($sitemap))->toBe('2024-01-01');
    expect($fields['urls']['resolve']($sitemap))->toBe([]);
});
