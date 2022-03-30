<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Actions\Indexable;
use Aerni\AdvancedSeo\Actions\SupplementDefaultsData;
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
        // If there is only one entry, we don't want to render the alternate urls.
        if ($this->entries()->count() === 1) {
            return [];
        }

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
        // Make sure we actually return `0.0` and `1.0`.
        return number_format($this->entry->seo_sitemap_priority->value(), 1);
    }

    protected function entries(): Collection
    {
        $root = $this->entry->root();
        $descendants = $root->descendants();

        return collect([$root->locale() => $root])->merge($descendants)
            // ->each(fn ($entry) => $entry->cacheBlueprint(false)) // TODO: This is dependant on an open PR: https://github.com/statamic/cms/pull/5702
            // ->map(fn ($entry) => SupplementDefaultsData::handle($entry)) // Make sure to get the correct localization when extending the blueprint
            ->filter(fn ($entry) => Indexable::handle($entry));
    }
}
