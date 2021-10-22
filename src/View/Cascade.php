<?php

namespace Aerni\AdvancedSeo\View;

use Aerni\AdvancedSeo\Blueprints\OnPageSeoBlueprint;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Support\Helpers;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\SchemaOrg\Schema;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Sites\Site as StatamicSite;
use Statamic\Tags\Context;

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

    public function get(): Collection
    {
        return $this->computedContext();
    }

    public function data(): Collection
    {
        $this->siteDefaults = $this->siteDefaults();
        $this->onPageSeo = $this->onPageSeo();

        $data = $this->siteDefaults
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
            'schema' => $this->schema(),
        ];
    }

    /**
     * Only return values that are not empty and whose keys exists in the Blueprint.
     * This makes sure that we don't return any data of fields that were disabled in the config, e.g. OG Image Generator
     */
    protected function onPageSeo(): Collection
    {
        return $this->context
            ->intersectByKeys(OnPageSeoBlueprint::make()->items())
            ->filter(function ($item) {
                return $item->raw();
            });
    }

    /**
     * Get the augmented site defaults and filter out any values that shouldn't be there
     * like features that were disabled in the config.
     */
    protected function siteDefaults(): Collection
    {
        return Seo::allOfType('site')->flatMap(function ($defaults) {
            return $defaults->in($this->site->handle())->toAugmentedArray();
        })->filter(function ($item) {
            return $item instanceof \Statamic\Fields\Value;
        });
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

    protected function schema(): string
    {
        return $this->siteSchema() . $this->entrySchema();
    }

    protected function siteSchema(): ?string
    {
        $type = optional($this->data->get('site_json_ld_type'))->raw();

        if (empty($type)) {
            return null;
        }

        if ($type === 'custom') {
            $data = $this->data->get('site_json_ld')->value();

            return "<script type=\"application/ld+json\">{$data}</script>";
        }

        if ($type === 'organization') {
            $schema = Schema::organization()
                ->name($this->data->get('organization_name')->value())
                ->url(config('app.url') . $this->context->get('homepage'));

            if ($logo = optional($this->data->get('organization_logo'))->value()) {
                $schema->logo($logo->absoluteUrl());
            }
        }

        if ($type === 'person') {
            $schema = Schema::person()
                ->name($this->data->get('person_name')->value())
                ->url(config('app.url') . $this->context->get('homepage'));
        }

        return $schema->toScript();
    }

    protected function entrySchema(): ?string
    {
        $data = optional($this->data->get('json_ld'))->value();

        if ($data) {
            return "<script type=\"application/ld+json\">{$data}</script>";
        }

        return null;
    }
}
