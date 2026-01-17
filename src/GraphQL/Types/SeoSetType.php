<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\GraphQL\Fields\CollectionSetField;
use Aerni\AdvancedSeo\GraphQL\Fields\SiteSetField;
use Aerni\AdvancedSeo\GraphQL\Fields\TaxonomySetField;
use Rebing\GraphQL\Support\Type;

class SeoSetType extends Type
{
    const NAME = 'seoSet';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The Advanced SEO site, collection, and taxonomy sets',
    ];

    public function fields(): array
    {
        return [
            'site' => new SiteSetField,
            'collection' => new CollectionSetField,
            'taxonomy' => new TaxonomySetField,
        ];
    }
}
