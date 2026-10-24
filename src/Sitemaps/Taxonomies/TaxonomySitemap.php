<?php

namespace Aerni\AdvancedSeo\Sitemaps\Taxonomies;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Actions\ViewExistsForSite;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;
use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Blink;

class TaxonomySitemap extends BaseSitemap
{
    public function __construct(protected Taxonomy $model) {}

    public function type(): string
    {
        return 'taxonomy';
    }

    public function handle(): string
    {
        return $this->model->handle();
    }

    public function urls(): Collection
    {
        return Blink::once($this->cacheKey(), function () {
            return $this->taxonomyUrls()
                ->merge($this->termUrls())
                ->merge($this->collectionTaxonomyUrls())
                ->merge($this->collectionTermUrls())
                ->each->sitemap($this);
        });
    }

    protected function taxonomyUrls(): Collection
    {
        return $this->taxonomies()
            ->filter(fn ($taxonomy, $site) => $this->sites()->contains($site))
            ->map(fn ($taxonomy, $site) => new TaxonomySitemapUrl($taxonomy, $site))
            ->values();
    }

    protected function termUrls(): Collection
    {
        return $this->terms()->mapInto(TermSitemapUrl::class);
    }

    protected function collectionTaxonomyUrls(): Collection
    {
        return $this->collectionTaxonomies()
            ->filter(fn ($item) => $this->sites()->contains($item['site']))
            ->map(fn ($item) => new CollectionTaxonomySitemapUrl($item['taxonomy'], $item['site']));
    }

    protected function collectionTermUrls(): Collection
    {
        return $this->collectionTerms()
            ->filter(fn ($term) => $this->sites()->contains($term->locale()))
            ->mapInto(CollectionTermSitemapUrl::class);
    }

    public function taxonomies(): Collection
    {
        return $this->model->sites()
            ->filter(fn ($site) => ViewExistsForSite::run($this->model->template(), $site))
            ->filter(fn ($site) => IncludeInSitemap::run($this->model, $site))
            ->mapWithKeys(fn ($site) => [$site => $this->model]);
    }

    protected function terms(): Collection
    {
        return $this->model
            ->queryTerms()
            ->where($this->includeInSitemapQuery(...))
            ->get()
            ->filter(fn ($term) => ViewExistsForSite::run($term->template(), $term->locale()))
            ->filter(IncludeInSitemap::run(...));
    }

    public function collectionTaxonomies(): Collection
    {
        return Blink::once($this->cacheKey().'.collection-taxonomies', function () {
            return $this->model->collections()
                ->map(fn ($collection) => $this->freshTaxonomy()->collection($collection)) // Need to get a fresh instance of the Taxonomy, else we'll override the previously set collection.
                ->flatMap(function ($taxonomy) {
                    // Only allow sites that have been set on both the taxonomy and the collection
                    return $taxonomy->sites()
                        ->merge($taxonomy->collection()->sites())
                        ->duplicates()
                        ->map(fn ($site) => ['taxonomy' => $taxonomy, 'site' => $site]);
                })
                ->filter(fn ($item) => ViewExistsForSite::run($item['taxonomy']->template(), $item['site']))
                ->filter(fn ($item) => IncludeInSitemap::run($item['taxonomy'], $item['site']));
        });
    }

    public function collectionTerms(): Collection
    {
        return Blink::once($this->cacheKey().'.collection-terms', function () {
            $terms = $this->model->queryTerms()->get();

            return $this->model->collections()
                ->flatMap(function ($collection) use ($terms) {
                    return $terms->map(fn ($term) => $term->fresh()->collection($collection)); // Need to get a fresh instance of the Term, else we'll override the previously set collection.
                })
                ->filter(fn ($term) => ViewExistsForSite::run($term->template(), $term->locale()))
                ->filter(function ($term) {
                    // Only allow sites that have been set on both the taxonomy and the collection
                    return $term->taxonomy()->sites()
                        ->merge($term->collection()->sites())
                        ->duplicates()
                        ->contains($term->locale());
                })
                ->filter(fn ($term) => IncludeInSitemap::run($term->taxonomy(), $term->locale()));
        });
    }

    protected function freshTaxonomy(): Taxonomy
    {
        return \Statamic\Facades\Taxonomy::find($this->model->id());
    }
}
