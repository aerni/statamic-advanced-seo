<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Blueprints\ContentSeoSetConfigBlueprint;
use Aerni\AdvancedSeo\Blueprints\ContentSeoSetLocalizationBlueprint;
use Aerni\AdvancedSeo\Blueprints\SiteSeoSetConfigBlueprint;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\SeoConfig;
use Aerni\AdvancedSeo\Facades\SeoLocalization;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Statamic\Contracts\Entries\Collection as StatamicCollection;
use Statamic\Contracts\Query\QueryableValue;
use Statamic\Contracts\Taxonomies\Taxonomy as StatamicTaxonomy;
use Statamic\Facades\Blink;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Support\Str;

class SeoSet implements Arrayable, QueryableValue
{
    public function __construct(
        protected readonly string $type,
        protected readonly string $handle,
        protected readonly string $title,
        protected readonly string $icon,
    ) {
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
            ['collections', 'localization'],
            ['taxonomies', 'localization'] => ContentSeoSetLocalizationBlueprint::class,
            ['site', 'config'] => SiteSeoSetConfigBlueprint::class,
            ['collections', 'config'],
            ['taxonomies', 'config'] => ContentSeoSetConfigBlueprint::class,
            default => throw new InvalidArgumentException(
                "No blueprint defined for SEO set type '{$this->type}' with blueprint type '{$type}'"
            ),
        };
    }

    public function parent(): null|StatamicCollection|StatamicTaxonomy
    {
        return Blink::once($this->blinkKey('parent'), function () {
            return match ($this->type) {
                'collections' => \Statamic\Facades\Collection::find($this->handle),
                'taxonomies' => \Statamic\Facades\Taxonomy::find($this->handle),
                'site' => null,
            };
        });
    }

    public function enabled(): bool
    {
        return $this->config()->enabled();
    }

    public function config(): SeoSetConfig
    {
        return Blink::once($this->blinkKey('config'), function () {
            return SeoConfig::findOrMake($this->id())->seoSet($this);
        });
    }

    public function origins(): Collection
    {
        return $this->config()->origins();
    }

    /**
     * Get all sites associated with this SEO set.
     *
     * @return Collection<string, \Statamic\Sites\Site>
     */
    public function sites(): Collection
    {
        if (! $parent = $this->parent()) {
            return Site::all();
        }

        return $parent->sites()->mapWithKeys(fn ($site) => [$site => Site::get($site)]);
    }

    /**
     * Get all localizations for this SEO set, keyed by locale.
     *
     * @return Collection<string, SeoSetLocalization>
     */
    public function localizations(): Collection
    {
        return Blink::once($this->blinkKey('localizations'), function () {
            $persisted = SeoLocalization::whereSeoSet($this->id())->keyBy->locale();

            return $this->sites()->map(function ($site, $handle) use ($persisted) {
                $localization = $persisted->get($handle) ?? SeoLocalization::make();

                return $localization->seoSet($this)->locale($handle);
            });
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

    public function inDefaultSite(): SeoSetLocalization
    {
        return $this->in($this->defaultSite());
    }

    /**
     * Saves the config and triggers cascading side effects.
     * Side effects are handled by the HandleSeoSetConfigSaved event listener.
     *
     * @see \Aerni\AdvancedSeo\Listeners\HandleSeoSetConfigSaved
     */
    public function save(): self
    {
        $this->config()->save();

        return $this;
    }

    /**
     * Deletes the config and triggers cascading cleanup.
     * Side effects are handled by the HandleSeoSetConfigDeleted event listener.
     *
     * @see \Aerni\AdvancedSeo\Listeners\HandleSeoSetConfigDeleted
     */
    public function delete(): bool
    {
        $this->config()->delete();

        return true;
    }

    public function toQueryableValue(): string
    {
        return $this->id();
    }

    public function toArray(): array
    {
        $enabled = $this->enabled();

        return [
            'id' => $this->id(),
            'type' => $this->type,
            'handle' => $this->handle,
            'title' => $this->title,
            'icon' => $this->icon,
            'enabled' => $enabled,
            'sitemap' => $enabled ? $this->config()->value('sitemap') : false,
            'social_images_generator' => $enabled ? $this->config()->value('social_images_generator') : false,
            'localization_url' => $this->inSelectedSite()?->editUrl(),
            'config_url' => $this->config()->editUrl(),
            'configurable' => User::current()->can('configure', [self::class, $this]),
        ];
    }

    public function flushBlink(): void
    {
        Blink::flushStartingWith($this->blinkKey());
    }

    protected function blinkKey(?string $suffix = null): string
    {
        return "advanced-seo::{$this->id()}::{$suffix}";
    }
}
