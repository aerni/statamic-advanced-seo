<?php

use Aerni\AdvancedSeo\GraphQL\Types\BaseSiteSetType;
use Aerni\AdvancedSeo\GraphQL\Types\FaviconsSiteSetType;

it('extends BaseSiteSetType', function () {
    expect(new FaviconsSiteSetType)->toBeInstanceOf(BaseSiteSetType::class);
});

it('has the correct name', function () {
    expect(FaviconsSiteSetType::NAME)->toBe('faviconsSiteSet');
});

it('exposes all expected fields', function () {
    expect((new FaviconsSiteSetType)->fields())->toHaveKeys([
        'favicon_svg',
    ]);
});
