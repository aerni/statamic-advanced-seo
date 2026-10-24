<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemapUrl;
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
});

it('returns the absolute url as loc', function () {
    $taxonomy = Taxonomy::find('tags');

    $url = new TaxonomySitemapUrl($taxonomy, 'english');

    expect($url->loc())->toBe('https://example.com/tags');
});

it('returns the site handle', function () {
    $url = new TaxonomySitemapUrl(Taxonomy::find('tags'), 'english');

    expect($url->site())->toBe('english');
});

it('returns a formatted lastmod date', function () {
    $url = new TaxonomySitemapUrl(Taxonomy::find('tags'), 'english');

    expect($url->lastmod())->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});

it('returns lastmod from the most recently modified term', function () {
    Term::make()->taxonomy('tags')->inDefaultLocale()->slug('php')->data(['title' => 'PHP'])->save();

    $url = new TaxonomySitemapUrl(Taxonomy::find('tags'), 'english');

    expect($url->lastmod())->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});

it('returns alternates for multisite taxonomies', function () {
    View::addLocation(__DIR__.'/../../__fixtures__/views');

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap && $s->handle() === 'tags');

    $url = new TaxonomySitemapUrl(Taxonomy::find('tags'), 'english');
    $url->sitemap($sitemap);

    $alternates = $url->alternates();

    expect($alternates)->toBeArray()
        ->and(collect($alternates)->pluck('hreflang'))->toContain('en')
        ->and(collect($alternates)->pluck('hreflang'))->toContain('de')
        ->and(collect($alternates)->pluck('hreflang'))->toContain('x-default');
});

it('returns null alternates when sitemap has fewer than two sites', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    Taxonomy::make('categories')->sites(['english'])->saveQuietly();

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof TaxonomySitemap && $s->handle() === 'categories');

    $url = new TaxonomySitemapUrl(Taxonomy::find('categories'), 'english');
    $url->sitemap($sitemap);

    expect($url->alternates())->toBeNull();
});

it('includes cross-domain alternates while keeping URL listings on their own domain', function (string $site, string $localUrl, string $foreignUrl) {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.de', 'locale' => 'de'],
    ]);
    Site::setCurrent($site);
    View::addLocation(__DIR__.'/../../__fixtures__/views');
    Seo::find('site::defaults')->config()->set('hreflang_x_default', 'german')->save();
    flushBlink();

    $sitemap = Sitemap::index($site)->sitemaps()
        ->first(fn ($sitemap) => $sitemap instanceof TaxonomySitemap && $sitemap->handle() === 'tags');
    $url = $sitemap->urls()->first(fn ($url) => $url instanceof TaxonomySitemapUrl);

    expect($sitemap->taxonomies()->keys()->sort()->values()->all())->toBe(['english', 'german'])
        ->and($sitemap->urls()->map->loc()->all())->toContain($localUrl)->not->toContain($foreignUrl)
        ->and(collect($url->alternates())->pluck('href', 'hreflang')->all())->toBe([
            'en' => 'https://example.com/tags',
            'de' => 'https://example.de/tags',
            'x-default' => 'https://example.de/tags',
        ]);
})->with([
    'english sitemap' => ['english', 'https://example.com/tags', 'https://example.de/tags'],
    'german sitemap' => ['german', 'https://example.de/tags', 'https://example.com/tags'],
]);

it('omits alternates when the taxonomy index template is missing', function () {
    View::addLocation(__DIR__.'/../../__fixtures__/views');
    Taxonomy::find('tags')->template('missing-template')->saveQuietly();
    flushBlink();

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($sitemap) => $sitemap instanceof TaxonomySitemap && $sitemap->handle() === 'tags');
    $url = new TaxonomySitemapUrl(Taxonomy::find('tags'), 'english');
    $url->sitemap($sitemap);

    expect($url->alternates())->toBeNull();
});
