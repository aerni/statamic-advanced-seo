<?php

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesSitemap;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesSitemap::class);

beforeEach(function () {
    flushBlink();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['english'])->saveQuietly();
    Taxonomy::make('tags')->sites(['english'])->saveQuietly();
});

// --- Collections ---

it('includes a collection with a route for the site', function () {
    expect(IncludeInSitemap::run(Collection::find('pages'), 'english'))->toBeTrue();
});

it('excludes a collection without a route for the site', function () {
    Collection::make('routeless')->sites(['english'])->saveQuietly();

    expect(IncludeInSitemap::run(Collection::find('routeless'), 'english'))->toBeFalse();
});

it('excludes a collection when sitemap is disabled in config', function () {
    Seo::find('collections::pages')
        ->config()
        ->set('sitemap', false)
        ->save();

    expect(IncludeInSitemap::run(Collection::find('pages'), 'english'))->toBeFalse();
});

it('excludes a collection when the seoSet is disabled', function () {
    Seo::find('collections::pages')
        ->config()
        ->enabled(false)
        ->save();

    expect(IncludeInSitemap::run(Collection::find('pages'), 'english'))->toBeFalse();
});

it('excludes a collection when crawling is disabled', function () {
    config(['advanced-seo.crawling.environments' => []]);

    expect(IncludeInSitemap::run(Collection::find('pages'), 'english'))->toBeFalse();
});

// --- Taxonomies ---

it('includes a taxonomy for the site', function () {
    expect(IncludeInSitemap::run(Taxonomy::find('tags'), 'english'))->toBeTrue();
});

it('excludes a taxonomy when sitemap is disabled in config', function () {
    Seo::find('taxonomies::tags')
        ->config()
        ->set('sitemap', false)
        ->save();

    expect(IncludeInSitemap::run(Taxonomy::find('tags'), 'english'))->toBeFalse();
});

// --- Entries ---

it('includes a published entry with default SEO values', function () {
    $entry = Entry::make()->collection('pages')->locale('english')->slug('about');
    $entry->save();

    expect(IncludeInSitemap::run($entry, 'english'))->toBeTrue();
});

it('excludes an unpublished entry', function () {
    $entry = Entry::make()->collection('pages')->locale('english')->slug('draft')->published(false);
    $entry->save();

    expect(IncludeInSitemap::run($entry, 'english'))->toBeFalse();
});

it('excludes an entry with noindex', function () {
    $entry = Entry::make()->collection('pages')->locale('english')->slug('hidden')
        ->data(['seo_noindex' => true]);
    $entry->save();

    expect(IncludeInSitemap::run($entry, 'english'))->toBeFalse();
});

it('excludes an entry with a custom canonical', function () {
    $entry = Entry::make()->collection('pages')->locale('english')->slug('canonical')
        ->data(['seo_canonical_type' => 'custom']);
    $entry->save();

    expect(IncludeInSitemap::run($entry, 'english'))->toBeFalse();
});

// --- Error cases ---

it('throws when a collection is passed without a site', function () {
    IncludeInSitemap::run(Collection::find('pages'));
})->throws(Exception::class, 'A site is required');

it('throws when a taxonomy is passed without a site', function () {
    IncludeInSitemap::run(Taxonomy::find('tags'));
})->throws(Exception::class, 'A site is required');
