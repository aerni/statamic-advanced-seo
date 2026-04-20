<?php

use Aerni\AdvancedSeo\Features\Fathom;

it('is enabled when config is true', function () {
    config(['advanced-seo.analytics.fathom' => true]);

    expect(Fathom::enabled())->toBeTrue();
});

it('is disabled when config is false', function () {
    config(['advanced-seo.analytics.fathom' => false]);

    expect(Fathom::enabled())->toBeFalse();
});
