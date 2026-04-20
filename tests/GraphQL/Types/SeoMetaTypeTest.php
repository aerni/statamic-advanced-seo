<?php

use Aerni\AdvancedSeo\GraphQL\Types\SeoMetaType;

it('has the correct name', function () {
    expect(SeoMetaType::NAME)->toBe('seoMeta');
});

it('exposes all expected fields', function () {
    expect((new SeoMetaType)->fields())->toHaveKeys([
        'computed',
        'raw',
        'view',
    ]);
});
