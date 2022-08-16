<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Statamic\Facades\Site;
use Illuminate\Support\Arr;
use Statamic\Facades\GraphQL;
use Statamic\Facades\GlobalSet;
use GraphQL\Type\Definition\Type;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\GraphQL\Queries\Query;
use Statamic\GraphQL\Types\GlobalSetInterface;
use Aerni\AdvancedSeo\GraphQL\Types\SeoDefaultSetInterface;

class SeoDefaultSetsQuery extends Query
{
    protected $attributes = [
        'name' => 'seoDefaultSets',
    ];

    public function type(): Type
    {
        return GraphQL::listOf(GraphQL::type(SeoDefaultSetInterface::NAME));
    }

    public function args(): array
    {
        return [
            'site' => GraphQL::string(),
        ];
    }

    public function resolve($root, $args)
    {
        if ($site = Arr::get($args, 'site')) {
            return Seo::all()->flatten()->map->in($site);
        }

        return Seo::all()->flatten()->flatMap(fn ($set) => $set->localizations()->values());
    }
}
