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
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/xml; charset=utf-8')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('sitemapindex');
});

it('returns a specific sitemap', function () {
    $this->get('/sitemaps/collection-pages.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/xml; charset=utf-8')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('urlset');
});

it('returns the xsl stylesheet', function () {
    $this->get('/sitemap.xsl')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/xsl; charset=utf-8')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('xsl:stylesheet');
});

it('returns 404 for a nonexistent sitemap', function () {
    $this->get('/sitemaps/nonexistent.xml')->assertNotFound();
});
