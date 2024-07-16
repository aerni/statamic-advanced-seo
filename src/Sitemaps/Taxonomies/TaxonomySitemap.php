<?php

namespace Aerni\AdvancedSeo\Sitemaps\Taxonomies;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;
use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;

class TaxonomySitemap extends BaseSitemap
{
    public function __construct(protected Taxonomy $model) {}

    public function urls(): Collection
    {
        return $this->taxonomyUrls()
            ->merge($this->collectionTaxonomyUrls())
            ->merge($this->termUrls())
            ->merge($this->collectionTermUrls());
    }

    protected function taxonomyUrls(): Collection
    {
        return $this->taxonomies()
            ->map(fn ($taxonomy, $site) => (new TaxonomySitemapUrl($taxonomy, $site, $this))->toArray())
            ->filter()
            ->values();
    }

    protected function collectionTaxonomyUrls(): Collection
    {
        return $this->collectionTaxonomies()
            ->map(fn ($item) => (new CollectionTaxonomySitemapUrl($item['taxonomy'], $item['site'], $this))->toArray())
            ->filter()
            ->values();
    }

    protected function termUrls(): Collection
    {
        return $this->terms()
            ->map(fn ($term) => (new TermSitemapUrl($term, $this))->toArray())
            ->filter()
            ->values();
    }

    protected function collectionTermUrls(): Collection
    {
        return $this->collectionTerms()
            ->map(fn ($term) => (new CollectionTermSitemapUrl($term, $this))->toArray())
            ->filter()
            ->values();
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
        $terms = $this->model->queryTerms()->get();

        if (! view()->exists($terms->first()?->template())) {
            return collect();
        }

        return $terms->filter(IncludeInSitemap::run(...));
    }

    public function collectionTaxonomies(): Collection
    {
        return $this->taxonomyCollections()
            ->filter(fn ($taxonomy) => view()->exists($taxonomy->template()))
            ->flatMap(function ($taxonomy) {
                return $taxonomy->collection()->sites()
                    ->map(fn ($site) => ['taxonomy' => $taxonomy, 'site' => $site]);
            })
            ->filter(fn ($item) => IncludeInSitemap::run($item['taxonomy'], $item['site']));
    }

    public function collectionTerms(): Collection
    {
        $terms = $this->model->queryTerms()->get();

        return $this->model->collections()
            ->flatMap(function ($collection) use ($terms) {
                return $terms->map(fn ($term) => $term->fresh()->collection($collection));
            })
            ->filter(fn ($term) => view()->exists($term->template()))
            ->filter(fn ($term) => IncludeInSitemap::run($term->taxonomy(), $term->locale()));
    }

    /**
     * Get all the collections that use this taxonomy and attach the each collection
     * to a new instance of the taxonomy so that we can get the correct absolute URL
     * of the collection terms later.
     */
    protected function taxonomyCollections(): Collection
    {
        return $this->model->collections()->map(function ($collection) {
            return $collection->taxonomies()
                ->first(fn ($taxonomy) => $taxonomy->handle() === $this->handle())
                ->collection($collection);
        });
    }
}
