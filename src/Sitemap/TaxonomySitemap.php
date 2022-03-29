<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;

class TaxonomySitemap extends BaseSitemap
{
    public function __construct(protected Taxonomy $model)
    {
    }

    public function urls(): Collection
    {
        return $this->taxonomyUrls()->merge($this->termUrls())->values();
    }

    protected function taxonomyUrls(): Collection
    {
        return $this->taxonomies()
            ->merge($this->collectionTaxonomies())
            ->map(fn ($taxonomy, $site) => (new TaxonomySitemapUrl($taxonomy, $site, $this))->toArray());
    }

    protected function termUrls(): Collection
    {
        return $this->terms($this->model)
            ->merge($this->collectionTerms())
            ->map(fn ($term) => (new TermSitemapUrl($term, $this))->toArray());
    }

    protected function taxonomies(): Collection
    {
        // We only want to return the taxonomy if the template exists.
        if (! view()->exists($this->model->template())) {
            return collect();
        }

        /**
         * Return an item for each site configured on the taxonomy,
         * so that we can get the correct taxonomy URL in the TaxonomySitemapItem.
         */
        return $this->model->sites()
            ->mapWithKeys(fn ($site) => [$site => $this->model])
            ->filter(fn ($taxonomy, $site) => $this->indexable($taxonomy, $site)); // Filter out any taxonomies that are not indexable.
    }

    protected function terms(Taxonomy $taxonomy): Collection
    {
        $terms = $taxonomy->queryTerms()
            ->where('published', '!=', false) // We only want published terms.
            ->where('seo_noindex', '!=', true) // We only want indexable terms.
            ->get()
            ->filter(fn ($term) => $term->taxonomy()->sites()->contains($term->locale())) // We only want terms of sites that are configured on the taxonomy.
            ->filter(fn ($term) => $this->indexable($term)); // Filter out any terms that are not indexable.

        $template = $terms->first()?->template();

        // We only want to return the terms if the template exists.
        return view()->exists($template) ? $terms : collect();
    }

    protected function collectionTaxonomies()
    {
        // TODO: There is currently no way to get the items for collection taxonomies, e.g. /products/tags
    }

    protected function taxonomyCollections(): Collection
    {
        // Get all the collections that use this taxonomy.
        $taxonomyCollections = $this->model->collections();

        /**
         * Attach each collection to a new instance of the taxonomy
         * so that we can get the correct absolute URL of the collection terms later.
         */
        return $taxonomyCollections->map(function ($collection) {
            return $collection->taxonomies()
                ->first(fn ($taxonomy) => $taxonomy->handle() === $this->handle())
                ->collection($collection);
        });
    }

    protected function collectionTerms(): Collection
    {
        // Get the terms of each collection taxonomy.
        $collectionTerms = $this->taxonomyCollections()
            ->flatMap(fn ($taxonomy) => $this->terms($taxonomy));

        // Filter the terms by the entries they are used on.
        $filteredTerms = $collectionTerms->filter(function ($term) {
            return $term->queryEntries()
                ->where('published', '!=', false) // We only want published entries.
                ->where('uri', '!=', null) // We only want entries that have a route. This works for both single and per-site collection routes.
                ->where('seo_noindex', '!=', true) // We only want indexable terms.
                ->get()
                // TODO: Do we also need to filter by indexable to remove terms if their collection has been deactivated?
                // ->filter(fn ($entry) => $this->indexable($entry)) // Filter out any entries that are not indexable.
                ->isNotEmpty();
        });

        return $filteredTerms;
    }
}
