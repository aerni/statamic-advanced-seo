<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\UpdateScripts\RemoveTwitterTitleAndDescription;
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
    (new RemoveTwitterTitleAndDescription('aerni/advanced-seo'))->update();
}

it('removes twitter fields from entries in all localizations', function () {
    $origin = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data([
            'title' => 'Test Page',
            'seo_twitter_title' => 'English Twitter Title',
            'seo_twitter_description' => 'English Twitter Description',
        ]);

    $origin->saveQuietly();

    $origin->makeLocalization('german')
        ->data([
            'title' => 'German Test Page',
            'seo_twitter_title' => 'German Twitter Title',
            'seo_twitter_description' => 'German Twitter Description',
        ])
        ->saveQuietly();

    runTwitterFieldsRemovalScript();

    $english = Entry::query()->where('locale', 'english')->first();
    $german = Entry::query()->where('locale', 'german')->first();

    expect($english->get('title'))->toBe('Test Page');
    expect($english->get('seo_twitter_title'))->toBeNull();
    expect($english->get('seo_twitter_description'))->toBeNull();

    expect($german->get('title'))->toBe('German Test Page');
    expect($german->get('seo_twitter_title'))->toBeNull();
    expect($german->get('seo_twitter_description'))->toBeNull();
});

it('removes twitter fields from terms in all localizations', function () {
    Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', [
            'title' => 'Test Tag',
            'seo_twitter_title' => 'English Twitter Title',
            'seo_twitter_description' => 'English Twitter Description',
        ])
        ->dataForLocale('german', [
            'title' => 'German Test Tag',
            'seo_twitter_title' => 'German Twitter Title',
            'seo_twitter_description' => 'German Twitter Description',
        ])
        ->save();

    runTwitterFieldsRemovalScript();

    $english = Term::find('tags::test-tag')->in('english');
    $german = Term::find('tags::test-tag')->in('german');

    expect($english->get('title'))->toBe('Test Tag');
    expect($english->get('seo_twitter_title'))->toBeNull();
    expect($english->get('seo_twitter_description'))->toBeNull();

    expect($german->get('title'))->toBe('German Test Tag');
    expect($german->get('seo_twitter_title'))->toBeNull();
    expect($german->get('seo_twitter_description'))->toBeNull();
});

it('removes twitter fields from seo set localizations in all sites', function () {
    Seo::find('collections::pages')
        ->in('english')
        ->data([
            'seo_title' => 'English Title',
            'seo_twitter_title' => 'English Twitter Title',
            'seo_twitter_description' => 'English Twitter Description',
        ])
        ->save();

    Seo::find('collections::pages')
        ->in('german')
        ->data([
            'seo_title' => 'German Title',
            'seo_twitter_title' => 'German Twitter Title',
            'seo_twitter_description' => 'German Twitter Description',
        ])
        ->save();

    runTwitterFieldsRemovalScript();

    $english = Seo::find('collections::pages')->in('english');
    $german = Seo::find('collections::pages')->in('german');

    expect($english->get('seo_title'))->toBe('English Title');
    expect($english->has('seo_twitter_title'))->toBeFalse();
    expect($english->has('seo_twitter_description'))->toBeFalse();

    expect($german->get('seo_title'))->toBe('German Title');
    expect($german->has('seo_twitter_title'))->toBeFalse();
    expect($german->has('seo_twitter_description'))->toBeFalse();
});
