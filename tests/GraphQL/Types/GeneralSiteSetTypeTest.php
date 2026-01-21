<?php

use Aerni\AdvancedSeo\GraphQL\Types\BaseSiteSetType;
use Aerni\AdvancedSeo\GraphQL\Types\GeneralSiteSetType;

it('extends BaseSiteSetType', function () {
    expect(new GeneralSiteSetType)->toBeInstanceOf(BaseSiteSetType::class);
});

it('has the correct name', function () {
    expect(GeneralSiteSetType::NAME)->toBe('generalSiteSet');
});

it('exposes all expected fields', function () {
    expect((new GeneralSiteSetType)->fields())->toHaveKeys([
        'site_name',
        'title_separator',
        'site_json_ld_type',
        'organization_name',
        'organization_logo',
        'person_name',
        'site_json_ld',
        'use_breadcrumbs',
    ]);
});
