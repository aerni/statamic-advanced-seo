<?php

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Collections\CollectionSitemap;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    flushBlink();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['english', 'german'])->saveQuietly();
    Collection::make('blog')->routes('/blog/{slug}')->sites(['english'])->saveQuietly();
});

it('has a type of collection', function () {
    $sitemap = Sitemap::index('english')->sitemaps()->first(fn ($s) => $s instanceof CollectionSitemap);

    expect($sitemap->type())->toBe('collection');
});

it('uses the collection handle', function () {
    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof CollectionSitemap && $s->handle() === 'blog');

    expect($sitemap)->not->toBeNull()
        ->and($sitemap->handle())->toBe('blog');
});

it('returns a collection of urls', function () {
    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof CollectionSitemap);

    expect($sitemap->urls())->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('creates sitemaps for all collections with routes', function () {
    $index = Sitemap::index('english');

    $collectionSitemaps = $index->sitemaps()
        ->filter(fn ($s) => $s instanceof CollectionSitemap)
        ->pluck(null)
        ->map->handle()
        ->values();

    expect($collectionSitemaps)->toContain('pages')
        ->toContain('blog');
});
