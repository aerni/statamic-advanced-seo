<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;

class GeneralSiteSetType extends BaseSiteSetType
{
    const NAME = 'generalSiteSet';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The general SEO settings',
    ];

    protected function blueprint(): string
    {
        return GeneralBlueprint::class;
    }
}
