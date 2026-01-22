<?php

use Aerni\AdvancedSeo\Features\Favicons;

it('is enabled when config is true', function () {
    config(['advanced-seo.favicons.enabled' => true]);

    expect(Favicons::enabled())->toBeTrue();
});

it('is disabled when config is false', function () {
    config(['advanced-seo.favicons.enabled' => false]);

    expect(Favicons::enabled())->toBeFalse();
});
