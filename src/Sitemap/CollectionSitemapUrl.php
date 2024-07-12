<?php

namespace Aerni\AdvancedSeo\Sitemap;

use Aerni\AdvancedSeo\Actions\Indexable;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Site;

class CollectionSitemapUrl extends BaseSitemapUrl
{
    public function __construct(protected Entry $entry, protected CollectionSitemap $sitemap) {}

    public function loc(): string
    {
        return $this->absoluteUrl($this->entry);
    }

    // TODO: Can we use the entryAndTermHreflang method from the HasHreflang trait as the code is just a copy of it.
    public function alternates(): ?array
    {
        if (! Site::multiEnabled()) {
            return null;
        }

        if (! Indexable::handle($this->entry)) {
            return null;
        }

        $sites = $this->entry->sites();

        if ($sites->count() < 2) {
            return null;
        }

        $hreflang = $sites
            ->map(fn ($locale) => $this->entry->in($locale))
            ->filter() // A model might not exist in a site. So we need to remove it to prevent calling methods on null
            ->filter(Indexable::handle(...));

        if ($hreflang->count() < 2) {
            return null;
        }

        $hreflang->transform(fn ($model) => [
            'href' => $this->absoluteUrl($model),
            'hreflang' => Helpers::parseLocale($model->site()->locale()),
        ]);

        $origin = $this->entry->origin() ?? $this->entry;

        $xDefault = Indexable::handle($origin) ? $origin : $this->entry;

        return $hreflang->push([
            'href' => $this->absoluteUrl($xDefault),
            'hreflang' => 'x-default',
        ])->values()->all();
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

    public function site(): string
    {
        return $this->entry->site()->handle();
    }

    public function isCanonicalUrl(): bool
    {
        return match ($this->entry->seo_canonical_type->value()) {
            'current' => true,
            default => false,
        };
    }
}
