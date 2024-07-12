<?php

namespace Aerni\AdvancedSeo\Sitemaps\Taxonomies;

use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Actions\Indexable;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;
use Statamic\Contracts\Taxonomies\Taxonomy;

class TaxonomySitemap extends BaseSitemap
{
    public function __construct(protected Taxonomy $model)
    {
    }

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
            ->mapWithKeys(fn ($site) => [$site => $this->model])
            ->filter(fn ($taxonomy, $site) => Indexable::handle($taxonomy, $site));
    }

    public function collectionTaxonomies(): Collection
    {
        $taxonomies = $this->taxonomyCollections();

        // We only want to return the terms if the template exists.
        if (! view()->exists($taxonomies->first()?->template())) {
            return collect();
        }

        return $taxonomies->flatMap(function ($taxonomy) {
            return $taxonomy->collection()->sites()
                ->map(fn ($site) => [
                    'taxonomy' => $taxonomy,
                    'site' => $site,
                ]);
        })->filter(fn ($item) => Indexable::handle($item['taxonomy'], $item['site']));
    }

    public function terms(Taxonomy $taxonomy): Collection
    {
        $terms = $taxonomy->queryTerms()->get();

        // We only want to return the terms if the template exists.
        if (! view()->exists($terms->first()?->template())) {
            return collect();
        }

        return $terms
            ->filter(fn ($term) => $term->taxonomy()->sites()->contains($term->locale())) // We only want terms of sites that are configured on the taxonomy.
            ->filter(fn ($term) => Indexable::handle($term)); // We only want indexable terms.
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
                ->filter(fn ($entry) => Indexable::handle($entry))
                ->isNotEmpty();
        })->values();
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
}
