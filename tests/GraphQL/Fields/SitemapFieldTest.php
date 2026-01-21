<?php

use Aerni\AdvancedSeo\GraphQL\Fields\SitemapField;
use Aerni\AdvancedSeo\GraphQL\Types\SeoSitemapType;
use GraphQL\Type\Definition\ResolveInfo;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    config(['advanced-seo.crawling.environments' => ['testing']]);

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'http://localhost/', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'http://localhost/de/', 'locale' => 'de'],
    ]);
});

it('returns a list of SeoSitemapType', function () {
    expect((new SitemapField)->type()->getWrappedType()->name)->toBe(SeoSitemapType::NAME);
});

it('has baseUrl, handle, and site arguments', function () {
    $args = (new SitemapField)->args();

    expect($args)->toHaveKeys(['baseUrl', 'handle', 'site']);
    expect($args['baseUrl']['rules'])->toContain('url');
});

it('resolves sitemap URLs for a collection', function () {
    Collection::make('pages')
        ->sites(['english'])
        ->routes('/{slug}')
        ->saveQuietly();

    Entry::make()
        ->collection('pages')
        ->slug('test-page')
        ->data(['title' => 'Test Page'])
        ->save();

    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'collection';

    $result = (new SitemapField)->resolve(null, [], null, $info);

    expect($result)->toBeArray();
    expect($result)->not->toBeEmpty();
});

it('filters sitemap URLs by handle', function () {
    Collection::make('pages')
        ->sites(['english'])
        ->routes('/{slug}')
        ->saveQuietly();

    Collection::make('articles')
        ->sites(['english'])
        ->routes('/articles/{slug}')
        ->saveQuietly();

    Entry::make()
        ->collection('pages')
        ->slug('test-page')
        ->save();

    Entry::make()
        ->collection('articles')
        ->slug('test-article')
        ->save();

    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'collection';

    $result = (new SitemapField)->resolve(null, ['handle' => 'pages'], null, $info);

    expect($result)->toHaveCount(1);
    expect(collect($result)->first()['loc'])->toContain('test-page');
});

it('filters sitemap URLs by site', function () {
    Collection::make('pages')
        ->sites(['english', 'german'])
        ->routes('/{slug}')
        ->saveQuietly();

    Entry::make()
        ->collection('pages')
        ->slug('test-page')
        ->save();

    Entry::make()
        ->collection('pages')
        ->slug('german-page')
        ->locale('german')
        ->save();

    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'collection';

    $result = (new SitemapField)->resolve(null, ['site' => 'german'], null, $info);

    expect($result)->toHaveCount(1);
    expect(collect($result)->first()['loc'])->toContain('/de/');
});

it('applies custom baseUrl to sitemap URLs', function () {
    Collection::make('pages')
        ->sites(['english'])
        ->routes('/{slug}')
        ->saveQuietly();

    Entry::make()
        ->collection('pages')
        ->slug('test-page')
        ->save();

    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'collection';

    $result = (new SitemapField)->resolve(null, ['baseUrl' => 'https://example.com'], null, $info);

    expect($result)->toBeArray();
    expect(collect($result)->first()['loc'])->toStartWith('https://example.com');
});

it('returns null when no URLs exist', function () {
    $info = Mockery::mock(ResolveInfo::class);
    $info->fieldName = 'taxonomy';

    $result = (new SitemapField)->resolve(null, [], null, $info);

    expect($result)->toBeNull();
});
