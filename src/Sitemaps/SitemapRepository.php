<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Closure;
use Statamic\Facades\Path;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Contracts\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemap;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemapUrl;

class SitemapRepository
{
    protected array $extensions = [];

    public function __construct(protected SitemapIndex $sitemapIndex)
    {
    }

    public function register(Closure|string $sitemap): void
    {
        $this->extensions[] = $sitemap;
    }

    public function make(string $handle): CustomSitemap
    {
        return new CustomSitemap($handle);
    }

    public function makeUrl(string $loc): CustomSitemapUrl
    {
        return new CustomSitemapUrl($loc);
    }

    public function add(Sitemap $sitemap): void
    {
        $this->sitemapIndex->add($sitemap);
    }

    public function index(): SitemapIndex
    {
        $this->boot();

        return $this->sitemapIndex;
    }

    public function all(): Collection
    {
        $this->boot();

        return $this->sitemapIndex->sitemaps();
    }

    public function find(string $id): ?Sitemap
    {
        return $this->all()->firstWhere(fn ($sitemap) => $sitemap->id() === $id);
    }

    public function xsl(): string
    {
        return file_get_contents(__DIR__.'/../../resources/views/sitemaps/sitemap.xsl');
    }

    public function path(string $path = ''): string
    {
        return Path::assemble(
            config('advanced-seo.sitemap.path', storage_path('statamic/sitemaps')),
            $path
        );
    }

    protected function boot(): void
    {
        foreach ($this->extensions as $extension) {
            $extension instanceof Closure
                ? $this->add($extension())
                : $this->add(app($extension));
        }

        /**
         * TODO: Once we drop support for Laravel 10, we could use Laravel's new once() helper instead.
         * Ensure we don't boot extensions multiple times during the same request,
         * which could happen if the `index()` and `all()` methods are called in the same request.
         */
        $this->extensions = [];
    }
}
