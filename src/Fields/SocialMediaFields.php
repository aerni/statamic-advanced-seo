<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Facades\SocialImage;

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
                    'display' => 'Social Images Generator',
                    'instructions' => 'Configurate the settings of the social images generator.',
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
                    // 'width' => 50,
                ],
            ],
            // [
            //     'handle' => 'social_images_generator_taxonomies',
            //     'field' => [
            //         'type' => 'taxonomies',
            //         'icon' => 'taxonomy',
            //         'mode' => 'select',
            //         'display' => 'Taxonomies',
            //         'instructions' => 'Enable the generator for the selected taxonomies.',
            //         'listable' => 'hidden',
            //         'width' => 50,
            //     ],
            // ],
        ];
    }

    protected function openGraphImage(): array
    {
        return [
            [
                'handle' => 'section_og',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Configure the site-wide Open Graph settings.',
                    'display' => 'Open Graph',
                ],
            ],
            [
                'handle' => 'og_image',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Open Graph Image',
                    'instructions' => 'This image will be used as a fallback if none was set on the content. It will be cropped to ' . SocialImage::sizeString('og') . '.',
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
                    'instructions' => 'Configure the site-wide Twitter settings.',
                    'display' => 'Twitter',
                ],
            ],
            [
                'handle' => 'twitter_summary_image',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Twitter Summary Image',
                    'instructions' => 'This image will be used as a fallback if none was set on the content. It will be cropped to ' . SocialImage::sizeString('twitter.summary') . '.',
                    'width' => 50,
                    'validate' => [
                        'image',
                        'mimes:jpg,png',
                    ],
                ]),
            ],
            [
                'handle' => 'twitter_summary_large_image',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Twitter Summary Large Image',
                    'instructions' => 'This image will be used as a fallback if none was set on the content. It will be cropped to ' . SocialImage::sizeString('twitter.summary_large_image') . '.',
                    'width' => 50,
                    'validate' => [
                        'image',
                        'mimes:jpg,png',
                    ],
                ]),
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
        ];
    }
}
