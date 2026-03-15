<?php

use Aerni\AdvancedSeo\Facades\SocialImageTheme;
use Aerni\AdvancedSeo\GraphQL\Types\BaseContentSetType;
use Aerni\AdvancedSeo\GraphQL\Types\TaxonomySetType;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;

uses(FakesComposerLock::class);

it('extends BaseContentSetType', function () {
    expect(new TaxonomySetType)->toBeInstanceOf(BaseContentSetType::class);
});

it('has the correct name', function () {
    expect(TaxonomySetType::NAME)->toBe('taxonomySet');
});

it('exposes all expected fields', function () {
    $this->installScreenshotPackage();

    SocialImageTheme::shouldReceive('all')->andReturn(collect(['default']));

    expect((new TaxonomySetType)->fields())->toHaveKeys([
        'title',
        'description',
        'generate_social_images',
        'social_images_theme',
        'og_image',
        'og_title',
        'og_description',
        'noindex',
        'nofollow',
        'canonical_type',
        'canonical_entry',
        'canonical_custom',
        'sitemap_enabled',
        'sitemap_priority',
        'sitemap_change_frequency',
        'json_ld',
    ]);
});

it('excludes social image fields when the screenshot package is not installed', function () {
    $fields = (new TaxonomySetType)->fields();

    expect($fields)->not->toHaveKey('generate_social_images');
    expect($fields)->not->toHaveKey('social_images_theme');
});

it('excludes sitemap fields when sitemap is disabled', function () {
    config()->set('advanced-seo.sitemap.enabled', false);

    $fields = (new TaxonomySetType)->fields();

    expect($fields)->not->toHaveKey('sitemap_enabled');
    expect($fields)->not->toHaveKey('sitemap_priority');
    expect($fields)->not->toHaveKey('sitemap_change_frequency');
});
