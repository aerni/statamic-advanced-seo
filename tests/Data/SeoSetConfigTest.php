<?php

use Statamic\Facades\Stache;
use Statamic\Facades\Taxonomy;
use Statamic\Fields\Blueprint;
use Statamic\Facades\Collection;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Collection::make('articles')->saveQuietly();
    Taxonomy::make('tags')->saveQuietly();
});

it('can get the blueprint', function () {
    $blueprint = Seo::find('collections::articles')->config()->blueprint();

    expect($blueprint)->toBeInstanceOf(Blueprint::class);
    expect($blueprint->handle())->toBe('content_config');

    $blueprint = Seo::find('taxonomies::tags')->config()->blueprint();

    expect($blueprint)->toBeInstanceOf(Blueprint::class);
    expect($blueprint->handle())->toBe('content_config');

    $blueprint = Seo::find('site::general')->config()->blueprint();

    expect($blueprint)->toBeInstanceOf(Blueprint::class);
    expect($blueprint->handle())->toBe('site_config');
});

it('removes localization field values of disabled features when saving the set config', function () {
    $seoSet = Seo::find('collections::articles');

    $seoSet->inDefaultSite()
        ->set('seo_title', 'foo')
        ->set('seo_sitemap_enabled', true)
        ->save();

    $seoSet->config()
        ->set('sitemap', false)
        ->save();

    /* Ensure we get a fresh localization from the stache */
    clearStache();

    $localization = $seoSet->inDefaultSite();

    expect($localization->get('seo_title'))->toBe('foo');
    expect($localization->get('seo_sitemap_enabled'))->toBeNull();
});
