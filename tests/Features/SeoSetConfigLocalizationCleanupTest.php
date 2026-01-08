<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Illuminate\Filesystem\Filesystem;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
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

    /* Ensure we can evaluate the sitemap feature as we need fresh localizations and fresh EvaluateFeature */
    flushBlink();

    $seoSet->config()
        ->set('sitemap', false)
        ->save();

    /* Ensure we get a fresh localization from the stache */
    clearStache();

    $localization = $seoSet->inDefaultSite();

    expect($localization->get('seo_title'))->toBe('foo');
    expect($localization->get('seo_sitemap_enabled'))->toBeNull();
});
