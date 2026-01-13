<?php

use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Eloquent\SeoSetConfig as EloquentSeoSetConfig;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;

uses(UseEloquentDriver::class);

beforeEach(function () {
    Collection::make('pages')->saveQuietly();
    SeoConfig::make()->seoSet('collections::pages')->save();
});

it('can make a config', function () {
    expect(SeoConfig::make())->toBeInstanceOf(SeoSetConfig::class);
    expect(SeoConfig::make())->toBeInstanceOf(EloquentSeoSetConfig::class);
});

it('can find a config', function () {
    expect(SeoConfig::find('collections::pages'))->toBeInstanceOf(SeoSetConfig::class);
    expect(SeoConfig::find('collections::nonexistent'))->toBeNull();
});

it('can find or make a config', function () {
    $existing = SeoConfig::findOrMake('collections::pages');

    expect($existing)->toBeInstanceOf(SeoSetConfig::class)
        ->and($existing->model())->not->toBeNull();

    $new = SeoConfig::findOrMake('collections::nonexistent');

    expect($new)->toBeInstanceOf(SeoSetConfig::class)
        ->and($new->model())->toBeNull();
});

it('can get all configs', function () {
    $all = SeoConfig::all();

    expect($all)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($all)->toHaveCount(1)
        ->and($all->first())->toBeInstanceOf(SeoSetConfig::class);
});

it('can save a config', function () {
    $config = SeoConfig::find('collections::pages')
        ->set('key', 'value');

    $modelBeforeSave = $config->model();

    expect(Blink::has("advanced-seo.eloquent.set.config.{$config->id()}"))->toBeTrue();

    SeoConfig::save($config);

    expect($modelBeforeSave)->not->toBe($config->model());
    expect(Blink::has("advanced-seo.eloquent.set.config.{$config->id()}"))->toBeFalse();

    $fresh = SeoConfig::find('collections::pages');

    expect($fresh)->toBeInstanceOf(SeoSetConfig::class)
        ->and($fresh->get('key'))->toBe('value');
});

it('can delete a config', function () {
    $config = SeoConfig::find('collections::pages');

    expect(Blink::has("advanced-seo.eloquent.set.config.{$config->id()}"))->toBeTrue();

    SeoConfig::delete($config);

    expect(Blink::has("advanced-seo.eloquent.set.config.{$config->id()}"))->toBeFalse();

    expect(SeoConfig::find('collections::pages'))->toBeNull()
        ->and(SeoConfig::all())->toHaveCount(0);
});
