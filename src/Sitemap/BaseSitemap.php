<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Statamic\Facades\Site;
use Statamic\Facades\URL;

abstract class BaseSitemap implements Sitemap
{
    abstract public function items(): Collection|self;

    public function url(): string
    {
        $siteUrl = Site::get($this->site)->absoluteUrl();
        $filename = "sitemap_{$this->type}_{$this->handle}.xml";

        return URL::tidy("{$siteUrl}/{$filename}");
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

    public function indexable(): bool
    {
        $disabled = config("advanced-seo.disabled.{$this->type}", []);

        // Check if the collection/taxonomy is set to be disabled globally.
        if (in_array($this->handle, $disabled)) {
            return false;
        }

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
        $excluded = $config->value("excluded_{$this->type}") ?? [];

        // If the collection/taxonomy is excluded, the sitemap shouldn't be indexable.
        return ! in_array($this->handle, $excluded);
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
