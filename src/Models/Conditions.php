<?php

namespace Aerni\AdvancedSeo\Models;

use Aerni\AdvancedSeo\Conditions\ShowSitemapFields;
use Aerni\AdvancedSeo\Conditions\ShowSocialImagesGeneratorFields;
use Aerni\AdvancedSeo\Data\DefaultsData;
use Illuminate\Support\Collection;

class Conditions extends Model
{
    protected static function getRows(): array
    {
        return [
            'showSitemapFields' => ShowSitemapFields::class,
            'showSocialImagesGeneratorFields' => ShowSocialImagesGeneratorFields::class,
        ];
    }

    protected static function all(): Collection
    {
        return static::$rows;
    }

    protected static function evaluate(DefaultsData $data): Collection
    {
        return collect(static::$rows)->map(fn ($condition) => app($condition)::handle($data));
    }
}
