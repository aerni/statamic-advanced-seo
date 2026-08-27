<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Aerni\AdvancedSeo\Registries\SeoSetRegistry;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Blink::flush();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);
});

it('deletes SEO config and localizations when a collection is deleted', function () {
    Collection::make('pages')->sites(['english', 'german'])->saveQuietly();
    Collection::make('blog')->sites(['english', 'german'])->saveQuietly();
    Blink::flush();

    $pages = Seo::find('collections::pages');
    $blog = Seo::find('collections::blog');

    $pages->save();
    $blog->save();
    $pages->in('english')->set('seo_title', 'Pages EN')->save();
    $pages->in('german')->set('seo_title', 'Pages DE')->save();
    $blog->in('english')->set('seo_title', 'Blog EN')->save();

    expect(SeoConfig::find('collections::pages'))->not->toBeNull()
        ->and(SeoLocalization::whereSeoSet('collections::pages'))->toHaveCount(2)
        ->and(SeoConfig::find('collections::blog'))->not->toBeNull()
        ->and(SeoLocalization::whereSeoSet('collections::blog'))->toHaveCount(1);

    /* Warm a set-specific Blink key (same prefix SeoSet::flushBlink uses). */
    $pages->config();
    $pagesBlinkKey = "advanced-seo::{$pages->id()}::config";
    expect(Blink::has($pagesBlinkKey))->toBeTrue();

    /* Drop only the registry cache so Seo::find cannot use a pre-delete set list. */
    Blink::forget(SeoSetRegistry::class.'::all');

    Collection::find('pages')->delete();

    expect(SeoConfig::find('collections::pages'))->toBeNull()
        ->and(SeoLocalization::whereSeoSet('collections::pages'))->toHaveCount(0)
        ->and(SeoConfig::find('collections::blog'))->not->toBeNull()
        ->and(SeoLocalization::whereSeoSet('collections::blog'))->toHaveCount(1)
        ->and(Blink::has($pagesBlinkKey))->toBeFalse();
});

it('deletes SEO config and localizations when a taxonomy is deleted', function () {
    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
    Taxonomy::make('categories')->sites(['english', 'german'])->saveQuietly();
    Blink::flush();

    $tags = Seo::find('taxonomies::tags');
    $categories = Seo::find('taxonomies::categories');

    $tags->save();
    $categories->save();
    $tags->in('english')->set('seo_title', 'Tags EN')->save();
    $tags->in('german')->set('seo_title', 'Tags DE')->save();
    $categories->in('english')->set('seo_title', 'Categories EN')->save();

    expect(SeoConfig::find('taxonomies::tags'))->not->toBeNull()
        ->and(SeoLocalization::whereSeoSet('taxonomies::tags'))->toHaveCount(2)
        ->and(SeoConfig::find('taxonomies::categories'))->not->toBeNull()
        ->and(SeoLocalization::whereSeoSet('taxonomies::categories'))->toHaveCount(1);

    $tags->config();
    $tagsBlinkKey = "advanced-seo::{$tags->id()}::config";
    expect(Blink::has($tagsBlinkKey))->toBeTrue();

    Blink::forget(SeoSetRegistry::class.'::all');

    Taxonomy::find('tags')->delete();

    expect(SeoConfig::find('taxonomies::tags'))->toBeNull()
        ->and(SeoLocalization::whereSeoSet('taxonomies::tags'))->toHaveCount(0)
        ->and(SeoConfig::find('taxonomies::categories'))->not->toBeNull()
        ->and(SeoLocalization::whereSeoSet('taxonomies::categories'))->toHaveCount(1)
        ->and(Blink::has($tagsBlinkKey))->toBeFalse();
});
