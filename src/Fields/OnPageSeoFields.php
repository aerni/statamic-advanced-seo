<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Models\SocialImageTheme;
use Statamic\Facades\Fieldset;

class OnPageSeoFields extends BaseFields
{
    use HasAssetField;

    public function sections(): array
    {
        return [
            $this->titleAndDescription(),
            $this->socialImages(),
            $this->canonicalUrl(),
            $this->indexing(),
            $this->sitemap(),
            $this->jsonLd(),
        ];
    }

    public function titleAndDescription(): array
    {
        return [
            [
                'handle' => 'seo_section_title_description',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('seo_section_title_description.display'),
                    'instructions' => $this->trans('seo_section_title_description.instructions'),
                ],
            ],
            [
                'handle' => 'seo_title',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_title.display'),
                    'instructions' => $this->trans('seo_title.instructions'),
                    'default' => '@auto',
                    'auto' => 'title',
                    'localizable' => true,
                    'classes' => 'text-fieldtype',
                    'field' => [
                        'type' => 'text',
                        'character_limit' => 60,
                        'antlers' => false,
                    ],
                ],
            ],
            [
                'handle' => 'seo_description',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_description.display'),
                    'instructions' => $this->trans('seo_description.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'textarea-fieldtype',
                    'field' => [
                        'type' => 'textarea',
                        'character_limit' => 160,
                    ],
                ],
            ],
            [
                'handle' => 'seo_site_name_position',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_site_name_position.display'),
                    'instructions' => $this->trans('seo_site_name_position.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'button_group-fieldtype',
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
        ];
    }

    public function socialImages(): array
    {
        return collect([
            $this->socialImagesGenerator(),
            $this->socialImagesGeneratorFields(),
            $this->openGraphImage(),
            $this->twitterImage(),
        ])->flatten(1)->toArray();
    }

    public function socialImagesGenerator(): array
    {
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return [];
        }

        $fields = collect([
            [
                'handle' => 'seo_section_social_images_generator',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('seo_section_social_images_generator.display'),
                    'instructions' => $this->trans('seo_section_social_images_generator.instructions'),
                    'listable' => 'hidden',
                    'if' => 'showSocialImagesGeneratorFields',
                ],
            ],
            [
                'handle' => 'seo_generate_social_images',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_generate_social_images.display'),
                    'instructions' => $this->trans('seo_generate_social_images.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'toggle-fieldtype',
                    'if' => 'showSocialImagesGeneratorFields',
                    'field' => [
                        'type' => 'toggle',
                    ],
                ],
            ],
        ]);

        // Make themes selectable if we've got more than one. If not, use a hidden field with the default theme instead.
        if (SocialImageTheme::all()->count() > 1) {
            $fields->push([
                'handle' => 'seo_social_images_theme',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_social_images_theme.display'),
                    'instructions' => $this->trans('seo_social_images_theme.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'select-fieldtype',
                    'if' => [
                        'showSocialImagesGeneratorFields' => 'custom showSocialImagesGeneratorFields',
                        'seo_generate_social_images.value' => 'true',
                    ],
                    'field' => [
                        'type' => 'select',
                        'options' => SocialImageTheme::fieldtypeOptions(),
                        'default' => SocialImageTheme::fieldtypeDefault(),
                        'clearable' => false,
                        'multiple' => false,
                        'searchable' => false,
                        'taggable' => false,
                        'push_tags' => false,
                        'cast_booleans' => false,
                    ],
                ],
            ]);
        } else {
            $fields->push([
                'handle' => 'seo_social_images_theme',
                'field' => [
                    'type' => 'hidden',
                    'default' => SocialImageTheme::fieldtypeDefault(),
                    'if' => [
                        'showSocialImagesGeneratorFields' => 'custom showSocialImagesGeneratorFields',
                        'seo_generate_social_images.value' => 'true',
                    ],
                ],
            ]);
        }

        $fields->push(
            [
                'handle' => 'seo_generated_og_image',
                'field' => [
                    'type' => 'social_image',
                    'display' => $this->trans('seo_generated_og_image.display'),
                    'image_type' => 'open_graph',
                    'read_only' => true,
                    'listable' => 'hidden',
                    'width' => 50,
                    'if' => [
                        'showSocialImagesGeneratorFields' => 'custom showSocialImagesGeneratorFields',
                        'seo_generate_social_images.value' => 'true',
                    ],
                ],
            ],
            [
                'handle' => 'seo_generated_twitter_image',
                'field' => [
                    'type' => 'social_image',
                    'display' => $this->trans('seo_generated_twitter_image.display'),
                    'image_type' => 'twitter',
                    'read_only' => true,
                    'listable' => 'hidden',
                    'width' => 50,
                    'if' => [
                        'showSocialImagesGeneratorFields' => 'custom showSocialImagesGeneratorFields',
                        'seo_generate_social_images.value' => 'true',
                    ],
                ],
            ],
        );

        return $fields->toArray();
    }

    public function socialImagesGeneratorFields(): array
    {
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return [];
        }

        $fieldset = Fieldset::setDirectory(resource_path('fieldsets'))->find('social_images_generator');

        if (! $fieldset) {
            return [];
        }

        return collect($fieldset->contents()['fields'])->map(function ($field) {
            // Prefix the field handles to avoid naming conflicts.
            $field['handle'] = "seo_social_images_{$field['handle']}";

            // Hide the fields if the toggle is of.
            $field['field']['if'] = [
                'showSocialImagesGeneratorFields' => 'custom showSocialImagesGeneratorFields',
                'seo_generate_social_images.value' => 'true',
            ];

            return $field;
        })->toArray();
    }

    public function openGraphImage(): array
    {
        return [
            [
                'handle' => 'seo_section_og',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('seo_section_og.display'),
                    'instructions' => $this->trans('seo_section_og.instructions'),
                ],
            ],
            [
                'handle' => 'seo_og_image',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_og_image.display'),
                    'instructions' => $this->trans('seo_og_image.instructions', ['size' => SocialImage::sizeString('open_graph')]),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'assets-fieldtype',
                    'if' => [
                        'seo_generate_social_images.value' => 'isnt true',
                    ],
                    'field' => [
                        'type' => 'assets',
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
                'handle' => 'seo_og_title',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_og_title.display'),
                    'instructions' => $this->trans('seo_og_title.instructions'),
                    'default' => '@auto',
                    'auto' => 'seo_title',
                    'localizable' => true,
                    'classes' => 'text-fieldtype',
                    'field' => [
                        'type' => 'text',
                        'character_limit' => 70,
                        'antlers' => false,
                    ],
                ],
            ],
            [
                'handle' => 'seo_og_description',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_og_description.display'),
                    'instructions' => $this->trans('seo_og_description.instructions'),
                    'default' => '@auto',
                    'auto' => 'seo_description',
                    'localizable' => true,
                    'classes' => 'textarea-fieldtype',
                    'field' => [
                        'type' => 'textarea',
                        'character_limit' => 200,
                    ],
                ],
            ],
        ];
    }

    public function twitterImage(): array
    {
        return [
            [
                'handle' => 'seo_section_twitter',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('seo_section_twitter.display'),
                    'instructions' => $this->trans('seo_section_twitter.instructions'),
                ],
            ],
            [
                'handle' => 'seo_twitter_card',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_twitter_card.display'),
                    'instructions' => $this->trans('seo_twitter_card.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'button_group-fieldtype',
                    'field' => [
                        'type' => 'button_group',
                        'options' => [
                            'summary' => $this->trans('seo_twitter_card.summary'),
                            'summary_large_image' => $this->trans('seo_twitter_card.summary_large_image'),
                        ],
                    ],
                ],
            ],
            [
                'handle' => 'seo_twitter_summary_image',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_twitter_summary_image.display'),
                    'instructions' => $this->trans('seo_twitter_summary_image.instructions', ['size' => SocialImage::sizeString('twitter_summary')]),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'assets-fieldtype',
                    'twitter_card' => SocialImage::findModel('twitter_summary')['card'],
                    'if' => [
                        'seo_generate_social_images.value' => 'isnt true',
                        'seo_twitter_card.value' => 'equals summary',
                    ],
                    'field' => [
                        'type' => 'assets',
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
                'handle' => 'seo_twitter_summary_large_image',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_twitter_summary_large_image.display'),
                    'instructions' => $this->trans('seo_twitter_summary_large_image.instructions', ['size' => SocialImage::sizeString('twitter_summary_large_image')]),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'assets-fieldtype',
                    'twitter_card' => SocialImage::findModel('twitter_summary_large_image')['card'],
                    'if' => [
                        'seo_generate_social_images.value' => 'isnt true',
                        'seo_twitter_card.value' => 'equals summary_large_image',
                    ],
                    'field' => [
                        'type' => 'assets',
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
                'handle' => 'seo_twitter_title',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_twitter_title.display'),
                    'instructions' => $this->trans('seo_twitter_title.instructions'),
                    'default' => '@auto',
                    'auto' => 'seo_title',
                    'localizable' => true,
                    'classes' => 'text-fieldtype',
                    'field' => [
                        'type' => 'text',
                        'character_limit' => 70,
                        'antlers' => false,
                    ],
                ],
            ],
            [
                'handle' => 'seo_twitter_description',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_twitter_description.display'),
                    'instructions' => $this->trans('seo_twitter_description.instructions'),
                    'default' => '@auto',
                    'auto' => 'seo_description',
                    'localizable' => true,
                    'classes' => 'textarea-fieldtype',
                    'field' => [
                        'type' => 'textarea',
                        'character_limit' => 200,
                    ],
                ],
            ],
        ];
    }

    public function canonicalUrl(): array
    {
        return [
            [
                'handle' => 'seo_section_canonical_url',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('seo_section_canonical_url.display'),
                    'instructions' => $this->trans('seo_section_canonical_url.instructions'),
                ],
            ],
            [
                'handle' => 'seo_canonical_type',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_canonical_type.display'),
                    'instructions' => $this->trans('seo_canonical_type.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'button_group-fieldtype',
                    'field' => [
                        'type' => 'button_group',
                        'options' => [
                            'current' => $this->trans('seo_canonical_type.current', ['type' => ucfirst(str_singular($this->typePlaceholder()))]),
                            'other' => $this->trans('seo_canonical_type.other'),
                            'custom' => $this->trans('seo_canonical_type.custom'),
                        ],
                    ],
                ],
            ],
            [
                'handle' => 'seo_canonical_entry',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_canonical_entry.display'),
                    'instructions' => $this->trans('seo_canonical_entry.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'relationship-fieldtype',
                    'if' => [
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
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_canonical_custom.display'),
                    'instructions' => $this->trans('seo_canonical_custom.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'text-fieldtype',
                    'if' => [
                        'seo_canonical_type.value' => 'equals custom',
                    ],
                    'field' => [
                        'type' => 'text',
                        'input_type' => 'url',
                        'validate' => [
                            'required_if:seo_canonical_type,custom',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function indexing(): array
    {
        return [
            [
                'handle' => 'seo_section_indexing',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('seo_section_indexing.display'),
                    'instructions' => $this->trans('seo_section_indexing.instructions'),
                ],
            ],
            [
                'handle' => 'seo_noindex',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_noindex.display'),
                    'instructions' => $this->trans('seo_noindex.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'toggle-fieldtype',
                    'width' => 50,
                    'field' => [
                        'type' => 'toggle',
                    ],
                ],
            ],
            [
                'handle' => 'seo_nofollow',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_nofollow.display'),
                    'instructions' => $this->trans('seo_nofollow.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'toggle-fieldtype',
                    'width' => 50,
                    'field' => [
                        'type' => 'toggle',
                    ],
                ],
            ],
        ];
    }

    public function sitemap(): array
    {
        if (! config('advanced-seo.sitemap.enabled', true)) {
            return [];
        }

        return [
            [
                'handle' => 'seo_section_sitemap',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('seo_section_sitemap.display'),
                    'instructions' => $this->trans('seo_section_sitemap.instructions'),
                    'if' => [
                        'showSitemapFields' => 'custom showSitemapFields',
                        'seo_noindex.value' => 'false',
                        'seo_canonical_type.value' => 'equals current',
                    ],
                ],
            ],
            [
                'handle' => 'seo_sitemap_enabled',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_sitemap_enabled.display'),
                    'instructions' => $this->trans('seo_sitemap_enabled.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'toggle-fieldtype',
                    'if' => [
                        'showSitemapFields' => 'custom showSitemapFields',
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
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_sitemap_priority.display'),
                    'instructions' => $this->trans('seo_sitemap_priority.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'select-fieldtype',
                    'width' => 50,
                    'if' => [
                        'showSitemapFields' => 'custom showSitemapFields',
                        'seo_noindex.value' => 'false',
                        'seo_canonical_type.value' => 'equals current',
                        'seo_sitemap_enabled.value' => 'true',
                    ],
                    'field' => [
                        'type' => 'select',
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
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_sitemap_change_frequency.display'),
                    'instructions' => $this->trans('seo_sitemap_change_frequency.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'select-fieldtype',
                    'width' => 50,
                    'if' => [
                        'showSitemapFields' => 'custom showSitemapFields',
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
        ];
    }

    public function jsonLd(): array
    {
        return [
            [
                'handle' => 'seo_section_json_ld',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('seo_section_json_ld.display'),
                    'instructions' => $this->trans('seo_section_json_ld.instructions'),
                ],
            ],
            [
                'handle' => 'seo_json_ld',
                'field' => [
                    'type' => 'seo_source',
                    'display' => $this->trans('seo_json_ld.display'),
                    'instructions' => $this->trans('seo_json_ld.instructions'),
                    'default' => '@default',
                    'localizable' => true,
                    'classes' => 'code-fieldtype',
                    'field' => [
                        'type' => 'code',
                        'theme' => 'material',
                        'mode' => 'javascript',
                        'indent_type' => 'tabs',
                        'indent_size' => 4,
                        'key_map' => 'default',
                        'line_numbers' => true,
                        'line_wrapping' => true,
                    ],
                ],
            ],
        ];
    }
}
