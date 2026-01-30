<?php

use Aerni\AdvancedSeo\Contracts\SeoSetConfig as Contract;
use Aerni\AdvancedSeo\Eloquent\SeoSetConfig;
use Aerni\AdvancedSeo\Eloquent\SeoSetConfigModel;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Statamic\Facades\Collection;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(UseEloquentDriver::class, PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Collection::make('pages')->saveQuietly();
    SeoConfig::make()->seoSet('collections::pages')->save();
});

it('can create a config from an eloquent model', function () {
    $model = app('statamic.eloquent.seo_set_config.model')::first();

    $result = SeoSetConfig::fromModel($model);

    expect($result)->toBeInstanceOf(Contract::class)
        ->and($result->model())->toBe($model);
});

it('can convert to an eloquent model', function () {
    $model = SeoConfig::make()
        ->seoSet('collections::pages')
        ->toModel();

    expect($model)->toBeInstanceOf(SeoSetConfigModel::class)
        ->and($model->type)->toBe('collections')
        ->and($model->handle)->toBe('pages');
});

it('can make a model from a contract', function () {
    $config = SeoConfig::make()->seoSet('collections::pages');

    $model = SeoSetConfig::makeModelFromContract($config);

    expect($model)->toBeInstanceOf(SeoSetConfigModel::class)
        ->and($model->type)->toBe('collections')
        ->and($model->handle)->toBe('pages');
});

it('can get and set the model', function () {
    $config = new SeoSetConfig;
    $model = new SeoSetConfigModel;

    expect($config->model())->toBeNull();

    $result = $config->model($model);

    expect($result)->toBe($config)
        ->and($config->model())->toBe($model);
});
