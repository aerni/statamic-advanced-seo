<?php

use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Events\SeoSetConfigSaved;
use Aerni\AdvancedSeo\Events\SeoSetConfigDeleted;
use Aerni\AdvancedSeo\Events\SeoSetLocalizationSaved;
use Aerni\AdvancedSeo\Events\SeoSetLocalizationDeleted;
use Aerni\AdvancedSeo\Subscribers\FlushBlinkCachesSubscriber;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Collection::make('articles')->saveQuietly();
});

it('clears all advanced-seo prefixed caches', function () {
    Blink::put('advanced-seo::test::config', 'value1');
    Blink::put('advanced-seo::features::sitemap::test', 'value2');
    Blink::put('some-other::cache', 'value3');

    app(FlushBlinkCachesSubscriber::class)->clearCaches();

    expect(Blink::has('advanced-seo::test::config'))->toBeFalse();
    expect(Blink::has('advanced-seo::features::sitemap::test'))->toBeFalse();
    expect(Blink::has('some-other::cache'))->toBeTrue();
});

it('clears blink caches when config is saved', function () {
    $seoSet = Seo::find('collections::articles');

    $seoSet->config();

    expect(Blink::has("advanced-seo::collections::articles::config"))->toBeTrue();

    $seoSet->config()->save();

    expect(Blink::has("advanced-seo::collections::articles::config"))->toBeFalse();
});

it('clears blink caches when config is deleted', function () {
    $seoSet = Seo::find('collections::articles');

    $seoSet->config();

    expect(Blink::has("advanced-seo::collections::articles::config"))->toBeTrue();

    $seoSet->config()->delete();

    expect(Blink::has("advanced-seo::collections::articles::config"))->toBeFalse();
});

it('clears blink caches when localization is saved', function () {
    $seoSet = Seo::find('collections::articles');

    $seoSet->localizations();

    expect(Blink::has("advanced-seo::collections::articles::localizations"))->toBeTrue();

    $seoSet->inDefaultSite()->save();

    expect(Blink::has("advanced-seo::collections::articles::localizations"))->toBeFalse();
});

it('clears blink caches when localization is deleted', function () {
    $seoSet = Seo::find('collections::articles');

    $seoSet->localizations();

    expect(Blink::has("advanced-seo::collections::articles::localizations"))->toBeTrue();

    $seoSet->inDefaultSite()->delete();

    expect(Blink::has("advanced-seo::collections::articles::localizations"))->toBeFalse();
});
