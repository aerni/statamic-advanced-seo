<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\URL;
use Statamic\Support\Traits\FluentlyGetsAndSets;

abstract class BaseSitemap implements Sitemap
{
    use FluentlyGetsAndSets;

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
        $baseUrl = url('/');
        $filename = "sitemap_{$this->type()}_{$this->handle()}.xml";

        return URL::tidy("{$baseUrl}/{$filename}");
    }

    public function lastmod(): ?string
    {
        return $this->urls()->sortByDesc('lastmod')->first()['lastmod'];
    }

    public function clearCache(): void
    {
        Cache::forget("advanced-seo::sitemaps::index");
        Cache::forget("advanced-seo::sitemaps::{$this->id()}");
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->fluentlyGetOrSet($name)->args($arguments);
    }
}
