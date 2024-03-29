<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Actions\GetPageData;
use Aerni\AdvancedSeo\View\GraphQlCascade;
use Rebing\GraphQL\Support\Type;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\GraphQL;

class SeoMetaType extends Type
{
    const NAME = 'seoMeta';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The Advanced SEO meta data of an entry or term',
    ];

    public function fields(): array
    {
        return [
            'computed' => [
                'type' => GraphQL::type(ComputedMetaDataType::NAME),
                'description' => 'The Advanced SEO computed meta data',
                'resolve' => fn (Entry|Term $model) => GraphQlCascade::from($model),
            ],
            'raw' => [
                'type' => GraphQL::type(RawMetaDataType::NAME),
                'description' => 'The Advanced SEO raw meta data',
                // TODO: This eats performance as we are augmenting all the data before it's needed.
                // Before, we simply augmented single fields from the entry when needed.
                // Can we also get single augmented fields. Maybe a new action that only handles one field?
                'resolve' => fn (Entry|Term $model) => GetPageData::handle($model),
            ],
            'view' => [
                'type' => GraphQL::type(RenderedViewsType::NAME),
                'description' => 'The rendered Advanced SEO `head` and `body` views. Only use this when your frontend is hosted on the same domain as Statamic, as the views contain a whole bunch of absolute URLs that won\'t make sense otherwise.',
                'resolve' => fn (Entry|Term $model) => GraphQlCascade::from($model),
            ],
        ];
    }
}
