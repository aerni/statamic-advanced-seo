<?php

namespace Aerni\AdvancedSeo\Blueprints\Sections;

use Aerni\AdvancedSeo\Contracts\BlueprintSection;

class SocialSection implements BlueprintSection
{
    public function contents(): array
    {
        $fields = $this->fields();

        if (empty($fields)) {
            return [];
        }

        return [
            'display' => 'Social',
            'fields' => $this->fields(),
        ];
    }

    public function fields(): array
    {
        $fields = collect();

        if (config('advanced-seo.social_images.generator', false)) {
            $fields->push($this->socialImagesGeneratorSection());
        }

        if (config('advanced-seo.social_images.open_graph', true)) {
            $fields->push($this->openGraphSection());
        }

        if (config('advanced-seo.social_images.twitter', true)) {
            $fields->push($this->twitterSection());
        }

        return $fields->flatten(1)->toArray();
    }

    protected function socialImagesGeneratorSection(): array
    {
        return [
            [
                'handle' => 'section_social_images',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Automatically generate your social images.',
                    'display' => 'Generate Social Images',
                ],
            ],
            [
                'handle' => 'generate_social_images',
                'field' => [
                    'display' => 'Generate Social Images',
                    'type' => 'toggle',
                    'icon' => 'toggle',
                    'instructions' => 'Activate to generate your social images.',
                    'listable' => 'hidden',
                ],
            ],
            [
                'handle' => 'social_images_collections',
                'field' => [
                    'mode' => 'select',
                    'display' => 'Collections',
                    'type' => 'collections',
                    'icon' => 'collections',
                    'instructions' => 'Select the collections you want to generate images for.',
                    'listable' => 'hidden',
                    'width' => 50,
                    'if' => [
                        'generate_social_images' => 'equals true',
                    ],
                    'validate' => [
                        'required_if:generate_social_images,true',
                    ],
                ],
            ],
        ];
    }

    protected function openGraphSection(): array
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
                'field' => [
                    'mode' => 'list',
                    'container' => 'seo',
                    'restrict' => true,
                    'allow_uploads' => true,
                    'max_files' => 1,
                    'type' => 'assets',
                    'localizable' => true,
                    'listable' => 'hidden',
                    'folder' => 'social_images',
                    'display' => 'Open Graph Image',
                    'instructions' => 'Add a default Open Graph Image. The recommended size is `1200x630px`.',
                    'validate' => [
                        'image',
                        'mimes:jpg,png',
                    ],
                ],
            ],
        ];
    }

    protected function twitterSection(): array
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
                'field' => [
                    'mode' => 'list',
                    'container' => 'seo',
                    'restrict' => true,
                    'allow_uploads' => true,
                    'max_files' => 1,
                    'type' => 'assets',
                    'localizable' => true,
                    'listable' => 'hidden',
                    'folder' => 'social_images',
                    'display' => 'Twitter Image',
                    'instructions' => 'Add a default Twitter Image with an aspect ratio of `2:1` and minimum size of `300x157px`.',
                    'validate' => [
                        'image',
                        'mimes:jpg,png',
                        'dimensions:min_width=300,min_height=157',
                    ],
                ],
            ],
        ];
    }
}
