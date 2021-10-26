<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Traits\HasAssetField;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Fieldset;
use Statamic\Facades\Site;

class OnPageSeoFields extends BaseFields
{
    use HasAssetField;

    public function sections(): array
    {
        return [
            $this->metaTags(),
            $this->socialImages(),
            $this->canonicalUrl(),
            $this->indexing(),
            $this->sitemap(),
            $this->jsonLd(),
        ];
    }

    public function metaTags(): array
    {
        return [
            [
                'handle' => 'seo_section_meta_tags',
                'field' => [
                    'type' => 'section',
                    'display' => 'Meta Tags',
                    'instructions' => 'Configure the basic Meta Tags of this entry.',
                ],
            ],
            [
                'handle' => 'seo_title',
                'field' => [
                    'type' => 'text',
                    'display' => 'Meta Title',
                    'instructions' => 'Set the Meta Title of this entry. Defaults to the entry\'s `Title`.',
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
                    'instructions' => 'Set the Meta Description of this entry.',
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
                    'display' => 'Generate Social Images',
                    'instructions' => 'Automatically generate your social images.',
                    'listable' => 'hidden',
                ],
            ],
            [
                'handle' => 'seo_generate_social_images',
                'field' => [
                    'type' => 'toggle',
                    'icon' => 'toggle',
                    'display' => 'Generate Social Images',
                    'instructions' => 'Activate to automatically generate the Open Graph and Twitter Images of this entry.',
                    'listable' => 'hidden',
                    'default' => true,
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
                    'display' => 'Open Graph',
                    'instructions' => 'Configure the Open Graph settings of this entry.',
                ],
            ],
            [
                'handle' => 'seo_og_title',
                'field' => [
                    'type' => 'text',
                    'display' => 'Open Graph Title',
                    'instructions' => 'Set the Open Graph Title of this entry. Defaults to the entry\'s `Meta Title` or `Title`.',
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
                    'instructions' => 'Set the Open Graph Description of this entry. Defaults to the entry\'s `Meta Description`.',
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
                    'instructions' => 'Add an Open Graph Image for this entry. The recommended size is `1200x630px`.',
                    'validate' => [
                        'image',
                        'mimes:jpg,png',
                    ],
                ]),
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
                    'display' => 'Twitter',
                    'instructions' => 'Configure the Twitter settings of this entry.',
                ],
            ],
            [
                'handle' => 'seo_twitter_title',
                'field' => [
                    'type' => 'text',
                    'display' => 'Twitter Title',
                    'instructions' => 'Set the Twitter Title of this entry. Defaults to the entry\'s `Meta Title` or `Title`.',
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
                    'instructions' => 'Set the Twitter Description of this entry. Defaults to the entry\'s `Meta Description`.',
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
                    'instructions' => 'Add a Twitter Image for this entry with an aspect ratio of `2:1` and minimum size of `300x157px`.',
                    'validate' => [
                        'image',
                        'mimes:jpg,png',
                        'dimensions:min_width=300,min_height=157',
                    ],
                ]),
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
                    'display' => 'Canonical URL',
                    'instructions' => 'Configure the canonical URL settings for this entry.',
                ],
            ],
            [
                'handle' => 'seo_canonical_type',
                'field' => [
                    'type' => 'button_group',
                    'icon' => 'button_group',
                    'display' => 'Canonical URL',
                    'instructions' => 'Where should the canonical URL for this entry point to.',
                    'options' => [
                        'current' => 'Current Entry',
                        'other' => 'Other Entry',
                        'custom' => 'Custom URL',
                    ],
                    'default' => 'current',
                    'listable' => 'hidden',
                    'localizable' => true,
                ],
            ],
            [
                'handle' => 'seo_canonical_entry',
                'field' => [
                    'type' => 'entries',
                    'display' => 'Entry',
                    'instructions' => 'If this is an entry with duplicate content, link to the entry with the original content.',
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
                    'instructions' => 'A fully qualified URL starting with `https://`. Use this if the original content is on a different domain.',
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
                    'instructions' => 'Configure the indexing settings for this entry.',
                ],
            ],
            [
                'handle' => 'seo_noindex',
                'field' => [
                    'type' => 'toggle',
                    'display' => 'Noindex',
                    'instructions' => 'Prevent this entry from being indexed by search engines.',
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
                    'instructions' => 'Prevent site crawlers from following links in this entry.',
                    'listable' => 'hidden',
                    'localizable' => true,
                    'width' => 50,
                ],
            ],
        ];
    }

    public function sitemap(): array
    {
        return [
            [
                'handle' => 'seo_section_sitemap',
                'field' => [
                    'type' => 'section',
                    'display' => 'Sitemap',
                    'instructions' => 'Configure the sitemap settings for this entry.',
                ],
            ],
            [
                'handle' => 'seo_sitemap_priority',
                'field' => [
                    'type' => 'select',
                    'display' => 'Priority',
                    'instructions' => 'Choose the priorty of this entry in the sitemap. `1.0` is the most important.',
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
                    'width' => 50,
                    'default' => '0.5',
                    'listable' => 'hidden',
                    'localizable' => true,
                ],
            ],
            [
                'handle' => 'seo_sitemap_change_frequency',
                'field' => [
                    'type' => 'select',
                    'display' => 'Change Frequency',
                    'instructions' => 'Choose the frequency in which search engines should crawl this entry.',
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
                    'width' => 50,
                    'default' => 'weekly',
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
                    'instructions' => 'Configure custom [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) for this entry.',
                ],
            ],
            [
                'handle' => 'seo_json_ld',
                'field' => [
                    'type' => 'code',
                    'icon' => 'code',
                    'display' => 'JSON-LD Schema',
                    'instructions' => 'Add custom [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) for this entry. This will be wrapped in the appropriate script tag.',
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

        $enabledCollections = Seo::find('site', 'general')
            ->in(Site::selected()->handle())
            ->value('social_images_generator_collections') ?? [];

        // Don't show the generator section if the entry's collection is not configured.
        if ($this->data instanceof Entry && ! in_array($this->data->collection()->handle(), $enabledCollections)) {
            return false;
        }

        // Don't show the generator section if the entry's collection is not configured.
        if (is_array($this->data) && array_key_exists('collection', $this->data) && ! in_array($this->data['collection'], $enabledCollections)) {
            return false;
        }

        // TODO: Make this work with Taxonomies.

        return true;
    }
}
