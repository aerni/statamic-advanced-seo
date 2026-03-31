<?php

use Aerni\AdvancedSeo\Cascades\ContextViewCascade;
use Statamic\Facades\AssetContainer;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Tags\Context;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);

    AssetContainer::make('assets')->disk('local')->saveQuietly();
    Collection::make('pages')->routes('/{slug}')->sites(['english', 'german'])->saveQuietly();
    Taxonomy::make('tags')->sites(['english', 'german'])->saveQuietly();
});

it('returns taxonomy title with separator and site name', function () {
    $taxonomy = Taxonomy::find('tags');

    $context = new Context(collect([
        'page' => $taxonomy,
        'title' => 'Tags',
        'current_url' => 'https://example.com/tags',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->title())->toContain('Tags');
});

it('returns 404 title with separator and site name', function () {
    $context = new Context(collect([
        'response_code' => 404,
        'title' => 'Not Found',
        'current_url' => 'https://example.com/nonexistent',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->title())->toContain('404');
});

it('returns the locale from the current site', function () {
    Site::setCurrent('english');

    $context = new Context(collect([
        'title' => 'Tags',
        'current_url' => 'https://example.com/tags',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->locale())->toBe('en');
});

it('returns the canonical url from context current_url', function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);
    Site::setCurrent('english');

    $context = new Context(collect([
        'title' => 'Tags',
        'current_url' => 'https://example.com/tags',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->canonical())->toBe('https://example.com/tags');
});

it('returns null hreflang when multisite is disabled', function () {
    config(['statamic.system.multisite' => false]);

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    $context = new Context(collect([
        'title' => 'Tags',
        'current_url' => 'https://example.com/tags',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->hreflang())->toBeNull();
});

it('returns null hreflang for non-taxonomy context', function () {
    $context = new Context(collect([
        'response_code' => 404,
        'title' => 'Not Found',
        'current_url' => 'https://example.com/nonexistent',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    expect($cascade->hreflang())->toBeNull();
});

it('detects homepage from context is_homepage flag', function () {
    $context = new Context(collect([
        'title' => 'Home',
        'is_homepage' => true,
        'current_url' => 'https://example.com',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);
    $cascade->set('use_breadcrumbs', true);

    // Homepage should return null breadcrumbs.
    expect($cascade->breadcrumbs())->toBeNull();
});

it('returns default title when context has a title', function () {
    $context = new Context(collect([
        'title' => 'Custom Page',
        'current_url' => 'https://example.com/custom',
        'site' => Site::get('english'),
    ]));

    $cascade = ContextViewCascade::from($context);

    // For non-taxonomy, non-404 contexts, the title comes from cascade or context title.
    expect($cascade->title())->toContain('Custom Page');
});
