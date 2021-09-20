<?php

namespace Aerni\AdvancedSeo\View;

use Statamic\Facades\Site;
use Illuminate\Support\Str;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Sites\Site as StatamicSite;
use Aerni\AdvancedSeo\Repositories\SiteDefaultsRepository;
use Aerni\AdvancedSeo\Repositories\TaxonomyDefaultsRepository;
use Aerni\AdvancedSeo\Repositories\CollectionDefaultsRepository;

class Cascade
{
    protected StatamicSite $site;
    protected Collection $context;
    protected Collection $data;

    public function __construct(Collection $context)
    {
        $this->site = Site::current();
        $this->context = $context;
        $this->data = $this->data();
    }

    public static function make(Collection $context): self
    {
        return new static($context);
    }

    public function get(): Collection
    {
        $data = $this->data->merge([
            'compiled_title' => $this->compiledTitle(),
            'locale' => $this->locale(),
        ]);

        // TODO: I need a smart way of handling default settings when they are booleans.
        dd($this->indexing());

        dd($data);

        return $data;
    }

    public function data(): Collection
    {
        return collect()
            ->merge($this->context)
            ->merge($this->siteDefaults())
            ->merge($this->contentDefaults())
            ->merge($this->onPageSeo());
    }

    protected function onPageSeo(): array
    {
        return $this->context->filter(function ($value, $key) {
            return Str::contains($key, 'seo_');
        })->all();
    }

    protected function siteDefaults(): array
    {
        return Seo::allOfType('site')->flatMap(function ($defaults) {
            return $defaults->in($this->site->handle())->toAugmentedArray();
        })->all();
    }

    protected function collectionDefaults(string $handle): array
    {
        return (new CollectionDefaultsRepository($handle))->toAugmentedArray($this->site->handle());
    }

    protected function taxonomyDefaults(string $handle): array
    {
        return (new TaxonomyDefaultsRepository($handle))->toAugmentedArray($this->site->handle());
    }

    protected function contentDefaults(): ?array
    {
        $parent = optional($this->context->get('seo'))->augmentable();

        if ($parent instanceof Entry) {
            return $this->collectionDefaults($parent->collection()->handle());
        }

        if ($parent instanceof Term || $parent instanceof LocalizedTerm) {
            return $this->taxonomyDefaults($parent->taxonomy()->handle());
        }

        return null;
    }

    protected function compiledTitle(): string
    {
        return "{$this->title()} {$this->titleSeparator()} {$this->siteName()}";
    }

    protected function title(): string
    {
        return $this->data->get('title');
    }

    protected function titleSeparator(): string
    {
        return $this->data->get('title_separator') ?? '|';
    }

    protected function siteName(): string
    {
        return $this->data->get('site_name') ?? config('app.name');
    }

    protected function locale(): string
    {
        return $this->site->locale();
    }

    protected function indexing()
    {
        $siteDefaults = collect($this->siteDefaults());

        $noindex = $siteDefaults->get('noindex') ? $siteDefaults->get('noindex') : $this->data->get('noindex');
        dd($noindex);
        $noindex = $siteDefaults->get('noindex') ?? $this->data->get('noindex');
        $nofollow = $siteDefaults['nofollow'] ?? $this->data->get('nofollow');

        dd($noindex, $nofollow);
    }
}
