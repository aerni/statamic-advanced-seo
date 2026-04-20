<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\GraphQL\Fields\SeoField;
use Aerni\AdvancedSeo\GraphQL\Types\SeoMetaType;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => '/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->saveQuietly();

    Taxonomy::make('tags')->saveQuietly();
});

it('has the correct name', function () {
    expect((new SeoField)->name)->toBe('seo');
});

it('returns the SeoMetaType', function () {
    expect((new SeoField)->type()->name)->toBe(SeoMetaType::NAME);
});

it('resolves an entry when collection is enabled', function () {
    $entry = Entry::make()
        ->collection('pages')
        ->slug('test-page')
        ->data(['title' => 'Test Page']);

    $entry->save();

    $result = invade(new SeoField)->resolve($entry);

    expect($result)->toBe($entry);
});

it('resolves a term when taxonomy is enabled', function () {
    $term = Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->data(['title' => 'Test Tag']);

    $term->save();

    $result = invade(new SeoField)->resolve($term);

    expect($result)->toBe($term);
});

it('returns null when model is disabled', function () {
    $entry = Entry::make()
        ->collection('pages')
        ->slug('test-page')
        ->data(['title' => 'Test Page']);

    $entry->save();

    Seo::find('collections::pages')->config()->enabled(false);

    $result = invade(new SeoField)->resolve($entry);

    expect($result)->toBeNull();
});
