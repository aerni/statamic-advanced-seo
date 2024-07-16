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
            ->merge($this->collectionTermUrls())
            ->values();
    }

    protected function taxonomyUrls(): Collection
    {
        return $this->taxonomies()
            ->map(fn ($taxonomy, $site) => (new TaxonomySitemapUrl($taxonomy, $site, $this))->toArray())
            ->values();
    }

    protected function collectionTaxonomyUrls(): Collection
    {
        return $this->collectionTaxonomies()
            ->map(fn ($item) => (new CollectionTaxonomySitemapUrl($item['taxonomy'], $item['site'], $this))->toArray())
            ->filter();
    }

    protected function termUrls(): Collection
    {
        return $this->terms($this->model)
            ->map(fn ($term) => (new TermSitemapUrl($term, $this))->toArray())
            ->filter();
    }

    protected function collectionTermUrls(): Collection
    {
        return $this->collectionTerms()
            ->map(fn ($term) => (new CollectionTermSitemapUrl($term, $this))->toArray())
            ->filter();
    }

    public function taxonomies(): Collection
    {
        // We only want to return the taxonomy if the template exists.
        if (! view()->exists($this->model->template())) {
            return collect();
        }

        return $this->model->sites()
            ->filter(fn ($site) => IncludeInSitemap::run($this->model, $site))
            ->mapWithKeys(fn ($site) => [$site => $this->model]);
    }

    public function terms(Taxonomy $taxonomy): Collection
    {
        $terms = $taxonomy->queryTerms()->get();

        // We only want to return the terms if the template exists.
        if (! view()->exists($terms->first()?->template())) {
            return collect();
        }

        // We only want indexable terms.
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
        // Get the terms of each collection taxonomy.
        $collectionTerms = $this->taxonomyCollections()
            ->flatMap(fn ($taxonomy) => $this->terms($taxonomy));

        // Filter the terms by the entries they are used on.
        return $collectionTerms->filter(function ($term) {
            return $term->queryEntries()
                ->where('published', '!=', false) // We only want published entries.
                ->where('uri', '!=', null) // We only want entries that have a route. This works for both single and per-site collection routes.
                ->where('locale', '=', $term->locale()) // We only want entries with the same locale as the term.
                ->get()
                ->filter(fn ($entry) => IncludeInSitemap::run($entry))
                ->isNotEmpty();
        })->values();
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
