<?php

namespace Aerni\AdvancedSeo\Fields;

use Statamic\Facades\Site;
use Statamic\Taxonomies\Term;
use Statamic\Facades\Fieldset;
use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Entry;
use Aerni\AdvancedSeo\Concerns\HasAssetField;

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
                    'type' => 'text',
                    'display' => 'Meta Title',
                    'instructions' => $this->trans('seo_title', 'instructions'),
                    'placeholder' => $this->getValueFromCascade('seo_title'),
                    'input_type' => 'text',
                    'localizable' => true,
                    'listable' => 'hidden',
                    'character_limit' => 60,
                    'antlers' => false,
                    'validate' => [
                        'max:60',
                    ],
                ],
            ],
            [
                'handle' => 'seo_description',
                'field' => [
                    'type' => 'textarea',
                    'display' => 'Meta Description',
                    'instructions' => $this->trans('seo_description', 'instructions'),
                    'placeholder' => $this->getValueFromCascade('seo_description'),
                    'localizable' => true,
                    'listable' => 'hidden',
                    'character_limit' => 160,
                    'validate' => [
                        'max:160',
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
                    'type' => 'toggle',
                    'icon' => 'toggle',
                    'display' => 'Generate Social Images',
                    'instructions' => $this->trans('seo_generate_social_images', 'instructions'),
                    'default' => (bool) $this->getValueFromCascade('seo_generate_social_images'),
                    'localizable' => true,
                    'listable' => 'hidden',
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
                        'seo_generate_social_images' => 'equals true',
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
                        'seo_generate_social_images' => 'equals true',
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
                'seo_generate_social_images' => 'equals true',
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
                    'type' => 'text',
                    'display' => 'Open Graph Title',
                    'instructions' => $this->trans('seo_og_title', 'instructions'),
                    'placeholder' => $this->getValueFromCascade('seo_og_title'),
                    'input_type' => 'text',
                    'localizable' => true,
                    'listable' => 'hidden',
                    'character_limit' => 70,
                    'antlers' => false,
                    'validate' => [
                        'max:70',
                    ],
                ],
            ],
            [
                'handle' => 'seo_og_description',
                'field' => [
                    'type' => 'textarea',
                    'display' => 'Open Graph Description',
                    'instructions' => $this->trans('seo_og_description', 'instructions'),
                    'placeholder' => $this->getValueFromCascade('seo_og_description'),
                    'localizable' => true,
                    'listable' => 'hidden',
                    'character_limit' => '200',
                    'width' => 100,
                    'validate' => [
                        'max:200',
                    ],
                ],
            ],
            [
                'handle' => 'seo_og_image',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Open Graph Image',
                    'instructions' => $this->trans('seo_og_image', 'instructions'),
                    'default' => $this->getValueFromCascade('seo_og_image'),
                    'validate' => [
                        'image',
                        'mimes:jpg,png',
                    ],
                ]),
            ],
        ];

        if ($this->displaySocialImagesGenerator()) {
            $fields[3]['field']['if']['seo_generate_social_images'] = 'equals false';
            $fields[3]['field']['default'] = null;
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
                    'type' => 'button_group',
                    'icon' => 'button_group',
                    'display' => 'Twitter Card',
                    'instructions' => $this->trans('seo_twitter_card', 'instructions'),
                    'options' => [
                        'summary' => 'Regular',
                        'summary_large_image' => 'Large Image',
                    ],
                    'default' => $this->getValueFromCascade('seo_twitter_card') ?? 'summary',
                    'listable' => 'hidden',
                    'localizable' => true,
                ],
            ],
            [
                'handle' => 'seo_twitter_title',
                'field' => [
                    'type' => 'text',
                    'display' => 'Twitter Title',
                    'instructions' => $this->trans('seo_twitter_title', 'instructions'),
                    'placeholder' => $this->getValueFromCascade('seo_twitter_title'),
                    'input_type' => 'text',
                    'localizable' => true,
                    'listable' => 'hidden',
                    'character_limit' => 70,
                    'antlers' => false,
                    'validate' => [
                        'max:70',
                    ],
                ],
            ],
            [
                'handle' => 'seo_twitter_description',
                'field' => [
                    'type' => 'textarea',
                    'display' => 'Twitter Description',
                    'instructions' => $this->trans('seo_twitter_description', 'instructions'),
                    'placeholder' => $this->getValueFromCascade('seo_twitter_description'),
                    'localizable' => true,
                    'listable' => 'hidden',
                    'character_limit' => '200',
                    'width' => 100,
                    'validate' => [
                        'max:200',
                    ],
                ],
            ],
            [
                'handle' => 'seo_twitter_image',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Twitter Image',
                    'instructions' => $this->trans('seo_twitter_image', 'instructions'),
                    'default' => $this->getValueFromCascade('seo_twitter_image'),
                    'validate' => [
                        'image',
                        'mimes:jpg,png',
                    ],
                ]),
            ],
        ];

        if ($this->displaySocialImagesGenerator()) {
            $fields[4]['field']['if']['seo_generate_social_images'] = 'equals false';
            $fields[4]['field']['default'] = null;
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
                    'type' => 'button_group',
                    'icon' => 'button_group',
                    'display' => 'Canonical URL',
                    'instructions' => $this->trans('seo_canonical_type', 'instructions'),
                    'options' => [
                        'current' => 'Current ' . ucfirst(str_singular($this->typePlaceholder())),
                        'other' => 'Other Entry',
                        'custom' => 'Custom URL',
                    ],
                    'default' => $this->getValueFromCascade('seo_canonical_type') ?? 'current',
                    'listable' => 'hidden',
                    'localizable' => true,
                ],
            ],
            [
                'handle' => 'seo_canonical_entry',
                'field' => [
                    'type' => 'entries',
                    'display' => 'Entry',
                    'instructions' => $this->trans('seo_canonical_entry', 'instructions'),
                    'default' => $this->getValueFromCascade('seo_canonical_entry'),
                    'component' => 'relationship',
                    'mode' => 'stack',
                    'max_items' => 1,
                    'localizable' => true,
                    'listable' => 'hidden',
                    'validate' => [
                        'required_if:seo_canonical_type,other',
                    ],
                    'if' => [
                        'seo_canonical_type' => 'equals other',
                    ],
                ],
            ],
            [
                'handle' => 'seo_canonical_custom',
                'field' => [
                    'type' => 'text',
                    'display' => 'URL',
                    'instructions' => $this->trans('seo_canonical_custom', 'instructions'),
                    'placeholder' => $this->getValueFromCascade('seo_canonical_custom'),
                    'input_type' => 'url',
                    'icon' => 'text',
                    'listable' => 'hidden',
                    'localizable' => true,
                    'validate' => [
                        'required_if:seo_canonical_type,custom',
                    ],
                    'if' => [
                        'seo_canonical_type' => 'equals custom',
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
                    'type' => 'toggle',
                    'display' => 'Noindex',
                    'instructions' => $this->trans('seo_noindex', 'instructions'),
                    'default' => (bool) $this->getValueFromCascade('seo_noindex'),
                    'listable' => 'hidden',
                    'localizable' => true,
                    'width' => 50,
                ],
            ],
            [
                'handle' => 'seo_nofollow',
                'field' => [
                    'type' => 'toggle',
                    'display' => 'Nofollow',
                    'instructions' => $this->trans('seo_nofollow', 'instructions'),
                    'default' => (bool) $this->getValueFromCascade('seo_nofollow'),
                    'listable' => 'hidden',
                    'localizable' => true,
                    'width' => 50,
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
                    'type' => 'select',
                    'display' => 'Priority',
                    'instructions' => $this->trans('seo_sitemap_priority', 'instructions'),
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
                    'default' => $this->getValueFromCascade('seo_sitemap_priority') ?? '0.5',
                    'clearable' => false,
                    'multiple' => false,
                    'searchable' => false,
                    'taggable' => false,
                    'push_tags' => false,
                    'cast_booleans' => false,
                    'width' => 50,
                    'listable' => 'hidden',
                    'localizable' => true,
                ],
            ],
            [
                'handle' => 'seo_sitemap_change_frequency',
                'field' => [
                    'type' => 'select',
                    'display' => 'Change Frequency',
                    'instructions' => $this->trans('seo_sitemap_change_frequency', 'instructions'),
                    'options' => [
                        'always' => 'Always',
                        'hourly' => 'Hourly',
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                        'never' => 'Never',
                    ],
                    'default' => $this->getValueFromCascade('seo_sitemap_change_frequency') ?? 'daily',
                    'clearable' => false,
                    'multiple' => false,
                    'searchable' => false,
                    'taggable' => false,
                    'push_tags' => false,
                    'cast_booleans' => false,
                    'width' => 50,
                    'listable' => 'hidden',
                    'localizable' => true,
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
                    'type' => 'code',
                    'icon' => 'code',
                    'display' => 'JSON-LD Schema',
                    'instructions' => $this->trans('seo_json_ld', 'instructions'),
                    'default' => $this->getValueFromCascade('seo_json_ld'),
                    'theme' => 'material',
                    'mode' => 'javascript',
                    'indent_type' => 'tabs',
                    'indent_size' => 4,
                    'key_map' => 'default',
                    'line_numbers' => true,
                    'line_wrapping' => true,
                    'listable' => 'hidden',
                    'localizable' => true,
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
