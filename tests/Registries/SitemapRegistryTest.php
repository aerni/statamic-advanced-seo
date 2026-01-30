<?php

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemap;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\SitemapIndex;
use Aerni\AdvancedSeo\Tests\Concerns\EnablesSitemap;
use Illuminate\Support\Facades\File;
use Statamic\Exceptions\SiteNotFoundException;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesSitemap::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
        'french' => ['name' => 'French', 'url' => 'https://french.example.com', 'locale' => 'fr'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['english', 'german', 'french'])->saveQuietly();
});

afterEach(function () {
    File::deleteDirectory(Sitemap::path());
});

it('can create a custom sitemap', function () {
    expect(Sitemap::make('custom-pages'))
        ->toBeInstanceOf(CustomSitemap::class);
});

it('can create a custom sitemap url', function () {
    expect(Sitemap::makeUrl('https://example.com/page'))
        ->toBeInstanceOf(CustomSitemapUrl::class);
});

it('can get the xsl stylesheet', function () {
    expect(Sitemap::xsl())
        ->toBeString()
        ->toContain('xsl:stylesheet');
});

it('can get the base storage path', function () {
    expect(Sitemap::path())->toContain('statamic/sitemaps');

    config(['advanced-seo.sitemap.path' => '/custom/sitemaps']);

    expect(Sitemap::path())->toContain('/custom/sitemaps');
});

it('can get a domain-specific path', function () {
    expect(Sitemap::path('example.com'))->toContain('statamic/sitemaps/example.com');
});

it('can get a full file path', function () {
    expect(Sitemap::path('example.com', 'sitemap.xml'))->toContain('statamic/sitemaps/example.com/sitemap.xml');
});

it('can get the sitemap index for a valid site', function () {
    expect(Sitemap::index('english'))->toBeInstanceOf(SitemapIndex::class);
});

it('returns null for an invalid site', function () {
    expect(Sitemap::index('nonexistent'))->toBeNull();
});

it('can get all sitemap indexes', function () {
    $indexes = Sitemap::all();

    $domains = $indexes->map(fn ($index) => $index->domain()->name);

    expect($indexes)->toHaveCount(2)
        ->and($domains)->toContain('example.com')
        ->and($domains)->toContain('french.example.com');
});

it('can generate sitemaps for all sites', function () {
    Entry::make()->collection('pages')->locale('english')->slug('about')->save();
    Entry::make()->collection('pages')->locale('french')->slug('a-propos')->save();

    Sitemap::generate();

    expect(File::exists(Sitemap::path('example.com', 'sitemap.xml')))->toBeTrue()
        ->and(File::exists(Sitemap::path('example.com', 'collection-pages.xml')))->toBeTrue()
        ->and(File::exists(Sitemap::path('french.example.com', 'sitemap.xml')))->toBeTrue()
        ->and(File::exists(Sitemap::path('french.example.com', 'collection-pages.xml')))->toBeTrue();
});

it('can generate sitemaps for a specific site', function () {
    Entry::make()->collection('pages')->locale('english')->slug('about')->save();
    Entry::make()->collection('pages')->locale('french')->slug('a-propos')->save();

    Sitemap::generate('english');

    expect(File::exists(Sitemap::path('example.com', 'sitemap.xml')))->toBeTrue()
        ->and(File::exists(Sitemap::path('example.com', 'collection-pages.xml')))->toBeTrue()
        ->and(File::exists(Sitemap::path('french.example.com', 'sitemap.xml')))->toBeFalse()
        ->and(File::exists(Sitemap::path('french.example.com', 'collection-pages.xml')))->toBeFalse();
});

it('throws an exception when trying to generate a sitemap for an invalid site', function () {
    Sitemap::generate('nonexistent');
})->throws(SiteNotFoundException::class);

it('clears existing sitemaps before generating', function () {
    $path = Sitemap::path('example.com');

    File::ensureDirectoryExists($path);
    File::put($path.'stale-sitemap.xml', 'stale');

    Sitemap::generate('english');

    expect(File::exists($path.'stale-sitemap.xml'))->toBeFalse()
        ->and(File::exists($path.'sitemap.xml'))->toBeTrue();
});
