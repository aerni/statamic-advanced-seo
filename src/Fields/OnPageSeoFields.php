<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Actions\GetFallbackTitle;
use Statamic\Facades\Site;
use Statamic\Taxonomies\Term;
use Statamic\Facades\Fieldset;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Entry;
use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Actions\GetFallbackValue;

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
                    'display' => 'Title & Description',
                    'instructions' => $this->trans('seo_section_title_description', 'instructions'),
                ],
            ],
            [
                'handle' => 'seo_title',
                'field' => [
                    'display' => 'Meta Title',
                    'instructions' => $this->trans('seo_title', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'text',
                        'default' => $this->getValueFromCascade('seo_title') ?? GetFallbackTitle::handle($this->data),
                        'character_limit' => 60,
                        'antlers' => false,
                        'validate' => [
                            'max:60',
                        ],
                    ],
                ],
            ],
            [
                'handle' => 'seo_description',
                'field' => [
                    'display' => 'Meta Description',
                    'instructions' => $this->trans('seo_description', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'textarea',
                        'default' => $this->getValueFromCascade('seo_description'),
                        'character_limit' => 160,
                        'validate' => [
                            'max:160',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function socialImages(): array
    {
        $fields = collect([
            $this->openGraphImage(),
            $this->twitterImage(),
        ]);

        if ($this->displaySocialImagesGenerator()) {
            $fields->prepend($this->socialImagesGeneratorFields());
            $fields->prepend($this->socialImagesGenerator());
        }

        return $fields->flatten(1)->toArray();
    }

    public function socialImagesGenerator(): array
    {
        return [
            [
                'handle' => 'seo_section_social_images_generator',
                'field' => [
                    'type' => 'section',
                    'display' => 'Social Images Generator',
                    'instructions' => $this->trans('seo_section_social_images_generator', 'instructions'),
                    'listable' => 'hidden',
                ],
            ],
            [
                'handle' => 'seo_generate_social_images',
                'field' => [
                    'display' => 'Generate Social Images',
                    'instructions' => $this->trans('seo_generate_social_images', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'toggle',
                        'default' => (bool) $this->getValueFromCascade('seo_generate_social_images'),
                    ],
                ],
            ],
            [
                'handle' => 'seo_og_image_preview',
                'field' => [
                    'type' => 'social_images_preview',
                    'image_type' => 'og',
                    'display' => 'Open Graph',
                    'instructions' => $this->trans('seo_og_image_preview', 'instructions'),
                    'read_only' => true,
                    'listable' => 'hidden',
                    'width' => 50,
                    'if' => [
                        'seo_generate_social_images.value' => 'equals true',
                    ],
                ],
            ],
            [
                'handle' => 'seo_twitter_image_preview',
                'field' => [
                    'type' => 'social_images_preview',
                    'image_type' => 'twitter',
                    'display' => 'Twitter',
                    'instructions' => $this->trans('seo_twitter_image_preview', 'instructions'),
                    'read_only' => true,
                    'listable' => 'hidden',
                    'width' => 50,
                    'if' => [
                        'seo_generate_social_images.value' => 'equals true',
                    ],
                ],
            ],
        ];
    }

    public function socialImagesGeneratorFields(): array
    {
        $fieldset = Fieldset::setDirectory(resource_path('fieldsets'))->find('social_images_generator');

        if (! $fieldset) {
            return [];
        }

        return collect($fieldset->contents()['fields'])->map(function ($field) {
            // Prefix the field handles to avoid naming conflicts.
            $field['handle'] = "seo_social_images_{$field['handle']}";

            // Hide the fields if the toggle is of.
            $field['field']['if'] = [
                'seo_generate_social_images.value' => 'equals true',
            ];

            // Add the placeholder values from the content defaults.
            $field['field']['placeholder'] = $this->getValueFromCascade($field['handle']);

            return $field;
        })->toArray();
    }

    public function openGraphImage(): array
    {
        $fields = [
            [
                'handle' => 'seo_section_og',
                'field' => [
                    'type' => 'section',
                    'display' => 'Open Graph',
                    'instructions' => $this->trans('seo_section_og', 'instructions'),
                ],
            ],
            [
                'handle' => 'seo_og_title',
                'field' => [
                    'display' => 'Open Graph Title',
                    'instructions' => $this->trans('seo_og_title', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'text',
                        'default' => $this->getValueFromCascade('seo_og_title') ?? GetFallbackTitle::handle($this->data),
                        'character_limit' => 70,
                        'antlers' => false,
                        'validate' => [
                            'max:70',
                        ],
                    ],
                ],
            ],
            [
                'handle' => 'seo_og_description',
                'field' => [
                    'display' => 'Open Graph Description',
                    'instructions' => $this->trans('seo_og_description', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'textarea',
                        'default' => $this->getValueFromCascade('seo_og_description'),
                        'character_limit' => 200,
                        'validate' => [
                            'max:200',
                        ],
                    ],
                ],
            ],
            [
                'handle' => 'seo_og_image',
                'field' => [
                    'display' => 'Open Graph Image',
                    'instructions' => $this->trans('seo_og_image', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'assets',
                        'default' => $this->getValueFromCascade('seo_og_image'),
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
        ];

        if ($this->displaySocialImagesGenerator()) {
            $fields[3]['field']['if']['seo_generate_social_images.value'] = 'equals false';
        }

        return $fields;
    }

    public function twitterImage(): array
    {
        $fields = [
            [
                'handle' => 'seo_section_twitter',
                'field' => [
                    'type' => 'section',
                    'display' => 'Twitter',
                    'instructions' => $this->trans('seo_section_twitter', 'instructions'),
                ],
            ],
            [
                'handle' => 'seo_twitter_card',
                'field' => [
                    'display' => 'Twitter Card',
                    'instructions' => $this->trans('seo_twitter_card', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'button_group',
                        'default' => $this->getValueFromCascade('seo_twitter_card'),
                        'options' => [
                            'summary' => 'Regular',
                            'summary_large_image' => 'Large Image',
                        ],
                    ],
                ],
            ],
            [
                'handle' => 'seo_twitter_title',
                'field' => [
                    'display' => 'Twitter Title',
                    'instructions' => $this->trans('seo_twitter_title', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'text',
                        'default' => $this->getValueFromCascade('seo_twitter_title') ?? GetFallbackTitle::handle($this->data),
                        'character_limit' => 70,
                        'antlers' => false,
                        'validate' => [
                            'max:70',
                        ],
                    ],
                ],
            ],
            [
                'handle' => 'seo_twitter_description',
                'field' => [
                    'display' => 'Twitter Description',
                    'instructions' => $this->trans('seo_twitter_description', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'textarea',
                        'default' => $this->getValueFromCascade('seo_twitter_description'),
                        'character_limit' => 200,
                        'validate' => [
                            'max:200',
                        ],
                    ],
                ],
            ],
            [
                'handle' => 'seo_twitter_image',
                'field' => [
                    'display' => 'Twitter Image',
                    'instructions' => $this->trans('seo_twitter_image', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'assets',
                        'default' => $this->getValueFromCascade('seo_twitter_image'),
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
        ];

        if ($this->displaySocialImagesGenerator()) {
            $fields[4]['field']['if']['seo_generate_social_images.value'] = 'equals false';
        }

        return $fields;
    }

    public function canonicalUrl(): array
    {
        return [
            [
                'handle' => 'seo_section_canonical_url',
                'field' => [
                    'type' => 'section',
                    'display' => 'Canonical URL',
                    'instructions' => $this->trans('seo_section_canonical_url', 'instructions'),
                ],
            ],
            [
                'handle' => 'seo_canonical_type',
                'field' => [
                    'display' => 'Canonical URL',
                    'instructions' => $this->trans('seo_canonical_type', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'button_group',
                        'default' => $this->getValueFromCascade('seo_canonical_type'),
                        'options' => [
                            'current' => 'Current ' . ucfirst(str_singular($this->typePlaceholder())),
                            'other' => 'Other Entry',
                            'custom' => 'Custom URL',
                        ],
                    ],
                ],
            ],
            [
                'handle' => 'seo_canonical_entry',
                'field' => [
                    'display' => 'Entry',
                    'instructions' => $this->trans('seo_canonical_entry', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'if' => [
                        'seo_canonical_type.value' => 'equals other',
                    ],
                    'field' => [
                        'type' => 'entries',
                        'default' => $this->getValueFromCascade('seo_canonical_entry'),
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
                    'display' => 'URL',
                    'instructions' => $this->trans('seo_canonical_custom', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'if' => [
                        'seo_canonical_type.value' => 'equals custom',
                    ],
                    'field' => [
                        'type' => 'text',
                        'default' => $this->getValueFromCascade('seo_canonical_custom'),
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
                    'display' => 'Indexing',
                    'instructions' => $this->trans('seo_section_indexing', 'instructions'),
                ],
            ],
            [
                'handle' => 'seo_noindex',
                'field' => [
                    'display' => 'Noindex',
                    'instructions' => $this->trans('seo_noindex', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'width' => 50,
                    'field' => [
                        'type' => 'toggle',
                        'default' => (bool) $this->getValueFromCascade('seo_noindex'),
                    ],
                ],
            ],
            [
                'handle' => 'seo_nofollow',
                'field' => [
                    'display' => 'Nofollow',
                    'instructions' => $this->trans('seo_nofollow', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'width' => 50,
                    'field' => [
                        'type' => 'toggle',
                        'default' => (bool) $this->getValueFromCascade('seo_nofollow'),
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
                    'display' => 'Sitemap',
                    'instructions' => $this->trans('seo_section_sitemap', 'instructions'),
                ],
            ],
            [
                'handle' => 'seo_sitemap_priority',
                'field' => [
                    'display' => 'Priority',
                    'instructions' => $this->trans('seo_sitemap_priority', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'width' => 50,
                    'field' => [
                        'type' => 'select',
                        'default' => $this->getValueFromCascade('seo_sitemap_priority'),
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
                    'display' => 'Change Frequency',
                    'instructions' => $this->trans('seo_sitemap_change_frequency', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'width' => 50,
                    'field' => [
                        'type' => 'select',
                        'default' => $this->getValueFromCascade('seo_sitemap_change_frequency'),
                        'options' => [
                            'always' => 'Always',
                            'hourly' => 'Hourly',
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                            'yearly' => 'Yearly',
                            'never' => 'Never',
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
                    'display' => 'JSON-ld Schema',
                    'instructions' => $this->trans('seo_section_json_ld', 'instructions'),
                ],
            ],
            [
                'handle' => 'seo_json_ld',
                'field' => [
                    'display' => 'JSON-LD Schema',
                    'instructions' => $this->trans('seo_json_ld', 'instructions'),
                    'type' => 'seo_source',
                    'default' => '@default',
                    'localizable' => true,
                    'field' => [
                        'type' => 'code',
                        'default' => $this->getValueFromCascade('seo_json_ld'),
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

    public function displaySocialImagesGenerator(): bool
    {
        // Don't show the generator section if the generator is disabled.
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return false;
        }

        // Terms are not yet supported.
        if ($this->data instanceof Term) {
            return false;
        }

        // Terms are not yet supported.
        // This is the check for the "Create Term" view. Because the data won't yet be an instance of Term.
        if ($this->data->get('type') === 'taxonomies') {
            return false;
        }

        $enabledCollections = Seo::find('site', 'social_media')
            ?->in(Site::selected()->handle())
            ?->value('social_images_generator_collections') ?? [];

        // Don't show the generator section if the entry's collection is not configured.
        if ($this->data instanceof Entry && ! in_array($this->data->collectionHandle(), $enabledCollections)) {
            return false;
        }

        // Don't show the generator section if the collection is not configured.
        // This is the check for the "Create Entry" view. Because the data won't yet be an instance of Entry.
        if ($this->data->get('type') === 'collections' && ! in_array($this->data->get('handle'), $enabledCollections)) {
            return false;
        }

        return true;
    }
}
