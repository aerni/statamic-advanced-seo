<?php

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TermSitemapUrl;
use Illuminate\Support\Facades\View;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    flushBlink();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    Collection::make('blog')->routes('/blog/{slug}')->sites(['english', 'german'])->saveQuietly();

    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
    Taxonomy::make('categories')->sites(['english'])->saveQuietly();
});

it('has a type of taxonomy', function () {
    $sitemap = Sitemap::index('english')->sitemaps()->first(fn ($s) => $s instanceof TaxonomySitemap);

    expect($sitemap->type())->toBe('taxonomy');
});

it('uses the taxonomy handle', function () {
    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap && $s->handle() === 'tags');

    expect($sitemap)->not->toBeNull()
        ->and($sitemap->handle())->toBe('tags');
});

it('returns a collection of urls', function () {
    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap);

    expect($sitemap->urls())->toBeInstanceOf(Illuminate\Support\Collection::class);
});

it('creates sitemaps for all taxonomies', function () {
    $index = Sitemap::index('english');

    $taxonomySitemaps = $index->sitemaps()
        ->filter(fn ($s) => $s instanceof TaxonomySitemap)
        ->pluck(null)
        ->map->handle()
        ->values();

    expect($taxonomySitemaps)->toContain('tags')
        ->toContain('categories');
});

it('excludes terms whose template view does not exist', function () {
    View::addLocation(__DIR__.'/../../__fixtures__/views');

    Term::make()->taxonomy('tags')->inDefaultLocale()->slug('php')
        ->data(['title' => 'PHP', 'template' => 'nonexistent-template'])->save();
    Term::make()->taxonomy('tags')->inDefaultLocale()->slug('laravel')
        ->data(['title' => 'Laravel'])->save();

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap && $s->handle() === 'tags');

    $termLocs = $sitemap->urls()
        ->filter(fn ($url) => $url instanceof TermSitemapUrl)
        ->map->loc();

    expect($termLocs)->toContain('https://example.com/tags/laravel')
        ->and($termLocs)->not->toContain('https://example.com/tags/php');
});
