<?php

namespace Aerni\AdvancedSeo\GraphQL\Queries;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\GraphQL\Types\SeoDefaultsInterface;
use Aerni\AdvancedSeo\Models\Defaults;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Queries\Query;

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
        // Remove any disabled defaults. Like collections and taxonomies that were disabled in the config.
        $enabled = collect(Defaults::enabled()->map(fn ($default) => $default['id']))->flip();

        $variables = Seo::all()
            ->flatten()
            ->filter(fn ($set) => $enabled->has("{$set->type()}::{$set->handle()}"))
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
