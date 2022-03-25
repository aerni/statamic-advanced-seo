<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;

class CustomSitemap extends BaseSitemap
{
    protected string $type = 'custom';

    public function __construct(
        protected string $handle,
        protected string $site,
        protected array $items,
    ) {
    }

    public function items(array $items = null): Collection|self
    {
        return $this->fluentlyGetOrSet('items')
            ->getter(function ($items) {
                return collect($items)->map(fn ($item) => $item->toArray());
            })
            ->args(func_get_args());
    }
}
