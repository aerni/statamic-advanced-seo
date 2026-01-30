<?php

use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemapUrl;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class);

beforeEach(function () {
    flushBlink();

    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
        'german' => ['name' => 'German', 'url' => 'https://example.com/de', 'locale' => 'de'],
    ]);
});

it('generates a slugified id from type and handle', function () {
    $sitemap = new TestSitemap;

    expect($sitemap->id())->toBe('test-pages');
});

it('generates a filename from the id', function () {
    $sitemap = new TestSitemap;

    expect($sitemap->filename())->toBe('test-pages.xml');
});

it('can set and retrieve the index', function () {
    $index = Sitemap::index('english');
    $sitemap = new TestSitemap;
    $sitemap->index($index);

    expect($sitemap->index())->toBe($index);
});

it('returns the sites from the index', function () {
    $index = Sitemap::index('english');
    $sitemap = new TestSitemap;
    $sitemap->index($index);

    expect($sitemap->sites()->all())->toBe($index->sites()->all());
});

it('returns the most recent lastmod from its urls', function () {
    Carbon::setTestNow('2025-06-15 12:00:00');

    $older = new CustomSitemapUrl('https://example.com/old');
    $older->lastmod(now()->subDays(10));

    $newer = new CustomSitemapUrl('https://example.com/new');
    $newer->lastmod(now());

    $sitemap = new TestSitemap(testUrls: collect([$older, $newer]));

    expect($sitemap->lastmod())->toBe($newer->lastmod());

    Carbon::setTestNow();
});

it('returns null for lastmod when there are no urls', function () {
    $sitemap = new TestSitemap;

    expect($sitemap->lastmod())->toBeNull();
});

it('converts to array with expected keys', function () {
    $index = Sitemap::index('english');
    $sitemap = new TestSitemap;
    $sitemap->index($index);

    expect($sitemap->toArray())->toHaveKeys(['url', 'lastmod', 'urls']);
});

it('responds with xml content type and noindex header', function () {
    $index = Sitemap::index('english');
    $sitemap = new TestSitemap;
    $sitemap->index($index);

    $response = $sitemap->toResponse(request());

    expect($response->headers->get('Content-Type'))->toBe('text/xml')
        ->and($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
});

class TestSitemap extends BaseSitemap
{
    public function __construct(
        private ?Collection $testUrls = null,
    ) {}

    public function type(): string
    {
        return 'test';
    }

    public function handle(): string
    {
        return 'pages';
    }

    public function urls(): Collection
    {
        return $this->testUrls ?? collect();
    }
}
