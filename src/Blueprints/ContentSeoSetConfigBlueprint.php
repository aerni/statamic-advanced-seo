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
                $this->sitemaps(),
                $this->socialAppearance(),
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

    protected function sitemaps(): array
    {
        return [
            'display' => __('Sitemaps'),
            'fields' => [
                [
                    'handle' => 'sitemap',
                    'field' => [
                        'type' => 'toggle',
                        'display' => __('Sitemap'),
                        'instructions' => __("Enables the sitemap for this {$this->contentTypeLabel()}."),
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
                        'display' => __('Social Images Generator'),
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
