<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class SeoType extends Type
{
    const NAME = 'Seo';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return [
            'computedData' => [
                'type' => GraphQL::type(ComputedDataType::NAME),
                'resolve' => fn ($cascade) => $cascade->getComputedData(),
            ],
            'pageData' => [
                'type' => GraphQL::type(PageDataType::NAME),
                'resolve' => fn ($cascade) => $cascade->getPageData(),
            ],
            'siteDefaults' => [
                'type' => GraphQL::type(SiteDefaultsType::NAME),
                'resolve' => fn ($cascade) => $cascade->getSiteDefaults(),
            ],
            'renderedView' => [
                'type' => GraphQL::type(RenderedViewType::NAME),
                'resolve' => fn ($cascade) => $cascade->all(),
            ],
        ];
    }
}