<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Statamic\Facades\GraphQL;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\GraphQL\Types\SiteType;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Rebing\GraphQL\Support\InterfaceType;
use Aerni\AdvancedSeo\GraphQL\Types\SeoDefaultsType;

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
        $seoDefaultSets = Seo::all()
            ->flatten()
            ->each(fn ($seoDefaultSet) => $seoDefaultSet->blueprint()->addGqlTypes())
            ->mapInto(SeoDefaultsType::class)
            ->all();

        GraphQL::addTypes($seoDefaultSets);
    }
}
