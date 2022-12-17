<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\GraphQL\Fields\ContentDefaultsField;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class SeoDefaultsType extends Type
{
    const NAME = 'seoDefaults';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The Advanced SEO site, collection, and taxonomy defaults',
    ];

    public function fields(): array
    {
        return [
            'site' => [
                'type' => GraphQL::type(SiteDefaultsType::NAME),
                'description' => 'The Advanced SEO site defaults',
                'args' => [
                    'site' => [
                        'type' => GraphQL::string(),
                    ],
                ],
                'resolve' => fn ($root, $args) => $args,
            ],
            'collections' => new ContentDefaultsField,
            'taxonomies' => new ContentDefaultsField,
        ];
    }
}
