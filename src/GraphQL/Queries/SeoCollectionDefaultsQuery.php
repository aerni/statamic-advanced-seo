<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\GraphQL\Types\ContentDefaultsType;
use GraphQL\Type\Definition\Type;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Queries\Query;

class SeoCollectionDefaultsQuery extends Query
{
    protected $attributes = [
        'name' => 'seoCollectionDefaults',
    ];

    public function type(): Type
    {
        return GraphQL::type(ContentDefaultsType::NAME);
    }

    public function args(): array
    {
        return [
            'handle' => [
                'name' => 'handle',
                'type' => GraphQL::string(),
                'rules' => ['required'],
            ],
            'site' => GraphQL::string(),
        ];
    }

    public function resolve($root, $args): ?SeoVariables
    {
        $set = Seo::find('collections', $args['handle']);

        if (! $set) {
            return null;
        }

        if (! $set->isEnabled()) {
            return null;
        }

        return array_has($args, 'site')
            ? $set->in($args['site'])
            : $set->inDefaultSite();
    }
}
