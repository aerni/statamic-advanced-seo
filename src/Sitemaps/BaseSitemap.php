<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Statamic\Facades\URL;
use Illuminate\Support\Str;
use Statamic\Facades\Addon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Statamic\Contracts\Query\Builder;
use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Concerns\HasBaseUrl;
use Illuminate\Contracts\Support\Arrayable;
use Aerni\AdvancedSeo\Contracts\SitemapFile;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Statamic\Support\Traits\FluentlyGetsAndSets;
use Aerni\AdvancedSeo\Contracts\Sitemap as Contract;

abstract class BaseSitemap implements Arrayable, Contract, Renderable, Responsable, SitemapFile
{
    use FluentlyGetsAndSets, HasBaseUrl;

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
        return URL::tidy(config('app.url')."/{$this->filename()}");
    }

    public function lastmod(): ?string
    {
        return $this->urls()->sortByDesc('lastmod')->first()?->lastmod();
    }

    protected function includeInSitemapQuery(Builder $query): Builder
    {
        return $query
            ->where('published', true)
            ->whereNotNull('url');

        /**
         * A reminder for my later self. We used to also include the following queries here:
         *
         * $query
         *   ->where('seo_noindex', false)
         *   ->where('seo_sitemap_enabled', true)
         *   ->where('seo_canonical_type', 'current')
         *
         * But we removed them as they lead to unexpected results due to the following reasons:
         *
         * Features like the sitemap are evaluated and enabled based the locale of an entry,
         * which determines if certain fields are added when extending the blueprint.
         * But the query lead to unexpected results because it looks in the Stache which could
         * have fields in there or not based on which entry built the Stache. It's complicated.
         * So we just removed the queries as we are filtering those specific fields later on anyways.
         */
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url(),
            'lastmod' => $this->lastmod(),
            'urls' => $this->urls()->toArray(),
        ];
    }

    public function render(): string
    {
        return view('advanced-seo::sitemaps.show', [
            'urls' => $this->toArray()['urls'],
            'version' => Addon::get('aerni/advanced-seo')->version(),
        ])->render();
    }

    public function toResponse($request)
    {
        return response(
            content: $this->file() ?? $this->render(),
            headers: [
                'Content-Type' => 'text/xml',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]
        );
    }

    public function filename(): string
    {
        return "{$this->type()}-{$this->handle()}-sitemap.xml";
    }

    public function file(): ?string
    {
        return File::exists($this->path()) ? File::get($this->path()) : null;
    }

    public function path(): string
    {
        return Sitemap::path($this->filename());
    }

    public function save(): self
    {
        File::ensureDirectoryExists(Sitemap::path());

        File::put($this->path(), $this->render());

        return $this;
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->fluentlyGetOrSet($name)->args($arguments);
    }
}
