<?php

use Aerni\AdvancedSeo\Facades\SocialImageTheme;
use Aerni\AdvancedSeo\GraphQL\Types\BaseContentSetType;
use Aerni\AdvancedSeo\GraphQL\Types\CollectionSetType;

beforeEach(function () {
    config()->set('advanced-seo.social_images.generator.enabled', true);
});

it('extends BaseContentSetType', function () {
    expect(new CollectionSetType)->toBeInstanceOf(BaseContentSetType::class);
});

it('has the correct name', function () {
    expect(CollectionSetType::NAME)->toBe('collectionSet');
});

it('exposes all expected fields', function () {
    SocialImageTheme::shouldReceive('all')->andReturn(collect(['default']));

    expect((new CollectionSetType)->fields())->toHaveKeys([
        'title',
        'description',
        'site_name_position',
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

it('excludes social image fields when generator is disabled', function () {
    config()->set('advanced-seo.social_images.generator.enabled', false);

    $fields = (new CollectionSetType)->fields();

    expect($fields)->not->toHaveKey('generate_social_images');
    expect($fields)->not->toHaveKey('social_images_theme');
});

it('excludes sitemap fields when sitemap is disabled', function () {
    config()->set('advanced-seo.sitemap.enabled', false);

    $fields = (new CollectionSetType)->fields();

    expect($fields)->not->toHaveKey('sitemap_enabled');
    expect($fields)->not->toHaveKey('sitemap_priority');
    expect($fields)->not->toHaveKey('sitemap_change_frequency');
});
