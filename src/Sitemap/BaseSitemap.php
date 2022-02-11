<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Statamic\Facades\Site;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Aerni\AdvancedSeo\Contracts\Sitemap;

abstract class BaseSitemap implements Sitemap
{
    abstract public function __construct(string $handle, string $site);

    abstract public function items(): Collection;

    abstract public function type(): string;

    public function handle(): string
    {
        return $this->handle;
    }

    public function site(): string
    {
        return $this->site;
    }

    public static function make(string $handle, string $site): self
    {
        return new static($handle, $site);
    }

    public function url(): string
    {
        $siteUrl = Site::get($this->site)->absoluteUrl();
        $filename = "sitemap_{$this->type()}_{$this->handle}.xml";

        return $siteUrl . '/' . $filename;
    }

    public function lastmod(): string
    {
        return $this->items()->sortByDesc('lastmod')->first()['lastmod'];
    }

    public function clearCache(): void
    {
        Cache::forget("advanced-seo::sitemaps::{$this->site}");
        Cache::forget("advanced-seo::sitemaps::{$this->site}::{$this->type()}::{$this->handle}");
    }

    public function indexable(): bool
    {
        $config = Seo::find('site', 'indexing')?->in($this->site);

        // If there is no config, the sitemap should be indexable.
        if (is_null($config)) {
            return true;
        }

        // If we have a global noindex, the sitemap shouldn't be indexable.
        if ($config->value('noindex')) {
            return false;
        }

        // Check if the collection/taxonomy is set to be excluded from the sitemap
        $excluded = $config->value("excluded_{$this->type()}") ?? [];

        // If the collection/taxonomy is excluded, the sitemap shouldn't be indexable.
        return ! in_array($this->handle, $excluded);
    }
}
