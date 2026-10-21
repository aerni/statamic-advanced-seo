<?php

namespace Aerni\AdvancedSeo\Sitemaps\Taxonomies;

use Aerni\AdvancedSeo\Actions\ResolveXDefault;
use Aerni\AdvancedSeo\Sitemaps\BaseSitemapUrl;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Collection;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

class CollectionTermSitemapUrl extends BaseSitemapUrl
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

        $terms = $this->terms();

        if ($terms->count() < 2) {
            return null;
        }

        $xDefault = ResolveXDefault::localization($terms, $this->term);

        $hreflang = $terms->map(fn ($term) => [
            'href' => $term->absoluteUrl(),
            'hreflang' => Helpers::parseLocale($term->site()->locale()),
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
        return $this->term->lastModified()->format('Y-m-d\TH:i:sP');
    }

    public function site(): string
    {
        return $this->term->locale();
    }

    protected function terms(): Collection
    {
        return $this->sitemap()->collectionTerms()
            ->filter(function ($term) {
                return $term->id() === $this->term->id()
                    && $term->term()->collection()->handle() === $this->term->term()->collection()->handle();
            });
    }
}
