<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Facades\SocialImageTheme;
use Aerni\AdvancedSeo\Features\Sitemap;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;

class ContentSeoSetConfigBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'content_config';
    }

    protected function tabs(): array
    {
        return [
            'main' => [
                $this->enabled(),
                $this->origins(),
                $this->sitemap(),
                $this->socialImagesGenerator(),
            ],
        ];
    }

    protected function enabled(): array
    {
        return [
            'display' => __('Enabled'),
            'fields' => [
                [
                    'handle' => 'enabled',
                    'field' => [
                        'type' => 'toggle',
                        'display' => __('Enabled'),
                        'instructions' => __("Enables SEO for this {$this->contentTypeLabel()}."),
                        'default' => true,
                    ],
                ],
            ],
        ];
    }

    protected function origins(): array
    {
        return [
            'display' => __('Origins'),
            'fields' => [
                [
                    'handle' => 'origins',
                    'field' => [
                        'type' => 'site_origins',
                        'display' => __('Origins'),
                        'instructions' => __('Choose to inherit values from selected origins.'),
                        'default' => [],
                        'if' => ['enabled' => 'true'],
                    ],
                ],
            ],
        ];
    }

    protected function sitemap(): array
    {
        return [
            'display' => __('Sitemap'),
            'fields' => [
                [
                    'handle' => 'sitemap',
                    'field' => [
                        'type' => 'toggle',
                        'display' => __('Enabled'),
                        'instructions' => __("Enables the sitemap for this {$this->contentTypeLabel()}."),
                        'default' => true,
                        'if' => ['enabled' => 'true'],
                        'feature' => Sitemap::class,
                    ],
                ],
            ],
        ];
    }

    protected function socialImagesGenerator(): array
    {
        return [
            'display' => __('Social Images Generator'),
            'fields' => [
                [
                    'handle' => 'social_images_generator',
                    'field' => [
                        'type' => 'toggle',
                        'display' => __('Enabled'),
                        'instructions' => __("Enables the social images generator for this {$this->contentTypeLabel()}."),
                        'default' => false,
                        'if' => ['enabled' => 'true'],
                        'feature' => SocialImagesGenerator::class,
                    ],
                ],
                [
                    'handle' => 'social_images_themes',
                    'field' => [
                        'type' => 'select',
                        'display' => __('Themes'),
                        'instructions' => __("Select the social image themes available for this {$this->contentTypeLabel()}."),
                        'options' => SocialImageTheme::all()->options(),
                        'default' => SocialImageTheme::all()->default()?->handle,
                        'multiple' => true,
                        'validate' => [
                            'required',
                            'sometimes',
                        ],
                        'if' => [
                            'enabled' => 'true',
                            'social_images_generator' => 'true',
                        ],
                        'feature' => SocialImagesGenerator::class,
                    ],
                ],
            ],
        ];
    }
}
