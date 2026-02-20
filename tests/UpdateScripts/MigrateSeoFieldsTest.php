<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\UpdateScripts\V3\MigrateSeoFields;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    Collection::make('pages')->sites(['english', 'german'])->saveQuietly();
    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
});

function runMigrateSeoFieldsScript(): void
{
    (new MigrateSeoFields)->run();
}

it('migrates @auto values to @default', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->set('seo_title', '@auto')
        ->save();

    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', ['seo_title' => '@auto'])
        ->save();

    runMigrateSeoFieldsScript();

    expect(Entry::all()->first()->get('seo_title'))->toBe('@default');
    expect(Term::find('tags::test-tag')->in('english')->get('seo_title'))->toBe('@default');
});

it('migrates @null values to @default', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->set('seo_title', '@null')
        ->save();

    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', ['seo_title' => '@null'])
        ->save();

    runMigrateSeoFieldsScript();

    expect(Entry::all()->first()->get('seo_title'))->toBe('@default');
    expect(Term::find('tags::test-tag')->in('english')->get('seo_title'))->toBe('@default');
});

it('migrates @field references to Antlers syntax', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->set('seo_title', '@field:title')
        ->save();

    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', ['seo_title' => '@field:title'])
        ->save();

    runMigrateSeoFieldsScript();

    expect(Entry::all()->first()->get('seo_title'))->toBe('{{ title }}');
    expect(Term::find('tags::test-tag')->in('english')->get('seo_title'))->toBe('{{ title }}');
});

it('migrates legacy values on seo set localizations', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data([
            'seo_description' => '@auto',
            'seo_og_title' => '@null',
            'seo_og_description' => '@field:seo_title',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    expect($localization->get('seo_description'))->toBe('@default');
    expect($localization->get('seo_og_title'))->toBe('@default');
    expect($localization->get('seo_og_description'))->toBe('{{ seo_title }}');
});

it('does not modify non-seo fields', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'title' => 'Test Page',
            'content' => 'Some content',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();

    expect($entry->get('title'))->toBe('Test Page');
    expect($entry->get('content'))->toBe('Some content');
});

it('removes twitter fields from entries and terms', function () {
    Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'title' => 'Test Page',
            'seo_twitter_card' => 'summary',
            'seo_twitter_title' => 'Twitter Title',
            'seo_twitter_description' => 'Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ])
        ->save();

    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', [
            'title' => 'Test Tag',
            'seo_twitter_card' => 'summary',
            'seo_twitter_title' => 'Twitter Title',
            'seo_twitter_description' => 'Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $entry = Entry::all()->first();
    $term = Term::find('tags::test-tag')->in('english');

    expect($entry->get('title'))->toBe('Test Page');
    expect($entry->get('seo_twitter_card'))->toBeNull();
    expect($entry->get('seo_twitter_title'))->toBeNull();
    expect($entry->get('seo_twitter_description'))->toBeNull();
    expect($entry->get('seo_twitter_summary_image'))->toBeNull();
    expect($entry->get('seo_twitter_summary_large_image'))->toBeNull();

    expect($term->get('title'))->toBe('Test Tag');
    expect($term->get('seo_twitter_card'))->toBeNull();
    expect($term->get('seo_twitter_title'))->toBeNull();
    expect($term->get('seo_twitter_description'))->toBeNull();
    expect($term->get('seo_twitter_summary_image'))->toBeNull();
    expect($term->get('seo_twitter_summary_large_image'))->toBeNull();
});

it('removes twitter fields from seo set localizations', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data([
            'seo_twitter_card' => 'summary',
            'seo_twitter_title' => 'Twitter Title',
            'seo_twitter_description' => 'Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('collections::pages')->inDefaultSite();

    expect($localization->has('seo_twitter_card'))->toBeFalse();
    expect($localization->has('seo_twitter_title'))->toBeFalse();
    expect($localization->has('seo_twitter_description'))->toBeFalse();
    expect($localization->has('seo_twitter_summary_image'))->toBeFalse();
    expect($localization->has('seo_twitter_summary_large_image'))->toBeFalse();
});

it('migrates twitter card value from default site localization to config', function () {
    Seo::find('collections::pages')
        ->inDefaultSite()
        ->data(['seo_twitter_card' => 'summary'])
        ->save();

    runMigrateSeoFieldsScript();

    $seo = Seo::find('collections::pages');

    expect($seo->inDefaultSite()->get('twitter_card'))->toBeNull();
    expect($seo->config()->get('twitter_card'))->toBe('summary');
});

it('removes twitter image fields from site defaults', function () {
    Seo::find('site::defaults')
        ->inDefaultSite()
        ->data([
            'twitter_summary_image' => 'social_images/twitter.jpg',
            'twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ])
        ->save();

    runMigrateSeoFieldsScript();

    $localization = Seo::find('site::defaults')->inDefaultSite();

    expect($localization->has('twitter_summary_image'))->toBeFalse();
    expect($localization->has('twitter_summary_large_image'))->toBeFalse();
});
