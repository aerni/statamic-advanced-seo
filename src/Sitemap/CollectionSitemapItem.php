<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Statamic\Contracts\Entries\Entry;

class CollectionSitemapItem extends BaseSitemapItem
{
    public function __construct(protected Entry $entry)
    {
    }

    public function loc(): string
    {
        $url = match ($this->entry->seo_canonical_type->value()) {
            'current' => $this->entry->absoluteUrl(),
            'other' => $this->entry->seo_canonical_entry?->absoluteUrl(),
            'custom' => $this->entry->seo_canonical_custom,
            default => null,
        };

        return $url ?? $this->entry->absoluteUrl();
    }

    public function lastmod(): string
    {
        return $this->entry->lastModified()->format('Y-m-d\TH:i:sP');
    }

    public function changefreq(): string
    {
        return $this->entry->seo_sitemap_change_frequency;
    }

    public function priority(): string
    {
        return $this->entry->seo_sitemap_priority;
    }
}
