<?php

namespace Aerni\AdvancedSeo\Registries;

use Aerni\AdvancedSeo\Sitemaps\Domain;
use Statamic\Facades\Site as Sites;
use Statamic\Facades\URL;
use Statamic\Sites\Site;

class DomainRegistry extends Registry
{
    /**
     * Find a domain by name.
     */
    public function find(string $name): ?Domain
    {
        return $this->all()->first(fn (Domain $domain) => $domain->name === $name);
    }

    /**
     * Get the domain for a site.
     */
    public function forSite(Site $site): Domain
    {
        return $this->all()->first(fn (Domain $domain) => $domain->sites->contains($site));
    }

    /**
     * Build domains by grouping sites by their host.
     */
    protected function items(): array
    {
        return Sites::all()
            ->groupBy($this->extractHost(...))
            ->map(fn ($sites, $name) => new Domain($name, $sites))
            ->values()
            ->all();
    }

    /**
     * Extract the host from a site's URL.
     * For relative site URLs, falls back to APP_URL.
     */
    private function extractHost(Site $site): string
    {
        $url = $site->url();

        return parse_url(
            URL::isAbsolute($url) ? $url : config('app.url'),
            PHP_URL_HOST
        );
    }
}
