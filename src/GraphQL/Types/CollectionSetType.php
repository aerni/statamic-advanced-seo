<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

class CollectionSetType extends BaseContentSetType
{
    const NAME = 'collectionSet';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The SEO defaults for a collection',
    ];
}
