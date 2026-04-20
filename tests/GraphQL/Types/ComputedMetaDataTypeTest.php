<?php

use Aerni\AdvancedSeo\GraphQL\Types\ComputedMetaDataType;

it('has the correct name', function () {
    expect(ComputedMetaDataType::NAME)->toBe('computedMetaData');
});

it('exposes all expected fields', function () {
    expect((new ComputedMetaDataType)->fields())->toHaveKeys([
        'og_image_preset',
        'twitter_card',
        'twitter_handle',
        'twitter_image_preset',
        'indexing',
        'locale',
        'hreflang',
        'canonical',
        'site_schema',
        'breadcrumbs',
    ]);
});
