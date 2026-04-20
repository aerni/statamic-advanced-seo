<?php

use Aerni\AdvancedSeo\GraphQL\Fields\CollectionSetField;
use Aerni\AdvancedSeo\GraphQL\Fields\SiteSetField;
use Aerni\AdvancedSeo\GraphQL\Fields\TaxonomySetField;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSetType;

it('has the correct name', function () {
    expect(SeoSetType::NAME)->toBe('seoSet');
});

it('exposes all expected fields', function () {
    $fields = (new SeoSetType)->fields();

    expect($fields)->toHaveKeys(['site', 'collection', 'taxonomy']);
    expect($fields['site'])->toBeInstanceOf(SiteSetField::class);
    expect($fields['collection'])->toBeInstanceOf(CollectionSetField::class);
    expect($fields['taxonomy'])->toBeInstanceOf(TaxonomySetField::class);
});
