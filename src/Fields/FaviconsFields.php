<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Traits\HasAssetField;

class FaviconsFields extends BaseFields
{
    use HasAssetField;

    public function sections(): array
    {
        return [
            $this->favicons(),
        ];
    }

    public function favicons(): array
    {
        if (! config('advanced-seo.favicons', false)) {
            return [];
        }

        return [
            [
                'handle' => 'section_favicons',
                'field' => [
                    'type' => 'section',
                    'listable' => 'hidden',
                    'display' => 'Favicons',
                    'instructions' => 'Automatically generate favicons for different devices. This requires the [PHP Imagick Extension](https://github.com/Imagick/imagick).',
                ],
            ],
            [
                'handle' => 'generate_favicons',
                'field' => [
                    'display' => 'Generate Favicons',
                    'type' => 'toggle',
                    'icon' => 'toggle',
                    'instructions' => 'Activate to generate favicons.',
                    'listable' => 'hidden',
                ],
            ],
            [
                'handle' => 'favicon_svg',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Favicon (SVG)',
                    'instructions' => 'Add your favicon as SVG file.',
                    'width' => 50,
                    'restrict' => true,
                    'folder' => 'favicons',
                    'localizable' => false,
                    'validate' => [
                        'required_if:generate_favicons,true',
                        'image',
                        'mimes:svg',
                    ],
                    'if' => [
                        'generate_favicons' => 'equals true',
                    ],
                ]),
            ],
            [
                'handle' => 'section_favicon_colors',
                'field' => [
                    'type' => 'section',
                    'listable' => 'hidden',
                    'display' => 'Favicon Colors',
                    'instructions' => 'Configure your favicon colors.',
                    'if' => [
                        'generate_favicons' => 'equals true',
                    ],
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
                        'required_if:generate_favicons,true',
                    ],
                    'if' => [
                        'generate_favicons' => 'equals true',
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
                        'required_if:generate_favicons,true',
                    ],
                    'if' => [
                        'generate_favicons' => 'equals true',
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
                        'required_if:generate_favicons,true',
                    ],
                    'if' => [
                        'generate_favicons' => 'equals true',
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
                    'if' => [
                        'generate_favicons' => 'equals true',
                    ],
                ],
            ],
            [
                'handle' => 'favicon_safari_override',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Safari (mask-icon)',
                    'instructions' => 'A single color and as flattened as possible SVG. This will use the `Safari` color defined above.',
                    'width' => 50,
                    'restrict' => true,
                    'folder' => 'favicons',
                    'localizable' => false,
                    'validate' => [
                        'image',
                        'mimes:svg',
                    ],
                    'if' => [
                        'generate_favicons' => 'equals true',
                    ],
                ]),
            ],
            [
                'handle' => 'favicon_ios_override',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'iOS (apple-touch-icon)',
                    'instructions' => 'A `180x180px` PNG for iOS devices.',
                    'width' => 50,
                    'restrict' => true,
                    'folder' => 'favicons',
                    'localizable' => false,
                    'validate' => [
                        'image',
                        'mimes:png',
                        'dimensions:width=180,height=180',
                    ],
                    'if' => [
                        'generate_favicons' => 'equals true',
                    ],
                ]),
            ],
            [
                'handle' => 'favicon_android_chrome_override',
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Android Chrome',
                    'instructions' => 'A `512x512px` PNG for Android devices.',
                    'width' => 50,
                    'restrict' => true,
                    'folder' => 'favicons',
                    'localizable' => false,
                    'validate' => [
                        'image',
                        'mimes:png',
                        'dimensions:width=512,height=512',
                    ],
                    'if' => [
                        'generate_favicons' => 'equals true',
                    ],
                ]),
            ],
        ];
    }
}
