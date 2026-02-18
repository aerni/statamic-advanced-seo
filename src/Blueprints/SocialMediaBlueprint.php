<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Concerns\HasAssetField;

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
                $this->socialMedia(),
            ],
        ];
    }

    protected function socialMedia(): array
    {
        return [
            'display' => $this->trans('section_social_media.display'),
            'instructions' => $this->trans('section_social_media.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'og_image',
                    'field' => $this->getAssetFieldConfig([
                        'display' => $this->trans('og_image.display'),
                        'instructions' => $this->trans('og_image.instructions'),
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
                    ],
                ],
            ],
        ];
    }
}
