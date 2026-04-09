<?php

use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Enums\Scope;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Sitemap;
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

it('is disabled on the free edition', function () {
    useFreeEdition();

    config(['advanced-seo.sitemap.enabled' => true]);

    expect(Sitemap::enabled())->toBeFalse();
});

it('is enabled when config is true', function () {
    config(['advanced-seo.sitemap.enabled' => true]);

    expect(Sitemap::enabled())->toBeTrue();
});

it('is disabled when config is false', function () {
    config(['advanced-seo.sitemap.enabled' => false]);

    expect(Sitemap::enabled())->toBeFalse();
});

it('is enabled when no context is provided', function () {
    config(['advanced-seo.sitemap.enabled' => true]);

    expect(Sitemap::enabled(null))->toBeTrue();
});

it('is enabled in config scope even when seoSet sitemap is disabled', function () {
    config(['advanced-seo.sitemap.enabled' => true]);

    Seo::find('collections::pages')
        ->config()
        ->set('sitemap', false)
        ->save();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Config,
        site: 'english',
    );

    expect(Sitemap::enabled($context))->toBeTrue();
});

it('is disabled if the seoSet is disabled', function () {
    config(['advanced-seo.sitemap.enabled' => true]);

    Seo::find('collections::pages')
        ->config()
        ->enabled(false)
        ->save();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Localization,
        site: 'english',
    );

    expect(Sitemap::enabled($context))->toBeFalse();
});

it('is disabled if the sitemap is disabled in the config', function () {
    config(['advanced-seo.sitemap.enabled' => true]);

    Seo::find('collections::pages')
        ->config()
        ->set('sitemap', false)
        ->save();

    $context = new Context(
        parent: Collection::find('pages'),
        type: 'collections',
        handle: 'pages',
        scope: Scope::Content,
        site: 'english',
    );

    expect(Sitemap::enabled($context))->toBeFalse();
});

it('shows in all contexts when enabled', function () {
    config(['advanced-seo.sitemap.enabled' => true]);

    Seo::find('collections::pages')
        ->config()
        ->set('sitemap', true)
        ->save();

    foreach ([Scope::Config, Scope::Localization, Scope::Content] as $scope) {
        $context = new Context(
            parent: Collection::find('pages'),
            type: 'collections',
            handle: 'pages',
            scope: $scope,
            site: 'english',
        );

        expect(Sitemap::enabled($context))->toBeTrue("Failed for scope: {$scope->value}");
    }
});
