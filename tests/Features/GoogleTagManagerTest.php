<?php

use Aerni\AdvancedSeo\Features\GoogleTagManager;

it('is enabled when config is true', function () {
    config(['advanced-seo.analytics.google_tag_manager' => true]);

    expect(GoogleTagManager::enabled())->toBeTrue();
});

it('is disabled when config is false', function () {
    config(['advanced-seo.analytics.google_tag_manager' => false]);

    expect(GoogleTagManager::enabled())->toBeFalse();
});
