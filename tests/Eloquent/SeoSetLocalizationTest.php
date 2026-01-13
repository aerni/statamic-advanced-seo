<?php

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization as Contract;
use Aerni\AdvancedSeo\Eloquent\SeoSetLocalization;
use Aerni\AdvancedSeo\Eloquent\SeoSetLocalizationModel;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Aerni\AdvancedSeo\Tests\Concerns\UseEloquentDriver;
use Illuminate\Database\Eloquent\Model;
use Statamic\Facades\Collection;

uses(UseEloquentDriver::class);

beforeEach(function () {
    Collection::make('pages')->saveQuietly();
    SeoLocalization::make()->seoSet('collections::pages')->locale('english')->save();
});

it('can create a localization from an eloquent model', function () {
    $model = app('statamic.eloquent.seo_set_localization.model')::first();

    $result = SeoSetLocalization::fromModel($model);

    expect($result)->toBeInstanceOf(Contract::class)
        ->and($result->model())->toBe($model);
});

it('can convert to an eloquent model', function () {
    $model = SeoLocalization::make()
        ->seoSet('collections::pages')
        ->locale('english')
        ->toModel();

    expect($model)->toBeInstanceOf(SeoSetLocalizationModel::class)
        ->and($model->type)->toBe('collections')
        ->and($model->handle)->toBe('pages')
        ->and($model->locale)->toBe('english');
});

it('can make a model from a contract', function () {
    $localization = SeoLocalization::make()
        ->seoSet('collections::pages')
        ->locale('english');

    $model = SeoSetLocalization::makeModelFromContract($localization);

    expect($model)->toBeInstanceOf(Model::class)
        ->and($model->type)->toBe('collections')
        ->and($model->handle)->toBe('pages')
        ->and($model->locale)->toBe('english');
});

it('can get and set the model', function () {
    $localization = new SeoSetLocalization;
    $model = new SeoSetLocalizationModel;

    expect($localization->model())->toBeNull();

    $result = $localization->model($model);

    expect($result)->toBe($localization)
        ->and($localization->model())->toBe($model);
});
