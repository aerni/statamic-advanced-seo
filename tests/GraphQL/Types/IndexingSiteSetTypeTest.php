<?php

use Aerni\AdvancedSeo\GraphQL\Types\BaseSiteSetType;
use Aerni\AdvancedSeo\GraphQL\Types\IndexingSiteSetType;

it('extends BaseSiteSetType', function () {
    expect(new IndexingSiteSetType)->toBeInstanceOf(BaseSiteSetType::class);
});

it('has the correct name', function () {
    expect(IndexingSiteSetType::NAME)->toBe('indexingSiteSet');
});

it('exposes all expected fields', function () {
    expect((new IndexingSiteSetType)->fields())->toHaveKeys([
        'noindex',
        'nofollow',
        'google_site_verification_code',
        'bing_site_verification_code',
    ]);
});

it('excludes site verification fields when site verification is disabled', function () {
    config()->set('advanced-seo.site_verification', false);

    $fields = (new IndexingSiteSetType)->fields();

    expect($fields)->not->toHaveKey('google_site_verification_code');
    expect($fields)->not->toHaveKey('bing_site_verification_code');
});
