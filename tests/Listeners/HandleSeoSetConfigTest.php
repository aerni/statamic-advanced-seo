<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Blink::flush();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => '/de', 'locale' => 'de'],
    ]);

    Collection::make('pages')->sites(['english', 'german'])->saveQuietly();
    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
});

// --- Disabling a SeoSet ---

it('deletes all localizations when a collection SeoSet is disabled', function () {
    Seo::find('collections::pages')->in('english')->set('seo_title', 'EN Title')->save();
    Seo::find('collections::pages')->in('german')->set('seo_title', 'DE Title')->save();

    expect(SeoLocalization::whereSeoSet('collections::pages'))->toHaveCount(2);

    $config = Seo::find('collections::pages')->config();
    $config->enabled(false);
    $config->save();

    expect(SeoLocalization::whereSeoSet('collections::pages'))->toHaveCount(0);
});

it('strips seo_* values from entries when a collection SeoSet is disabled', function () {
    Entry::make()->collection('pages')->locale('english')->slug('about')->data([
        'title' => 'About',
        'seo_title' => 'Custom SEO Title',
        'seo_noindex' => true,
    ])->save();

    $config = Seo::find('collections::pages')->config();
    $config->enabled(false);
    $config->save();

    $entry = Entry::query()->where('slug', 'about')->first();

    expect($entry->get('title'))->toBe('About')
        ->and($entry->get('seo_title'))->toBeNull()
        ->and($entry->get('seo_noindex'))->toBeNull();
});

it('deletes all localizations when a taxonomy SeoSet is disabled', function () {
    Seo::find('taxonomies::tags')->in('english')->set('seo_title', 'EN Tags')->save();
    Seo::find('taxonomies::tags')->in('german')->set('seo_title', 'DE Tags')->save();

    expect(SeoLocalization::whereSeoSet('taxonomies::tags'))->toHaveCount(2);

    $config = Seo::find('taxonomies::tags')->config();
    $config->enabled(false);
    $config->save();

    expect(SeoLocalization::whereSeoSet('taxonomies::tags'))->toHaveCount(0);
});

it('strips seo_* values from terms when a taxonomy SeoSet is disabled', function () {
    Term::make()->taxonomy('tags')->inDefaultLocale()->slug('php')->data([
        'title' => 'PHP',
        'seo_title' => 'PHP Articles',
        'seo_noindex' => true,
    ])->save();

    $config = Seo::find('taxonomies::tags')->config();
    $config->enabled(false);
    $config->save();

    $term = Term::query()->where('slug', 'php')->first();

    expect($term->get('title'))->toBe('PHP')
        ->and($term->get('seo_title'))->toBeNull()
        ->and($term->get('seo_noindex'))->toBeNull();
});

// --- Deleting a SeoSet config ---

it('deletes all localizations when a SeoSet config is deleted', function () {
    Seo::find('collections::pages')->in('english')->set('seo_title', 'EN Title')->save();
    Seo::find('collections::pages')->in('german')->set('seo_title', 'DE Title')->save();

    expect(SeoLocalization::whereSeoSet('collections::pages'))->toHaveCount(2);

    Seo::find('collections::pages')->config()->delete();

    expect(SeoLocalization::whereSeoSet('collections::pages'))->toHaveCount(0);
});

it('strips seo_* values from entries when a SeoSet config is deleted', function () {
    Entry::make()->collection('pages')->locale('english')->slug('about')->data([
        'title' => 'About',
        'seo_title' => 'Custom SEO Title',
    ])->save();

    Seo::find('collections::pages')->config()->delete();

    $entry = Entry::query()->where('slug', 'about')->first();

    expect($entry->get('seo_title'))->toBeNull();
});

// --- Feature-toggle cleanup on save ---

it('strips seo_sitemap_enabled from entries when sitemap is disabled on save', function () {
    Entry::make()->collection('pages')->locale('english')->slug('a')->data([
        'title' => 'A',
        'seo_sitemap_enabled' => false,
        'seo_title' => 'Keep me',
    ])->save();

    $config = Seo::find('collections::pages')->config();
    $config->set('sitemap', false);
    $config->set('social_images_generator', true);
    $config->save();

    $entry = Entry::query()->where('slug', 'a')->first();

    expect($entry->get('seo_sitemap_enabled'))->toBeNull()
        ->and($entry->get('seo_title'))->toBe('Keep me');
});

it('strips seo_generate_social_images and seo_social_images_theme when generator is disabled on save', function () {
    Entry::make()->collection('pages')->locale('english')->slug('a')->data([
        'title' => 'A',
        'seo_generate_social_images' => true,
        'seo_social_images_theme' => 'custom',
        'seo_title' => 'Keep me',
    ])->save();

    $config = Seo::find('collections::pages')->config();
    $config->set('sitemap', true);
    $config->set('social_images_generator', false);
    $config->save();

    $entry = Entry::query()->where('slug', 'a')->first();

    expect($entry->get('seo_generate_social_images'))->toBeNull()
        ->and($entry->get('seo_social_images_theme'))->toBeNull()
        ->and($entry->get('seo_title'))->toBe('Keep me');
});

it('does not strip feature-gated values when both features are enabled', function () {
    Entry::make()->collection('pages')->locale('english')->slug('a')->data([
        'title' => 'A',
        'seo_sitemap_enabled' => false,
        'seo_generate_social_images' => true,
    ])->save();

    $config = Seo::find('collections::pages')->config();
    $config->set('sitemap', true);
    $config->set('social_images_generator', true);
    $config->save();

    $entry = Entry::query()->where('slug', 'a')->first();

    expect($entry->get('seo_sitemap_enabled'))->toBeFalse()
        ->and($entry->get('seo_generate_social_images'))->toBeTrue();
});

// --- Orphaned localization cleanup ---

it('deletes orphaned localizations for sites that are no longer configured on the collection', function () {
    Seo::find('collections::pages')->in('english')->set('seo_title', 'EN')->save();
    Seo::find('collections::pages')->in('german')->set('seo_title', 'DE')->save();

    expect(SeoLocalization::whereSeoSet('collections::pages'))->toHaveCount(2);

    // Remove german from the collection, then re-save the config.
    Collection::find('pages')->sites(['english'])->save();

    Blink::flush();

    $config = Seo::find('collections::pages')->config();
    $config->save();

    $remaining = SeoLocalization::whereSeoSet('collections::pages');

    expect($remaining)->toHaveCount(1)
        ->and($remaining->first()->locale())->toBe('english');
});
