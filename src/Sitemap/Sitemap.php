<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Statamic\Entries\Entry;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Taxonomies\LocalizedTerm;

class Sitemap
{
    protected bool $indexable;

    public function __construct(protected string $type, protected string $handle, protected string $site)
    {
        $this->indexable = $this->indexable();
    }

    public function site(): string
    {
        return $this->site;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function handle(): string
    {
        return $this->handle;
    }

    public function items(): Collection
    {
        if (! $this->indexable) {
            return collect();
        }

        $items = $this->type === 'collections'
            ? $this->collectionEntries()
            : $this->taxonomyAndTerms();

        return $items->map(function ($item) {
            return (new Item($item))->toArray();
        });
    }

    protected function collectionEntries(): Collection
    {
        return EntryFacade::query()
            ->where('collection', $this->handle)
            ->where('site', $this->site)
            ->get()
            ->filter(function ($entry) {
                return $entry->published() && $entry->uri() && ! $this->noindex($entry);
            });
    }

    protected function taxonomyAndTerms(): Collection
    {
        $taxonomyAndTerms = collect();

        $taxonomy = Taxonomy::find($this->handle);

        // We only want to add the taxonomy item if the template exists.
        if (view()->exists($taxonomy->template())) {
            $taxonomyAndTerms->push($taxonomy);
        }

        $taxonomyTerms = $taxonomy->queryTerms()
            ->where('site', $this->site)
            ->get()
            ->filter(function ($term) {
                return $term->published() && ! $this->noindex($term);
            });

        // If we don't have any taxonomy terms, we don't need to continue.
        if ($taxonomyTerms->isEmpty()) {
            return $taxonomyAndTerms;
        }

        // We only want add the terms if the template exists.
        if (view()->exists($taxonomyTerms->first()->template())) {
            $taxonomyAndTerms = $taxonomyAndTerms->merge($taxonomyTerms);
        }

        // Get all the taxonomies that are configured on the collection.
        $collectionTaxonomies = $taxonomy->collections()->flatMap(function ($collection) {
            return $collection->taxonomies()->map->collection($collection)->filter(function ($collectionTaxonomy) {
                return $collectionTaxonomy->handle() === $this->handle;
            });
        });

        // TODO: There is currently no way to get the template of collection taxonomies, e.g. /products/tags
        // Statamic first has to provide a way for this.
        // $collectionTaxonomy = $collectionTaxonomies->filter(function ($taxonomy) {
        //     return view()->exists($taxonomy->template());
        // });

        // Get all the terms that are set on a collection entry.
        $collectionTerms = $collectionTaxonomies->flatMap(function ($taxonomy) {
            return $taxonomy->queryTerms()
                ->where('site', $this->site)
                ->get()->map->collection($taxonomy->collection());
        })->filter(function ($term) {
            // TODO: Test with localized entries. Especially `seo_noindex`.
            $termIsLinkedInAPublishedEntry = $term->queryEntries()
                ->where('site', $this->site)
                ->get()
                ->filter(fn ($entry) => $entry->published() && ! $entry->value('seo_noindex'))
                ->isNotEmpty();

            return $termIsLinkedInAPublishedEntry
                && $term->published()
                && view()->exists($term->template())
                && ! $this->noindex($term);
        });

        return $taxonomyAndTerms->merge($collectionTerms);
    }

    protected function indexable(): bool
    {
        $config = Seo::find('site', 'indexing')?->in($this->site);

        // If there is no config, the sitemap should be indexable.
        if (is_null($config)) {
            return true;
        }

        // If we have a global noindex, the sitemap shouldn't be indexable.
        if ($config->value('noindex')) {
            return false;
        }

        $excluded = $this->type === 'collections'
            ? $config->value('excluded_collections') ?? []
            : $config->value('excluded_taxonomies') ?? [];

        // If the collection/taxonomy is excluded, the sitemap shouldn't be indexable.
        return ! in_array($this->handle, $excluded);
    }

    // TODO: This should probably be moved to the individual Sitemap Item class.
    protected function noindex(Entry|LocalizedTerm $data): bool
    {
        $handle = $data instanceof Entry
            ? $data->collectionHandle()
            : $data->taxonomyHandle();

        $defaultNoindex = Seo::find(str_plural($this->type), $handle)
            ?->in($this->site)
            ?->value('seo_noindex');

        $contentNoindex = $data->get('seo_noindex');

        return $contentNoindex ?? $defaultNoindex ?? false;
    }

    public function url(): string
    {
        $siteUrl = Site::get($this->site)->absoluteUrl();
        $filename = "sitemap_{$this->type}_{$this->handle}.xml";

        return $siteUrl . '/' . $filename;
    }

    public function lastmod(): string
    {
        return $this->items()->sortByDesc('lastmod')->first()['lastmod'];
    }

    public function clearCache(): void
    {
        Cache::forget("advanced-seo::sitemaps::{$this->site}");
        Cache::forget("advanced-seo::sitemaps::{$this->site}::{$this->type}::{$this->handle}");
    }
}
