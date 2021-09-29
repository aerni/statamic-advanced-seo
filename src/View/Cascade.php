<?php

namespace Aerni\AdvancedSeo\View;

use Statamic\Facades\Site;
use Statamic\Tags\Context;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Facades\Entry;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Support\Helpers;
use Statamic\Sites\Site as StatamicSite;
use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;

class Cascade
{
    protected Context $context;
    protected StatamicSite $site;
    protected Collection $data;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->site = Site::current();
        $this->data = $this->data();
    }

    public static function make(Context $context): self
    {
        return new static($context);
    }

    // TODO: I need a smart way of handling default settings when they are booleans.
    // TODO: Filter out data that has no corresponding field in the blueprint.
    public function get(): Collection
    {
        return $this->computedContext();
    }

    public function data(): Collection
    {
        $this->siteDefaults = $this->siteDefaults();
        $this->contentDefaults = $this->contentDefaults();
        $this->onPageSeo = $this->onPageSeo();

        $data = $this->siteDefaults
            ->merge($this->contentDefaults)
            ->merge($this->onPageSeo)
            ->mapWithKeys(function ($item, $key) {
                return [Str::remove('seo_', $key) => $item];
            })
            ->sortKeys();

        return $this->ensureOverrides($data);
    }

    protected function computedContext(): Collection
    {
        // Remove all seo variables from the context.
        $contextWithoutSeoVariables = $this->context->filter(function ($value, $key) {
            return ! Str::contains($key, 'seo_');
        });

        // Add the computed data to the data.
        $seoVariables = $this->data->merge($this->computedData())->all();

        // Return the new context with all seo variables in an seo key.
        return $contextWithoutSeoVariables->merge(['seo' => $seoVariables]);
    }

    protected function computedData(): array
    {
        return [
            'title' => $this->compiledTitle(),
            'og_title' => $this->ogTitle(),
            'og_description' => $this->ogDescription(),
            'twitter_title' => $this->twitterTitle(),
            'twitter_description' => $this->twitterDescription(),
            'indexing' => $this->indexing(),
            'locale' => $this->locale(),
            'hreflang' => $this->hreflang(),
            'canonical' => $this->canonical(),
        ];
    }

    protected function onPageSeo(): Collection
    {
        return $this->context
            ->intersectByKeys(OnPageSeoBlueprint::make()->items())
            ->filter(function ($item) {
                return $item->raw();
            });
    }

    protected function siteDefaults(): Collection
    {
        return Seo::allOfType('site')->flatMap(function ($defaults) {
            return $defaults->in($this->site->handle())->toAugmentedArray();
        });
    }

    protected function contentDefaults(): Collection
    {
        $defaultsType = $this->context->get('collection') ?? $this->context->get('taxonomy');

        if ($defaultsType instanceof \Statamic\Entries\Collection) {
            $data = Seo::find('collections', $defaultsType->handle())->in($this->site)->toAugmentedArray();
        }

        if ($defaultsType instanceof \Statamic\Taxonomies\Taxonomy) {
            $data = Seo::find('taxonomies', $defaultsType->handle())->in($this->site)->toAugmentedArray();
        }

        return collect($data ?? []);
    }

    protected function ensureOverrides(Collection $data): Collection
    {
        return $data->merge([
            'noindex' => optional($this->siteDefaults->get('noindex'))->value() ?: optional($data->get('noindex'))->value(),
            'nofollow' => optional($this->siteDefaults->get('nofollow'))->value() ?: optional($data->get('nofollow'))->value(),
        ]);
    }

    protected function compiledTitle(): string
    {
        return "{$this->title()} {$this->titleSeparator()} {$this->siteName()}";
    }

    protected function title(): string
    {
        return $this->data->get('title') ?? $this->context->get('title');
    }

    protected function titleSeparator(): string
    {
        return $this->data->get('title_separator') ?? '|';
    }

    protected function siteName(): string
    {
        return $this->data->get('site_name') ?? config('app.name');
    }

    protected function ogTitle(): string
    {
        return $this->data->get('og_title') ?? $this->title();
    }

    protected function ogDescription(): ?string
    {
        return $this->data->get('og_description') ?? $this->data->get('description');
    }

    protected function twitterTitle(): string
    {
        return $this->data->get('twitter_title') ?? $this->title();
    }

    protected function twitterDescription(): ?string
    {
        return $this->data->get('twitter_description') ?? $this->data->get('description');
    }

    protected function indexing(): string
    {
        return collect([
            'noindex' => $this->data->get('noindex'),
            'nofollow' => $this->data->get('nofollow'),
        ])->filter()->keys()->implode(', ');
    }

    protected function locale(): string
    {
        return Helpers::parseLocale($this->site->locale());
    }

    protected function hreflang(): array
    {
        $entry = Entry::find($this->context->get('id'));

        if (! $entry) {
            return [];
        }

        // Get all published entry localizations.
        $alternates = $entry->sites()->filter(function ($locale) use ($entry) {
            return optional($entry->in($locale))->published();
        })->values();

        return $alternates->map(function ($locale) use ($entry) {
            return [
                'url' => $entry->in($locale)->absoluteUrl(),
                'locale' => Helpers::parseLocale(Site::get($locale)->locale()),
            ];
        })->toArray();
    }

    protected function canonical(): string
    {
        $type = optional($this->data->get('canonical_type'))->raw();

        if ($type === 'other') {
            return config('app.url') . optional($this->data->get('canonical_entry')->value())->url();
        }

        if ($type === 'custom') {
            return $this->data->get('canonical_custom')->raw();
        }

        $page = Arr::get($this->context->get('get'), 'page');
        $currentUrl = $this->context->get('permalink');

        return $page ? "{$currentUrl}?page={$page}" : $currentUrl;
    }
}
