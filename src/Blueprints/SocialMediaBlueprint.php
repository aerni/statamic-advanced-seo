<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Facades\SocialImage;

class SocialMediaBlueprint extends BaseBlueprint
{
    use HasAssetField;

    protected function handle(): string
    {
        return 'social';
    }

    protected function tabs(): array
    {
        return [
            'social' => [
                $this->openGraphImage(),
                $this->twitterImage(),
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
                        'instructions' => $this->trans('og_image.instructions', ['size' => SocialImage::openGraph()->sizeString()]),
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
                        'instructions' => $this->trans('twitter_summary_image.instructions', ['size' => SocialImage::twitter()->sizeString()]),
                        'twitter_card' => 'summary',
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
                        'instructions' => $this->trans('twitter_summary_large_image.instructions', ['size' => SocialImage::twitterLarge()->sizeString()]),
                        'twitter_card' => 'summary_large_image',
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
