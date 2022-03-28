<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;

class CustomSitemap extends BaseSitemap
{
    public function __construct(protected string $handle)
    {
    }

    public function add(CustomSitemapUrl $item): self
    {
        return $this->fluentlyGetOrSet('urls')
            ->setter(fn ($item) => $this->urls()->push($item))
            ->args(func_get_args());
    }

    public function urls(): Collection
    {
        return $this->fluentlyGetOrSet('urls')
            ->getter(fn ($urls) => collect($urls))
            ->args(func_get_args());
    }
}
