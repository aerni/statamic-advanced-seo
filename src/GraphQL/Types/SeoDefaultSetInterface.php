<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Rebing\GraphQL\Support\InterfaceType;
use Statamic\Facades\GraphQL;
use Statamic\GraphQL\Types\SiteType;

class SeoDefaultSetInterface extends InterfaceType
{
    const NAME = 'SeoDefaultSetInterface';

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
        return GraphQL::type(SeoDefaultSetType::buildName($variables));
    }

    public static function addTypes(): void
    {
        $seoDefaultSets = Seo::all()
            ->flatten()
            ->each(fn ($seoDefaultSet) => $seoDefaultSet->blueprint()->addGqlTypes())
            ->mapInto(SeoDefaultSetType::class)
            ->all();

        GraphQL::addTypes($seoDefaultSets);
    }
}
