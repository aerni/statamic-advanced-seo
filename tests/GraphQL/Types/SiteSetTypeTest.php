<?php

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\GraphQL\Types\SiteSetType;
use GraphQL\Type\Definition\ResolveInfo;

it('has the correct name', function () {
    expect(SiteSetType::NAME)->toBe('siteSet');
});

it('exposes fields for all site sets', function () {
    expect((new SiteSetType)->fields())->toHaveKeys([
        'general',
        'indexing',
        'socialMedia',
        'analytics',
        'favicons',
    ]);
});

it('resolves site set fields through SeoSetResolver', function () {
    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'general';

    $result = (new SiteSetType)->fields()['general']['resolve']([], [], null, $info);

    expect($result)->toBeInstanceOf(SeoSetLocalization::class);
    expect($result->handle())->toBe('general');
});

it('excludes analytics field when all analytics features are disabled', function () {
    config()->set('advanced-seo.analytics.fathom', false);
    config()->set('advanced-seo.analytics.cloudflare_analytics', false);
    config()->set('advanced-seo.analytics.google_tag_manager', false);

    // Clear registry cache after config change
    flushBlink();

    expect((new SiteSetType)->fields())->not->toHaveKey('analytics');
});

it('excludes favicons field when favicons is disabled', function () {
    config()->set('advanced-seo.favicons.enabled', false);

    // Clear registry cache after config change
    flushBlink();

    expect((new SiteSetType)->fields())->not->toHaveKey('favicons');
});
