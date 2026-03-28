<?php

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Collections\CollectionSitemap;
use Aerni\AdvancedSeo\Sitemaps\Domain;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['english', 'german'])->saveQuietly();
    Collection::make('blog')->routes('/blog/{slug}')->sites(['english'])->saveQuietly();

    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
});

afterEach(function () {
    File::deleteDirectory(Sitemap::path());
});

it('returns a domain instance', function () {
    expect(Sitemap::index('english')->domain())->toBeInstanceOf(Domain::class);
});

it('returns the site handles for this domain', function () {
    expect(Sitemap::index('english')->sites())
        ->toContain('english')
        ->toContain('german');
});

it('has a filename of sitemap.xml', function () {
    expect(Sitemap::index('english')->filename())->toBe('sitemap.xml');
});

it('returns a collection of sitemaps sorted by handle', function () {
    $sitemaps = Sitemap::index('english')->sitemaps();
    $handles = $sitemaps->map->handle()->values()->all();

    expect($sitemaps)
        ->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->not->toBeEmpty()
        ->and($handles)->toBe(['blog', 'pages', 'tags']);
});

it('includes collection sitemaps', function () {
    $sitemaps = Sitemap::index('english')->sitemaps()->whereInstanceOf(CollectionSitemap::class);

    expect($sitemaps)->not->toBeEmpty();
});

it('includes taxonomy sitemaps', function () {
    $taxonomySitemaps = Sitemap::index('english')->sitemaps()->whereInstanceOf(TaxonomySitemap::class);

    expect($taxonomySitemaps)->not->toBeEmpty();
});

it('excludes collections not assigned to any site on this domain', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => 'https://french.example.com', 'locale' => 'fr'],
    ]);

    Collection::make('articles')->routes('/articles/{slug}')->sites(['french'])->saveQuietly();

    $handles = Sitemap::index('english')->sitemaps()->map->handle()->all();

    expect($handles)->not->toContain('articles');
});

it('excludes collections without a route for the site', function () {
    Collection::make('routeless')->sites(['english'])->saveQuietly();

    $handles = Sitemap::index('english')->sitemaps()->map->handle()->all();

    expect($handles)->not->toContain('routeless');
});

it('excludes taxonomies not assigned to any site on this domain', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'french' => ['name' => 'French', 'url' => 'https://french.example.com', 'locale' => 'fr'],
    ]);

    Taxonomy::make('categories')->sites(['french'])->saveQuietly();

    $handles = Sitemap::index('english')->sitemaps()->map->handle()->all();

    expect($handles)->not->toContain('categories');
});

it('includes registered custom sitemaps', function () {
    Sitemap::make('custom-pages')
        ->site('english')
        ->add('https://example.com/custom')
        ->register();

    $found = Sitemap::index('english')->find('custom-custom-pages');

    expect($found)
        ->not->toBeNull()
        ->and($found->handle())->toBe('custom-pages')
        ->and($found->type())->toBe('custom');
});

it('prevents duplicate custom sitemaps by id', function () {
    Sitemap::make('custom-pages')
        ->site('english')
        ->add('https://example.com/page-1')
        ->register();

    Sitemap::make('custom-pages')
        ->site('english')
        ->add('https://example.com/page-2')
        ->register();

    $customPages = Sitemap::index('english')->sitemaps()
        ->filter(fn ($sitemap) => $sitemap->type() === 'custom' && $sitemap->handle() === 'custom-pages');

    expect($customPages)->toHaveCount(1);
});

it('can find a sitemap by id', function () {
    $index = Sitemap::index('english');

    $first = $index->sitemaps()->first();
    $found = $index->find($first->id());

    expect($found)
        ->not->toBeNull()
        ->and($found->id())->toBe($first->id());
});

it('returns null when finding a nonexistent sitemap', function () {
    expect(Sitemap::index('english')->find('nonexistent-id'))->toBeNull();
});

it('sets the index reference on each sitemap', function () {
    $index = Sitemap::index('english');

    $index->sitemaps()->each(fn ($sitemap) => expect($sitemap->index())->toBe($index));
});

it('converts to array', function () {
    $array = Sitemap::index('english')->toArray();

    expect($array)->toBeArray()
        ->not->toBeEmpty()
        ->and($array[0])->toHaveKeys(['url', 'lastmod']);
});

it('renders xml with sitemapindex root element', function () {
    expect(Sitemap::index('english')->render())
        ->toContain('<?xml')
        ->toContain('sitemapindex')
        ->toContain('<sitemap>')
        ->toContain('<loc>');
});

it('responds with xml content type', function () {
    $response = Sitemap::index('english')->toResponse(request());

    expect($response->headers->get('Content-Type'))->toBe('text/xml')
        ->and($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
});

it('returns the correct file path', function () {
    $index = Sitemap::index('english');

    expect($index->path())
        ->toEndWith($index->domain()->name.'/sitemap.xml');
});

it('saves the index to disk', function () {
    $index = Sitemap::index('english');

    expect($index->save())->toBe($index);
    expect(File::exists($index->path()))->toBeTrue();
});

it('returns null when file does not exist', function () {
    expect(Sitemap::index('english')->file())->toBeNull();
});

it('returns file contents when saved', function () {
    expect(Sitemap::index('english')->save()->file())
        ->toBeString()
        ->toContain('sitemapindex');
});
