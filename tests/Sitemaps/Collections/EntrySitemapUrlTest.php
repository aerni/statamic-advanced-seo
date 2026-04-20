<?php

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Collections\CollectionSitemap;
use Aerni\AdvancedSeo\Sitemaps\Collections\EntrySitemapUrl;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
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

    $origin = Entry::make()->collection('pages')->locale('english')->slug('about');
    $origin->save();

    $origin->makeLocalization('german')->slug('ueber-uns')->save();
});

it('returns the absolute url as loc', function () {
    $entry = Entry::query()->where('slug', 'about')->first();

    $url = new EntrySitemapUrl($entry);

    expect($url->loc())->toBe('https://example.com/about');
});

it('returns the entry locale as site', function () {
    $entry = Entry::query()->where('slug', 'about')->first();

    $url = new EntrySitemapUrl($entry);

    expect($url->site())->toBe('english');
});

it('returns a formatted lastmod date', function () {
    $entry = Entry::query()->where('slug', 'about')->first();

    $url = new EntrySitemapUrl($entry);

    expect($url->lastmod())->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});

it('returns alternates for multisite entries', function () {
    $entry = Entry::query()->where('slug', 'about')->first();

    $sitemap = Sitemap::index('english')->sitemaps()
        ->first(fn ($s) => $s instanceof CollectionSitemap && $s->handle() === 'pages');

    $url = new EntrySitemapUrl($entry);
    $url->sitemap($sitemap);

    $alternates = $url->alternates();

    expect($alternates)->toBeArray()
        ->and(collect($alternates)->pluck('hreflang'))->toContain('en')
        ->and(collect($alternates)->pluck('hreflang'))->toContain('x-default');
});

it('returns null alternates for single site entries', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    Collection::make('blog')->routes('/blog/{slug}')->sites(['english'])->saveQuietly();
    flushBlink();
    Entry::make()->collection('blog')->locale('english')->slug('post')->save();

    $entry = Entry::query()->where('slug', 'post')->where('collection', 'blog')->first();

    $url = new EntrySitemapUrl($entry);

    expect($url->alternates())->toBeNull();
});
