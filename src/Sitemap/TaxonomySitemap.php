<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Aerni\AdvancedSeo\Sitemap\TermSitemapItem;
use Statamic\Facades\Taxonomy as TaxonomyFacade;
use Aerni\AdvancedSeo\Sitemap\TaxonomySitemapItem;

class TaxonomySitemap extends BaseSitemap
{
    protected string $type = 'taxonomies';
    protected Taxonomy $taxonomy;

    public function __construct(protected string $handle, protected string $site)
    {
        $this->taxonomy = TaxonomyFacade::find($this->handle);
    }

    public function items(): Collection
    {
        return $this->taxonomyItems()->merge($this->termItems());
    }

    protected function taxonomyItems(): Collection
    {
        return $this->taxonomy()
            ->merge($this->collectionTaxonomy())
            ->map(fn ($item) => (new TaxonomySitemapItem($item, $this->site))->toArray());
    }

    protected function termItems(): Collection
    {
        return $this->terms($this->taxonomy)
            ->merge($this->collectionTerms())
            ->map(fn ($item) => (new TermSitemapItem($item))->toArray());
    }

    protected function taxonomy(): Collection
    {
        // We only want to return the taxonomy if the template exists.
        return view()->exists($this->taxonomy->template())
            ? collect([$this->taxonomy])
            : collect();
    }

    protected function terms(Taxonomy $taxonomy): Collection
    {
        $terms = $taxonomy->queryTerms()
            ->where('site', $this->site)
            ->where('published', '!=', false) // We only want published terms.
            ->where('seo_noindex', '!=', true) // We only want indexable terms.
            ->get();

        $template = $terms->first()?->template();

        // We only want to return the terms if the template exists.
        return view()->exists($template) ? $terms : collect();
    }

    protected function collectionTaxonomy()
    {
        // TODO: There is currently no way to get the items for collection taxonomies, e.g. /products/tags
    }

    protected function collectionTaxonomies(): Collection
    {
        // Get all the collections that use this taxonomy.
        $taxonomyCollections = $this->taxonomy->collections();

        /**
         * Attach each collection to a new instance of the taxonomy
         * so that we can get the correct absolute URL of the collection terms later.
         */
        return $taxonomyCollections->map(function ($collection) {
            return $collection->taxonomies()
                ->first(fn ($taxonomy) => $taxonomy->handle() === $this->handle)
                ->collection($collection);
        });
    }

    protected function collectionTerms(): Collection
    {
        // Get the terms of each collection taxonomy.
        $collectionTerms = $this->collectionTaxonomies()->flatMap(function ($taxonomy) {
            return $this->terms($taxonomy);
        });

        // Filter the terms by the entries they are used on.
        $filteredTerms = $collectionTerms->filter(function ($term) {
            return $term->queryEntries()
                ->where('site', $this->site)
                ->where('published', '!=', false) // We only want published entries.
                ->where('uri', '!=', null) // We only want entries that have a route. This works for both single and per-site collection routes.
                ->where('seo_noindex', '!=', true) // We only want indexable terms.
                ->get()
                ->isNotEmpty();
        });

        return $filteredTerms;
    }
}
