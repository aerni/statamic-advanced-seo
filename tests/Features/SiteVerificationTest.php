<?php

use Aerni\AdvancedSeo\Features\SiteVerification;

it('is enabled when config is true', function () {
    config(['advanced-seo.site_verification' => true]);

    expect(SiteVerification::enabled())->toBeTrue();
});

it('is disabled when config is false', function () {
    config(['advanced-seo.site_verification' => false]);

    expect(SiteVerification::enabled())->toBeFalse();
});
