<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\SocialImageTheme;
use Aerni\AdvancedSeo\Features\Sitemap;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Illuminate\Support\Str;

class OnPageSeoBlueprint extends BaseBlueprint
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
                $this->canonicalUrl(),
                $this->sitemap(),
                $this->jsonLd(),
            ],
        ];
    }

    protected function searchAppearance(): array
    {
        return [
            'display' => $this->trans('seo_section_search_appearance.display'),
            'instructions' => $this->trans('seo_section_search_appearance.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'seo_title',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_title.display'),
                        'instructions' => $this->trans('seo_title.instructions'),
                        'localizable' => true,
                        'field' => [
                            'type' => 'text',
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
                            'type' => 'textarea',
                            'character_limit' => 160,
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_site_name_position',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_site_name_position.display'),
                        'instructions' => $this->trans('seo_site_name_position.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'field' => [
                            'type' => 'button_group',
                            'options' => [
                                'end' => $this->trans('seo_site_name_position.end'),
                                'start' => $this->trans('seo_site_name_position.start'),
                                'disabled' => $this->trans('seo_site_name_position.disabled'),
                            ],
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
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'seo_og_title',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_og_title.display'),
                        'instructions' => $this->trans('seo_og_title.instructions'),
                        'localizable' => true,
                        'field' => [
                            'type' => 'text',
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
                            'type' => 'textarea',
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
                        'visibility' => $this->lazy(fn (?Context $context) => SocialImageTheme::allowedFor($context->seoSet())->count() === 1 ? 'hidden' : 'visible', 'visible'),
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
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'seo_noindex',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_noindex.display'),
                        'instructions' => $this->trans('seo_noindex.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'width' => 50,
                        'field' => [
                            'type' => 'toggle',
                        ],
                    ],
                ],
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
            ],
        ];
    }

    protected function canonicalUrl(): array
    {
        return [
            'display' => $this->trans('seo_section_canonical_url.display'),
            'instructions' => $this->trans('seo_section_canonical_url.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'seo_canonical_type',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_canonical_type.display'),
                        'instructions' => $this->trans('seo_canonical_type.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'if' => [
                            'seo_noindex.value' => 'false',
                        ],
                        'field' => [
                            'type' => 'button_group',
                            'options' => [
                                'current' => $this->trans('seo_canonical_type.current', ['type' => ucfirst(Str::singular($this->contentTypeLabel()))]),
                                'other' => $this->trans('seo_canonical_type.other'),
                                'custom' => $this->trans('seo_canonical_type.custom'),
                            ],
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_canonical_entry',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_canonical_entry.display'),
                        'instructions' => $this->trans('seo_canonical_entry.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'if' => [
                            'seo_noindex.value' => 'false',
                            'seo_canonical_type.value' => 'equals other',
                        ],
                        'field' => [
                            'type' => 'entries',
                            'component' => 'relationship',
                            'mode' => 'stack',
                            'max_items' => 1,
                            'validate' => [
                                'required_if:seo_canonical_type,other',
                            ],
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_canonical_custom',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_canonical_custom.display'),
                        'instructions' => $this->trans('seo_canonical_custom.instructions'),
                        'default' => '@default',
                        'localizable' => true,

                        'if' => [
                            'seo_noindex.value' => 'false',
                            'seo_canonical_type.value' => 'equals custom',
                        ],
                        'field' => [
                            'type' => 'text',
                            'input_type' => 'url',
                            'validate' => [
                                'required_if:seo_canonical_type,custom',
                                'active_url',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function sitemap(): array
    {
        return [
            'display' => $this->trans('seo_section_sitemap.display'),
            'instructions' => $this->trans('seo_section_sitemap.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'seo_sitemap_enabled',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_sitemap_enabled.display'),
                        'instructions' => $this->trans('seo_sitemap_enabled.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'feature' => Sitemap::class,
                        'if' => [
                            'seo_noindex.value' => 'false',
                            'seo_canonical_type.value' => 'equals current',
                        ],
                        'field' => [
                            'type' => 'toggle',
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_sitemap_priority',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_sitemap_priority.display'),
                        'instructions' => $this->trans('seo_sitemap_priority.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'width' => 50,
                        'feature' => Sitemap::class,
                        'if' => [
                            'seo_noindex.value' => 'false',
                            'seo_canonical_type.value' => 'equals current',
                            'seo_sitemap_enabled.value' => 'true',
                        ],
                        'field' => [
                            'type' => 'select',
                            'options' => [
                                [
                                    'key' => '0.0',
                                    'value' => '0.0',
                                ],
                                [
                                    'key' => '0.1',
                                    'value' => '0.1',
                                ],
                                [
                                    'key' => '0.2',
                                    'value' => '0.2',
                                ],
                                [
                                    'key' => '0.3',
                                    'value' => '0.3',
                                ],
                                [
                                    'key' => '0.4',
                                    'value' => '0.4',
                                ],
                                [
                                    'key' => '0.5',
                                    'value' => '0.5',
                                ],
                                [
                                    'key' => '0.6',
                                    'value' => '0.6',
                                ],
                                [
                                    'key' => '0.7',
                                    'value' => '0.7',
                                ],
                                [
                                    'key' => '0.8',
                                    'value' => '0.8',
                                ],
                                [
                                    'key' => '0.9',
                                    'value' => '0.9',
                                ],
                                [
                                    'key' => '1.0',
                                    'value' => '1.0',
                                ],
                            ],
                            'clearable' => false,
                            'multiple' => false,
                            'searchable' => false,
                            'taggable' => false,
                            'push_tags' => false,
                            'cast_booleans' => false,
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_sitemap_change_frequency',
                    'field' => [
                        'type' => 'seo',
                        'display' => $this->trans('seo_sitemap_change_frequency.display'),
                        'instructions' => $this->trans('seo_sitemap_change_frequency.instructions'),
                        'default' => '@default',
                        'localizable' => true,
                        'width' => 50,
                        'feature' => Sitemap::class,
                        'if' => [
                            'seo_noindex.value' => 'false',
                            'seo_canonical_type.value' => 'equals current',
                            'seo_sitemap_enabled.value' => 'true',
                        ],
                        'field' => [
                            'type' => 'select',
                            'options' => [
                                'always' => $this->trans('seo_sitemap_change_frequency.always'),
                                'hourly' => $this->trans('seo_sitemap_change_frequency.hourly'),
                                'daily' => $this->trans('seo_sitemap_change_frequency.daily'),
                                'weekly' => $this->trans('seo_sitemap_change_frequency.weekly'),
                                'monthly' => $this->trans('seo_sitemap_change_frequency.monthly'),
                                'yearly' => $this->trans('seo_sitemap_change_frequency.yearly'),
                                'never' => $this->trans('seo_sitemap_change_frequency.never'),
                            ],
                            'clearable' => false,
                            'multiple' => false,
                            'searchable' => false,
                            'taggable' => false,
                            'push_tags' => false,
                            'cast_booleans' => false,
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function jsonLd(): array
    {
        return [
            'display' => $this->trans('seo_section_json_ld.display'),
            'instructions' => $this->trans('seo_section_json_ld.instructions'),
            'collapsible' => true,
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
                            'type' => 'code',
                            'theme' => 'material',
                            'mode' => 'javascript',
                            'mode_selectable' => false,
                            'indent_type' => 'tabs',
                            'indent_size' => 4,
                            'key_map' => 'default',
                            'line_numbers' => true,
                            'line_wrapping' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
