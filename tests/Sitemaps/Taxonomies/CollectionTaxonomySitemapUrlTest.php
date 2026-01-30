<?php

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\CollectionTaxonomySitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Illuminate\Support\Facades\View;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    flushBlink();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    Collection::make('blog')->routes('/blog/{slug}')->sites(['english', 'german'])->taxonomies(['tags'])->saveQuietly();

    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
});

it('returns the collection taxonomy url as loc', function () {
    $taxonomy = Taxonomy::find('tags')->collection(Collection::find('blog'));

    $url = new CollectionTaxonomySitemapUrl($taxonomy, 'english');

    expect($url->loc())->toBe('https://example.com/blog/tags');
});

it('returns the site handle', function () {
    $taxonomy = Taxonomy::find('tags')->collection(Collection::find('blog'));

    $url = new CollectionTaxonomySitemapUrl($taxonomy, 'german');

    expect($url->site())->toBe('german');
});

it('returns a formatted lastmod date', function () {
    $taxonomy = Taxonomy::find('tags')->collection(Collection::find('blog'));

    $url = new CollectionTaxonomySitemapUrl($taxonomy, 'english');

    expect($url->lastmod())->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});

it('returns alternates for multisite collection taxonomies', function () {
    View::addLocation(__DIR__.'/../../__fixtures__/views');

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap && $s->handle() === 'tags');

    $taxonomy = Taxonomy::find('tags')->collection(Collection::find('blog'));

    $url = new CollectionTaxonomySitemapUrl($taxonomy, 'english');
    $url->sitemap($sitemap);

    $alternates = $url->alternates();

    expect($alternates)->toBeArray()
        ->and(collect($alternates)->pluck('hreflang'))->toContain('en')
        ->and(collect($alternates)->pluck('hreflang'))->toContain('de')
        ->and(collect($alternates)->pluck('hreflang'))->toContain('x-default');
});

it('returns null alternates for single site collection taxonomies', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    Collection::make('posts')->routes('/posts/{slug}')->sites(['english'])->taxonomies(['categories'])->saveQuietly();
    Taxonomy::make('categories')->sites(['english'])->saveQuietly();

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap && $s->handle() === 'categories');

    $taxonomy = Taxonomy::find('categories')->collection(Collection::find('posts'));

    $url = new CollectionTaxonomySitemapUrl($taxonomy, 'english');
    $url->sitemap($sitemap);

    expect($url->alternates())->toBeNull();
});

it('returns a default changefreq', function () {
    $taxonomy = Taxonomy::find('tags')->collection(Collection::find('blog'));

    $url = new CollectionTaxonomySitemapUrl($taxonomy, 'english');

    expect($url->changefreq())->toBeString()->not->toBeEmpty();
});

it('returns a default priority', function () {
    $taxonomy = Taxonomy::find('tags')->collection(Collection::find('blog'));

    $url = new CollectionTaxonomySitemapUrl($taxonomy, 'english');

    expect($url->priority())->toBeString()->not->toBeEmpty();
});
