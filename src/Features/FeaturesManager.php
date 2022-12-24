<?php

namespace Aerni\AdvancedSeo\Features;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Illuminate\Support\Collection;

class FeaturesManager
{
    protected static array $conditions = [
        'sitemap' => \Aerni\AdvancedSeo\Conditions\ShowSitemapFields::class,
        'social_images_generator' => \Aerni\AdvancedSeo\Conditions\ShowSocialImagesGeneratorFields::class,
    ];

    public static function enabled(string $condition, DefaultsData $data): ?bool
    {
        return static::evaluate($condition, $data)->isNotEmpty();
    }

    public static function disabled(string $condition, DefaultsData $data): ?bool
    {
        return ! static::enabled($condition, $data);
    }

    public static function evaluate(string $feature, DefaultsData $data): Collection
    {
        $conditions = collect(static::$conditions)->get($feature);

        return collect($conditions)->map(function ($condition) use ($data) {
            return $condition
                ? resolve($condition)::handle($data)
                : null;
        })->filter();
    }
}
