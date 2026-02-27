<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Context\Context;
use Aerni\AdvancedSeo\Facades\SocialImageTheme;
use Aerni\AdvancedSeo\Features\Sitemap;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;
use Illuminate\Support\Str;

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
            'instructions' => $this->trans('seo_section_search_appearance.default_instructions'),
            'collapsible' => true,
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
            'collapsible' => true,
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
                    'field' => $this->getAssetFieldConfig([
                        'display' => $this->trans('seo_og_image.display'),
                        'instructions' => $this->trans('seo_og_image.default_instructions'),
                        'validate' => [
                            'image',
                            'mimes:jpg,png',
                        ],
                    ]),
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
                        'visibility' => $this->lazy(fn (?Context $context) => SocialImageTheme::allowedFor($context->seoSet())->count() === 1 ? 'hidden' : 'visible', 'visible'),
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
            'collapsible' => true,
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
                    'handle' => 'seo_nofollow',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('seo_nofollow.display'),
                        'instructions' => $this->trans('seo_nofollow.default_instructions'),
                        'default' => false,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                    ],
                ],
            ],
        ];
    }

    protected function canonicalUrl(): array
    {
        return [
            'display' => $this->trans('seo_section_canonical_url.display'),
            'instructions' => $this->trans('seo_section_canonical_url.default_instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'seo_canonical_type',
                    'field' => [
                        'type' => 'button_group',
                        'display' => $this->trans('seo_canonical_type.display'),
                        'instructions' => $this->trans('seo_canonical_type.default_instructions'),
                        'options' => [
                            'current' => $this->trans('seo_canonical_type.current', ['type' => ucfirst(Str::singular($this->contentTypeLabel()))]),
                            'other' => $this->trans('seo_canonical_type.other'),
                            'custom' => $this->trans('seo_canonical_type.custom'),
                        ],
                        'default' => 'current',
                        'icon' => 'button_group',
                        'listable' => 'hidden',
                        'localizable' => true,
                    ],
                ],
                [
                    'handle' => 'seo_canonical_entry',
                    'field' => [
                        'type' => 'entries',
                        'display' => $this->trans('seo_canonical_entry.display'),
                        'instructions' => $this->trans('seo_canonical_entry.default_instructions'),
                        'component' => 'relationship',
                        'mode' => 'stack',
                        'max_items' => 1,
                        'localizable' => true,
                        'listable' => 'hidden',
                        'width' => 50,
                        'validate' => [
                            'required_if:seo_canonical_type,other',
                        ],
                    ],
                ],
                [
                    'handle' => 'seo_canonical_custom',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('seo_canonical_custom.display'),
                        'instructions' => $this->trans('seo_canonical_custom.default_instructions'),
                        'input_type' => 'url',
                        'icon' => 'text',
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                        'validate' => [
                            'required_if:seo_canonical_type,custom',
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
            'instructions' => $this->trans('seo_section_sitemap.default_instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'seo_sitemap_enabled',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('seo_sitemap_enabled.display'),
                        'instructions' => $this->trans('seo_sitemap_enabled.default_instructions'),
                        'default' => true,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'feature' => Sitemap::class,
                    ],
                ],
                [
                    'handle' => 'seo_sitemap_priority',
                    'field' => [
                        'type' => 'select',
                        'display' => $this->trans('seo_sitemap_priority.display'),
                        'instructions' => $this->trans('seo_sitemap_priority.default_instructions'),
                        'options' => [
                            '0.0' => '0.0',
                            '0.1' => '0.1',
                            '0.2' => '0.2',
                            '0.3' => '0.3',
                            '0.4' => '0.4',
                            '0.5' => '0.5',
                            '0.6' => '0.6',
                            '0.7' => '0.7',
                            '0.8' => '0.8',
                            '0.9' => '0.9',
                            '1.0' => '1.0',
                        ],
                        'default' => '0.5',
                        'clearable' => false,
                        'multiple' => false,
                        'searchable' => false,
                        'taggable' => false,
                        'push_tags' => false,
                        'cast_booleans' => false,
                        'width' => 50,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'feature' => Sitemap::class,
                    ],
                ],
                [
                    'handle' => 'seo_sitemap_change_frequency',
                    'field' => [
                        'type' => 'select',
                        'display' => $this->trans('seo_sitemap_change_frequency.display'),
                        'instructions' => $this->trans('seo_sitemap_change_frequency.default_instructions'),
                        'options' => [
                            'always' => $this->trans('seo_sitemap_change_frequency.always'),
                            'hourly' => $this->trans('seo_sitemap_change_frequency.hourly'),
                            'daily' => $this->trans('seo_sitemap_change_frequency.daily'),
                            'weekly' => $this->trans('seo_sitemap_change_frequency.weekly'),
                            'monthly' => $this->trans('seo_sitemap_change_frequency.monthly'),
                            'yearly' => $this->trans('seo_sitemap_change_frequency.yearly'),
                            'never' => $this->trans('seo_sitemap_change_frequency.never'),
                        ],
                        'default' => 'daily',
                        'clearable' => false,
                        'multiple' => false,
                        'searchable' => false,
                        'taggable' => false,
                        'push_tags' => false,
                        'cast_booleans' => false,
                        'width' => 50,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'feature' => Sitemap::class,
                    ],
                ],
            ],
        ];
    }

    protected function jsonLd(): array
    {
        return [
            'display' => $this->trans('seo_section_structured_data.display'),
            'instructions' => $this->trans('seo_section_structured_data.default_instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'seo_json_ld',
                    'field' => [
                        'type' => 'code',
                        'display' => $this->trans('seo_json_ld.display'),
                        'instructions' => $this->trans('seo_json_ld.default_instructions'),
                        'theme' => 'material',
                        'mode' => 'javascript',
                        'mode_selectable' => false,
                        'indent_type' => 'tabs',
                        'indent_size' => 4,
                        'key_map' => 'default',
                        'line_numbers' => true,
                        'line_wrapping' => true,
                        'listable' => 'hidden',
                        'localizable' => true,
                    ],
                ],
            ],
        ];
    }
}
