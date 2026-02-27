<?php

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Data\SeoSetGroup;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Facades\Collection as StatamicCollection;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    StatamicCollection::make('pages')->saveQuietly();
    StatamicCollection::make('blog')->icon('alt')->saveQuietly();
    StatamicCollection::make('products')->saveQuietly();

    Taxonomy::make('tags')->saveQuietly();
    Taxonomy::make('categories')->saveQuietly();
});

it('can get all seo sets', function () {
    expect(Seo::all())->toHaveCount(6);
});

it('can find an seo set', function () {
    expect(Seo::find('site::defaults'))->toBeInstanceOf(SeoSet::class);
});

it('returns null for non-existent set', function () {
    expect(Seo::find('collections::nonexistent'))->toBeNull();
});

it('can get sets by type', function () {
    $sets = Seo::whereType('collections');
    $types = $sets->map->type()->unique()->all();

    expect($sets)->toBeInstanceOf(Collection::class);
    expect($types)->toBe(['collections']);
});

it('can get set groups', function () {
    $groups = Seo::groups();

    expect($groups)->toBeInstanceOf(Collection::class)
        ->and($groups->count())->toBe(3)
        ->and($groups->map->type()->all())->toBe(['site', 'collections', 'taxonomies'])
        ->and($groups->first())->toBeInstanceOf(SeoSetGroup::class);
});

it('can get a default value from specific set', function () {
    expect(Seo::defaultValue('site::defaults.separator'))->toBe('|');
});

it('can get a default value by type', function () {
    expect(Seo::defaultValue('collections.seo_noindex'))->toBe(false);
});

it("returns a fallback when field doesn't exist", function () {
    expect(Seo::defaultValue('site::defaults.nonexistent', 'fallback_value'))->toBe('fallback_value');
});

it('sorts sets by handle', function () {
    expect(Seo::whereType('collections')->map->handle()->all())->toBe(['blog', 'pages', 'products']);
    expect(Seo::whereType('taxonomies')->map->handle()->all())->toBe(['categories', 'tags']);
});

it('creates all statically defined site sets', function () {
    expect(Seo::whereType('site')->map->handle()->all())
        ->toBe(['defaults']);
});
