<?php

namespace Aerni\AdvancedSeo\GraphQL\Types;

use Rebing\GraphQL\Support\Type;
use Statamic\Facades\GraphQL;

class SocialImagePresetType extends Type
{
    const NAME = 'socialImagePreset';

    protected $attributes = [
        'name' => self::NAME,
    ];

    public function fields(): array
    {
        return [
            'width' => [
                'type' => GraphQL::string(),
                'resolve' => fn (array $preset) => $preset['width'],
            ],
            'height' => [
                'type' => GraphQL::string(),
                'resolve' => fn (array $preset) => $preset['height'],
            ],
        ];
    }
}
