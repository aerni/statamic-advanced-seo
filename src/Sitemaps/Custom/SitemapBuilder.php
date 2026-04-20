<?php

namespace Aerni\AdvancedSeo\Sitemaps\Custom;

use Aerni\AdvancedSeo\Sitemaps\BaseSitemap;
use Closure;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;

class SitemapBuilder extends BaseSitemap
{
    protected string $site;

    protected Collection $urls;

    public function __construct(protected string $handle)
    {
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
        return $this->fluentlyGetOrSet('site')
            ->setter(function ($site) {
                throw_unless(Site::get($site), new \InvalidArgumentException("Invalid site: {$site}"));

                return $site;
            })
            ->args(func_get_args());
    }

    public function add(string $url, ?Closure $callback = null): self
    {
        $url = new CustomSitemapUrl($this, $url);

        if ($callback) {
            $callback($url);
        }

        $this->urls = $this->urls->push($url)->unique(fn ($url) => $url->loc());

        return $this;
    }

    public function urls(): Collection
    {
        return $this->urls;
    }

    public function register(): void
    {
        app('advanced-seo.sitemaps')->push($this);
    }
}
