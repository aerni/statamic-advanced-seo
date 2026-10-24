<?php

namespace Aerni\AdvancedSeo\Sitemaps\Taxonomies;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Actions\ResolveXDefault;
use Aerni\AdvancedSeo\Actions\ViewExistsForSite;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemapUrl;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

class TermSitemapUrl extends BaseSitemapUrl
{
    public function __construct(protected Term $term) {}

    public function loc(): string
    {
        return $this->term->absoluteUrl();
    }

    public function alternates(): ?array
    {
        if (! Site::multiEnabled()) {
            return null;
        }

        $terms = $this->term->term()
            ->localizations()
            ->filter(fn ($term) => ViewExistsForSite::run($term->template(), $term->locale()))
            ->filter(IncludeInSitemap::run(...));

        if ($terms->count() < 2) {
            return null;
        }

        return $terms
            ->map(fn ($term) => [
                'href' => $term->absoluteUrl(),
                'hreflang' => Helpers::parseLocale($term->site()->locale()),
            ])
            ->when(ResolveXDefault::run($terms), fn ($hreflang, $xDefault) => $hreflang->push([
                'href' => $xDefault->absoluteUrl(),
                'hreflang' => 'x-default',
            ]))
            ->values()
            ->all();
    }

    public function lastmod(): string
    {
        return $this->term->lastModified()->format('Y-m-d\TH:i:sP');
    }

    public function site(): string
    {
        return $this->term->locale();
    }
}
