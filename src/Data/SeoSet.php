<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Actions\RemoveSeoValues;
use Aerni\AdvancedSeo\Concerns\HasDefaultsData;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
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
use Statamic\Support\Str;

class SeoSet implements Arrayable, QueryableValue
{
    use HasDefaultsData;

    public function __construct(
        protected readonly string $type,
        protected readonly string $handle,
        protected readonly string $title,
        protected readonly string $icon,
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

    public function blueprint(string $type): string
    {
        return match ([$this->type, $type]) {
            ['site', 'localization'] => 'Aerni\\AdvancedSeo\\Blueprints\\'.Str::studly($this->handle).'Blueprint',
            ['collections', 'localization'], ['taxonomies', 'localization'] => \Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint::class,
            default => throw new \Exception("No blueprint defined for SEO set type '{$this->type}' with blueprint type '{$type}'"),
        };
    }

    public function parent(): null|StatamicCollection|StatamicTaxonomy
    {
        return Blink::once("advanced-seo::{$this->id()}::parent", function () {
            return match ($this->type) {
                'collections' => \Statamic\Facades\Collection::find($this->handle),
                'taxonomies' => \Statamic\Facades\Taxonomy::find($this->handle),
                'site' => null,
            };
        });
    }

    public function defaultValues(): Collection
    {
        return Blink::once("advanced-seo::{$this->id()}::defaultValues", function () {
            $filename = match ($this->type) {
                'site' => "{$this->handle}.yaml",
                'collections', 'taxonomies' => 'content_defaults.yaml',
            };

            $path = __DIR__."/../../content/{$filename}";

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
        if (! $parent = $this->parent()) {
            return Site::all();
        }

        return $parent->sites()->mapWithKeys(fn ($site) => [$site => Site::get($site)]);
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
        return $this->sites()->get(Site::selected()->handle()) ?? $this->defaultSite();
    }

    protected function defaultSite(): string
    {
        return $this->sites()->keys()->first();
    }

    public function in(string $locale): ?SeoSetLocalization
    {
        return $this->localizations()->get($locale);
    }

    public function inSelectedSite(): ?SeoSetLocalization
    {
        return $this->in(Site::selected()->handle());
    }

    public function inDefaultSite(): ?SeoSetLocalization
    {
        return $this->in($this->defaultSite());
    }

    public function save(): self
    {
        $this->config()->save();

        $this->saveOrDeleteLocalizations();

        return $this;
    }

    // TODO: Should we move this into a listener for SeoSetConfigSaved event?
    protected function saveOrDeleteLocalizations(): void
    {
        if (! $this->enabled()) {
            $this->localizations()->each->delete();

            RemoveSeoValues::handle($this->parent());

            return;
        }

        $this->localizations()->each->save();

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
            'configurable' => User::current()->can('configure', [self::class, $this]),
        ];
    }
}
