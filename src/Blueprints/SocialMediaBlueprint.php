<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Fields\SocialMediaFields;

class SocialMediaBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'social';
    }

    protected function sections(): array
    {
        return [
            'social' => SocialMediaFields::class,
        ];
    }
}
