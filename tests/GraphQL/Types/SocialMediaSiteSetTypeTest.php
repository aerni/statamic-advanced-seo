<?php

use Aerni\AdvancedSeo\GraphQL\Types\BaseSiteSetType;
use Aerni\AdvancedSeo\GraphQL\Types\SocialMediaSiteSetType;

it('extends BaseSiteSetType', function () {
    expect(new SocialMediaSiteSetType)->toBeInstanceOf(BaseSiteSetType::class);
});

it('has the correct name', function () {
    expect(SocialMediaSiteSetType::NAME)->toBe('socialMediaSiteSet');
});

it('exposes all expected fields', function () {
    expect((new SocialMediaSiteSetType)->fields())->toHaveKeys([
        'og_image',
        'twitter_summary_image',
        'twitter_summary_large_image',
        'twitter_handle',
    ]);
});
