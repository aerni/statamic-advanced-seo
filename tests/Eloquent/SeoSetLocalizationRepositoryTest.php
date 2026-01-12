<?php

use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Aerni\AdvancedSeo\Eloquent\SeoSetLocalization as EloquentSeoSetLocalization;

uses(UseEloquentDriver::class);

beforeEach(function () {
    Collection::make('pages')->saveQuietly();
    Collection::make('articles')->saveQuietly();
    SeoLocalization::make()->seoSet('collections::pages')->locale('english')->save();
    SeoLocalization::make()->seoSet('collections::articles')->locale('english')->save();
});

it('can make a localization', function () {
    expect(SeoLocalization::make())->toBeInstanceOf(SeoSetLocalization::class);
    expect(SeoLocalization::make())->toBeInstanceOf(EloquentSeoSetLocalization::class);
});

it('can find a localization', function () {
    expect(SeoLocalization::find('collections::pages::english'))->toBeInstanceOf(SeoSetLocalization::class);
    expect(SeoLocalization::find('collections::nonexistent::english'))->toBeNull();
});

it('can get all localizations', function () {
    $all = SeoLocalization::all();

    expect($all)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($all)->toHaveCount(2)
        ->and($all->map->id()->sort()->values()->all())->toBe([
            'collections::articles::english',
            'collections::pages::english',
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
    $localization = SeoLocalization::find('collections::pages::english')
        ->set('seo_title', 'value');

    $modelBeforeSave = $localization->model();

    expect(Blink::has("advanced-seo.eloquent.set.localization.{$localization->id()}"))->toBeTrue();

    SeoLocalization::save($localization);

    expect($modelBeforeSave)->not->toBe($localization->model());
    expect(Blink::has("advanced-seo.eloquent.set.localization.{$localization->id()}"))->toBeFalse();

    $fresh = SeoLocalization::find('collections::pages::english');

    expect($fresh)->toBeInstanceOf(SeoSetLocalization::class)
        ->and($fresh->get('seo_title'))->toBe('value');
});

it('can delete a localization', function () {
    $localization = SeoLocalization::find('collections::pages::english');

    expect(Blink::has("advanced-seo.eloquent.set.localization.{$localization->id()}"))->toBeTrue();

    SeoLocalization::delete($localization);

    expect(Blink::has("advanced-seo.eloquent.set.localization.{$localization->id()}"))->toBeFalse();

    expect(SeoLocalization::find('collections::pages::english'))->toBeNull()
        ->and(SeoLocalization::all())->toHaveCount(1);
});
