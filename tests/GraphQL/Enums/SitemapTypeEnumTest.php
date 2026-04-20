<?php

use Aerni\AdvancedSeo\GraphQL\Enums\SitemapTypeEnum;

it('has the correct name', function () {
    expect(SitemapTypeEnum::NAME)->toBe('sitemapType');
});

it('has collection, taxonomy, and custom values', function () {
    expect((new SitemapTypeEnum)->values())->toHaveKeys(['COLLECTION', 'TAXONOMY', 'CUSTOM']);
});

it('includes values in attributes', function () {
    expect((new SitemapTypeEnum)->getAttributes()['values'])->toHaveKeys(['COLLECTION', 'TAXONOMY', 'CUSTOM']);
});
