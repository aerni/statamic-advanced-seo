<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Statamic\Fields\Blueprint;

class DefaultsRepository
{
    public string $contentType;
    public string $handle;
    protected SeoDefaultSet $set;

    public function __construct(string $handle = null)
    {
        // Only assign the handle if none was set by default.
        $this->handle = $this->handle ?? $handle;

        $this->set = $this->findOrMakeSeoSet($this->handle);
    }

    public function get(string $site): Collection
    {
        return $this->findOrMakeSeoVariables($site)->values();
    }

    public function save(string $site, Collection $data): void
    {
        $variables = $this->findOrMakeSeoVariables($site);

        // We only want to save data that is different to the origin.
        if ($variables->hasOrigin()) {
            $data = $data->diffAssoc($variables->origin()->data())->all();
        }

        $variables->data($data)->save();
    }

    public function blueprint(): Blueprint
    {
        return ContentDefaultsBlueprint::make()->get();
    }

    protected function findOrMakeSeoSet(): SeoDefaultSet
    {
        return Seo::find($this->contentType, $this->handle) ?? Seo::make()->type($this->contentType)->handle($this->handle);
    }

    protected function findOrMakeSeoVariables(string $site): SeoVariables
    {
        return $this->set->in($site) ?? $this->set->makeLocalization($site);
    }
}
