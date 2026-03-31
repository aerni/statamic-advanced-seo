<?php

use Aerni\AdvancedSeo\Cascades\SeoFieldtypeCascade;
use Aerni\AdvancedSeo\Context\Context as SeoContext;
use Statamic\Facades\AssetContainer;
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
    ]);

    AssetContainer::make('assets')->disk('local')->saveQuietly();
    Collection::make('pages')->routes('/{slug}')->sites(['english'])->saveQuietly();

    $entry = Entry::make()->collection('pages')->locale('english')->slug('about')->data(['title' => 'About']);
    $entry->save();

    $this->context = SeoContext::from($entry);
    $this->cascade = SeoFieldtypeCascade::from($this->context);
});

it('removes the seo prefix from all keys', function () {
    $keys = $this->cascade->data()->keys();

    expect($keys->filter(fn ($key) => str_starts_with($key, 'seo_')))->toBeEmpty();
});

it('sorts data keys alphabetically', function () {
    $keys = $this->cascade->data()->keys()->all();

    expect($keys)->toBe(collect($keys)->sort()->values()->all());
});

it('includes content defaults for content-scoped context', function () {
    expect($this->context->isContent())->toBeTrue();
    expect($this->cascade->data())->not->toBeEmpty();
});

it('processes a taxonomy term context', function () {
    Taxonomy::make('tags')->sites(['english'])->saveQuietly();

    $term = Term::make()->taxonomy('tags')->inDefaultLocale()->slug('php')->data(['title' => 'PHP']);
    $term->save();

    $context = SeoContext::from($term);
    $cascade = SeoFieldtypeCascade::from($context);

    expect($cascade->data())->not->toBeEmpty();
    expect($cascade->data()->keys()->all())->toBe(
        collect($cascade->data()->keys()->all())->sort()->values()->all()
    );
});

it('does not have computed keys', function () {
    expect($this->cascade->values()->all())->toBe($this->cascade->data()->all());
});

it('returns value directly without computed resolution', function () {
    $firstKey = $this->cascade->data()->keys()->first();

    if ($firstKey) {
        expect($this->cascade->value($firstKey))->toBe($this->cascade->get($firstKey));
    }
});
