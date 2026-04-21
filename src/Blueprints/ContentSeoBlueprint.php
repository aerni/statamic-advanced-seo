<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Features\Sitemap;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;

class ContentSeoBlueprint extends BaseBlueprint
{
    use HasAssetField;

    protected function handle(): string
    {
        return 'content';
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
            'instructions' => $this->trans('seo_section_search_appearance.instructions'),
            'fields' => [
                [
                    'handle' => 'seo_noindex_alert',
                    'field' => [
                        'type' => 'alert',
                        'alert' => 'indexing_disabled',
                        'listable' => 'hidden',
                        'hide_display' => true,
                    ],
                ],
                [
                    'handle' => 'seo_title',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_title.display'),
                        'instructions' => $this->trans('seo_title.instructions'),
                        'localizable' => true,
                        'field' => [
                            'type' => 'token_input',
                            'character_limit' => 60,
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_description',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_description.display'),
                        'instructions' => $this->trans('seo_description.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'field' => [
                            'type' => 'token_input',
                            'character_limit' => 160,
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_search_preview',
                    'field' => [
                        'type' => 'search_preview',
                        'display' => $this->trans('seo_search_preview.display'),
                        'listable' => 'hidden',
                    ],
                ],
            ],
        ];
    }

    protected function socialAppearance(): array
    {
        return [
            'display' => $this->trans('seo_section_social_appearance.display'),
            'instructions' => $this->trans('seo_section_social_appearance.instructions'),
            'fields' => [
                [
                    'handle' => 'seo_og_title',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_og_title.display'),
                        'instructions' => $this->trans('seo_og_title.instructions'),
                        'localizable' => true,
                        'field' => [
                            'type' => 'token_input',
                            'character_limit' => 70,
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_og_description',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_og_description.display'),
                        'instructions' => $this->trans('seo_og_description.instructions'),
                        'localizable' => true,
                        'field' => [
                            'type' => 'token_input',
                            'character_limit' => 200,
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_og_image',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_og_image.display'),
                        'instructions' => $this->trans('seo_og_image.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'if' => [
                            'seo_generate_social_images.value' => 'isnt true',
                        ],
                        'field' => [
                            'type' => 'social_image',
                            'container' => config('advanced-seo.social_images.container', 'assets'),
                            'folder' => 'social_images',
                            'max_files' => 1,
                            'mode' => 'list',
                            'allow_uploads' => true,
                            'restrict' => false,
                            'validate' => [
                                'image',
                                'mimes:jpg,png',
                            ],
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_generate_social_images',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_generate_social_images.display'),
                        'instructions' => $this->trans('seo_generate_social_images.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'feature' => SocialImagesGenerator::class,
                        'width' => 50,
                        'field' => [
                            'type' => 'toggle',
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_social_images_theme',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_social_images_theme.display'),
                        'instructions' => $this->trans('seo_social_images_theme.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'visibility' => $this->lazy(fn (?Context $context) => SocialImage::themes()->allowedFor($context->seoSet())->count() === 1 ? 'hidden' : 'visible', 'visible'),
                        'feature' => SocialImagesGenerator::class,
                        'width' => 50,
                        'if' => [
                            'seo_generate_social_images.value' => 'true',
                        ],
                        'field' => [
                            'type' => 'social_images_theme',
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_social_preview',
                    'field' => [
                        'type' => 'social_preview',
                        'display' => $this->trans('seo_social_preview.display'),
                        'listable' => 'hidden',
                    ],
                ],
            ],
        ];
    }

    protected function indexing(): array
    {
        return [
            'display' => $this->trans('seo_section_indexing.display'),
            'instructions' => $this->trans('seo_section_indexing.instructions'),
            'fields' => [
                [
                    'handle' => 'seo_noindex',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_noindex.display'),
                        'instructions' => $this->trans('seo_noindex.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'field' => [
                            'type' => 'toggle',
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_canonical_type',
                    'field' => [
                        'type' => 'button_group',
                        'display' => $this->trans('seo_canonical_type.display'),
                        'instructions' => $this->trans('seo_canonical_type.instructions'),
                        'options' => [
                            'current' => $this->trans('seo_canonical_type.current'),
                            'entry' => $this->trans('seo_canonical_type.entry'),
                            'custom' => $this->trans('seo_canonical_type.custom'),
                        ],
                        'default' => 'current',
                        'localizable' => true,
                        'listable' => 'hidden',
                        'width' => 50,
                        'if' => [
                            'seo_noindex.value' => 'false',
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_sitemap_enabled',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_sitemap_enabled.display'),
                        'instructions' => $this->trans('seo_sitemap_enabled.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'width' => 50,
                        'feature' => Sitemap::class,
                        'if' => [
                            'seo_noindex.value' => 'false',
                            'seo_canonical_type' => 'equals current',
                        ],
                        'field' => [
                            'type' => 'toggle',
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_canonical_entry',
                    'field' => [
                        'type' => 'entries',
                        'display' => $this->trans('seo_canonical_entry.display'),
                        'instructions' => $this->trans('seo_canonical_entry.instructions'),
                        'component' => 'relationship',
                        'mode' => 'stack',
                        'max_items' => 1,
                        'query_scopes' => ['routable_entries'],
                        'select_across_sites' => true,
                        'localizable' => true,
                        'listable' => 'hidden',
                        'width' => 50,
                        'if' => [
                            'seo_noindex.value' => 'false',
                            'seo_canonical_type' => 'equals entry',
                        ],
                        'validate' => [
                            'required_if:seo_canonical_type,entry',
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_canonical_custom',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('seo_canonical_custom.display'),
                        'instructions' => $this->trans('seo_canonical_custom.instructions'),
                        'input_type' => 'url',
                        'localizable' => true,
                        'listable' => 'hidden',
                        'width' => 50,
                        'if' => [
                            'seo_noindex.value' => 'false',
                            'seo_canonical_type' => 'equals custom',
                        ],
                        'validate' => [
                            'required_if:seo_canonical_type,custom',
                            'active_url',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function robots(): array
    {
        return [
            'display' => $this->trans('seo_section_robots.display'),
            'instructions' => $this->trans('seo_section_robots.instructions'),
            'collapsible' => true,
            'collapsed' => true,

            'fields' => [
                [
                    'handle' => 'seo_nofollow',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_nofollow.display'),
                        'instructions' => $this->trans('seo_nofollow.instructions'),
                        'default' => '@default',
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
                        'type' => 'seo',
                        'display' => $this->trans('seo_noarchive.display'),
                        'instructions' => $this->trans('seo_noarchive.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'width' => 50,
                        'field' => [
                            'type' => 'toggle',
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_nosnippet',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_nosnippet.display'),
                        'instructions' => $this->trans('seo_nosnippet.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'width' => 50,
                        'field' => [
                            'type' => 'toggle',
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_noimageindex',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_noimageindex.display'),
                        'instructions' => $this->trans('seo_noimageindex.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'width' => 50,
                        'field' => [
                            'type' => 'toggle',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function structuredData(): array
    {
        return [
            'display' => $this->trans('seo_section_structured_data.display'),
            'instructions' => $this->trans('seo_section_structured_data.instructions'),
            'collapsible' => true,
            'collapsed' => true,

            'fields' => [
                [
                    'handle' => 'seo_json_ld',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_json_ld.display'),
                        'instructions' => $this->trans('seo_json_ld.instructions'),
                        'default' => '@default',
                        'localizable' => true,

                        'field' => [
                            'type' => 'json_ld',
                            'theme' => 'material',
                            'mode' => 'javascript',
                            'mode_selectable' => false,
                        ],
                    ],
                ],
            ],
        ];
    }
}
