<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Aerni\AdvancedSeo\Registries\SitemapRegistry;
use Aerni\AdvancedSeo\Sitemaps\Custom\SitemapBuilder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Traits\ForwardsCalls;
use Statamic\Exceptions\SiteNotFoundException;
use Statamic\Facades\Path;

/**
 * @mixin SitemapRegistry
 */
class SitemapService
{
    use ForwardsCalls;

    public function __construct(protected SitemapRegistry $registry) {}

    /**
     * Create a new custom sitemap builder.
     */
    public function make(string $handle): SitemapBuilder
    {
        return new SitemapBuilder($handle);
    }

    /**
     * Get the XSL stylesheet content.
     */
    public function xsl(): string
    {
        return File::get(__DIR__.'/../../resources/views/sitemaps/sitemap.xsl');
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

    public function __call(string $method, array $parameters): mixed
    {
        return $this->forwardCallTo($this->registry, $method, $parameters);
    }
}
