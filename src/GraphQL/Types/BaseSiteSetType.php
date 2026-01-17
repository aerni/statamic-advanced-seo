<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\GraphQL\Resolvers\SeoSetLocalizationResolver;
use Rebing\GraphQL\Support\Type;

abstract class BaseSiteSetType extends Type
{
    abstract protected function blueprint(): string;

    public function fields(): array
    {
        return $this->blueprint()::definition()->fields()->toGql()
            ->map(fn ($field, $handle) => $field + ['resolve' => SeoSetLocalizationResolver::resolve($handle)])
            ->all();
    }
}
