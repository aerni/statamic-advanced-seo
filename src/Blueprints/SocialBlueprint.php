<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\SocialFields;

class SocialBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'social';
    }

    protected function sections(): array
    {
        return [
            'social' => SocialFields::class,
        ];
    }
}
