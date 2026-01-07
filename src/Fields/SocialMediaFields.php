<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Facades\SocialImage;

class SocialMediaFields extends BaseFields
{
    use HasAssetField;

    protected function sections(): array
    {
        return [
            $this->openGraphImage(),
            $this->twitterImage(),
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
