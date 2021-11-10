<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\FaviconsFields;
use Aerni\AdvancedSeo\Fields\GeneralFields;
use Aerni\AdvancedSeo\Fields\SocialFields;

class GeneralBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'general';
    }

    protected function sections(): array
    {
        return [
            'general' => GeneralFields::class,
            'favicons' => FaviconsFields::class,
            'social' => SocialFields::class,
        ];
    }
}
