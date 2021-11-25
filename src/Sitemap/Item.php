<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Facades\Data;
use Statamic\Facades\Site;
use Statamic\Taxonomies\LocalizedTerm;

class Item
{
    const DEFAULT_CHANGEFREQ = 'daily';
    const DEFAULT_PRIORITY = '0.5';

    protected ?SeoVariables $defaults;

    public function __construct(protected Entry|Taxonomy|LocalizedTerm $content)
    {
        $this->defaults = Seo::find($this->type(), $this->handle())?->in(Site::current());
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
            ?? self::DEFAULT_CHANGEFREQ;
    }

    public function priority(): string
    {
        return $this->content->get('seo_sitemap_priority')
            ?? $this->defaults?->get('seo_sitemap_priority')
            ?? self::DEFAULT_PRIORITY;
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
