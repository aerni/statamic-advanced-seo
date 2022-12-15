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
        'description' => 'The rendered Advanced SEO `head` and `body` views',
    ];

    public function fields(): array
    {
        return [
            'head' => [
                'type' => GraphQL::string(),
                'resolve' => fn (GraphQlCascade $cascade) => view('advanced-seo::head', $cascade->toAugmentedArray()),
            ],
            'body' => [
                'type' => GraphQL::string(),
                'resolve' => fn (GraphQlCascade $cascade) => view('advanced-seo::body', $cascade->toAugmentedArray()),
            ],
        ];
    }
}
