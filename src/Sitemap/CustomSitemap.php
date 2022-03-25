<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;

class CustomSitemap extends BaseSitemap
{
    public function __construct(
        public string $type,
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
