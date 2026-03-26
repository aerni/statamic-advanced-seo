<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Facades\SocialImage;
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
                $this->sitemaps(),
                $this->socialAppearance(),
            ],
        ];
    }

    protected function enabled(): array
    {
        return [
            'display' => __('General'),
            'fields' => [
                [
                    'handle' => 'enabled',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('config_enabled.display'),
                        'instructions' => $this->trans('config_enabled.instructions'),
                        'default' => true,
                    ],
                ],
                [
                    'handle' => 'editable',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('config_editable.display'),
                        'instructions' => $this->trans('config_editable.instructions'),
                        'default' => true,
                        'if' => ['enabled' => 'true'],
                    ],
                ],
            ],
        ];
    }

    protected function origins(): array
    {
        return [
            'display' => __('advanced-seo::messages.origins'),
            'fields' => [
                [
                    'handle' => 'origins',
                    'field' => [
                        'type' => 'site_origins',
                        'display' => __('advanced-seo::messages.origins'),
                        'instructions' => __('advanced-seo::messages.origins_instructions'),
                        'default' => [],
                        'if' => ['enabled' => 'true'],
                    ],
                ],
            ],
        ];
    }

    protected function sitemaps(): array
    {
        return [
            'display' => $this->trans('config_sitemaps.display'),
            'fields' => [
                [
                    'handle' => 'sitemap',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('config_sitemap.display'),
                        'instructions' => $this->trans('config_sitemap.instructions'),
                        'default' => true,
                        'if' => ['enabled' => 'true'],
                        'feature' => Sitemap::class,
                    ],
                ],
            ],
        ];
    }

    protected function socialAppearance(): array
    {
        return [
            'display' => $this->trans('seo_section_social_appearance.display'),
            'fields' => [
                [
                    'handle' => 'twitter_card',
                    'field' => [
                        'type' => 'button_group',
                        'display' => $this->trans('twitter_card.display'),
                        'instructions' => $this->trans('twitter_card.instructions'),
                        'options' => [
                            'summary_large_image' => $this->trans('twitter_card.summary_large_image'),
                            'summary' => $this->trans('twitter_card.summary'),
                        ],
                        'default' => 'summary_large_image',
                        'if' => ['enabled' => 'true'],
                    ],
                ],
                [
                    'handle' => 'social_images_generator',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('config_social_images_generator.display'),
                        'instructions' => $this->trans('config_social_images_generator.instructions'),
                        'default' => false,
                        'if' => ['enabled' => 'true'],
                        'feature' => SocialImagesGenerator::class,
                    ],
                ],
                [
                    'handle' => 'social_images_themes',
                    'field' => [
                        'type' => 'select',
                        'display' => $this->trans('config_social_images_themes.display'),
                        'instructions' => $this->trans('config_social_images_themes.instructions'),
                        'options' => SocialImage::themes()->all()->options(),
                        'default' => SocialImage::themes()->all()->default()?->handle,
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
