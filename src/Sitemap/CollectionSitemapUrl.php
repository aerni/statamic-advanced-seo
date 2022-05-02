<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Actions\Indexable;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Site;

class CollectionSitemapUrl extends BaseSitemapUrl
{
    public function __construct(protected Entry $entry)
    {
    }

    public function loc(): string
    {
        return $this->entry->absoluteUrl();
    }

    public function alternates(): array
    {
        $entries = $this->entries();

        // We only want alternate URLs if there are at least two entries.
        if ($entries->count() <= 1) {
            return [];
        }

        return $entries->map(fn ($entry) => [
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
        // Make sure we actually return `0.0` and `1.0`.
        return number_format($this->entry->seo_sitemap_priority->value(), 1);
    }

    public function isCanonicalUrl(): bool
    {
        return match ($this->entry->seo_canonical_type->value()) {
            'current' => true,
            default => false,
        };
    }

    protected function entries(): Collection
    {
        $root = $this->entry->root();
        $descendants = $root->descendants();

        return collect([$root->locale() => $root])->merge($descendants)
            ->filter(fn ($entry) => Indexable::handle($entry));
    }
}
