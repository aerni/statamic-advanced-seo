<?php

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Enums\Context;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Sitemap;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();
});

describe('Sitemap Feature', function () {
    it('always shows in config context even when disabled', function () {
        $set = Seo::find('collections::pages');
        $set->config()->set('sitemap', false)->save();

        $data = new DefaultsData(
            type: 'collections',
            handle: 'pages',
            locale: 'english',
            sites: collect(['english']),
            context: Context::CONFIG,
        );

        expect(Sitemap::enabled($data))->toBeTrue();
    });

    it('hides in localization context when disabled', function () {
        $set = Seo::find('collections::pages');
        $set->config()->set('sitemap', false)->save();

        $data = new DefaultsData(
            type: 'collections',
            handle: 'pages',
            locale: 'english',
            sites: collect(['english']),
            context: Context::LOCALIZATION,
        );

        expect(Sitemap::enabled($data))->toBeFalse();
    });

    it('hides in content context when disabled', function () {
        $set = Seo::find('collections::pages');
        $set->config()->set('sitemap', false)->save();

        $data = new DefaultsData(
            type: 'collections',
            handle: 'pages',
            locale: 'english',
            sites: collect(['english']),
            context: Context::CONTENT,
        );

        expect(Sitemap::enabled($data))->toBeFalse();
    });

    it('shows in all contexts when enabled', function () {
        $set = Seo::find('collections::pages');
        $set->config()->set('sitemap', true)->save();

        foreach ([Context::CONFIG, Context::LOCALIZATION, Context::CONTENT] as $context) {
            $data = new DefaultsData(
                type: 'collections',
                handle: 'pages',
                locale: 'english',
                sites: collect(['english']),
                context: $context,
            );

            expect(Sitemap::enabled($data))->toBeTrue("Failed for context: {$context->value}");
        }
    });
});

describe('Social Images Generator Feature', function () {
    it('always shows in config context even when disabled', function () {
        config(['advanced-seo.social_images.generator.enabled' => true]);

        $set = Seo::find('collections::pages');
        $set->config()->set('social_images_generator', false)->save();

        $data = new DefaultsData(
            type: 'collections',
            handle: 'pages',
            locale: 'english',
            sites: collect(['english']),
            context: Context::CONFIG,
        );

        expect(SocialImagesGenerator::enabled($data))->toBeTrue();
    });

    it('hides in localization context when disabled', function () {
        config(['advanced-seo.social_images.generator.enabled' => true]);

        $set = Seo::find('collections::pages');
        $set->config()->set('social_images_generator', false)->save();

        $data = new DefaultsData(
            type: 'collections',
            handle: 'pages',
            locale: 'english',
            sites: collect(['english']),
            context: Context::LOCALIZATION,
        );

        expect(SocialImagesGenerator::enabled($data))->toBeFalse();
    });

    it('hides in content context when disabled', function () {
        config(['advanced-seo.social_images.generator.enabled' => true]);

        $set = Seo::find('collections::pages');
        $set->config()->set('social_images_generator', false)->save();

        $data = new DefaultsData(
            type: 'collections',
            handle: 'pages',
            locale: 'english',
            sites: collect(['english']),
            context: Context::CONTENT,
        );

        expect(SocialImagesGenerator::enabled($data))->toBeFalse();
    });

    it('shows in all contexts when enabled', function () {
        config(['advanced-seo.social_images.generator.enabled' => true]);

        $set = Seo::find('collections::pages');
        $set->config()->set('social_images_generator', true)->save();

        foreach ([Context::CONFIG, Context::LOCALIZATION, Context::CONTENT] as $context) {
            $data = new DefaultsData(
                type: 'collections',
                handle: 'pages',
                locale: 'english',
                sites: collect(['english']),
                context: $context,
            );

            expect(SocialImagesGenerator::enabled($data))->toBeTrue("Failed for context: {$context->value}");
        }
    });
});

describe('DefaultsData Context Helpers', function () {
    it('identifies config context', function () {
        $data = new DefaultsData('collections', 'pages', 'en', collect(), Context::CONFIG);
        expect($data->isConfigContext())->toBeTrue();
        expect($data->isLocalizationContext())->toBeFalse();
        expect($data->isContentContext())->toBeFalse();
    });

    it('identifies localization context', function () {
        $data = new DefaultsData('collections', 'pages', 'en', collect(), Context::LOCALIZATION);
        expect($data->isConfigContext())->toBeFalse();
        expect($data->isLocalizationContext())->toBeTrue();
        expect($data->isContentContext())->toBeFalse();
    });

    it('identifies content context', function () {
        $data = new DefaultsData('collections', 'pages', 'en', collect(), Context::CONTENT);
        expect($data->isConfigContext())->toBeFalse();
        expect($data->isLocalizationContext())->toBeFalse();
        expect($data->isContentContext())->toBeTrue();
    });
});
