<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\UpdateScripts\MigrateTwitterFields;
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

function runTwitterFieldsRemovalScript(): void
{
    (new MigrateTwitterFields('aerni/advanced-seo'))->update();
}

it('removes twitter fields from entries in all localizations', function () {
    $origin = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'title' => 'Test Page',
            'seo_twitter_card' => 'summary',
            'seo_twitter_title' => 'English Twitter Title',
            'seo_twitter_description' => 'English Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ]);

    $origin->saveQuietly();

    $origin->makeLocalization('german')
        ->data([
            'title' => 'German Test Page',
            'seo_twitter_card' => 'summary_large_image',
            'seo_twitter_title' => 'German Twitter Title',
            'seo_twitter_description' => 'German Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter_de.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large_de.jpg',
        ])
        ->saveQuietly();

    runTwitterFieldsRemovalScript();

    $english = Entry::query()->where('locale', 'english')->first();
    $german = Entry::query()->where('locale', 'german')->first();

    expect($english->get('title'))->toBe('Test Page');
    expect($english->get('seo_twitter_card'))->toBeNull();
    expect($english->get('seo_twitter_title'))->toBeNull();
    expect($english->get('seo_twitter_description'))->toBeNull();
    expect($english->get('seo_twitter_summary_image'))->toBeNull();
    expect($english->get('seo_twitter_summary_large_image'))->toBeNull();

    expect($german->get('title'))->toBe('German Test Page');
    expect($german->get('seo_twitter_card'))->toBeNull();
    expect($german->get('seo_twitter_title'))->toBeNull();
    expect($german->get('seo_twitter_description'))->toBeNull();
    expect($german->get('seo_twitter_summary_image'))->toBeNull();
    expect($german->get('seo_twitter_summary_large_image'))->toBeNull();
});

it('removes twitter fields from terms in all localizations', function () {
    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', [
            'title' => 'Test Tag',
            'seo_twitter_card' => 'summary',
            'seo_twitter_title' => 'English Twitter Title',
            'seo_twitter_description' => 'English Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ])
        ->dataForLocale('german', [
            'title' => 'German Test Tag',
            'seo_twitter_card' => 'summary_large_image',
            'seo_twitter_title' => 'German Twitter Title',
            'seo_twitter_description' => 'German Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter_de.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large_de.jpg',
        ])
        ->save();

    runTwitterFieldsRemovalScript();

    $english = Term::find('tags::test-tag')->in('english');
    $german = Term::find('tags::test-tag')->in('german');

    expect($english->get('title'))->toBe('Test Tag');
    expect($english->get('seo_twitter_card'))->toBeNull();
    expect($english->get('seo_twitter_title'))->toBeNull();
    expect($english->get('seo_twitter_description'))->toBeNull();
    expect($english->get('seo_twitter_summary_image'))->toBeNull();
    expect($english->get('seo_twitter_summary_large_image'))->toBeNull();

    expect($german->get('title'))->toBe('German Test Tag');
    expect($german->get('seo_twitter_card'))->toBeNull();
    expect($german->get('seo_twitter_title'))->toBeNull();
    expect($german->get('seo_twitter_description'))->toBeNull();
    expect($german->get('seo_twitter_summary_image'))->toBeNull();
    expect($german->get('seo_twitter_summary_large_image'))->toBeNull();
});

it('removes twitter fields from seo set localizations in all sites', function () {
    Seo::find('collections::pages')
        ->in('english')
        ->data([
            'seo_title' => 'English Title',
            'seo_twitter_card' => 'summary',
            'seo_twitter_title' => 'English Twitter Title',
            'seo_twitter_description' => 'English Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ])
        ->save();

    Seo::find('collections::pages')
        ->in('german')
        ->data([
            'seo_title' => 'German Title',
            'seo_twitter_card' => 'summary_large_image',
            'seo_twitter_title' => 'German Twitter Title',
            'seo_twitter_description' => 'German Twitter Description',
            'seo_twitter_summary_image' => 'social_images/twitter_de.jpg',
            'seo_twitter_summary_large_image' => 'social_images/twitter_large_de.jpg',
        ])
        ->save();

    runTwitterFieldsRemovalScript();

    $english = Seo::find('collections::pages')->in('english');
    $german = Seo::find('collections::pages')->in('german');

    expect($english->get('seo_title'))->toBe('English Title');
    expect($english->has('seo_twitter_card'))->toBeFalse();
    expect($english->has('seo_twitter_title'))->toBeFalse();
    expect($english->has('seo_twitter_description'))->toBeFalse();
    expect($english->has('seo_twitter_summary_image'))->toBeFalse();
    expect($english->has('seo_twitter_summary_large_image'))->toBeFalse();

    expect($german->get('seo_title'))->toBe('German Title');
    expect($german->has('seo_twitter_card'))->toBeFalse();
    expect($german->has('seo_twitter_title'))->toBeFalse();
    expect($german->has('seo_twitter_description'))->toBeFalse();
    expect($german->has('seo_twitter_summary_image'))->toBeFalse();
    expect($german->has('seo_twitter_summary_large_image'))->toBeFalse();
});

it('migrates twitter card value from default site localization to config', function () {
    Seo::find('collections::pages')
        ->in('english')
        ->data(['seo_twitter_card' => 'summary'])
        ->save();

    runTwitterFieldsRemovalScript();

    $config = Seo::find('collections::pages')->config();

    expect($config->get('twitter_card'))->toBe('summary');
});

it('removes twitter image fields from site social media defaults', function () {
    Seo::find('site::social_media')
        ->inDefaultSite()
        ->data([
            'og_image' => 'social_images/og.jpg',
            'twitter_handle' => '@example',
            'twitter_summary_image' => 'social_images/twitter.jpg',
            'twitter_summary_large_image' => 'social_images/twitter_large.jpg',
        ])
        ->save();

    runTwitterFieldsRemovalScript();

    $localization = Seo::find('site::social_media')->inDefaultSite();

    expect($localization->get('og_image'))->toBe('social_images/og.jpg');
    expect($localization->get('twitter_handle'))->toBe('@example');
    expect($localization->has('twitter_summary_image'))->toBeFalse();
    expect($localization->has('twitter_summary_large_image'))->toBeFalse();
});
