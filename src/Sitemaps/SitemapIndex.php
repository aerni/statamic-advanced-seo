<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Contracts\SitemapFile;
use Aerni\AdvancedSeo\Contracts\SitemapIndex as Contract;
use Aerni\AdvancedSeo\Facades\Sitemap as SitemapRepository;
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
    protected array $sitemaps = [];

    public function add(Sitemap $sitemap): self
    {
        $this->sitemaps = collect($this->sitemaps)
            ->push($sitemap)
            ->unique(fn ($sitemap) => $sitemap->handle())
            ->all();

        return $this;
    }

    public function sitemaps(): Collection
    {
        return Blink::once($this->filename(), function () {
            return collect($this->sitemaps)
                ->merge($this->collectionSitemaps())
                ->merge($this->taxonomySitemaps());
        });
    }

    protected function collectionSitemaps(): Collection
    {
        return CollectionFacade::all()
            ->filter($this->shouldProcessSitemap(...))
            ->mapInto(CollectionSitemap::class)
            ->values();
    }

    protected function taxonomySitemaps(): Collection
    {
        return Taxonomy::all()
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
        return SitemapRepository::path($this->filename());
    }

    public function save(): self
    {
        File::ensureDirectoryExists(SitemapRepository::path());

        File::put($this->path(), $this->render());

        return $this;
    }

    protected function shouldProcessSitemap(CollectionContract|TaxonomyContract $model): bool
    {
        return $model->sites()
            ->map(fn ($site) => IncludeInSitemap::run($model, $site))
            ->contains('true');
    }
}
