<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Features\Sitemap;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;

class ContentSeoSetLocalizationBlueprint extends BaseBlueprint
{
    use HasAssetField;

    protected function handle(): string
    {
        return 'content_localization';
    }

    protected function tabs(): array
    {
        return [
            'seo' => [
                $this->searchAppearance(),
                $this->socialAppearance(),
                $this->indexing(),
                $this->robots(),
                $this->structuredData(),
            ],
        ];
    }

    protected function searchAppearance(): array
    {
        return [
            'display' => $this->trans('seo_section_search_appearance.display'),
            'instructions' => $this->trans('seo_section_search_appearance.default_instructions'),

            'fields' => [
                [
                    'handle' => 'seo_title',
                    'field' => [
                        'type' => 'token_input',
                        'display' => $this->trans('seo_title.display'),
                        'instructions' => $this->trans('seo_title.default_instructions'),
                        'localizable' => true,
                        'listable' => 'hidden',
                        'character_limit' => 60,
                        'default' => '{{ title }} {{ separator }} {{ site_name }}',
                    ],
                ],
                [
                    'handle' => 'seo_description',
                    'field' => [
                        'type' => 'token_input',
                        'display' => $this->trans('seo_description.display'),
                        'instructions' => $this->trans('seo_description.default_instructions'),
                        'localizable' => true,
                        'listable' => 'hidden',
                        'character_limit' => 160,
                    ],
                ],
            ],
        ];
    }

    protected function socialAppearance(): array
    {
        return [
            'display' => $this->trans('seo_section_social_appearance.display'),
            'instructions' => $this->trans('seo_section_social_appearance.default_instructions'),

            'fields' => [
                [
                    'handle' => 'seo_og_title',
                    'field' => [
                        'type' => 'token_input',
                        'display' => $this->trans('seo_og_title.display'),
                        'instructions' => $this->trans('seo_og_title.default_instructions'),
                        'default' => '{{ seo_title }}',
                        'localizable' => true,
                        'character_limit' => 70,
                    ],
                ],
                [
                    'handle' => 'seo_og_description',
                    'field' => [
                        'type' => 'token_input',
                        'display' => $this->trans('seo_og_description.display'),
                        'instructions' => $this->trans('seo_og_description.default_instructions'),
                        'default' => '{{ seo_description }}',
                        'localizable' => true,
                        'character_limit' => 200,
                    ],
                ],
                [
                    'handle' => 'seo_og_image',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_og_image.display'),
                        'instructions' => $this->trans('seo_og_image.default_instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'field' => $this->getAssetFieldConfig([
                            'validate' => [
                                'image',
                                'mimes:jpg,png',
                            ],
                        ]),
                    ],
                ],
                [
                    'handle' => 'seo_generate_social_images',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('seo_generate_social_images.display'),
                        'instructions' => $this->trans('seo_generate_social_images.default_instructions'),
                        'default' => true,
                        'icon' => 'toggle',
                        'localizable' => true,
                        'listable' => 'hidden',
                        'feature' => SocialImagesGenerator::class,
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'seo_social_images_theme',
                    'field' => [
                        'type' => 'social_images_theme',
                        'display' => $this->trans('seo_social_images_theme.display'),
                        'instructions' => $this->trans('seo_social_images_theme.default_instructions'),
                        'localizable' => true,
                        'listable' => 'hidden',
                        'visibility' => $this->lazy(fn (?Context $context) => SocialImage::themes()->allowedFor($context->seoSet())->count() === 1 ? 'hidden' : 'visible', 'visible'),
                        'feature' => SocialImagesGenerator::class,
                        'width' => 50,
                    ],
                ],
            ],
        ];
    }

    protected function indexing(): array
    {
        return [
            'display' => $this->trans('seo_section_indexing.display'),
            'instructions' => $this->trans('seo_section_indexing.default_instructions'),

            'fields' => [
                [
                    'handle' => 'seo_noindex',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('seo_noindex.display'),
                        'instructions' => $this->trans('seo_noindex.default_instructions'),
                        'default' => false,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'seo_sitemap_enabled',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('seo_sitemap_enabled.display'),
                        'instructions' => $this->trans('seo_sitemap_enabled.default_instructions'),
                        'default' => true,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                        'feature' => Sitemap::class,
                    ],
                ],
            ],
        ];
    }

    protected function robots(): array
    {
        return [
            'display' => $this->trans('seo_section_robots.display'),
            'instructions' => $this->trans('seo_section_robots.default_instructions'),
            'fields' => [
                [
                    'handle' => 'seo_nofollow',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_nofollow.display'),
                        'instructions' => $this->trans('seo_nofollow.default_instructions'),
                        'default' => '@default',
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                        'field' => [
                            'type' => 'toggle',
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_noarchive',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('seo_noarchive.display'),
                        'instructions' => $this->trans('seo_noarchive.default_instructions'),
                        'default' => false,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'seo_nosnippet',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('seo_nosnippet.display'),
                        'instructions' => $this->trans('seo_nosnippet.default_instructions'),
                        'default' => false,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'seo_noimageindex',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('seo_noimageindex.display'),
                        'instructions' => $this->trans('seo_noimageindex.default_instructions'),
                        'default' => false,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                    ],
                ],
            ],
        ];
    }

    protected function structuredData(): array
    {
        return [
            'display' => $this->trans('seo_section_structured_data.display'),
            'instructions' => $this->trans('seo_section_structured_data.default_instructions'),

            'fields' => [
                [
                    'handle' => 'seo_json_ld',
                    'field' => [
                        'type' => 'json_ld',
                        'display' => $this->trans('seo_json_ld.display'),
                        'instructions' => $this->trans('seo_json_ld.default_instructions'),
                        'theme' => 'material',
                        'mode' => 'javascript',
                        'mode_selectable' => false,
                        'listable' => 'hidden',
                        'localizable' => true,
                    ],
                ],
            ],
        ];
    }
}
