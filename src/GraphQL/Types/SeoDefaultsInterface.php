<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Models\Defaults;
use Rebing\GraphQL\Support\InterfaceType;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Types\SiteType;

class SeoDefaultsInterface extends InterfaceType
{
    const NAME = 'SeoDefaultsInterface';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        $fields = [
            'type' => [
                'type' => GraphQL::nonNull(GraphQL::string()),
                'resolve' => fn ($variables) => $variables->type(),
            ],
            'handle' => [
                'type' => GraphQL::nonNull(GraphQL::string()),
                'resolve' => fn ($variables) => $variables->handle(),
            ],
            'title' => [
                'type' => GraphQL::nonNull(GraphQL::string()),
                'resolve' => fn ($variables) => $variables->title(),
            ],
            'site' => [
                'type' => GraphQL::nonNull(GraphQL::type(SiteType::NAME)),
                'resolve' => fn ($variables) => $variables->site(),
            ],
        ];

        foreach (GraphQL::getExtraTypeFields(static::NAME) as $field => $closure) {
            $fields[$field] = $closure();
        }

        return $fields;
    }

    public function resolveType(SeoVariables $variables): string
    {
        return GraphQL::type(SeoDefaultsType::buildName($variables));
    }

    public static function addTypes(): void
    {
        // Remove any disabled defaults. Like collections and taxonomies that were disabled in the config.
        $enabled = collect(Defaults::enabled()->map(fn ($default) => $default['id']))->flip();

        $seoDefaultSets = Seo::all()
            ->flatten()
            ->filter(fn ($set) => $enabled->has("{$set->type()}::{$set->handle()}"))
            ->each(fn ($seoDefaultSet) => $seoDefaultSet->blueprint()->addGqlTypes())
            ->mapInto(SeoDefaultsType::class)
            ->all();

        GraphQL::addTypes($seoDefaultSets);
    }
}
