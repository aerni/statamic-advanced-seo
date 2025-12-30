<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Actions\RemoveSeoValues;
use Aerni\AdvancedSeo\Concerns\HasDefaultsData;
use Aerni\AdvancedSeo\Contracts\SeoSet as Contract;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Events\SeoSetSaved;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Contracts\Query\QueryableValue;
use Statamic\Contracts\Taxonomies\Taxonomy as StatamicTaxonomy;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Facades\YAML;
use Statamic\Fields\Blueprint;

class SeoSet implements Arrayable, Contract, QueryableValue
{
    use HasDefaultsData;

    public function __construct(
        public readonly string $type,
        public readonly string $handle,
        public readonly string $title,
        public readonly string $icon,
        public readonly string $blueprint,
        public readonly string $contentFile,
        public readonly null|StatamicCollection|StatamicTaxonomy $parent = null
    ) {
        //
    }

    public function id(): string
    {
        return "{$this->type}::{$this->handle}";
    }

    public function type(): string
    {
        return $this->type;
    }

    public function handle(): string
    {
        return $this->handle;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function icon(): string
    {
        return $this->icon;
    }

    // TODO: Should the blueprint live in the config
    // Because the SeoSet doesn't really have a blueprint.
    public function blueprint(): Blueprint
    {
        return resolve($this->blueprint)
            ->make()
            ->data($this->defaultsData())
            ->get();
    }

    public function defaultValues(): Collection
    {
        return Blink::once("advanced-seo::{$this->id()}::defaultValues", function () {
            $path = __DIR__."/../../content/{$this->contentFile}";

            return file_exists($path)
                ? collect(YAML::file($path)->parse())
                : collect();
        });
    }

    public function enabled(): bool
    {
        return $this->config()->enabled();
    }

    public function config(): SeoSetConfig
    {
        return Blink::once("advanced-seo::{$this->id()}::config", function () {
            return SeoConfig::findOrMake($this->id());
        });
    }

    public function origins(): Collection
    {
        return $this->config()->origins();
    }

    public function sites(): Collection
    {
        if (! $this->parent) {
            return Site::all();
        }

        return $this->parent->sites()->mapWithKeys(fn ($site) => [$site => Site::get($site)]);
    }

    public function availableInSite(string $site): bool
    {
        return $this->sites()->has($site);
    }

    public function defaultSite(): string
    {
        return $this->sites()->keys()->first();
    }

    public function localizations(): Collection
    {
        return Blink::once("advanced-seo::{$this->id()}::localizations", function () {
            $persisted = SeoLocalization::whereSeoSet($this->id())->keyBy->locale();

            return $this->sites()
                ->map(fn ($site, $handle) => $persisted->get($handle) ?? SeoLocalization::make($this->id(), $handle));
        });
    }

    public function selectedSite(): string
    {
        $selectedSite = Site::selected()->handle();

        return $this->availableInSite($selectedSite)
            ? $selectedSite
            : $this->defaultSite();
    }

    public function in(string $locale): ?SeoSetLocalization
    {
        return $this->localizations()->get($locale);
    }

    public function inSelectedSite(): ?SeoSetLocalization
    {
        return $this->in(Site::selected()->handle());
    }

    public function inCurrentSite(): ?SeoSetLocalization
    {
        return $this->in(Site::current()->handle());
    }

    public function inDefaultSite(): ?SeoSetLocalization
    {
        return $this->in($this->defaultSite());
    }

    public function save(): self
    {
        $this->config()->save();

        $this->saveOrDeleteLocalizations();

        SeoSetSaved::dispatch($this);

        return $this;
    }

    protected function saveOrDeleteLocalizations(): void
    {
        if (! $this->enabled()) {
            $this->localizations()->each->delete();

            RemoveSeoValues::handle($this->parent);

            return;
        }

        // Save localizations that aren't persisted yet
        // TODO: Do we even need this? As localizations are loaded in memory and don't have to be persisted per se.
        $this->localizations()
            ->reject(fn ($localization) => $localization->initialPath())
            ->each->save();

        // Delete orphaned localizations (persisted but no longer valid for available sites)
        // TODO: What's the best way to delete orphaned localizations? Maybe not here essentially?
        // Maybe have a cleanup method that gets called when the registry is loaded?
        // Something similar to how Livewire cleans up old uploaded assets.
        SeoLocalization::whereSeoSet($this->id())
            ->keyBy->locale()
            ->diffKeys($this->sites())
            ->each->delete();
    }

    public function delete(): bool
    {
        $this->config()->delete();

        $this->localizations()->each->delete();

        return true;
    }

    public function toQueryableValue(): string
    {
        return $this->id();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'type' => $this->type,
            'handle' => $this->handle,
            'title' => $this->title,
            'icon' => $this->icon,
            'enabled' => $this->enabled(),
            'localization_url' => $this->inSelectedSite()->editUrl(),
            'config_url' => $this->config()->editUrl(),
            'configurable' => User::current()->can('configure', [SeoSet::class, $this]),
        ];
    }

    public function getRouteKey(): string
    {
        return $this->handle;
    }

    public function getRouteKeyName(): string
    {
        return 'seoSet';
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        // Route binding is handled by Route::bind() in ServiceProvider
        return null;
    }

    public function resolveChildRouteBinding($childType, $value, $field): ?self
    {
        return null;
    }
}
