<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\View\GraphQlCascade;
use Rebing\GraphQL\Support\Type;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\GraphQL;

class SeoType extends Type
{
    const NAME = 'seo';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'All the Advanced SEO data of an entry/term',
    ];

    public function fields(): array
    {
        return [
            'computed' => [
                'type' => GraphQL::type(ComputedDataType::NAME),
                'description' => 'The computed Advanced SEO fields like the `title`, `hreflang`, or `indexing`',
                'resolve' => fn (Entry|Term $model) => GraphQlCascade::from($model),
            ],
            'data' => [
                'type' => GraphQL::type(PageDataType::NAME),
                'description' => 'The unprocessed Advanced SEO fields of the entry/term',
                'resolve' => fn (Entry|Term $model) => $model,
            ],
            'defaults' => [
                'type' => GraphQL::type(SiteDefaultsType::NAME),
                'description' => 'The Advanced SEO site defaults like `site_name` in the locale of the entry/term',
                'resolve' => fn (Entry|Term $model) => ['site' => $model->locale()],
            ],
            'view' => [
                'type' => GraphQL::type(RenderedViewsType::NAME),
                'description' => 'The rendered Advanced SEO `head` and `body` views. Only use this when your frontend is hosted on the same domain as Statamic, as the views contain a whole bunch of absolute URLs that won\'t make sense otherwise.',
                'resolve' => fn (Entry|Term $model) => GraphQlCascade::from($model),
            ],
        ];
    }
}
