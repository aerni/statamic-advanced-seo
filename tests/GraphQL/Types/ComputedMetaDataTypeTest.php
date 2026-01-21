<?php

use Aerni\AdvancedSeo\GraphQL\Types\ComputedMetaDataType;

it('has the correct name', function () {
    expect(ComputedMetaDataType::NAME)->toBe('computedMetaData');
});

it('exposes all expected fields', function () {
    expect((new ComputedMetaDataType)->fields())->toHaveKeys([
        'site_name',
        'title',
        'og_image',
        'og_image_preset',
        'og_title',
        'twitter_image',
        'twitter_image_preset',
        'twitter_title',
        'twitter_handle',
        'indexing',
        'locale',
        'hreflang',
        'canonical',
        'site_schema',
        'breadcrumbs',
    ]);
});
