<?php

namespace Aerni\AdvancedSeo\Sitemaps\Collections;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemapUrl;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Site;

class EntrySitemapUrl extends BaseSitemapUrl
{
    public function __construct(protected Entry $entry) {}

    public function loc(): string
    {
        return $this->entry->absoluteUrl();
    }

    public function alternates(): ?array
    {
        if (! Site::multiEnabled()) {
            return null;
        }

        $sites = $this->entry->sites();

        if ($sites->count() < 2) {
            return null;
        }

        $hreflang = $sites
            ->map(fn ($locale) => $this->entry->in($locale))
            ->filter() // A model might not exist in a site. So we need to remove it to prevent calling methods on null
            ->filter(IncludeInSitemap::run(...));

        if ($hreflang->count() < 2) {
            return null;
        }

        $hreflang->transform(fn ($model) => [
            'href' => $model->absoluteUrl(),
            'hreflang' => Helpers::parseLocale($model->site()->locale()),
        ]);

        $origin = $this->entry->origin() ?? $this->entry;

        $xDefault = IncludeInSitemap::run($origin) ? $origin : $this->entry;

        return $hreflang->push([
            'href' => $xDefault->absoluteUrl(),
            'hreflang' => 'x-default',
        ])->values()->all();
    }

    public function lastmod(): string
    {
        return $this->entry->lastModified()->format('Y-m-d\TH:i:sP');
    }

    public function site(): string
    {
        return $this->entry->locale();
    }
}
