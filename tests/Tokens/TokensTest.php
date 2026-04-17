<?php

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Tokens\FieldToken;
use Aerni\AdvancedSeo\Tokens\Tokens;
use Aerni\AdvancedSeo\Tokens\ValueToken;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Blink::flush();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    AssetContainer::make('assets')->disk('local')->saveQuietly();
});

// --- Entry collection parent ---

it('returns field tokens for a collection', function () {
    Collection::make('pages')->sites(['english'])->saveQuietly();

    $collection = Collection::findByHandle('pages');
    $tokens = new Tokens($collection);

    $fieldTokens = $tokens->fieldTokens();

    // Should contain the title field (text type) and the seo fields.
    expect($fieldTokens)->each->toBeInstanceOf(FieldToken::class)
        ->and($fieldTokens->has('title'))->toBeTrue();
});

it('includes seo fields in the field tokens', function () {
    Collection::make('pages')->sites(['english'])->saveQuietly();

    $collection = Collection::findByHandle('pages');
    $tokens = new Tokens($collection);

    $fieldTokens = $tokens->fieldTokens();
    $handles = $fieldTokens->keys()->all();

    expect($handles)->toContain('seo_title')
        ->and($handles)->toContain('seo_description');
});

it('returns value tokens for a collection', function () {
    Collection::make('pages')->sites(['english'])->saveQuietly();

    $collection = Collection::findByHandle('pages');
    $tokens = new Tokens($collection);

    $valueTokens = $tokens->valueTokens();

    expect($valueTokens)->each->toBeInstanceOf(ValueToken::class);
});

it('excludes value tokens that overlap with field token handles', function () {
    Collection::make('pages')->sites(['english'])->saveQuietly();

    $collection = Collection::findByHandle('pages');
    $tokens = new Tokens($collection);

    $fieldHandles = $tokens->fieldTokens()->keys();
    $valueHandles = $tokens->valueTokens()->keys();

    expect($fieldHandles->intersect($valueHandles))->toBeEmpty();
});

it('merges field and value tokens in all()', function () {
    Collection::make('pages')->sites(['english'])->saveQuietly();

    $collection = Collection::findByHandle('pages');
    $tokens = new Tokens($collection);

    $all = $tokens->all();
    $fieldCount = $tokens->fieldTokens()->count();
    $valueCount = $tokens->valueTokens()->count();

    expect($all)->toHaveCount($fieldCount + $valueCount);
});

it('caches field tokens via Blink', function () {
    Collection::make('pages')->sites(['english'])->saveQuietly();

    $collection = Collection::findByHandle('pages');
    $tokens = new Tokens($collection);

    $first = $tokens->fieldTokens();
    $second = $tokens->fieldTokens();

    expect($first)->toBe($second);
});

// --- Site-scoped SeoSetLocalization parent ---

it('returns an empty blueprint collection for a site-scoped SeoSetLocalization', function () {
    $localization = Seo::find('site::defaults')->in('english');

    $tokens = new Tokens($localization);

    expect($tokens->fieldTokens())->toBeEmpty();
});

// --- Taxonomy parent ---

it('returns field tokens for a taxonomy', function () {
    Taxonomy::make('tags')->sites(['english'])->saveQuietly();

    $taxonomy = Taxonomy::findByHandle('tags');
    $tokens = new Tokens($taxonomy);

    $fieldTokens = $tokens->fieldTokens();

    expect($fieldTokens)->each->toBeInstanceOf(FieldToken::class)
        ->and($fieldTokens->has('title'))->toBeTrue();
});

// --- Sorted output ---

it('sorts field tokens by display name', function () {
    Collection::make('pages')->sites(['english'])->saveQuietly();

    $collection = Collection::findByHandle('pages');
    $tokens = new Tokens($collection);

    $displays = $tokens->fieldTokens()->map(fn (FieldToken $t) => $t->display())->values()->all();
    $sorted = $displays;
    sort($sorted);

    expect($displays)->toBe($sorted);
});

it('sorts value tokens by display name', function () {
    Collection::make('pages')->sites(['english'])->saveQuietly();

    $collection = Collection::findByHandle('pages');
    $tokens = new Tokens($collection);

    $displays = $tokens->valueTokens()->map(fn (ValueToken $t) => $t->display())->values()->all();
    $sorted = $displays;
    sort($sorted);

    expect($displays)->toBe($sorted);
});
