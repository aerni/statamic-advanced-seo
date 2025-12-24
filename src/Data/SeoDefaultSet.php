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
use Statamic\Support\Str;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class SeoDefaultSet implements Contract
{
    use ExistsAsFile;
    use FluentlyGetsAndSets;
    use HasDefaultsData;

    protected string $type;

    protected string $handle;

    protected array $localizations = [];

    protected bool $enabled = true;

    protected ?array $origins = [];

    public function id(): string
    {
        return "{$this->type()}::{$this->handle()}";
    }

    public function reference(): string
    {
        return "seo::{$this->id()}";
    }

    public function type($type = null)
    {
        return $this->fluentlyGetOrSet('type')->args(func_get_args());
    }

    public function handle($handle = null)
    {
        return $this->fluentlyGetOrSet('handle')->args(func_get_args());
    }

    public function enabled(?bool $enabled = null): bool|self
    {
        return $this->fluentlyGetOrSet('enabled')->args(func_get_args());
    }

    public function origins($origins = null): Collection|self
    {
        return $this
            ->fluentlyGetOrSet('origins')
            ->getter(function ($origins) {
                if (empty($origins) && $this->sites()->count() > 1) {
                    return $this->sites()->map(fn ($site) => null);
                }

                return collect($origins);

            })
            ->setter(function ($origins) {
                // TODO: Should we not set anything if there is only one origin?
                return collect($origins)
                    ->filter(function ($value, $key) {
                        $validValues = $this->sites()->keys();
                        return $validValues->contains($key) && $validValues->contains($value);
                    })->all();
            })
            ->args(func_get_args());
    }

    public function localizations(): Collection
    {
        return collect($this->localizations);
    }

    public function sites(): Collection
    {
        // Only get sites configured on the parent (collection/taxonomy)
        if ($parent = $this->parent()) {
            return $parent->sites()->mapWithKeys(fn ($site) => [$site => Site::get($site)]);
        }

        return Site::all();
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
            'enabled' => $this->enabled,
            'origins' => $this->origins,
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

        return $this->addLocalization($this->makeLocalization($site));

        // TODO: TODO: Should we really add the default data to the file? Don't they get derrived from the default value in the blueprint?
        // return $this->addLocalization($this->makeLocalization($site)->withDefaultData());
    }

    // TODO: The GlobalSet solves this by making a localization in the ->in() method
    // if the requested localization doesn't exist.
    public function ensureLocalizations(?Collection $sites = null): self
    {
        // Get sites from the instance if not provided, or ensure custom sites are valid
        $sites = $sites?->intersect(Site::all()->keys()) ?? $this->sites();

        // Make a localization for each site if it doesn't already exist.
        $sites->each(function ($site) {
            $this->in($site) ?? $this->addLocalization($this->makeLocalization($site));
        });

        // TODO: TODO: Should we really add the default data to the file? Don't they get derrived from the default value in the blueprint?
        // $this->localizations()->each(fn ($item) => $item->withDefaultData());

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

    // TODO: Get rid of the $localizations property and just always get them fresh.
    // Like Statamic does with Globals. But then we'd need a repository for variables too ...
    // public function in(?string $locale): ?SeoVariables
    // {
    //     return $this->localizations[$locale] ?? null;
    // }

    // TODO: This likely allows us to get rid of ensureLocalizations()
    // as we now always create one if requested.
    public function in(?string $site): ?SeoVariables
    {
        if (! $this->sites()->contains($site)) {
            return null;
        }

        if ($localizations = $this->localizations()->get($site)) {
            return $localizations;
        };

        $localization = $this->makeLocalization($site);

        // TODO: Should we really add the localization to the set or no?
        $this->addLocalization($localization);

        return $localization;
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
            'site' => cp_route('advanced-seo.site.edit', $this->handle()),
            'collections' => cp_route('advanced-seo.collections.edit', $this->handle()),
            'taxonomies' => cp_route('advanced-seo.taxonomies.edit', $this->handle()),
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

    // TODO: This is just a feature toggle for things like favicons.
    // The enabled state for collections/taxonomies is saved in $this->data.
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
