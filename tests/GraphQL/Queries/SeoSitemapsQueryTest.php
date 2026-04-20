<?php

use Aerni\AdvancedSeo\GraphQL\Enums\SitemapTypeEnum;
use Aerni\AdvancedSeo\GraphQL\Queries\SeoSitemapsQuery;
use Aerni\AdvancedSeo\GraphQL\Types\SitemapType;
use GraphQL\Type\Definition\NonNull;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com/', 'locale' => 'en'],
    ]);

    Collection::make('pages')->sites(['english'])->saveQuietly();
    Collection::make('posts')->sites(['english'])->saveQuietly();

    Taxonomy::make('tags')->sites(['english'])->saveQuietly();
});

it('has the correct name', function () {
    expect((new SeoSitemapsQuery)->name)->toBe('seoSitemaps');
});

it('returns the SitemapType', function () {
    expect((new SeoSitemapsQuery)->type()->getWrappedType()->name)->toBe(SitemapType::NAME);
});

it('has required arguments', function () {
    $args = (new SeoSitemapsQuery)->args();

    expect($args)->toHaveKeys(['site', 'type', 'handle']);
    expect($args['site']['type']->getWrappedType()->name)->toBe('String');
    expect($args['site']['type'])->toBeInstanceOf(NonNull::class);
    expect($args['type']['type']->name)->toBe(SitemapTypeEnum::NAME);
});

it('resolves sitemaps for a site', function () {
    $result = (new SeoSitemapsQuery)->resolve(null, ['site' => 'english']);

    expect($result)->toBeInstanceOf(Illuminate\Support\Collection::class);
});

it('returns null for non-existent site', function () {
    $result = (new SeoSitemapsQuery)->resolve(null, ['site' => 'non-existent']);

    expect($result)->toBeNull();
});

it('filters by collection type', function () {
    $result = (new SeoSitemapsQuery)->resolve(null, [
        'site' => 'english',
        'type' => 'collection',
    ]);

    expect($result)->not->toBeNull();

    $result->each(function ($sitemap) {
        expect($sitemap->type())->toBe('collection');
    });
});

it('filters by taxonomy type', function () {
    $result = (new SeoSitemapsQuery)->resolve(null, [
        'site' => 'english',
        'type' => 'taxonomy',
    ]);

    expect($result)->not->toBeNull();

    $result->each(function ($sitemap) {
        expect($sitemap->type())->toBe('taxonomy');
    });
});

it('filters by handle', function () {
    $result = (new SeoSitemapsQuery)->resolve(null, [
        'site' => 'english',
        'handle' => 'pages',
    ]);

    expect($result)->not->toBeNull();

    $result->each(function ($sitemap) {
        expect($sitemap->handle())->toBe('pages');
    });
});

it('filters by both type and handle', function () {
    $result = (new SeoSitemapsQuery)->resolve(null, [
        'site' => 'english',
        'type' => 'collection',
        'handle' => 'pages',
    ]);

    expect($result)->not->toBeNull();

    $result->each(function ($sitemap) {
        expect($sitemap->type())->toBe('collection');
        expect($sitemap->handle())->toBe('pages');
    });
});

it('returns empty collection when type filter matches nothing', function () {
    $result = (new SeoSitemapsQuery)->resolve(null, [
        'site' => 'english',
        'type' => 'custom',
    ]);

    expect($result)->toBeEmpty();
});

it('returns empty collection when handle filter matches nothing', function () {
    $result = (new SeoSitemapsQuery)->resolve(null, [
        'site' => 'english',
        'handle' => 'non-existent',
    ]);

    expect($result)->toBeEmpty();
});
