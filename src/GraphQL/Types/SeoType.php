<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\View\GraphQlCascade;
use Rebing\GraphQL\Support\Type;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Blink;
use Statamic\Facades\GraphQL;

class SeoType extends Type
{
    const NAME = 'seo';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return [
            'computedData' => [
                'type' => GraphQL::type(ComputedDataType::NAME),
                'resolve' => fn ($model) => $this->cascade($model),
            ],
            'pageData' => [
                'type' => GraphQL::type(PageDataType::NAME),
                'resolve' => fn ($model) => $model,
            ],
            'siteDefaults' => [
                'type' => GraphQL::type(SiteDefaultsType::NAME),
                'resolve' => fn ($model) => ['site' => $model->locale()],
            ],
            'renderedViews' => [
                'type' => GraphQL::type(RenderedViewsType::NAME),
                'resolve' => fn ($model) => $this->cascade($model),
            ],
        ];
    }

    private function cascade(Entry|Term $model): GraphQlCascade
    {
        return Blink::once(
            "advanced-seo::cascade::graphql",
            fn () => GraphQlCascade::from($model)->process()
        );
    }
}
