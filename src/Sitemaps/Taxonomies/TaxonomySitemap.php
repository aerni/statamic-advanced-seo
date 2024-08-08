<?php

namespace Aerni\AdvancedSeo\Sitemaps\Taxonomies;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;
use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Blink;

class TaxonomySitemap extends BaseSitemap
{
    public function __construct(protected Taxonomy $model) {}

    public function urls(): Collection
    {
        return Blink::once($this->filename(), function () {
            return $this->taxonomyUrls()
                ->merge($this->termUrls())
                ->merge($this->collectionTaxonomyUrls())
                ->merge($this->collectionTermUrls())
                ->filter(fn ($url) => $url->canonicalTypeIsCurrent());
        });
    }

    protected function taxonomyUrls(): Collection
    {
        return $this->taxonomies()
            ->map(fn ($taxonomy, $site) => new TaxonomySitemapUrl($taxonomy, $site, $this))
            ->values();
    }

    protected function termUrls(): Collection
    {
        return $this->terms()
            ->map(fn ($term) => new TermSitemapUrl($term, $this));
    }

    protected function collectionTaxonomyUrls(): Collection
    {
        return $this->collectionTaxonomies()
            ->map(fn ($item) => new CollectionTaxonomySitemapUrl($item['taxonomy'], $item['site'], $this));
    }

    protected function collectionTermUrls(): Collection
    {
        return $this->collectionTerms()
            ->map(fn ($term) => new CollectionTermSitemapUrl($term, $this));
    }

    public function taxonomies(): Collection
    {
        if (! view()->exists($this->model->template())) {
            return collect();
        }

        return $this->model->sites()
            ->filter(fn ($site) => IncludeInSitemap::run($this->model, $site))
            ->mapWithKeys(fn ($site) => [$site => $this->model]);
    }

    protected function terms(): Collection
    {
        $terms = $this->model
            ->queryTerms()
            ->where($this->includeInSitemapQuery(...))
            ->get();

        if (! view()->exists($terms->first()?->template())) {
            return collect();
        }

        return $terms->filter(IncludeInSitemap::run(...));
    }

    public function collectionTaxonomies(): Collection
    {
        return $this->model->collections()
            ->map(fn ($collection) => $this->freshTaxonomy()->collection($collection)) // Need to get a fresh instance of the Taxonomy, else we'll override the previously set collection.
            ->filter(fn ($taxonomy) => view()->exists($taxonomy->template()))
            ->flatMap(function ($taxonomy) {
                // Only allow sites that have been set on both the taxonomy and the collection
                return $taxonomy->sites()
                    ->merge($taxonomy->collection()->sites())
                    ->duplicates()
                    ->map(fn ($site) => ['taxonomy' => $taxonomy, 'site' => $site]);
            })
            ->filter(fn ($item) => IncludeInSitemap::run($item['taxonomy'], $item['site']));
    }

    public function collectionTerms(): Collection
    {
        $terms = $this->model->queryTerms()->get();

        return $this->model->collections()
            ->flatMap(function ($collection) use ($terms) {
                return $terms->map(fn ($term) => $term->fresh()->collection($collection)); // Need to get a fresh instance of the Term, else we'll override the previously set collection.
            })
            ->filter(fn ($term) => view()->exists($term->template()))
            ->filter(function ($term) {
                // Only allow sites that have been set on both the taxonomy and the collection
                return $term->taxonomy()->sites()
                    ->merge($term->collection()->sites())
                    ->duplicates()
                    ->contains($term->locale());
            })
            ->filter(fn ($term) => IncludeInSitemap::run($term->taxonomy(), $term->locale()));
    }

    protected function freshTaxonomy(): Taxonomy
    {
        return \Statamic\Facades\Taxonomy::find($this->model->id());
    }
}
