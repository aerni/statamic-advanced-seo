<?php

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Aerni\AdvancedSeo\SeoSets\SeoSetLocalization as StacheSeoSetLocalization;
use Statamic\Facades\Collection;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Collection::make('pages')->saveQuietly();
    Collection::make('articles')->saveQuietly();
    SeoLocalization::make()->seoSet('collections::pages')->locale('english')->save();
    SeoLocalization::make()->seoSet('collections::articles')->locale('english')->save();
});

it('can make a localization', function () {
    expect(SeoLocalization::make())->toBeInstanceOf(SeoSetLocalization::class);
    expect(SeoLocalization::make())->toBeInstanceOf(StacheSeoSetLocalization::class);
});

it('can find a localization', function () {
    expect(SeoLocalization::find('collections::pages::english'))->toBeInstanceOf(SeoSetLocalization::class);
    expect(SeoLocalization::find('collections::nonexistent'))->toBeNull();
});

it('can get all localizations', function () {
    $all = SeoLocalization::all();

    expect($all)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($all)->toHaveCount(2)
        ->and($all->map->id()->all())->toBe([
            'collections::pages::english',
            'collections::articles::english',
        ]);
});

it('can find localizations by set', function () {
    $pagesSets = SeoLocalization::whereSeoSet('collections::pages');
    $articlesSets = SeoLocalization::whereSeoSet('collections::articles');

    expect($pagesSets)->toBeInstanceOf(Illuminate\Support\Collection::class);

    expect($pagesSets)->toHaveCount(1)
        ->and($pagesSets->first()->id())->toBe('collections::pages::english');

    expect($articlesSets)->toHaveCount(1)
        ->and($articlesSets->first()->id())->toBe('collections::articles::english');
});

it('can save a localization', function () {
    $localization = SeoLocalization::find('collections::pages::english');

    $localization->set('seo_title', 'value');

    SeoLocalization::save($localization);

    /* Ensure we're reading from the persisted file, not from memory */
    clearStache();

    $fresh = SeoLocalization::find('collections::pages::english');

    expect($fresh)->toBeInstanceOf(SeoSetLocalization::class)
        ->and($fresh->get('seo_title'))->toBe('value');
});

it('can delete a localization', function () {
    $localization = SeoLocalization::find('collections::pages::english');

    SeoLocalization::delete($localization);

    expect(SeoLocalization::find('collections::pages::english'))->toBeNull()
        ->and(SeoLocalization::all())->toHaveCount(1);
});
