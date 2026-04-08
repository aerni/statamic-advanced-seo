<?php

use Aerni\AdvancedSeo\Features\EloquentDriver;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;

uses(FakesComposerLock::class);

beforeEach(function () {
    $this->installEloquentDriver();
});

it('is disabled on the free edition', function () {
    useFreeEdition();

    config(['advanced-seo.driver' => 'eloquent']);

    expect(EloquentDriver::enabled())->toBeFalse();
});

it('is disabled when the eloquent driver package is not installed', function () {
    $this->uninstallPackages();

    config(['advanced-seo.driver' => 'eloquent']);

    expect(EloquentDriver::enabled())->toBeFalse();
});

it('is disabled when the driver config is not set to eloquent', function () {
    config(['advanced-seo.driver' => 'file']);

    expect(EloquentDriver::enabled())->toBeFalse();
});

it('is enabled when pro, installed, and configured', function () {
    config(['advanced-seo.driver' => 'eloquent']);

    expect(EloquentDriver::enabled())->toBeTrue();
});
