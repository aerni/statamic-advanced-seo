<?php

use Aerni\AdvancedSeo\Features\Redirects;

beforeEach(function () {
    config(['advanced-seo.redirects.enabled' => true]);
});

it('is disabled on the free edition', function () {
    useFreeEdition();

    expect(Redirects::enabled())->toBeFalse();
});

it('is enabled on the pro edition', function () {
    expect(Redirects::enabled())->toBeTrue();
});

it('is disabled when config is false', function () {
    config(['advanced-seo.redirects.enabled' => false]);

    expect(Redirects::enabled())->toBeFalse();
});

it('ships disabled by default', function () {
    $config = require __DIR__.'/../../config/advanced-seo.php';

    expect($config['redirects']['enabled'])->toBeFalse();
});
