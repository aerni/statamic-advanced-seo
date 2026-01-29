<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Facades\Domain;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemap;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\SitemapIndex;
use Illuminate\Support\Facades\File;
use Statamic\Exceptions\SiteNotFoundException;
use Statamic\Facades\Path;
use Statamic\Facades\Site;

class SitemapRegistry extends Registry
{
    /**
     * Get the sitemap index for a site.
     */
    public function index(string $site): ?SitemapIndex
    {
        $site = Site::get($site);

        if (! $site) {
            return null;
        }

        return $this->all()->first(fn (SitemapIndex $index) => $index->domain() === Domain::forSite($site));
    }

    /**
     * Create a new custom sitemap.
     */
    public function make(string $handle): CustomSitemap
    {
        return new CustomSitemap($handle);
    }

    /**
     * Create a new custom sitemap URL.
     */
    public function makeUrl(string $loc): CustomSitemapUrl
    {
        return new CustomSitemapUrl($loc);
    }

    /**
     * Get the XSL stylesheet content.
     */
    public function xsl(): string
    {
        return file_get_contents(__DIR__.'/../../resources/views/sitemaps/sitemap.xsl');
    }

    /**
     * Get the storage path for sitemap files.
     * Supports domain-based directories.
     */
    public function path(string $domain = '', string $filename = ''): string
    {
        $basePath = config('advanced-seo.sitemap.path', storage_path('statamic/sitemaps'));

        return Path::assemble($basePath, $domain, $filename);
    }

    /**
     * Generate static sitemap files.
     * If a site handle is provided, generates only that site's domain index.
     * Otherwise, generates all indexes for all domains.
     *
     * @throws SiteNotFoundException
     */
    public function generate(?string $site = null): void
    {
        $indexes = $site
            ? collect([throw_unless($this->index($site), new SiteNotFoundException($site))])
            : $this->all();

        $indexes->each(function (SitemapIndex $index) {
            File::deleteDirectory($this->path($index->domain()));
            $index->save();
            $index->sitemaps()->each->save();
        });
    }

    /**
     * Build sitemap indexes for all domains.
     */
    protected function items(): array
    {
        return Domain::all()
            ->mapInto(SitemapIndex::class)
            ->all();
    }
}
