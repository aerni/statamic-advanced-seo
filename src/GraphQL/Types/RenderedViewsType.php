<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\View\GraphQlCascade;
use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class RenderedViewsType extends Type
{
    const NAME = 'renderedViews';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The rendered Advanced SEO `head` and `body` views. Only use this when your frontend is hosted on the same domain as Statamic, as the views contain a whole bunch of absolute URLs that won\'t make sense otherwise.',
    ];

    public function fields(): array
    {
        return [
            'head' => [
                'type' => GraphQL::string(),
                'resolve' => fn (GraphQlCascade $cascade) => view('advanced-seo::head', ['seo' => $cascade->toAugmentedArray()]),
            ],
            'body' => [
                'type' => GraphQL::string(),
                'resolve' => fn (GraphQlCascade $cascade) => view('advanced-seo::body', ['seo' => $cascade->toAugmentedArray()]),
            ],
        ];
    }
}
