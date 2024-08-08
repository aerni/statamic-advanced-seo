<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Aerni\AdvancedSeo\Concerns\HasBaseUrl;
use Aerni\AdvancedSeo\Contracts\SitemapUrl;
use Illuminate\Contracts\Support\Arrayable;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\URL;
use Statamic\Sites\Site;

abstract class BaseSitemapUrl implements Arrayable, SitemapUrl
{
    use HasBaseUrl;

    abstract public function loc(): string|self;

    abstract public function alternates(): array|self|null;

    abstract public function lastmod(): string|self|null;

    abstract public function changefreq(): string|self|null;

    abstract public function priority(): string|self|null;

    abstract public function site(): string|self;

    public function canonicalTypeIsCurrent(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return [
            'loc' => $this->loc(),
            'alternates' => $this->alternates(),
            'lastmod' => $this->lastmod(),
            'changefreq' => $this->changefreq(),
            'priority' => $this->priority(),
            'site' => $this->site(),
        ];
    }

    protected function absoluteUrl(Entry|Taxonomy|Term|Site $model): string
    {
        return $this->baseUrl()
            ? URL::assemble($this->baseUrl(), $model->url())
            : $model->absoluteUrl();
    }
}
