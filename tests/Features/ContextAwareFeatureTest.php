<?php

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Enums\Scope;
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

        $data = new Context(
            type: 'collections',
            handle: 'pages',
            scope: Scope::CONFIG,
            site: 'english',
        );

        expect(Sitemap::enabled($data))->toBeTrue();
    });

    it('hides in localization context when disabled', function () {
        $set = Seo::find('collections::pages');
        $set->config()->set('sitemap', false)->save();

        $data = new Context(
            type: 'collections',
            handle: 'pages',
            scope: Scope::LOCALIZATION,
            site: 'english',
        );

        expect(Sitemap::enabled($data))->toBeFalse();
    });

    it('hides in content context when disabled', function () {
        $set = Seo::find('collections::pages');
        $set->config()->set('sitemap', false)->save();

        $data = new Context(
            type: 'collections',
            handle: 'pages',
            scope: Scope::CONTENT,
            site: 'english',
        );

        expect(Sitemap::enabled($data))->toBeFalse();
    });

    it('shows in all contexts when enabled', function () {
        $set = Seo::find('collections::pages');
        $set->config()->set('sitemap', true)->save();

        foreach ([Scope::CONFIG, Scope::LOCALIZATION, Scope::CONTENT] as $scope) {
            $context = new Context(
                type: 'collections',
                handle: 'pages',
                scope: $scope,
                site: 'english',
            );

            expect(Sitemap::enabled($context))->toBeTrue("Failed for scope: {$scope->value}");
        }
    });
});

describe('Social Images Generator Feature', function () {
    it('always shows in config context even when disabled', function () {
        config(['advanced-seo.social_images.generator.enabled' => true]);

        $set = Seo::find('collections::pages');
        $set->config()->set('social_images_generator', false)->save();

        $data = new Context(
            type: 'collections',
            handle: 'pages',
            scope: Scope::CONFIG,
            site: 'english',
        );

        expect(SocialImagesGenerator::enabled($data))->toBeTrue();
    });

    it('hides in localization context when disabled', function () {
        config(['advanced-seo.social_images.generator.enabled' => true]);

        $set = Seo::find('collections::pages');
        $set->config()->set('social_images_generator', false)->save();

        $data = new Context(
            type: 'collections',
            handle: 'pages',
            scope: Scope::LOCALIZATION,
            site: 'english',
        );

        expect(SocialImagesGenerator::enabled($data))->toBeFalse();
    });

    it('hides in content context when disabled', function () {
        config(['advanced-seo.social_images.generator.enabled' => true]);

        $set = Seo::find('collections::pages');
        $set->config()->set('social_images_generator', false)->save();

        $data = new Context(
            type: 'collections',
            handle: 'pages',
            scope: Scope::CONTENT,
            site: 'english',
        );

        expect(SocialImagesGenerator::enabled($data))->toBeFalse();
    });

    it('shows in all contexts when enabled', function () {
        config(['advanced-seo.social_images.generator.enabled' => true]);

        $set = Seo::find('collections::pages');
        $set->config()->set('social_images_generator', true)->save();

        foreach ([Scope::CONFIG, Scope::LOCALIZATION, Scope::CONTENT] as $scope) {
            $context = new Context(
                type: 'collections',
                handle: 'pages',
                scope: $scope,
                site: 'english',
            );

            expect(SocialImagesGenerator::enabled($context))->toBeTrue("Failed for scope: {$scope->value}");
        }
    });
});

describe('Context Scope Helpers', function () {
    it('identifies config scope', function () {
        $context = new Context('collections', 'pages', Scope::CONFIG, 'en');
        expect($context->isConfig())->toBeTrue();
        expect($context->isLocalization())->toBeFalse();
        expect($context->isContent())->toBeFalse();
    });

    it('identifies localization scope', function () {
        $context = new Context('collections', 'pages', Scope::LOCALIZATION, 'en');
        expect($context->isConfig())->toBeFalse();
        expect($context->isLocalization())->toBeTrue();
        expect($context->isContent())->toBeFalse();
    });

    it('identifies content scope', function () {
        $context = new Context('collections', 'pages', Scope::CONTENT, 'en');
        expect($context->isConfig())->toBeFalse();
        expect($context->isLocalization())->toBeFalse();
        expect($context->isContent())->toBeTrue();
    });
});
