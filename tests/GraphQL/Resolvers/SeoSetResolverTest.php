<?php

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\GraphQL\Resolvers\SeoSetResolver;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    Collection::make('pages')
        ->sites(['english', 'german'])
        ->saveQuietly();
});

it('resolves the default localization when no site is specified', function () {
    $result = SeoSetResolver::resolve('collections::pages');

    expect($result->locale())->toBe(Site::default()->handle());
});

it('resolves a specific localization when site is specified', function () {
    $result = SeoSetResolver::resolve('collections::pages', 'german');

    expect($result)->toBeInstanceOf(SeoSetLocalization::class);
    expect($result->locale())->toBe('german');
});

it('returns null for a non-existent SEO set', function () {
    $result = SeoSetResolver::resolve('collections::non-existent');

    expect($result)->toBeNull();
});

it('returns null when the SEO set is disabled', function () {
    Seo::find('collections::pages')->config()->enabled(false);

    $result = SeoSetResolver::resolve('collections::pages');

    expect($result)->toBeNull();
});
