<?php

use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TermSitemapUrl;
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

    Term::make()->taxonomy('tags')->inDefaultLocale()->slug('php')->data(['title' => 'PHP'])->save();
});

it('returns the absolute url as loc', function () {
    $term = Term::query()->where('slug', 'php')->first();

    $url = new TermSitemapUrl($term);

    expect($url->loc())->toBe('https://example.com/tags/php');
});

it('returns the term locale as site', function () {
    $term = Term::query()->where('slug', 'php')->first();

    $url = new TermSitemapUrl($term);

    expect($url->site())->toBe('english');
});

it('returns a formatted lastmod date', function () {
    $term = Term::query()->where('slug', 'php')->first();

    $url = new TermSitemapUrl($term);

    expect($url->lastmod())->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});

it('returns alternates for multisite terms', function () {
    $term = Term::query()->where('slug', 'php')->first();

    $url = new TermSitemapUrl($term);

    $alternates = $url->alternates();

    expect($alternates)->toBeArray()
        ->and(collect($alternates)->pluck('hreflang'))->toContain('en')
        ->and(collect($alternates)->pluck('hreflang'))->toContain('x-default');
});

it('returns null alternates for single site terms', function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    Taxonomy::make('categories')->sites(['english'])->saveQuietly();
    flushBlink();
    Term::make()->taxonomy('categories')->inDefaultLocale()->slug('laravel')->data(['title' => 'Laravel'])->save();

    $term = Term::query()->where('slug', 'laravel')->first();

    $url = new TermSitemapUrl($term);

    expect($url->alternates())->toBeNull();
});
