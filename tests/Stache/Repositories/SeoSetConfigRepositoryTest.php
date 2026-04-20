<?php

use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Aerni\AdvancedSeo\SeoSets\SeoSetConfig as StacheSeoSetConfig;
use Statamic\Facades\Collection;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Collection::make('pages')->saveQuietly();
    SeoConfig::make()->seoSet('collections::pages')->save();
});

it('can make a config', function () {
    expect(SeoConfig::make())->toBeInstanceOf(SeoSetConfig::class);
    expect(SeoConfig::make())->toBeInstanceOf(StacheSeoSetConfig::class);
});

it('can find a config', function () {
    expect(SeoConfig::find('collections::pages'))->toBeInstanceOf(SeoSetConfig::class);
    expect(SeoConfig::find('collections::nonexistent'))->toBeNull();
});

it('can find or make a config', function () {
    $existing = SeoConfig::findOrMake('collections::pages');

    expect($existing)->toBeInstanceOf(SeoSetConfig::class)
        ->and($existing->initialPath())->not->toBeNull();

    $new = SeoConfig::findOrMake('collections::nonexistent');

    expect($new)->toBeInstanceOf(SeoSetConfig::class)
        ->and($new->initialPath())->toBeNull();
});

it('can get all configs', function () {
    $all = SeoConfig::all();

    expect($all)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($all)->toHaveCount(1)
        ->and($all->first())->toBeInstanceOf(SeoSetConfig::class);
});

it('can save a config', function () {
    config(['advanced-seo.sitemap.enabled' => true]);
    flushBlink();

    $config = SeoConfig::find('collections::pages');

    $config->set('sitemap', false);

    SeoConfig::save($config);

    /* Ensure we're reading from the persisted file, not from memory */
    clearStache();

    $fresh = SeoConfig::find('collections::pages');

    expect($fresh)->toBeInstanceOf(SeoSetConfig::class)
        ->and($fresh->get('sitemap'))->toBeFalse();
});

it('can delete a config', function () {
    $config = SeoConfig::find('collections::pages');

    SeoConfig::delete($config);

    expect(SeoConfig::find('collections::pages'))->toBeNull()
        ->and(SeoConfig::all())->toHaveCount(0);
});
