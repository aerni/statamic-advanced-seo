<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Fields\ContentDefaultsFields;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Taxonomies\LocalizedTerm;

class SitemapItem
{
    // TODO: Should all the `get` methods actually be `value` and fall back to the origin?

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

        $this->defaults = Seo::find($this->type(), $this->handle())?->in($site);
    }

    public function type(): string
    {
        return $this->content instanceof Entry ? 'collections' : 'taxonomies';
    }

    public function handle(): string
    {
        if ($this->content instanceof Entry) {
            return $this->content->collectionHandle();
        }

        if ($this->content instanceof Taxonomy) {
            return $this->content->handle();
        }

        if ($this->content instanceof LocalizedTerm) {
            return $this->content->taxonomyHandle();
        }
    }

    public function path(): string
    {
        return parse_url($this->loc())['path'] ?? '/';
    }

    public function loc(): string
    {
        $canonicalType = $this->content->get('seo_canonical_type') ?? $this->defaults?->get('seo_canonical_type');

        if ($canonicalType === 'other') {
            $entryId = $this->content->get('seo_canonical_entry') ?? $this->defaults?->get('seo_canonical_entry');

            return Data::find($entryId)->absoluteUrl();
        }

        if ($canonicalType === 'custom') {
            return $this->content->get('seo_canonical_custom') ?? $this->defaults?->get('seo_canonical_custom');
        }

        return $this->content->absoluteUrl();
    }

    public function lastmod(): string
    {
        // TODO: Get the last modified date of the last modified item. Like a taxonomy term.
        return method_exists($this->content, 'lastModified')
            ? $this->content->lastModified()->format('Y-m-d\TH:i:sP')
            : now()->format('Y-m-d\TH:i:sP');
    }

    public function changefreq(): string
    {
        return $this->content->get('seo_sitemap_change_frequency')
            ?? $this->defaults?->get('seo_sitemap_change_frequency')
            ?? ContentDefaultsFields::getDefaultValue('seo_sitemap_change_frequency');
    }

    public function priority(): string
    {
        return $this->content->get('seo_sitemap_priority')
            ?? $this->defaults?->get('seo_sitemap_priority')
            ?? ContentDefaultsFields::getDefaultValue('seo_sitemap_priority');
    }

    public function toArray(): array
    {
        return [
            'path' => $this->path(),
            'loc' => $this->loc(),
            'lastmod' => $this->lastmod(),
            'changefreq' => $this->changefreq(),
            'priority' => $this->priority(),
        ];
    }
}
