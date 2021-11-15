<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Statamic\Entries\Entry;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Site;
use Statamic\Facades\Taxonomy;
use Statamic\Facades\Term;
use Statamic\Taxonomies\LocalizedTerm;

class Sitemap
{
    public function __construct(protected string $type, protected string $handle, protected string $site)
    {
        //
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
        $items = $this->type === 'collections'
            ? $this->entries()
            : $this->terms();

        return $items->map(function ($item) {
            return (new Item($item))->toArray();
        });
    }

    protected function entries(): Collection
    {
        return EntryFacade::query()
            ->where('collection', $this->handle)
            ->where('site', $this->site)
            ->get()
            ->filter(function ($entry) {
                return $entry->published() && $entry->uri() && ! $this->noindex($entry);
            });
    }

    protected function terms(): Collection
    {
        $taxonomy = collect([Taxonomy::find($this->handle)])->filter(function ($taxonomy) {
            return $taxonomy->queryTerms()->get()->isNotEmpty() && view()->exists($taxonomy->template());
        });

        $terms = Term::query()
            ->where('taxonomy', $this->handle)
            ->where('site', $this->site)
            ->get()
            ->filter(function ($term) {
                return $term->published() && view()->exists($term->template()) && ! $this->noindex($term);
            });

        $collectionTaxonomies = CollectionFacade::all()
            ->flatMap(function ($collection) {
                return $collection->taxonomies()->map->collection($collection);
            });

        // TODO: There is currently no way to get the URL of collection taxonomies.
        // Statamic first has to provide a way for this.
        // $collectionTaxonomy = $collectionTaxonomies->filter(function ($taxonomy) {
        //     return view()->exists($taxonomy->template());
        // });

        $collectionTerms = $collectionTaxonomies
            ->flatMap(function ($taxonomy) {
                return $taxonomy->queryTerms()
                    ->where('site', $this->site)
                    ->get()->map->collection($taxonomy->collection());
            })->filter(function ($term) {
                $termIsLinkedInAnEntry = $term->queryEntries()->where('site', $this->site)->get()->isNotEmpty();

                return $termIsLinkedInAnEntry && $term->published() && view()->exists($term->template()) && ! $this->noindex($term);
            });

        return $taxonomy->merge($terms)->merge($collectionTerms);
    }

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
