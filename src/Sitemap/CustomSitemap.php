<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;

class CustomSitemap extends BaseSitemap
{
    protected string $type = 'custom';

    public function __construct(
        public string $handle,
        public string $site,
        public array $items,
    ) {
    }

    public function items(array $items = null): Collection|self
    {
        if (! $items) {
            return collect($this->items)->map(fn ($item) => $item->toArray());
        }

        $this->items = $items;

        return $this;
    }
}
