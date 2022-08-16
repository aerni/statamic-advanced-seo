<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Illuminate\Support\Arr;
use Statamic\Facades\GraphQL;
use GraphQL\Type\Definition\Type;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\GraphQL\Queries\Query;
use Aerni\AdvancedSeo\GraphQL\Types\SeoDefaultsInterface;
use Illuminate\Support\Collection;

class SeoDefaultsQuery extends Query
{
    protected $attributes = [
        'name' => 'seoDefaults',
    ];

    public function type(): Type
    {
        return GraphQL::listOf(GraphQL::type(SeoDefaultsInterface::NAME));
    }

    public function args(): array
    {
        return [
            'type' => GraphQL::string(),
            'handle' => GraphQL::string(),
            'site' => GraphQL::string(),
        ];
    }

    public function resolve($root, $args): Collection
    {
        $variables = Seo::all()
            ->flatten()
            ->flatMap(fn ($set) => $set->localizations()->values());

        if ($type = Arr::get($args, 'type')) {
            $variables = $variables->filter(fn ($variables) => $variables->type() === $type);
        }

        if ($handle = Arr::get($args, 'handle')) {
            $variables = $variables->filter(fn ($variables) => $variables->handle() === $handle);
        }

        if ($site = Arr::get($args, 'site')) {
            $variables = $variables->filter(fn ($variables) => $variables->locale() === $site);
        }

        return $variables;
    }
}
