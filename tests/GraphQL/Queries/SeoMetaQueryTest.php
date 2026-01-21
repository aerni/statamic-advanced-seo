<?php

use Statamic\Facades\Site;
use Statamic\Facades\Term;
use Statamic\Facades\Entry;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Collection;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\GraphQL\Types\SeoMetaType;
use Aerni\AdvancedSeo\GraphQL\Queries\SeoMetaQuery;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;
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

    Taxonomy::make('tags')
        ->sites(['english', 'german'])
        ->saveQuietly();
});

it('has the correct name', function () {
    expect((new SeoMetaQuery)->name)->toBe('seoMeta');
});

it('returns the SeoMetaType', function () {
    expect((new SeoMetaQuery)->type()->name)->toBe(SeoMetaType::NAME);
});

it('has id and site arguments', function () {
    $args = (new SeoMetaQuery)->args();

    expect($args)->toHaveKeys(['id', 'site']);
    expect($args['id']['rules'])->toContain('required');
});

it('resolves an entry by id', function () {
    $entry = Entry::make()
        ->collection('pages')
        ->data(['title' => 'Test Page']);

    $entry->save();

    $query = new SeoMetaQuery;
    $result = $query->resolve(null, ['id' => $entry->id()]);

    expect($result)->toBeInstanceOf(EntryContract::class);
    expect($result->id())->toBe($entry->id());
});

it('resolves an entry in a specific site', function () {
    $englishEntry = Entry::make()
        ->collection('pages')
        ->locale('english')
        ->data(['title' => 'English Page']);

    $englishEntry->save();

    $germanEntry = $englishEntry
        ->makeLocalization('german')
        ->data(['title' => 'German Page']);

    $germanEntry->save();

    $result = (new SeoMetaQuery)->resolve(null, ['id' => $englishEntry->id(), 'site' => 'german']);

    expect($result)->not->toBeNull();
    expect($result->locale())->toBe('german');
});

it('resolves a term by id', function () {
    $term = Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->data(['title' => 'Test Tag']);

    $term->save();

    $result = (new SeoMetaQuery)->resolve(null, ['id' => $term->id()]);

    expect($result)->toBeInstanceOf(TermContract::class);
});

it('resolves a term in a specific site', function () {
    $term = Term::make()
        ->taxonomy('tags')
        ->slug('test-tag')
        ->dataForLocale('english', ['title' => 'English Tag'])
        ->dataForLocale('german', ['title' => 'German Tag']);

    $term->save();

    $result = (new SeoMetaQuery)->resolve(null, ['id' => $term->id(), 'site' => 'german']);

    expect($result)->not->toBeNull();
    expect($result->locale())->toBe('german');
});

it('returns null when the collection is disabled', function () {
    $entry = Entry::make()
        ->collection('pages')
        ->slug('test-page')
        ->data(['title' => 'Test Page']);

    $entry->save();

    Seo::find('collections::pages')
        ->config()
        ->enabled(false);

    $result = (new SeoMetaQuery)->resolve(null, ['id' => $entry->id()]);

    expect($result)->toBeNull();
});

it('returns null for non-existent id', function () {
    $result = (new SeoMetaQuery)->resolve(null, ['id' => 'non-existent-id']);

    expect($result)->toBeNull();
});
