<?php

namespace Aerni\AdvancedSeo\Sitemaps\Collections;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Actions\ResolveXDefault;
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

        $localizations = $sites
            ->map(fn ($locale) => $this->entry->in($locale))
            ->filter() // A model might not exist in a site. So we need to remove it to prevent calling methods on null
            ->filter(IncludeInSitemap::run(...));

        if ($localizations->count() < 2) {
            return null;
        }

        $xDefault = ResolveXDefault::run($localizations);

        $hreflang = $localizations->map(fn ($model) => [
            'href' => $model->absoluteUrl(),
            'hreflang' => Helpers::parseLocale($model->site()->locale()),
        ]);

        if ($xDefault) {
            $hreflang->push([
                'href' => $xDefault->absoluteUrl(),
                'hreflang' => 'x-default',
            ]);
        }

        return $hreflang->values()->all();
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
