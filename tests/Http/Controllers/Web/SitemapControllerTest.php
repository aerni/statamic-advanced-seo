<?php

use Aerni\AdvancedSeo\Tests\Concerns\EnablesSitemap;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

uses(PreventsSavingStacheItemsToDisk::class, EnablesSitemap::class);

beforeEach(function () {
    Site::setSites([
        'english' => ['name' => 'English', 'url' => 'https://example.com', 'locale' => 'en'],
    ]);

    Collection::make('pages')->routes('/{slug}')->sites(['english'])->saveQuietly();
});

it('returns the sitemap index', function () {
    $response = $this->get('/sitemap.xml')->assertOk()->assertSee('sitemapindex');
    $headers = $response->headers;

    expect(strtolower($headers->get('Content-Type')))->toBe('text/xml; charset=utf-8');
    expect($headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
});

it('returns a specific sitemap', function () {
    $response = $this->get('/sitemaps/collection-pages.xml')->assertOk()->assertSee('urlset');
    $headers = $response->headers;

    expect(strtolower($headers->get('Content-Type')))->toBe('text/xml; charset=utf-8');
    expect($headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
});

it('returns the xsl stylesheet', function () {
    $response = $this->get('/sitemap.xsl')->assertOk()->assertSee('xsl:stylesheet');
    $headers = $response->headers;

    expect(strtolower($headers->get('Content-Type')))->toBe('text/xsl; charset=utf-8');
    expect($headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
});

it('returns 404 for a nonexistent sitemap', function () {
    $this->get('/sitemaps/nonexistent.xml')->assertNotFound();
});
