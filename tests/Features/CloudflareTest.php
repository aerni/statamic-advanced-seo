<?php

use Aerni\AdvancedSeo\Features\Cloudflare;

it('is enabled when config is true', function () {
    config(['advanced-seo.analytics.cloudflare_analytics' => true]);

    expect(Cloudflare::enabled())->toBeTrue();
});

it('is disabled when config is false', function () {
    config(['advanced-seo.analytics.cloudflare_analytics' => false]);

    expect(Cloudflare::enabled())->toBeFalse();
});
