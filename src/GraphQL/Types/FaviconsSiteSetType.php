<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\FaviconsBlueprint;

class FaviconsSiteSetType extends BaseSiteSetType
{
    const NAME = 'faviconsSiteSet';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The favicons settings',
    ];

    protected function blueprint(): string
    {
        return FaviconsBlueprint::class;
    }
}
