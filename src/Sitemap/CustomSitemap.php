<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Statamic\Facades\Site;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CustomSitemap
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

    public function url(): string
    {
        $siteUrl = Site::get($this->site)->absoluteUrl();
        $filename = "sitemap_{$this->type}_{$this->handle}.xml";

        return $siteUrl . '/' . $filename;
    }

    public function lastmod(): ?string
    {
        return $this->items()->sortByDesc('lastmod')->first()['lastmod'];
    }

    public function clearCache(): void
    {
        Cache::forget("advanced-seo::sitemaps::{$this->site}");
        Cache::forget("advanced-seo::sitemaps::{$this->site}::{$this->type}::{$this->handle}");
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (empty($arguments)) {
            return $this->$name;
        }

        $this->$name = $arguments[0];

        return $this;
    }
}
