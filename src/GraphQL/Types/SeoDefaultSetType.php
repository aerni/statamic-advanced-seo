<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Statamic\Facades\GraphQL;
use Statamic\Support\Str;

class SeoDefaultSetType extends \Rebing\GraphQL\Support\Type
{
    public function __construct(private SeoDefaultSet $set)
    {
        $this->attributes['name'] = static::buildName($set);
    }

    public static function buildName($set): string
    {
        $type = Str::studly($set->type());
        $handle = Str::studly($set->handle());

        return "SeoDefaultSet_{$type}_{$handle}";
    }

    public function interfaces(): array
    {
        return [
            GraphQL::type(SeoDefaultSetInterface::NAME),
        ];
    }

    public function fields(): array
    {
        return $this->set->blueprint()->fields()->toGql()
            ->filter(fn ($field, $handle) => ! Str::startsWith($handle, 'section'))
            ->merge((new SeoDefaultSetInterface)->fields())
            ->merge(collect(GraphQL::getExtraTypeFields($this->name))->map(fn ($closure) => $closure()))
            ->map(function (array $field) {
                $field['resolve'] = $field['resolve'] ?? $this->resolver();

                return $field;
            })
            ->all();
    }

    private function resolver()
    {
        return function (SeoVariables $variables, $args, $context, $info) {
            return $variables->resolveGqlValue($info->fieldName);
        };
    }
}
