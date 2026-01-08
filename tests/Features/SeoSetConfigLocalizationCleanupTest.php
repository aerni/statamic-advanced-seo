<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Facades\Collection;
use Statamic\Facades\Stache;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Collection::make('articles')->saveQuietly();
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
