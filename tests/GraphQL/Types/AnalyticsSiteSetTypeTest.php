<?php

use Aerni\AdvancedSeo\GraphQL\Types\AnalyticsSiteSetType;
use Aerni\AdvancedSeo\GraphQL\Types\BaseSiteSetType;

it('extends BaseSiteSetType', function () {
    expect(new AnalyticsSiteSetType)->toBeInstanceOf(BaseSiteSetType::class);
});

it('has the correct name', function () {
    expect(AnalyticsSiteSetType::NAME)->toBe('analyticsSiteSet');
});

it('exposes all expected fields', function () {
    expect((new AnalyticsSiteSetType)->fields())->toHaveKeys([
        'use_fathom',
        'fathom_id',
        'fathom_spa',
        'use_cloudflare_web_analytics',
        'cloudflare_web_analytics',
        'use_google_tag_manager',
        'google_tag_manager',
    ]);
});

it('excludes fathom fields when fathom is disabled', function () {
    config()->set('advanced-seo.analytics.fathom', false);

    $fields = (new AnalyticsSiteSetType)->fields();

    expect($fields)->not->toHaveKey('use_fathom');
    expect($fields)->not->toHaveKey('fathom_id');
    expect($fields)->not->toHaveKey('fathom_spa');
});

it('excludes cloudflare fields when cloudflare is disabled', function () {
    config()->set('advanced-seo.analytics.cloudflare_analytics', false);

    $fields = (new AnalyticsSiteSetType)->fields();

    expect($fields)->not->toHaveKey('use_cloudflare_web_analytics');
    expect($fields)->not->toHaveKey('cloudflare_web_analytics');
});

it('excludes google tag manager fields when google tag manager is disabled', function () {
    config()->set('advanced-seo.analytics.google_tag_manager', false);

    $fields = (new AnalyticsSiteSetType)->fields();

    expect($fields)->not->toHaveKey('use_google_tag_manager');
    expect($fields)->not->toHaveKey('google_tag_manager');
});
