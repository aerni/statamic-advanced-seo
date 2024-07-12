<?php

namespace Aerni\AdvancedSeo\Sitemaps\Custom;

use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;

class CustomSitemap extends BaseSitemap
{
    protected Collection $urls;

    public function __construct(protected string $handle)
    {
        $this->urls = collect();
    }

    public function add(CustomSitemapUrl $item): self
    {
        $this->urls = $this->urls->push($item)->unique();

        return $this;
    }

    public function urls(): Collection
    {
        return $this->urls->map->toArray();
    }
}
