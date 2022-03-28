<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Site;

class CollectionSitemapItem extends BaseSitemapItem
{
    public function __construct(protected Entry $entry, protected CollectionSitemap $sitemap)
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

    public function alternates(): array
    {
        return $this->entries()->map(fn ($entry) => [
            'hreflang' => Helpers::parseLocale(Site::get($entry->locale())->locale()),
            'href' => $entry->absoluteUrl(),
        ])->toArray();
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

    protected function entries(): Collection
    {
        $root = $this->entry->root();
        $descendants = $root->descendants();
        $allRelatedEntries = collect([$root->locale() => $root])->merge($descendants);

        return $allRelatedEntries
            ->filter(fn ($entry) => $entry->published != false)
            ->filter(fn ($entry) => $entry->uri != null)
            ->filter(fn ($entry) => $entry->seo_noindex != true)
            ->filter(fn ($entry) => $this->sitemap->indexable($entry));
    }
}
