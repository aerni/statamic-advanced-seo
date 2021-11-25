<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\FaviconsFields;

class FaviconsBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'favicons';
    }

    protected function sections(): array
    {
        return [
            'favicons' => FaviconsFields::class,
        ];
    }
}
