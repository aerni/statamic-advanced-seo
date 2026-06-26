<?php

use Aerni\AdvancedSeo\Features\Redirects;

it('is enabled with pro and the config flag on', function () {
    config(['advanced-seo.redirects.enabled' => true]);

    expect(Redirects::enabled())->toBeTrue();
});

it('is disabled when the config flag is off', function () {
    config(['advanced-seo.redirects.enabled' => false]);

    expect(Redirects::enabled())->toBeFalse();
});

it('is disabled on the free edition', function () {
    useFreeEdition();

    expect(Redirects::enabled())->toBeFalse();
});
