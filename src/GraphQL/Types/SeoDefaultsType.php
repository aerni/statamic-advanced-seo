<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Data\SeoVariables;
use GraphQL\Type\Definition\ResolveInfo;
use Statamic\Contracts\GraphQL\ResolvesValues;
use Statamic\Facades\GraphQL;
use Statamic\Support\Str;

class SeoDefaultsType extends \Rebing\GraphQL\Support\Type
{
    public function __construct(private SeoDefaultSet $set)
    {
        $this->attributes['name'] = static::buildName($set);
    }

    public static function buildName(SeoDefaultSet|SeoVariables $set): string
    {
        $type = Str::studly($set->type());
        $handle = Str::studly($set->handle());

        return "SeoDefaults_{$type}_{$handle}";
    }

    public function interfaces(): array
    {
        return [
            GraphQL::type(SeoDefaultsInterface::NAME),
        ];
    }

    public function fields(): array
    {
        return $this->set->blueprint()->fields()->toGql()
            ->filter(fn ($field, $handle) => ! Str::contains($handle, 'section_'))  // We don't want to expose the content of section fields
            ->merge((new SeoDefaultsInterface)->fields())
            ->merge(collect(GraphQL::getExtraTypeFields($this->name))->map(fn ($closure) => $closure()))
            ->map(function (array $field) {
                $field['resolve'] = $field['resolve'] ?? $this->resolver();

                return $field;
            })
            ->all();
    }

    private function resolver()
    {
        return function (ResolvesValues $variables, $args, $context, ResolveInfo $info) {
            return $variables->resolveGqlValue($info->fieldName);
        };
    }
}
