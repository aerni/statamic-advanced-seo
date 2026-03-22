<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Contracts\SitemapFile;
use Aerni\AdvancedSeo\Contracts\SitemapIndex as Contract;
use Aerni\AdvancedSeo\Facades\Sitemap as SitemapRegistry;
use Aerni\AdvancedSeo\Sitemaps\Collections\CollectionSitemap;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Contracts\Taxonomies\Taxonomy as TaxonomyContract;
use Statamic\Facades\Addon;
use Statamic\Facades\Blink;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Taxonomy;

class SitemapIndex implements Arrayable, Contract, Renderable, Responsable, SitemapFile
{
    public function __construct(protected Domain $domain) {}

    /**
     * Get the domain of this index.
     */
    public function domain(): Domain
    {
        return $this->domain;
    }

    /**
     * Get the site handles for this index's domain.
     */
    public function sites(): Collection
    {
        return $this->domain->sites->map->handle();
    }

    /**
     * Find a sitemap by ID.
     */
    public function find(string $id): ?Sitemap
    {
        return $this->sitemaps()->first(fn (Sitemap $sitemap) => $sitemap->id() === $id);
    }

    public function sitemaps(): Collection
    {
        return Blink::once($this->cacheKey(), function () {
            return $this->customSitemaps()
                ->merge($this->collectionSitemaps())
                ->merge($this->taxonomySitemaps())
                ->each->index($this);
        });
    }

    protected function cacheKey(): string
    {
        return "advanced-seo.sitemap-index.{$this->domain}";
    }

    protected function customSitemaps(): Collection
    {
        return collect([
            ...config('advanced-seo.sitemap.custom', []),
            ...app('advanced-seo.sitemaps'),
        ])
            ->map(fn ($sitemap) => is_string($sitemap) ? app($sitemap) : $sitemap)
            ->unique(fn ($sitemap) => $sitemap->id())
            ->filter(fn ($sitemap) => $this->sites()->contains($sitemap->site()))
            ->filter(fn ($sitemap) => $sitemap->urls()->isNotEmpty())
            ->values();
    }

    protected function collectionSitemaps(): Collection
    {
        return CollectionFacade::all()
            ->sortBy('handle')
            ->filter($this->shouldProcessSitemap(...))
            ->mapInto(CollectionSitemap::class)
            ->values();
    }

    protected function taxonomySitemaps(): Collection
    {
        return Taxonomy::all()
            ->sortBy('handle')
            ->filter($this->shouldProcessSitemap(...))
            ->mapInto(TaxonomySitemap::class)
            ->values();
    }

    public function toArray(): array
    {
        return $this->sitemaps()->toArray();
    }

    public function render(): string
    {
        return view('advanced-seo::sitemaps.index', [
            'sitemaps' => $this->toArray(),
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
        return 'sitemap.xml';
    }

    public function file(): ?string
    {
        return File::exists($this->path()) ? File::get($this->path()) : null;
    }

    public function path(): string
    {
        return SitemapRegistry::path($this->domain, $this->filename());
    }

    public function save(): self
    {
        File::ensureDirectoryExists(SitemapRegistry::path($this->domain));

        File::put($this->path(), $this->render());

        return $this;
    }

    protected function shouldProcessSitemap(CollectionContract|TaxonomyContract $model): bool
    {
        return $model->sites()
            ->intersect($this->sites())
            ->contains(fn ($site) => IncludeInSitemap::run($model, $site));
    }
}
