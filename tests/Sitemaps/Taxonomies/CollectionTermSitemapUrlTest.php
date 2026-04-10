<?php

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\CollectionTermSitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
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

    Collection::make('blog')->routes('/blog/{slug}')->sites(['english', 'german'])->taxonomies(['tags'])->saveQuietly();

    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();

    Term::make()->taxonomy('tags')->inDefaultLocale()->slug('php')->data(['title' => 'PHP'])->save();
});

it('returns the absolute url as loc', function () {
    $term = Term::query()->where('slug', 'php')->first();
    $collectionTerm = $term->fresh()->collection(Collection::find('blog'));

    $url = new CollectionTermSitemapUrl($collectionTerm);

    expect($url->loc())->toContain('/blog/tags/php');
});

it('returns the term locale as site', function () {
    $term = Term::query()->where('slug', 'php')->first();
    $collectionTerm = $term->fresh()->collection(Collection::find('blog'));

    $url = new CollectionTermSitemapUrl($collectionTerm);

    expect($url->site())->toBe('english');
});

it('returns a formatted lastmod date', function () {
    $term = Term::query()->where('slug', 'php')->first();
    $collectionTerm = $term->fresh()->collection(Collection::find('blog'));

    $url = new CollectionTermSitemapUrl($collectionTerm);

    expect($url->lastmod())->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});

it('returns alternates for multisite collection terms', function () {
    View::addLocation(__DIR__.'/../../__fixtures__/views');

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap && $s->handle() === 'tags');

    $term = Term::query()->where('slug', 'php')->first();
    $collectionTerm = $term->fresh()->collection(Collection::find('blog'));

    $url = new CollectionTermSitemapUrl($collectionTerm);
    $url->sitemap($sitemap);

    $alternates = $url->alternates();

    expect($alternates)->toBeArray()
        ->and(collect($alternates)->pluck('hreflang'))->toContain('en')
        ->and(collect($alternates)->pluck('hreflang'))->toContain('x-default');
});

it('returns null alternates for single site collection terms', function () {
    View::addLocation(__DIR__.'/../../__fixtures__/views');

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    Collection::make('posts')->routes('/posts/{slug}')->sites(['english'])->taxonomies(['categories'])->saveQuietly();
    Taxonomy::make('categories')->sites(['english'])->saveQuietly();
    flushBlink();
    Term::make()->taxonomy('categories')->inDefaultLocale()->slug('laravel')->data(['title' => 'Laravel'])->save();

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap && $s->handle() === 'categories');

    $term = Term::query()->where('slug', 'laravel')->first();
    $collectionTerm = $term->fresh()->collection(Collection::find('posts'));

    $url = new CollectionTermSitemapUrl($collectionTerm);
    $url->sitemap($sitemap);

    expect($url->alternates())->toBeNull();
});
