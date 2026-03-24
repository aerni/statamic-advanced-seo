<?php

use Aerni\AdvancedSeo\Cascades\ContentCascade;
use Aerni\AdvancedSeo\GraphQL\Types\RenderedViewsType;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Collection::make('pages')->saveQuietly();
    AssetContainer::make('assets')->disk('local')->saveQuietly();
    $this->cascade = ContentCascade::from(
        Entry::make()->collection('pages')->data(['title' => 'Test Page'])
    );
});

it('has the correct name', function () {
    expect(RenderedViewsType::NAME)->toBe('renderedViews');
});

it('exposes all expected fields', function () {
    expect((new RenderedViewsType)->fields())->toHaveKeys([
        'head',
        'body',
    ]);
});

it('renders head view with meta tags', function () {
    $head = (new RenderedViewsType)->fields()['head']['resolve']($this->cascade);

    expect($head)->toBeString();
    expect($head)->toContain('<title>Test Page');
});

it('renders body view', function () {
    $body = (new RenderedViewsType)->fields()['body']['resolve']($this->cascade);

    expect($body)->toBeString();
});
