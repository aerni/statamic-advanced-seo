<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;

class CustomSitemap extends BaseSitemap
{
    public function __construct(protected string $handle)
    {
        $this->urls = collect();
    }

    public function add(CustomSitemapUrl $item): self
    {
        $this->urls = $this->urls->push($item)->unique();

        return $this;
    }

    protected function collectUrls(): Collection
    {
        return $this->urls->map->toArray();
    }
}
