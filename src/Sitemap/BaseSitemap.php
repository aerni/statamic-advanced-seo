<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Concerns\HasBaseUrl;
use Aerni\AdvancedSeo\Contracts\Sitemap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Statamic\Facades\URL;
use Statamic\Support\Traits\FluentlyGetsAndSets;

abstract class BaseSitemap implements Sitemap
{
    use FluentlyGetsAndSets, HasBaseUrl;

    abstract protected function collectUrls(): Collection;

    public function urls(): Collection
    {
        // dd(Cache::get("advanced-seo::sitemaps::{$this->id()}"));
        // return $this->collectUrls();
        // Exclude collection from sitemap and save -> will clear the cache
        // Save the collection, now the cache should be empty and the URLs be fetched again
        // But somehow it isnt't. Why?
        ray(Cache::get("advanced-seo::sitemaps::{$this->id()}"))->label('Has Cached Sitemaps');

        $urls = Cache::remember(
            "advanced-seo::sitemaps::{$this->id()}",
            config('advanced-seo.sitemap.expiry', 60) * 60,
            fn () => $this->collectUrls()
        );

        ray(Cache::get("advanced-seo::sitemaps::{$this->id()}"))->label('Newly Cached Sitemaps');

        return $urls;
    }

    public function handle(): string
    {
        return $this->type() === 'custom'
            ? $this->handle
            : $this->model->handle();
    }

    public function type(): string
    {
        return Str::of(static::class)->afterLast('\\')->remove('Sitemap')->lower();
    }

    public function id(): string
    {
        return "{$this->type()}::{$this->handle()}";
    }

    public function url(): string
    {
        // TODO: Should this be the APP_URL or default site URL?
        $baseUrl = config('app.url');
        $filename = "sitemap-{$this->type()}-{$this->handle()}.xml";

        return URL::tidy("{$baseUrl}/{$filename}");
    }

    public function lastmod(): ?string
    {
        // TODO: This probably returns an exception when lastmod doesn't exist.
        // But should returning null be allowed even?
        return $this->urls()->sortByDesc('lastmod')->first()['lastmod'];
    }

    public function clearCache(): self
    {
        ray('Clearing Cache');
        Cache::forget("advanced-seo::sitemaps::{$this->id()}");

        return $this;
    }

    public function refreshCache(): self
    {
        $this->clearCache();

        ray('Refreshing Cache');

        $this->urls();

        return $this;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->fluentlyGetOrSet($name)->args($arguments);
    }
}
