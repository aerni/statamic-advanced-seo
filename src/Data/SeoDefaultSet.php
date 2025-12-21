<?php

namespace Aerni\AdvancedSeo\Data;

use Aerni\AdvancedSeo\Concerns\HasDefaultsData;
use Aerni\AdvancedSeo\Contracts\SeoDefaultSet as Contract;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Support\Collection;
use Statamic\Data\ExistsAsFile;
use Statamic\Facades\Collection as CollectionFacade;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;
use Statamic\Facades\Taxonomy;
use Statamic\Fields\Blueprint;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class SeoDefaultSet implements Contract
{
    use ExistsAsFile;
    use FluentlyGetsAndSets;
    use HasDefaultsData;

    protected string $handle;

    protected string $type;

    protected array $localizations;

    public function id(): string
    {
        return "{$this->type()}::{$this->handle()}";
    }

    public function handle($handle = null)
    {
        return $this->fluentlyGetOrSet('handle')->args(func_get_args());
    }

    public function type($type = null)
    {
        return $this->fluentlyGetOrSet('type')->args(func_get_args());
    }

    public function localizations(): Collection
    {
        return collect($this->localizations);
    }

    public function sites(): Collection
    {
        $allSites = Site::all()->keys();

        if ($parent = $this->parent()) {
            // Only return sites from the parent that are configured in Statamic's sites config
            return $allSites->filter(fn ($site) => $parent->sites()->contains($site));
        }

        return $allSites;
    }

    public function availableInSite(string $site): bool
    {
        return $this->sites()->contains($site);
    }

    public function defaultSite(): string
    {
        return $this->sites()->first();
    }

    public function selectedSite(): string
    {
        $selectedSite = Site::selected()->handle();

        return $this->sites()->contains($selectedSite)
            ? $selectedSite
            : $this->defaultSite();
    }

    public function title(): string
    {
        return Str::slugToTitle($this->handle());
    }

    public function path(): string
    {
        return vsprintf('%s/%s.yaml', [
            Stache::store('seo')->store($this->type())->directory(),
            $this->handle(),
        ]);
    }

    public function fileData(): array
    {
        return [
            'title' => $this->title(),
        ];
    }

    public function makeLocalization(string $site): SeoVariables
    {
        return (new SeoVariables)
            ->seoSet($this)
            ->locale($site);
    }

    public function createOrDeleteLocalizations(Collection $sites): self
    {
        return $this
            ->ensureLocalizations($sites)
            ->removeLocalizations($sites)
            ->save();
    }

    public function ensureLocalization(string $site): self
    {
        if ($this->in($site)) {
            return $this;
        }

        return $this->addLocalization($this->makeLocalization($site)->withDefaultData());
    }

    public function ensureLocalizations(?Collection $sites = null): self
    {
        // Get sites from the instance if not provided, or ensure custom sites are valid
        $sites = $sites?->intersect(Site::all()->keys()) ?? $this->sites();

        // Make a localization for each site if it doesn't already exist.
        $sites->each(function ($site) {
            $this->in($site) ?? $this->addLocalization($this->makeLocalization($site));
        });

        // TODO: Need to ensure that we still get the correct default data. Do we even need this method anymore?
        $this->localizations()->each(fn ($item) => $item->withDefaultData());

        return $this;
    }

    public function removeLocalizations(Collection $sites): self
    {
        $localizationsToDelete = $this->localizations()->map->locale()->diff($sites);

        $localizationsToDelete->each(function ($localization) {
            $this->removeLocalization($this->localizations()->get($localization));
        });

        return $this;
    }

    public function addLocalization(SeoVariables $localization): self
    {
        $localization->seoSet($this);

        $this->localizations[$localization->locale()] = $localization;

        return $this;
    }

    public function removeLocalization(SeoVariables $localization): self
    {
        unset($this->localizations[$localization->locale()]);

        return $this;
    }

    public function in($locale): ?SeoVariables
    {
        return $this->localizations[$locale] ?? null;
    }

    public function inSelectedSite(): ?SeoVariables
    {
        return $this->in(Site::selected()->handle());
    }

    public function inCurrentSite(): ?SeoVariables
    {
        return $this->in(Site::current()->handle());
    }

    public function inDefaultSite(): ?SeoVariables
    {
        return $this->in($this->defaultSite());
    }

    public function existsIn(string $locale): bool
    {
        return $this->in($locale) !== null;
    }

    public function editUrl(): string
    {
        return match ($this->type()) {
            'site' => cp_route('advanced-seo.site.defaults', [$this->handle(), Site::selected()]),
            'collections' => cp_route('advanced-seo.collections.defaults', [$this->handle(), Site::selected()]),
            'taxonomies' => cp_route('advanced-seo.taxonomies.defaults', [$this->handle(), Site::selected()]),
        };
    }

    public function configUrl(): string
    {
        return match ($this->type()) {
            'site' => cp_route('advanced-seo.site.config', [$this->handle(), Site::selected()]),
            'collections' => cp_route('advanced-seo.collections.config', [$this->handle(), Site::selected()]),
            'taxonomies' => cp_route('advanced-seo.taxonomies.config', [$this->handle(), Site::selected()]),
        };
    }

    public function blueprint(): Blueprint
    {
        $blueprint = Defaults::blueprint("{$this->type}::{$this->handle}");

        return resolve($blueprint)->make()
            ->data($this->defaultsData())
            ->get();
    }

    public function parent(): mixed
    {
        return match (true) {
            ($this->type() === 'collections') => CollectionFacade::findByHandle($this->handle()),
            ($this->type() === 'taxonomies') => Taxonomy::findByHandle($this->handle()),
            default => null,
        };
    }

    public function isEnabled(): bool
    {
        return Defaults::isEnabled("{$this->type()}::{$this->handle()}");
    }

    public function save(): self
    {
        // TODO: Maybe we can take inspiration from the GlobalSet saveOrDeleteLocalizations() method.
        // This method evaluates if a localization should be saved or deleted.
        \Aerni\AdvancedSeo\Facades\Seo::save($this);

        return $this;
    }

    public function delete(): bool
    {
        \Aerni\AdvancedSeo\Facades\Seo::delete($this);

        return true;
    }

    public static function __callStatic($method, $parameters)
    {
        return \Aerni\AdvancedSeo\Facades\Seo::{$method}(...$parameters);
    }
}
