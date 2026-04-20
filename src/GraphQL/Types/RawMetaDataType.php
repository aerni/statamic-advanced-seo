<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\ContentSeoBlueprint;
use Illuminate\Support\Collection;
use Rebing\GraphQL\Support\Type;
use Statamic\Support\Str;

class RawMetaDataType extends Type
{
    const NAME = 'rawMetaData';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The Advanced SEO raw meta data',
    ];

    public function fields(): array
    {
        return ContentSeoBlueprint::definition()->fields()->toGql()
            ->reject(fn ($field, $handle) => in_array($handle, ['seo_search_preview', 'seo_social_preview'])) // These fields don't return any value. No need to spam the fields definition with them.
            ->map(fn ($field, $handle) => $field + ['resolve' => $this->resolver($handle)])
            ->mapWithKeys(fn ($field, $handle) => [Str::remove('seo_', $handle) => $field])
            ->all();
    }

    private function resolver(string $field): callable
    {
        return fn (Collection $values) => $values->get($field)?->value();
    }
}
