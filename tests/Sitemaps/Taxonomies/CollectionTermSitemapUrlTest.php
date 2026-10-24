<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\CollectionTermSitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Illuminate\Support\Facades\View;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
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

it('includes cross-domain alternates while keeping URL listings on their own domain', function (string $site, string $localUrl, string $foreignUrl) {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.de', 'locale' => 'de'],
    ]);
    Site::setCurrent($site);
    View::addLocation(__DIR__.'/../../__fixtures__/views');
    Term::query()->where('slug', 'php')->first()->in('german')->data(['title' => 'PHP'])->save();
    Seo::find('site::defaults')->config()->set('hreflang_x_default', 'german')->save();
    flushBlink();
    foreach (['english', 'german'] as $entrySite) {
        Entry::make()->collection('blog')->locale($entrySite)->slug('post')
            ->data(['title' => 'Post', 'tags' => ['php']])->save();
    }
    flushBlink();

    $sitemap = Sitemap::index($site)->sitemaps()
        ->first(fn ($sitemap) => $sitemap instanceof TaxonomySitemap && $sitemap->handle() === 'tags');
    $url = $sitemap->urls()->first(fn ($url) => $url instanceof CollectionTermSitemapUrl);

    expect($sitemap->collectionTerms()->map->locale()->sort()->values()->all())->toBe(['english', 'german'])
        ->and($sitemap->urls()->map->loc()->all())->toContain($localUrl)->not->toContain($foreignUrl)
        ->and(collect($url->alternates())->pluck('href', 'hreflang')->all())->toBe([
            'en' => 'https://example.com/blog/tags/php',
            'de' => 'https://example.de/blog/tags/php',
            'x-default' => 'https://example.de/blog/tags/php',
        ]);
})->with([
    'english sitemap' => ['english', 'https://example.com/blog/tags/php', 'https://example.de/blog/tags/php'],
    'german sitemap' => ['german', 'https://example.de/blog/tags/php', 'https://example.com/blog/tags/php'],
]);

it('keeps collection term sitemap inclusion governed by taxonomy settings', function (array $data) {
    $term = Term::query()->where('slug', 'php')->first();
    $term->in('german')->data(['title' => 'PHP', ...$data])->save();
    View::addLocation(__DIR__.'/../../__fixtures__/views');
    Seo::find('site::defaults')->config()->set('hreflang_x_default', 'german')->save();
    flushBlink();
    foreach (['english', 'german'] as $entrySite) {
        Entry::make()->collection('blog')->locale($entrySite)->slug('post')
            ->data(['title' => 'Post', 'tags' => ['php']])->save();
    }
    flushBlink();

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($sitemap) => $sitemap instanceof TaxonomySitemap && $sitemap->handle() === 'tags');
    $url = $sitemap->urls()->first(fn ($url) => $url instanceof CollectionTermSitemapUrl);

    expect($sitemap->urls()->map->loc()->all())->toContain('https://example.com/de/blog/tags/php')
        ->and(collect($url->alternates())->pluck('href', 'hreflang')->all())->toBe([
            'en' => 'https://example.com/blog/tags/php',
            'de' => 'https://example.com/de/blog/tags/php',
            'x-default' => 'https://example.com/de/blog/tags/php',
        ]);
})->with([
    'term sitemap exclusion' => [['seo_sitemap_enabled' => false]],
    'term noindex' => [['seo_noindex' => true]],
    'term canonical elsewhere' => [['seo_canonical_type' => 'custom', 'seo_canonical_custom' => 'https://example.com/canonical']],
]);

it('excludes cross-domain collection term alternates with a missing template', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.de', 'locale' => 'de'],
    ]);
    Term::query()->where('slug', 'php')->first()->in('german')
        ->data(['title' => 'PHP', 'template' => 'missing-template'])->save();
    View::addLocation(__DIR__.'/../../__fixtures__/views');
    flushBlink();
    foreach (['english', 'german'] as $entrySite) {
        Entry::make()->collection('blog')->locale($entrySite)->slug('post')
            ->data(['title' => 'Post', 'tags' => ['php']])->save();
    }
    flushBlink();

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($sitemap) => $sitemap instanceof TaxonomySitemap && $sitemap->handle() === 'tags');
    $url = $sitemap->urls()->first(fn ($url) => $url instanceof CollectionTermSitemapUrl);

    expect($url->alternates())->toBeNull();
});
