<?php

use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Facades\Collection as StatamicCollection;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    flushBlink();

    Site::setSites([
        'default' => ['name' => 'Default', 'url' => '/', 'locale' => 'en'],
    ]);

    StatamicCollection::make('blog')
        ->sites(['default'])
        ->saveQuietly();

    StatamicCollection::make('pages')
        ->sites(['default'])
        ->saveQuietly();

    StatamicCollection::make('products')
        ->sites(['default'])
        ->saveQuietly();

    Taxonomy::make('categories')
        ->sites(['default'])
        ->saveQuietly();

    Taxonomy::make('tags')
        ->sites(['default'])
        ->saveQuietly();
});

it('can get all seo sets', function () {
    $sets = Seo::all();

    expect($sets)
        ->toBeInstanceOf(Collection::class)
        ->and($sets)
        ->toHaveCount(10);
});

it('can find an seo set', function () {
    expect(Seo::find('site::general'))->toBeInstanceOf(SeoSet::class);
});

it('includes all enabled site sets', function () {
    $siteSets = Seo::whereType('site');

    expect($siteSets)
        ->toBeInstanceOf(Collection::class)
        ->and($siteSets->count())
        ->toBeGreaterThanOrEqual(3);

    $handles = $siteSets->map->handle()->all();

    // The core site sets that can't be disabled.
    expect($handles)->toContain('general')
        ->and($handles)->toContain('indexing')
        ->and($handles)->toContain('social_media');
});

it('includes analytics set when analytics config is enabled', function () {
    config(['advanced-seo.analytics.fathom' => true]);

    expect(Seo::find('site::analytics'))->toBeInstanceOf(SeoSet::class);
});

it('excludes analytics set when no analytics config is set', function () {
    config(['advanced-seo.analytics.fathom' => false]);
    config(['advanced-seo.analytics.cloudflare_analytics' => false]);
    config(['advanced-seo.analytics.google_tag_manager' => false]);

    expect(Seo::find('site::analytics'))->toBeNull();
});

it('includes favicons set when favicons are enabled', function () {
    config(['advanced-seo.favicons.enabled' => true]);

    expect(Seo::find('site::favicons'))->toBeInstanceOf(SeoSet::class);
});

it('excludes favicons set when favicons are disabled', function () {
    config(['advanced-seo.favicons.enabled' => false]);

    expect(Seo::find('site::favicons'))->toBeNull();
});

it('includes all site sets with correct properties', function () {
    $indexingSet = Seo::find('site::indexing');

    expect($indexingSet)->not->toBeNull()
        ->and($indexingSet->id())->toBe('site::indexing')
        ->and($indexingSet->type())->toBe('site')
        ->and($indexingSet->handle())->toBe('indexing')
        ->and($indexingSet->title())->toBe('Indexing')
        ->and($indexingSet->icon())->toBe('hierarchy');

    $socialMediaSet = Seo::find('site::social_media');

    expect($socialMediaSet)->not->toBeNull()
        ->and($socialMediaSet->id())->toBe('site::social_media')
        ->and($socialMediaSet->type())->toBe('site')
        ->and($socialMediaSet->handle())->toBe('social_media')
        ->and($socialMediaSet->title())->toBe('Social Media')
        ->and($socialMediaSet->icon())->toBe('assets');
});

it('includes all collection sets', function () {
    $collectionSets = Seo::whereType('collections');

    expect($collectionSets)->toBeInstanceOf(Collection::class)
        ->and($collectionSets->count())->toBe(3);

    $handles = $collectionSets->map(fn ($set) => $set->handle())->all();

    expect($handles)->toContain('blog')
        ->and($handles)->toContain('pages')
        ->and($handles)->toContain('products');
});

it('can find collection set by id', function () {
    $blogSet = Seo::find('collections::blog');

    expect($blogSet)->toBeInstanceOf(SeoSet::class)
        ->and($blogSet->id())->toBe('collections::blog')
        ->and($blogSet->type())->toBe('collections')
        ->and($blogSet->handle())->toBe('blog')
        ->and($blogSet->title())->toBe('Blog');
});

it('includes collection sets with correct properties', function () {
    $pagesSet = Seo::find('collections::pages');

    expect($pagesSet)->not->toBeNull()
        ->and($pagesSet->id())->toBe('collections::pages')
        ->and($pagesSet->type())->toBe('collections')
        ->and($pagesSet->handle())->toBe('pages')
        ->and($pagesSet->title())->toBe('Pages');

    $productsSet = Seo::find('collections::products');

    expect($productsSet)->not->toBeNull()
        ->and($productsSet->id())->toBe('collections::products')
        ->and($productsSet->type())->toBe('collections')
        ->and($productsSet->handle())->toBe('products')
        ->and($productsSet->title())->toBe('Products');
});

it('includes all taxonomy sets', function () {
    $taxonomySets = Seo::whereType('taxonomies');

    expect($taxonomySets)->toBeInstanceOf(Collection::class)
        ->and($taxonomySets->count())->toBe(2);

    $handles = $taxonomySets->map(fn ($set) => $set->handle())->all();

    expect($handles)->toContain('categories')
        ->and($handles)->toContain('tags');
});

it('can find taxonomy set by id', function () {
    $categoriesSet = Seo::find('taxonomies::categories');

    expect($categoriesSet)->toBeInstanceOf(SeoSet::class)
        ->and($categoriesSet->id())->toBe('taxonomies::categories')
        ->and($categoriesSet->type())->toBe('taxonomies')
        ->and($categoriesSet->handle())->toBe('categories')
        ->and($categoriesSet->title())->toBe('Categories')
        ->and($categoriesSet->icon())->toBe('tags');
});

it('includes taxonomy sets with correct properties', function () {
    $tagsSet = Seo::find('taxonomies::tags');

    expect($tagsSet)->not->toBeNull()
        ->and($tagsSet->id())->toBe('taxonomies::tags')
        ->and($tagsSet->type())->toBe('taxonomies')
        ->and($tagsSet->handle())->toBe('tags')
        ->and($tagsSet->title())->toBe('Tags')
        ->and($tagsSet->icon())->toBe('tags');
});

it('returns null when finding non-existent set', function () {
    expect(Seo::find('site::nonexistent'))->toBeNull()
        ->and(Seo::find('collections::nonexistent'))->toBeNull()
        ->and(Seo::find('taxonomies::nonexistent'))->toBeNull();
});

it('can get sets grouped by type', function () {
    $groups = Seo::groups();

    expect($groups)->toBeInstanceOf(Collection::class)
        ->and($groups->count())->toBe(3);

    $types = $groups->map(fn ($group) => $group->type())->all();

    expect($types)->toContain('site')
        ->and($types)->toContain('collections')
        ->and($types)->toContain('taxonomies');
});

it('sorts sets by handle within each type', function () {
    $collectionSets = Seo::whereType('collections');
    $handles = $collectionSets->map(fn ($set) => $set->handle())->all();

    expect($handles)->toBe(['blog', 'pages', 'products']);

    $taxonomySets = Seo::whereType('taxonomies');
    $handles = $taxonomySets->map(fn ($set) => $set->handle())->all();

    expect($handles)->toBe(['categories', 'tags']);
});
