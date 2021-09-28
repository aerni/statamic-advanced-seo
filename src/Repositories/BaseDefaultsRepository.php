<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\Fields\Blueprint;

abstract class BaseDefaultsRepository
{
    public string $contentType;
    public string $handle;
    protected SeoDefaultSet $set;
    protected Collection $sites;

    public function __construct(string $handle)
    {
        $this->handle = $handle;
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

    // TODO: Is still still in use anywhere?
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
     * Make sure that localizations exist for the provided sites.
     * This will save the localizations to file.
     */
    public function ensureLocalizations(Collection $sites): self
    {
        $this->sites = $sites;

        $sites->map(function ($site) {
            $this->findOrMakeLocalization($site);
        });

        $this->set->save();

        return $this;
    }

    /**
     * Make a localization if it doesn't already exist.
     */
    public function findOrMakeLocalization(string $site): SeoVariables
    {
        return $this->set->in($site) ?? $this->makeLocalization($site);
    }

    /**
     * Make and return the localization for the provided site.
     */
    public function makeLocalization(string $site): SeoVariables
    {
        $localization = $this->set->makeLocalization($site);

        $origin = $this->determineOrigin($this->sites, Site::default()->handle());

        if ($site !== $origin) {
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
        return $sites->contains($site) ? $site : $sites->first();
    }

    abstract public function blueprint(): Blueprint;

    /**
     * Make an SEO set if it doesn't already exist.
     */
    protected function findOrMakeSeoSet(): SeoDefaultSet
    {
        return Seo::find($this->contentType, $this->handle)
            ?? Seo::make()->type($this->contentType)->handle($this->handle);
    }
}
