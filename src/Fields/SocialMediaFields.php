<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Traits\HasAssetField;

class SocialMediaFields extends BaseFields
{
    use HasAssetField;

    public function sections(): array
    {
        return [
            $this->socialImagesGenerator(),
            $this->openGraphImage(),
            $this->twitterImage(),
        ];
    }

    protected function socialImagesGenerator(): array
    {
        if (! config('advanced-seo.social_images.generator.enabled', false)) {
            return [];
        }

        return [
            [
                'handle' => 'section_social_images_generator',
                'field' => [
                    'type' => 'section',
                    'display' => 'Generate Social Images',
                    'instructions' => 'Automatically generate your social images.',
                    'listable' => 'hidden',
                ],
            ],
            [
                'handle' => 'social_images_generator_collections',
                'field' => [
                    'type' => 'collections',
                    'icon' => 'collections',
                    'mode' => 'select',
                    'display' => 'Collections',
                    'instructions' => 'Enable the generator for the selected collections.',
                    'listable' => 'hidden',
                    'width' => 50,
                ],
            ],
            [
                'handle' => 'social_images_generator_taxonomies',
                'field' => [
                    'type' => 'taxonomies',
                    'icon' => 'taxonomy',
                    'mode' => 'select',
                    'display' => 'Taxonomies',
                    'instructions' => 'Enable the generator for the selected taxonomies.',
                    'listable' => 'hidden',
                    'width' => 50,
                ],
            ],
        ];
    }

    protected function openGraphImage(): array
    {
        return [
            [
                'handle' => 'section_og',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Configure the default Open Graph settings.',
                    'display' => 'Open Graph',
                ],
            ],
            [
                'handle' => 'og_image',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Open Graph Image',
                    'instructions' => 'Add a default Open Graph Image. The recommended size is `1200x630px`.',
                    'validate' => [
                        'image',
                        'mimes:jpg,png',
                    ],
                ]),
            ],
        ];
    }

    protected function twitterImage(): array
    {
        return [
            [
                'handle' => 'section_twitter',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Configure your default Twitter settings.',
                    'display' => 'Twitter',
                ],
            ],
            [
                'handle' => 'twitter_handle',
                'field' => [
                    'listable' => 'hidden',
                    'display' => 'Twitter Username',
                    'input_type' => 'text',
                    'type' => 'text',
                    'instructions' => 'Add your Twitter username.',
                    'prepend' => '@',
                    'antlers' => false,
                    'width' => 50,
                ],
            ],
            [
                'handle' => 'twitter_image',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Twitter Image',
                    'instructions' => 'Add a default Twitter Image with an aspect ratio of `2:1` and minimum size of `300x157px`.',
                    'validate' => [
                        'image',
                        'mimes:jpg,png',
                        'dimensions:min_width=300,min_height=157',
                    ],
                ]),
            ],
        ];
    }
}
