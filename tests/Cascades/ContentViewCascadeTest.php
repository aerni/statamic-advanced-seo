<?php

use Aerni\AdvancedSeo\Cascades\ContentViewCascade;
use Illuminate\Pagination\LengthAwarePaginator;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    AssetContainer::make('assets')->disk('local')->saveQuietly();
    Collection::make('pages')->routes('/{slug}')->sites(['english'])->saveQuietly();

    $entry = Entry::make()->collection('pages')->locale('english')->slug('about')->data(['title' => 'About']);
    $entry->save();

    $this->entry = $entry;
    $this->cascade = ContentViewCascade::from($entry);
});

it('includes prev_url and next_url in computed keys', function () {
    expect($this->cascade->computedKeys()->all())->toContain('prev_url', 'next_url');
});

it('sanitizes string values in cascade data', function () {
    $data = $this->cascade->data();
    $stringValues = $data->filter(fn ($v) => is_string($v));

    $stringValues->each(function ($value) {
        expect($value)->not->toMatch('/<[a-z][\s\S]*>/i');
    });
});

it('returns base canonical without pagination when no paginator exists', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $cascade = ContentViewCascade::from($this->entry);

    expect($cascade->canonical())->toBe('https://example.com/about');
});

it('returns canonical with page parameter when paginated', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $paginator = new LengthAwarePaginator([], 30, 10, 3);
    Blink::put('tag-paginator', $paginator);
    request()->merge(['page' => 3]);

    $cascade = ContentViewCascade::from($this->entry);

    expect($cascade->canonical())->toBe('https://example.com/about?page=3');

    request()->query->remove('page');
});

it('returns base canonical on page 1 without page parameter', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $paginator = new LengthAwarePaginator([], 30, 10, 1);
    Blink::put('tag-paginator', $paginator);
    request()->merge(['page' => 1]);

    $cascade = ContentViewCascade::from($this->entry);

    expect($cascade->canonical())->toBe('https://example.com/about');

    request()->query->remove('page');
});

it('returns null prev url when no paginator exists', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    expect($this->cascade->prevUrl())->toBeNull();
});

it('returns null prev url on page 1', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $paginator = new LengthAwarePaginator([], 30, 10, 1);
    Blink::put('tag-paginator', $paginator);

    $cascade = ContentViewCascade::from($this->entry);

    expect($cascade->prevUrl())->toBeNull();
});

it('returns base canonical as prev url on page 2', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $paginator = new LengthAwarePaginator([], 30, 10, 2);
    Blink::put('tag-paginator', $paginator);

    $cascade = ContentViewCascade::from($this->entry);

    expect($cascade->prevUrl())->toBe('https://example.com/about');
});

it('returns paginated prev url on page 3', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $paginator = new LengthAwarePaginator([], 30, 10, 3);
    Blink::put('tag-paginator', $paginator);

    $cascade = ContentViewCascade::from($this->entry);

    expect($cascade->prevUrl())->toBe('https://example.com/about?page=2');
});

it('returns null next url when on the last page', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $paginator = new LengthAwarePaginator([], 30, 10, 3);
    Blink::put('tag-paginator', $paginator);

    $cascade = ContentViewCascade::from($this->entry);

    expect($cascade->nextUrl())->toBeNull();
});

it('returns next url when not on the last page', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    $paginator = new LengthAwarePaginator([], 30, 10, 1);
    Blink::put('tag-paginator', $paginator);

    $cascade = ContentViewCascade::from($this->entry);

    expect($cascade->nextUrl())->toBe('https://example.com/about?page=2');
});

it('returns null next url when no paginator exists', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    expect($this->cascade->nextUrl())->toBeNull();
});

it('returns null prev and next urls when not indexable', function () {
    config(['advanced-seo.crawling.environments' => ['production']]);

    $paginator = new LengthAwarePaginator([], 30, 10, 2);
    Blink::put('tag-paginator', $paginator);

    $cascade = ContentViewCascade::from($this->entry);

    expect($cascade->prevUrl())->toBeNull()
        ->and($cascade->nextUrl())->toBeNull();
});
