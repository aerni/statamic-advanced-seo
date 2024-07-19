<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Aerni\AdvancedSeo\Concerns\HasBaseUrl;
use Aerni\AdvancedSeo\Contracts\Sitemap;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Statamic\Contracts\Query\Builder;
use Statamic\Facades\URL;
use Statamic\Support\Traits\FluentlyGetsAndSets;

abstract class BaseSitemap implements Sitemap, Arrayable
{
    use FluentlyGetsAndSets, HasBaseUrl;

    protected Collection $urls;

    abstract public function urls(): Collection;

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
        $baseUrl = config('app.url');
        $filename = "sitemap-{$this->type()}-{$this->handle()}.xml";

        return URL::tidy("{$baseUrl}/{$filename}");
    }

    public function lastmod(): ?string
    {
        return $this->urls()->sortByDesc('lastmod')->first()?->lastmod();
    }

    public function clearCache(): void
    {
        Cache::forget('advanced-seo::sitemaps::index');
        Cache::forget("advanced-seo::sitemaps::{$this->id()}");
    }

    protected function includeInSitemapQuery(Builder $query): Builder
    {
        return $query
            ->where('published', true)
            ->whereNotNull('url')
            ->where('seo_noindex', false)
            ->where('seo_sitemap_enabled', true)
            ->where('seo_canonical_type', 'current');
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url(),
            'lastmod' => $this->lastmod(),
        ];
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->fluentlyGetOrSet($name)->args($arguments);
    }
}
