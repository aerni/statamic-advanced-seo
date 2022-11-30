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
        'description' => 'All the Advanced SEO data of an entry/term',
    ];

    public function fields(): array
    {
        return [
            'computedData' => [
                'type' => GraphQL::type(ComputedDataType::NAME),
                'description' => 'The computed Advanced SEO fields like the `title`, `hreflang`, or `indexing`',
                'resolve' => fn (Entry|Term $model) => $this->cascade($model),
            ],
            'pageData' => [
                'type' => GraphQL::type(PageDataType::NAME),
                'description' => 'The unprocessed Advanced SEO fields of the entry/term',
                'resolve' => fn (Entry|Term $model) => $model,
            ],
            'siteDefaults' => [
                'type' => GraphQL::type(SiteDefaultsType::NAME),
                'description' => 'The Advanced SEO site defaults like `site_name` in the locale of the entry/term',
                'resolve' => fn (Entry|Term $model) => ['site' => $model->locale()],
            ],
            'renderedViews' => [
                'type' => GraphQL::type(RenderedViewsType::NAME),
                'description' => 'The rendered Advanced SEO `head` and `body` views',
                'resolve' => fn (Entry|Term $model) => $this->cascade($model),
            ],
        ];
    }

    private function cascade(Entry|Term $model): GraphQlCascade
    {
        return Blink::once(
            'advanced-seo::cascade::graphql',
            fn () => GraphQlCascade::from($model)->process()
        );
    }
}
