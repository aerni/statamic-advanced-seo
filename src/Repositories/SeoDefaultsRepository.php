<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;

class SeoDefaultsRepository
{
    public string $type;
    public string $handle;
    protected Collection $sites;
    protected SeoDefaultSet $set;

    public function __construct(string $type, string $handle, Collection $sites)
    {
        $this->type = $type;
        $this->handle = $handle;
        $this->sites = $sites; // TODO: Do I really need the sites in the constructor?
        $this->set = $this->findOrMakeSeoSet();
    }

    public function get(string $site): Collection
    {
        return $this->findOrMakeLocalization($site)->values();
    }

    public function set(): SeoDefaultSet
    {
        return $this->set;
    }

    /**
     * Returns the augmented data of a specific site.
     */
    public function toAugmentedArray(string $site): array
    {
        return $this->findOrMakeLocalization($site)->toAugmentedArray();
    }

    /**
     * Save a localization with the provided data.
     */
    public function save(string $site, Collection $data): SeoVariables
    {
        $localization = $this->findOrMakeLocalization($site);

        if ($localization->hasOrigin()) {
            // Only save the data that is different to the origin.
            $data = $data->diffAssoc($localization->origin()->data())->all();
        }

        return $localization->data($data)->save();
    }

    /**
     * Delete the set with all its localizations.
     */
    public function delete(): bool
    {
        return $this->set->delete();
    }

    /**
     * Make sure that each site has a localization.
     */
    public function ensureLocalizations(Collection $sites): self
    {
        $sites->map(function ($site) {
            $this->findOrMakeLocalization($site);
        });

        return $this;
    }

    /**
     * Create a localization for each of the provided sites.
     */
    public function createLocalizations(Collection $sites): SeoDefaultSet
    {
        return $this
            ->ensureLocalizations($sites)
            ->set()
            ->save();
    }

    /**
     * Create a localization for each of the provided sites and delete
     * any existing localization of sites that are not present.
     */
    public function createOrDeleteLocalizations(Collection $sites): SeoDefaultSet
    {
        return $this
            ->ensureLocalizations($sites)
            ->removeLocalizations($sites)
            ->set()
            ->save();
    }

    /**
     * Remove any localization that is not present in the provided sites.
     */
    protected function removeLocalizations(Collection $sites): self
    {
        $localizationsToDelete = $this->set->localizations()->map->locale()->diff($sites);

        $localizationsToDelete->each(function ($localization) {
            $localization = $this->set->localizations()->get($localization);
            $this->set->removeLocalization($localization);
        });

        return $this;
    }

    /**
     * Make a localization if it doesn't already exist.
     */
    public function findOrMakeLocalization(string $site): SeoVariables
    {
        $localization = $this->set->in($site) ?? $this->set->makeLocalization($site);

        $origin = $this->determineOrigin($this->sites, Site::default()->handle());

        if ($site === $origin) {
            $localization->removeOrigin();
        } else {
            $localization->origin($origin);
        }

        return $this->set->addLocalization($localization)->in($site);
    }

    /**
     * TODO: It's probably a good idea to make the origin configurable by the user.
     * Determine the origin of a localization.
     */
    public function determineOrigin(Collection $sites, string $site): string
    {
        if ($sites->contains($site)) {
            return $site;
        }

        $defaultSite = Site::default()->handle();

        if ($sites->contains($defaultSite)) {
            return $sites->first(function ($site) use ($defaultSite) {
                return $site === $defaultSite;
            });
        }

        return $sites->first();
    }

    public function blueprint(): Blueprint
    {
        return $this->set->blueprint();
    }

    /**
     * Make an SEO set if it doesn't already exist.
     */
    protected function findOrMakeSeoSet(): SeoDefaultSet
    {
        return Seo::find($this->type, $this->handle)
            ?? Seo::make()->type($this->type)->handle($this->handle);
    }
}
