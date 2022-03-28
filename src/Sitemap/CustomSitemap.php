<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;

class CustomSitemap extends BaseSitemap
{
    public function __construct(
        protected string $handle,
        protected string $site,
        protected array $urls,
    ) {
    }

    public function urls(array $urls = null): Collection|self
    {
        return $this->fluentlyGetOrSet('urls')
            ->getter(function ($urls) {
                return collect($urls)->map(fn ($url) => $url->toArray());
            })
            ->args(func_get_args());
    }
}
