<?php

namespace Aerni\AdvancedSeo\Sitemaps;

use Aerni\AdvancedSeo\Actions\IncludeInSitemap;
use Aerni\AdvancedSeo\Contracts\Sitemap as Contract;
use Aerni\AdvancedSeo\Contracts\SitemapFile;
use Aerni\AdvancedSeo\Contracts\SitemapUrl;
use Aerni\AdvancedSeo\Facades\Sitemap;
use Aerni\AdvancedSeo\Sitemaps\Collections\CollectionSitemap;
use Aerni\AdvancedSeo\Sitemaps\Collections\EntrySitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemap;
use Aerni\AdvancedSeo\Sitemaps\Custom\CustomSitemapUrl;
use Aerni\AdvancedSeo\Sitemaps\Taxonomies\TaxonomySitemap;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Statamic\Contracts\Query\Builder;
use Statamic\Facades\Addon;
use Statamic\Facades\URL;
use Statamic\Support\Traits\FluentlyGetsAndSets;

abstract class BaseSitemap implements Arrayable, Contract, Renderable, Responsable, SitemapFile
{
    use FluentlyGetsAndSets;

    protected Collection $urls;

    abstract public function urls(): Collection;

    public function handle(): string
    {
        return match (static::class) {
            CollectionSitemap::class => $this->model->handle(),
            TaxonomySitemap::class => $this->model->handle(),
            CustomSitemap::class => $this->handle,
            default => Str::of(static::class)->afterLast('\\')->remove('Sitemap')->snake(),
        };
    }

    public function type(): string
    {
        return match (static::class) {
            CollectionSitemap::class => 'collection',
            TaxonomySitemap::class => 'taxonomy',
            default => 'custom',
        };
    }

    public function id(): string
    {
        return Str::slug("{$this->type()}-{$this->handle()}");
    }

    public function url(): string
    {
        return URL::tidy(config('app.url')."/sitemaps/{$this->filename()}");
    }

    public function lastmod(): ?string
    {
        return $this->urls()->sortByDesc('lastmod')->first()?->lastmod();
    }

    protected function includeInSitemapQuery(Builder $query): Builder
    {
        return $query
            ->where('published', true)
            ->whereNotNull('uri');

        /**
         * A reminder for my later self. We used to also include the following queries here:
         *
         * $query
         *   ->where('seo_noindex', false)
         *   ->where('seo_sitemap_enabled', true)
         *   ->where('seo_canonical_type', 'current')
         *
         * But we removed them as they lead to unexpected results due to the following reasons:
         *
         * Features like the sitemap are evaluated and enabled based the locale of an entry,
         * which determines if certain fields are added when extending the blueprint.
         * But the query lead to unexpected results because it looks in the Stache which could
         * have fields in there or not based on which entry built the Stache. It's complicated.
         * So we just removed the queries as we are filtering those specific fields later on anyways.
         */
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url(),
            'lastmod' => $this->lastmod(),
            'urls' => $this->urls()->toArray(),
        ];
    }

    public function render(): string
    {
        return view('advanced-seo::sitemaps.show', [
            'urls' => isset($this->urls) ? $this->urls->toArray() : $this->toArray()['urls'],
            'version' => Addon::get('aerni/advanced-seo')->version(),
        ])->render();
    }

    public function toResponse($request)
    {
        return response(
            content: $this->file() ?? $this->render(),
            headers: [
                'Content-Type' => 'text/xml',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]
        );
    }

    public function filename(): string
    {
        return "{$this->id()}.xml";
    }

    public function file(): ?string
    {
        return File::exists($this->path()) ? File::get($this->path()) : null;
    }

    public function path(): string
    {
        return Sitemap::path($this->filename());
    }

    public function save(): self
    {
        File::ensureDirectoryExists(Sitemap::path());

        File::put($this->path(), $this->prettifyXml($this->render()));

        return $this;
    }

    public function loadUrlsFromFile(): bool|self
    {
        $sitemap = simplexml_load_string($this->file());

        if (! $sitemap) {
            return false;
        }

        $sitemap->registerXPathNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

        $urls = [];

        foreach ($sitemap->url as $url) {
            $alternates = collect($url->xpath("xhtml:link"))
                ->map(fn ($element) =>  [
                    'hreflang' => ((array) $element)['@attributes']['hreflang'],
                    'href' => ((array) $element)['@attributes']['href'],
                ])->all();

            $urls[] = new CustomSitemapUrl(
                loc: $url->loc,
                alternates: $alternates,
                lastmod: $url->lastmod,
                changefreq: $url->changefreq,
                priority: $url->priority
            );
        }

        $this->urls = collect($urls);

        return $this;
    }

    public function updateUrls($entry): self
    {
        collect()
            ->push($root = $entry->root())
            ->merge($root->descendants())
            ->each(fn ($entry) => $this->updateUrl($entry));

        return $this;
    }

    // TODO: Probably a good idea to use an id() instead of absoluteUrl() to prevent issues if routes or slugs change.
    public function updateUrl($entry): self
    {
        if (! IncludeInSitemap::run($entry)) {
            $this->urls = $this->urls->reject(fn ($url) => $url->loc() === $entry->absoluteUrl());

            return $this;
        }

        $itemToReplace = $this->urls->search(fn ($url) => $url->loc() === $entry->absoluteUrl());

        if ($itemToReplace !== false) {
            $this->urls = $this->urls->replace([$itemToReplace => new EntrySitemapUrl($entry)]);
        } else {
            $this->urls->push(new EntrySitemapUrl($entry));
        }

        return $this;
    }

    // TODO: Implement this in SitemapIndex and other sitemaps as well.
    protected function prettifyXml(string $xml): string
    {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml);

        return $dom->saveXML();
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->fluentlyGetOrSet($name)->args($arguments);
    }
}
