<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\CollectionTaxonomySitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Illuminate\Support\Facades\View;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
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
    $url = $sitemap->urls()->first(fn ($url) => $url instanceof CollectionTaxonomySitemapUrl);

    expect($sitemap->collectionTaxonomies()->pluck('site')->sort()->values()->all())->toBe(['english', 'german'])
        ->and($sitemap->urls()->map->loc()->all())->toContain($localUrl)->not->toContain($foreignUrl)
        ->and(collect($url->alternates())->pluck('href', 'hreflang')->all())->toBe([
            'en' => 'https://example.com/blog/tags',
            'de' => 'https://example.de/blog/tags',
            'x-default' => 'https://example.de/blog/tags',
        ]);
})->with([
    'english sitemap' => ['english', 'https://example.com/blog/tags', 'https://example.de/blog/tags'],
    'german sitemap' => ['german', 'https://example.de/blog/tags', 'https://example.com/blog/tags'],
]);

it('omits alternates when the taxonomy index template is missing', function () {
    View::addLocation(__DIR__.'/../../__fixtures__/views');
    Taxonomy::find('tags')->template('missing-template')->saveQuietly();
    flushBlink();

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($sitemap) => $sitemap instanceof TaxonomySitemap && $sitemap->handle() === 'tags');
    $url = new CollectionTaxonomySitemapUrl(Taxonomy::find('tags')->collection(Collection::find('blog')), 'english');
    $url->sitemap($sitemap);

    expect($url->alternates())->toBeNull();
});

it('uses the mounted collection path for loc', function (string $mountRoute, string $site, string $taxonomyHandle, string $expected) {
    Collection::make('pages')->routes($mountRoute)->sites(['english', 'german'])->saveQuietly();
    $mount = Entry::make()->collection('pages')->locale('english')->slug('articles');
    $mount->saveQuietly();
    $mount->makeLocalization('german')->slug('artikel')->saveQuietly();
    Collection::find('blog')->mount($mount->id())->taxonomies([$taxonomyHandle])->saveQuietly();
    Taxonomy::make($taxonomyHandle)->sites(['english', 'german'])->saveQuietly();
    flushBlink();
    Site::setCurrent('german');
    $taxonomy = Taxonomy::find($taxonomyHandle)->collection(Collection::find('blog'));

    $url = new CollectionTaxonomySitemapUrl($taxonomy, $site);

    expect($url->loc())->toBe($expected)
        ->and(Site::current()->handle())->toBe('german');
})->with([
    'English mount' => ['/{slug}', 'english', 'tags', 'https://example.com/articles/tags'],
    'translated mount' => ['/{slug}', 'german', 'tags', 'https://example.com/de/artikel/tags'],
    'nested mount path' => ['/news/{slug}', 'english', 'tags', 'https://example.com/news/articles/tags'],
    'site root mount' => ['/', 'english', 'tags', 'https://example.com/tags'],
    'underscored taxonomy handle' => ['/news/{slug}', 'german', 'topic_tags', 'https://example.com/de/news/artikel/topic-tags'],
]);

it('uses localized mounted collection paths for sitemap alternates', function (string $germanUrl) {
    Site::setSites([
        'english' => ['url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['url' => $germanUrl, 'locale' => 'de'],
    ]);
    View::addLocation(__DIR__.'/../../__fixtures__/views');
    Collection::make('pages')->routes('/news/{slug}')->sites(['english', 'german'])->saveQuietly();
    $mount = Entry::make()->collection('pages')->locale('english')->slug('articles');
    $mount->saveQuietly();
    $mount->makeLocalization('german')->slug('artikel')->saveQuietly();
    Collection::find('blog')->mount($mount->id())->saveQuietly();
    Seo::find('site::defaults')->config()->set('hreflang_x_default', 'german')->save();
    flushBlink();
    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($sitemap) => $sitemap instanceof TaxonomySitemap && $sitemap->handle() === 'tags');
    $taxonomy = Taxonomy::find('tags')->collection(Collection::find('blog'));
    $url = new CollectionTaxonomySitemapUrl($taxonomy, 'english');
    $url->sitemap($sitemap);
    Site::setCurrent('german');

    $alternates = $url->alternates();

    expect(collect($alternates)->pluck('href', 'hreflang')->all())->toBe([
        'en' => 'https://example.com/news/articles/tags',
        'de' => $germanUrl.'/news/artikel/tags',
        'x-default' => $germanUrl.'/news/artikel/tags',
    ])->and(Site::current()->handle())->toBe('german');
})->with([
    'site prefix' => ['https://example.com/de'],
    'separate domain' => ['https://example.de'],
]);
