<?php

use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\GraphQL\Types\RawMetaDataType;
use Aerni\AdvancedSeo\Tests\Concerns\FakesComposerLock;

uses(FakesComposerLock::class);

it('has the correct name', function () {
    expect(RawMetaDataType::NAME)->toBe('rawMetaData');
});

it('exposes all expected fields', function () {
    $this->installScreenshotPackage();

    config(['advanced-seo.social_images.generator.enabled' => true]);
    config(['advanced-seo.sitemap.enabled' => true]);

    SocialImage::shouldReceive('themes->all')->andReturn(collect(['default']));

    expect((new RawMetaDataType)->fields())->toHaveKeys([
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
    $fields = (new RawMetaDataType)->fields();

    expect($fields)->not->toHaveKey('generate_social_images');
    expect($fields)->not->toHaveKey('social_images_theme');
});

it('excludes sitemap fields when sitemap is disabled', function () {
    config()->set('advanced-seo.sitemap.enabled', false);

    $fields = (new RawMetaDataType)->fields();

    expect($fields)->not->toHaveKey('sitemap_enabled');
    expect($fields)->not->toHaveKey('sitemap_priority');
    expect($fields)->not->toHaveKey('sitemap_change_frequency');
});
