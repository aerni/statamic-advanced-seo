<?php

use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapsType;

it('has the correct name', function () {
    expect(SeoSitemapsType::NAME)->toBe('seoSitemaps');
});

it('exposes all expected fields', function () {
    expect((new SeoSitemapsType)->fields())->toHaveKeys([
        'collection',
        'taxonomy',
        'custom',
    ]);
});
