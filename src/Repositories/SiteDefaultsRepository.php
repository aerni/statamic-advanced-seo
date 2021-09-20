<?php

namespace Aerni\AdvancedSeo\Repositories;

use Statamic\Fields\Blueprint;
use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;
use Aerni\AdvancedSeo\Blueprints\MarketingBlueprint;
use Aerni\AdvancedSeo\Repositories\BaseDefaultsRepository;

class SiteDefaultsRepository extends BaseDefaultsRepository
{
    public string $contentType = 'site';

    // TODO: Make this dynamic with a Blueprint Repository.
    public function blueprint(): Blueprint
    {
        if ($this->handle === 'general') {
            return GeneralBlueprint::make()->get();
        }

        if ($this->handle === 'marketing') {
            return MarketingBlueprint::make()->get();
        }
    }
}
