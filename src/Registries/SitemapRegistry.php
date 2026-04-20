<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Facades\Domain;
use Aerni\AdvancedSeo\Sitemaps\SitemapIndex;
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
     * Build sitemap indexes for all domains.
     */
    protected function items(): array
    {
        return Domain::all()
            ->mapInto(SitemapIndex::class)
            ->all();
    }
}
