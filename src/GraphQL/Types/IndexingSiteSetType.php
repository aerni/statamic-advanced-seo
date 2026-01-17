<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\IndexingBlueprint;

class IndexingSiteSetType extends BaseSiteSetType
{
    const NAME = 'indexingSiteSet';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The indexing settings',
    ];

    protected function blueprint(): string
    {
        return IndexingBlueprint::class;
    }
}
