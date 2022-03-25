<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Actions\DispatchBlueprintFoundEvent;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Entry as EntryFacade;
use Statamic\Facades\Site;
use Statamic\Taxonomies\LocalizedTerm;

class SitemapItem
{
    protected ?SeoVariables $defaults;

    public function __construct(protected Entry|Taxonomy|LocalizedTerm $content, protected string $site)
    {
        /**
         * The `absoluteUrl` method in the Taxonomy class always takes the current site as basis.
         * In order to get the localized URL, we need to set the correct site for this request.
         */
        if ($content instanceof Taxonomy) {
            Site::setCurrent($site);
        }

        DispatchBlueprintFoundEvent::handle($this->content);
    }

    public function type(): string
    {
        return match (true) {
            ($this->content instanceof Entry) => 'collections',
            ($this->content instanceof Taxonomy) => 'taxonomies',
            ($this->content instanceof LocalizedTerm) => 'taxonomies',
        };
    }

    public function handle(): string
    {
        return match (true) {
            ($this->content instanceof Entry) => $this->content->collectionHandle(),
            ($this->content instanceof Taxonomy) => $this->content->handle(),
            ($this->content instanceof LocalizedTerm) => $this->content->taxonomyHandle(),
        };
    }

    public function loc(): string
    {
        if ($this->content instanceof Taxonomy) {
            return $this->content->absoluteUrl();
        }

        $canonicalType = $this->content->seo_canonical_type->value();


        if ($canonicalType === 'other') {
            $entryId = $this->content->seo_canonical_entry?->id();

            return $entryId
                ? EntryFacade::find($entryId)->absoluteUrl()
                : $this->content->absoluteUrl();
        }

        if ($canonicalType === 'custom') {
            return $this->content->seo_canonical_custom ?? $this->content->absoluteUrl();
        }

        return $this->content->absoluteUrl();
    }

    public function lastmod(): string
    {
        return match (true) {
            ($this->content instanceof Entry) => $this->content->lastModified()->format('Y-m-d\TH:i:sP'),
            ($this->content instanceof Taxonomy) => $this->lastModifiedTaxonomyTerm()->lastModified()->format('Y-m-d\TH:i:sP'),
            ($this->content instanceof LocalizedTerm) => $this->content->lastModified()->format('Y-m-d\TH:i:sP'),
        };
    }

    protected function lastModifiedTaxonomyTerm(): LocalizedTerm
    {
        return $this->content->queryTerms()
            ->where('site', $this->site)
            ->get()
            ->sortByDesc(fn ($term) => $term->lastModified())
            ->first();
    }

    public function changefreq(): string
    {
        if ($this->content instanceof Taxonomy) {
            return Defaults::data('taxonomies')->get('seo_sitemap_change_frequency');
        }

        return $this->content->seo_sitemap_change_frequency;
    }

    public function priority(): string
    {
        if ($this->content instanceof Taxonomy) {
            return Defaults::data('taxonomies')->get('seo_sitemap_priority');
        }

        return $this->content->seo_sitemap_priority;
    }

    public function toArray(): array
    {
        return [
            'loc' => $this->loc(),
            'lastmod' => $this->lastmod(),
            'changefreq' => $this->changefreq(),
            'priority' => $this->priority(),
        ];
    }
}
