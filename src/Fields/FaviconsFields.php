<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Concerns\HasAssetField;

class FaviconsFields extends BaseFields
{
    use HasAssetField;

    public function sections(): array
    {
        return [
            $this->favicons(),
            // $this->faviconsGenerator(),
        ];
    }

    public function favicons(): array
    {
        if (! config('advanced-seo.favicons.enabled', true)) {
            return [];
        }

        return [
            [
                'handle' => 'section_favicon',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_favicon.display'),
                    'instructions' => $this->trans('section_favicon.instructions'),
                    'listable' => 'hidden',
                ],
            ],
            [
                'handle' => 'favicon_svg',
                'field' => $this->getAssetFieldConfig([
                    'display' => $this->trans('favicon_svg.display'),
                    'instructions' => $this->trans('favicon_svg.instructions'),
                    'container' => config('advanced-seo.favicons.container', 'assets'),
                    'folder' => 'favicons',
                    'localizable' => false,
                    'validate' => [
                        'image',
                        'mimes:svg',
                    ],
                ]),
            ],
        ];
    }

    public function faviconsGenerator(): array
    {
        if (! config('advanced-seo.favicons.enabled', true) || ! config('advanced-seo.favicons.generator.enabled', false)) {
            return [];
        }

        return [
            [
                'handle' => 'section_favicon_colors',
                'field' => [
                    'type' => 'section',
                    'display' => 'Favicon Colors',
                    'instructions' => 'Configure your favicon colors.',
                    'listable' => 'hidden',
                ],
            ],
            [
                'handle' => 'favicon_safari_color',
                'field' => [
                    'theme' => 'nano',
                    'lock_opacity' => true,
                    'default_color_mode' => 'HEXA',
                    'color_modes' => [
                        'hex',
                    ],
                    'display' => 'Safari (mask-icon)',
                    'type' => 'color',
                    'icon' => 'color',
                    'listable' => 'hidden',
                    'instructions' => 'The color of your favicon in Safari.',
                    'width' => 50,
                    'validate' => [
                        'required',
                    ],
                ],
            ],
            [
                'handle' => 'favicon_ios_color',
                'field' => [
                    'theme' => 'nano',
                    'lock_opacity' => true,
                    'default_color_mode' => 'HEXA',
                    'color_modes' => [
                        'hex',
                    ],
                    'display' => 'iOS (apple-touch-icon)',
                    'type' => 'color',
                    'icon' => 'color',
                    'listable' => 'hidden',
                    'instructions' => 'The background color of your favicon on iOS.',
                    'width' => 50,
                    'validate' => [
                        'required',
                    ],
                ],
            ],
            [
                'handle' => 'favicon_android_chrome_color',
                'field' => [
                    'theme' => 'nano',
                    'lock_opacity' => true,
                    'default_color_mode' => 'HEXA',
                    'color_modes' => [
                        'hex',
                    ],
                    'display' => 'Android Chrome',
                    'type' => 'color',
                    'icon' => 'color',
                    'listable' => 'hidden',
                    'instructions' => 'The background color of your favicon on Android Chrome.',
                    'width' => 50,
                    'validate' => [
                        'required',
                    ],
                ],
            ],
            [
                'handle' => 'section_favicon_overrides',
                'field' => [
                    'type' => 'section',
                    'listable' => 'hidden',
                    'display' => 'Favicon Overrides',
                    'instructions' => 'You may override the automatically generated favicons with your own.',
                ],
            ],
            [
                'handle' => 'favicon_safari_override',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Safari (mask-icon)',
                    'instructions' => 'A single color and as flattened as possible SVG. This will use the `Safari` color defined above.',
                    'container' => config('advanced-seo.favicons.container', 'assets'),
                    'width' => 50,
                    'folder' => 'favicons',
                    'localizable' => false,
                    'validate' => [
                        'image',
                        'mimes:svg',
                    ],
                ]),
            ],
            [
                'handle' => 'favicon_ios_override',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'iOS (apple-touch-icon)',
                    'instructions' => 'A `180x180px` PNG for iOS devices.',
                    'container' => config('advanced-seo.favicons.container', 'assets'),
                    'width' => 50,
                    'folder' => 'favicons',
                    'localizable' => false,
                    'validate' => [
                        'image',
                        'mimes:png',
                        'dimensions:width=180,height=180',
                    ],
                ]),
            ],
            [
                'handle' => 'favicon_android_chrome_override',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Android Chrome',
                    'instructions' => 'A `512x512px` PNG for Android devices.',
                    'container' => config('advanced-seo.favicons.container', 'assets'),
                    'width' => 50,
                    'folder' => 'favicons',
                    'localizable' => false,
                    'validate' => [
                        'image',
                        'mimes:png',
                        'dimensions:width=512,height=512',
                    ],
                ]),
            ],
        ];
    }
}
