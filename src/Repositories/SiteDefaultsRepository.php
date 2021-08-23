<?php

namespace Aerni\AdvancedSeo\Repositories;

use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;
use Statamic\Fields\Blueprint;

class SiteDefaultsRepository
{
    protected SeoDefaultSet $set;

    public function __construct()
    {
        $this->set = $this->findOrMakeSeoSet();
    }

    public function get(string $site): array
    {
        return $this->findOrMakeSeoVariables($site)->values()->all();
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
        return GeneralBlueprint::make()->get();
    }

    protected function findOrMakeSeoSet(): SeoDefaultSet
    {
        return Seo::find('site', 'general') ?? Seo::make()->type('site')->handle('general');
    }

    protected function findOrMakeSeoVariables(string $site): SeoVariables
    {
        return $this->set->in($site) ?? $this->set->makeLocalization($site);
    }
}
