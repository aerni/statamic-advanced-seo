<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Collection::make('articles')->saveQuietly();
    Collection::make('blog')->saveQuietly();
});

it('caches set-specific config, localizations, and parent', function () {
    $set = Seo::find('collections::articles');

    /* Trigger caching */
    $set->config();
    $set->localizations();
    $set->parent();

    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeTrue();
    expect(Blink::has('advanced-seo::collections::articles::localizations'))->toBeTrue();
    expect(Blink::has('advanced-seo::collections::articles::parent'))->toBeTrue();
});

it('clears set-specific caches when config is saved', function () {
    $set = Seo::find('collections::articles');

    /* Trigger caching */
    $set->config();
    $set->localizations();
    $set->parent();

    /* Manually add feature cache (can't trigger naturally in test) */
    Blink::put('advanced-seo::collections::articles::features::sitemap::default', true);

    $set->config()->save();

    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::localizations'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::parent'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::features::sitemap::default'))->toBeFalse();
});

it('clears set-specific caches when localization is saved', function () {
    $set = Seo::find('collections::articles');

    /* Trigger caching */
    $set->config();
    $set->localizations();
    $set->parent();

    /* Manually add feature cache (can't trigger naturally in test) */
    Blink::put('advanced-seo::collections::articles::features::sitemap::default', true);

    $set->inDefaultSite()->save();

    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::localizations'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::parent'))->toBeFalse();
    expect(Blink::has('advanced-seo::collections::articles::features::sitemap::default'))->toBeFalse();
});

it('only clears caches for the specific set being saved', function () {
    $articlesSet = Seo::find('collections::articles');
    $articlesSet->config();

    $blogSet = Seo::find('collections::blog');
    $blogSet->config();

    $articlesSet->config()->save();

    /* Articles caches should be cleared */
    expect(Blink::has('advanced-seo::collections::articles::config'))->toBeFalse();

    /* Blog caches should remain */
    expect(Blink::has('advanced-seo::collections::blog::config'))->toBeTrue();
});
