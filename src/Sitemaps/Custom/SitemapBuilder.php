<?php

namespace Aerni\AdvancedSeo\Sitemaps\Custom;

use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;
use Closure;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;

class SitemapBuilder extends BaseSitemap
{
    protected string $handle;

    protected string $site;

    protected Collection $urls;

    public function __construct(string $handle)
    {
        $this->handle = $handle;
        $this->site = Site::default()->handle();
        $this->urls = collect();
    }

    final public function type(): string
    {
        return 'custom';
    }

    final public function handle(): string
    {
        return $this->handle;
    }

    public function site(?string $site = null): string|self
    {
        if ($site === null) {
            return $this->site;
        }

        $this->site = $site;

        return $this;
    }

    public function add(string $url, ?Closure $callback = null): self
    {
        $url = new CustomSitemapUrl($url);

        if ($callback) {
            $callback($url);
        }

        $this->urls = $this->urls->push($url)->unique(fn ($url) => $url->loc());

        return $this;
    }

    public function urls(): Collection
    {
        return $this->urls->each->sitemap($this);
    }

    public function register(): void
    {
        app('advanced-seo.sitemaps')->push($this);
    }
}
