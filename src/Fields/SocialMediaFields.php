<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Facades\SocialImage;
use Aerni\AdvancedSeo\Features\SocialImagesGenerator;

class SocialMediaFields extends BaseFields
{
    use HasAssetField;

    protected function sections(): array
    {
        return [
            $this->socialImagesGenerator(),
            $this->openGraphImage(),
            $this->twitterImage(),
        ];
    }

    protected function socialImagesGenerator(): array
    {
        return [
            'display' => $this->trans('section_social_images_generator.display'),
            'instructions' => $this->trans('section_social_images_generator.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'social_images_generator_collections',
                    'field' => [
                        'type' => 'collections',
                        'display' => $this->trans('social_images_generator_collections.display'),
                        'instructions' => $this->trans('social_images_generator_collections.instructions'),
                        'icon' => 'collections',
                        'mode' => 'select',
                        'listable' => 'hidden',
                        'localizable' => true,
                        'feature' => SocialImagesGenerator::class,
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
            ],
        ];
    }

    protected function openGraphImage(): array
    {
        return [
            'display' => $this->trans('section_og.display'),
            'instructions' => $this->trans('section_og.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'og_image',
                    'field' => $this->getAssetFieldConfig([
                        'display' => $this->trans('og_image.display'),
                        'instructions' => $this->trans('og_image.instructions', ['size' => SocialImage::sizeString('open_graph')]),
                        'validate' => [
                            'image',
                            'mimes:jpg,png',
                        ],
                    ]),
                ],
            ],
        ];
    }

    protected function twitterImage(): array
    {
        return [
            'display' => $this->trans('section_twitter.display'),
            'instructions' => $this->trans('section_twitter.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'twitter_summary_image',
                    'field' => $this->getAssetFieldConfig([
                        'display' => $this->trans('twitter_summary_image.display'),
                        'instructions' => $this->trans('twitter_summary_image.instructions', ['size' => SocialImage::sizeString('twitter_summary')]),
                        'twitter_card' => SocialImage::findModel('twitter_summary')['card'],
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
                        'display' => $this->trans('twitter_summary_large_image.display'),
                        'instructions' => $this->trans('twitter_summary_large_image.instructions', ['size' => SocialImage::sizeString('twitter_summary_large_image')]),
                        'twitter_card' => SocialImage::findModel('twitter_summary_large_image')['card'],
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
                        'type' => 'text',
                        'display' => $this->trans('twitter_handle.display'),
                        'instructions' => $this->trans('twitter_handle.instructions'),
                        'input_type' => 'text',
                        'listable' => 'hidden',
                        'localizable' => true,
                        'prepend' => '@',
                        'antlers' => false,
                        'width' => 50,
                    ],
                ],
            ],
        ];
    }
}
