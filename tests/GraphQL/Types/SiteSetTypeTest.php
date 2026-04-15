<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\GraphQL\Types\SiteSetType;
use GraphQL\Type\Definition\ResolveInfo;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

it('has the correct name', function () {
    expect(SiteSetType::NAME)->toBe('siteSet');
});

it('resolves data from SeoSetLocalization using SeoSetLocalizationResolver', function () {
    Seo::find('site::defaults')
        ->inDefaultSite()
        ->set('site_name', 'Test Site')
        ->save();

    clearStache();

    $localization = Seo::find('site::defaults')->inDefaultSite();

    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'site_name';

    $result = (new SiteSetType)->fields()['site_name']['resolve']($localization, [], null, $info);

    expect($result)->toBe('Test Site');
});

it('exposes all expected fields', function () {
    $fields = (new SiteSetType)->fields();

    expect($fields)->toHaveKeys([
        // General: Titles
        'site_name',
        'separator',
        // General: Knowledge Graph
        'site_json_ld_type',
        'use_breadcrumbs',
        'organization_name',
        'organization_logo',
        'person_name',
        'site_json_ld',
        // General: Favicons
        'favicon_svg',
        // Social Media
        'og_image',
        'twitter_card',
        'twitter_handle',
        // Indexing: Crawling
        'noindex',
        // Indexing: Site Verification
        'google_site_verification_code',
        'bing_site_verification_code',
        // Analytics
        'fathom_id',
        'fathom_spa',
        'cloudflare_beacon_token',
        'gtm_container_id',
    ]);
});

it('excludes analytics fields when all analytics features are disabled', function () {
    config()->set('advanced-seo.analytics.fathom', false);
    config()->set('advanced-seo.analytics.cloudflare_analytics', false);
    config()->set('advanced-seo.analytics.google_tag_manager', false);

    flushBlink();

    $fields = (new SiteSetType)->fields();

    expect($fields)->not->toHaveKey('use_fathom');
    expect($fields)->not->toHaveKey('use_cloudflare_web_analytics');
    expect($fields)->not->toHaveKey('use_google_tag_manager');
});

it('excludes favicons fields when favicons is disabled', function () {
    config()->set('advanced-seo.favicons.enabled', false);

    flushBlink();

    $fields = (new SiteSetType)->fields();

    expect($fields)->not->toHaveKey('favicon_svg');
});

it('excludes site verification fields when site verification is disabled', function () {
    config()->set('advanced-seo.site_verification', false);

    flushBlink();

    $fields = (new SiteSetType)->fields();

    expect($fields)->not->toHaveKey('google_site_verification_code');
    expect($fields)->not->toHaveKey('bing_site_verification_code');
});
