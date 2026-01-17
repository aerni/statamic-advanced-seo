<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

class TaxonomySetType extends BaseContentSetType
{
    const NAME = 'taxonomySet';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The SEO defaults for a taxonomy',
    ];
}
