<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Aerni\AdvancedSeo\Blueprints\SocialMediaBlueprint;

class SocialMediaSiteSetType extends BaseSiteSetType
{
    const NAME = 'socialMediaSiteSet';

    protected $attributes = [
        'name' => self::NAME,
        'description' => 'The social media settings',
    ];

    protected function blueprint(): string
    {
        return SocialMediaBlueprint::class;
    }
}
