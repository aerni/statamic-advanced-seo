<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Blueprints\ContentDefaultsBlueprint;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Statamic\Fields\Blueprint;

class CollectionDefaultsRepository
{
    protected SeoDefaultSet $set;

    public function __construct(string $handle)
    {
        $this->set = $this->findOrMakeSeoSet($handle);
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

    protected function findOrMakeSeoSet(string $handle): SeoDefaultSet
    {
        return Seo::find('collections', $handle) ?? Seo::make()->type('collections')->handle($handle);
    }

    protected function findOrMakeSeoVariables(string $site): SeoVariables
    {
        return $this->set->in($site) ?? $this->set->makeLocalization($site);
    }
}
