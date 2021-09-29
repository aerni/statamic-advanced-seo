<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Traits\HasAssetField;

class GeneralFields extends BaseFields
{
    // TODO: Actually make use of this in the sections below.
    use HasAssetField;

    public function sections(): array
    {
        return [
            $this->general(),
        ];
    }

    public function general(): array
    {
        return [
            [
                'handle' => 'section_titles',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Configure how your site titles appear.',
                    'display' => 'Titles',
                ],
            ],
            [
                'handle' => 'site_name',
                'field' => [
                    'input_type' => 'text',
                    'type' => 'text',
                    'localizable' => true,
                    'listable' => 'hidden',
                    'display' => 'Website Name',
                    'instructions' => 'Set the name of the website.',
                    'width' => 50,
                ],
            ],
            [
                'handle' => 'title_separator',
                'field' => [
                    'options' => [
                        ' | ' => '|',
                        ' - ' => '-',
                        ' / ' => '/',
                        ' :: ' => '::',
                        ' > ' => '>',
                        ' ~ ' => '~',
                    ],
                    'clearable' => false,
                    'multiple' => false,
                    'searchable' => true,
                    'localizable' => true,
                    'taggable' => false,
                    'push_tags' => false,
                    'cast_booleans' => false,
                    'type' => 'select',
                    'instructions' => 'Set the separator of the page title and site name.',
                    'width' => 50,
                    'listable' => 'hidden',
                    'display' => 'Separator',
                    'default' => '|',
                ],
            ],
            [
                'handle' => 'section_schema',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Add basic [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) information about this website.',
                    'display' => 'Knowledge Graph',
                ],
            ],
            [
                'handle' => 'site_json_ld_type',
                'field' => [
                    'options' => [
                        'organization' => 'Organization',
                        'person' => 'Person',
                        'custom' => 'Custom',
                    ],
                    'default' => 'organization',
                    'localizable' => false,
                    'type' => 'button_group',
                    'instructions' => 'The type of content this website represents.',
                    'listable' => false,
                    'display' => 'Type',
                    'width' => 50,
                ],
            ],
            [
                'handle' => 'organization_name',
                'field' => [
                    'input_type' => 'text',
                    'type' => 'text',
                    'localizable' => true,
                    'listable' => 'hidden',
                    'display' => 'Organization Name',
                    'instructions' => 'Set the name of the organization.',
                    'width' => 50,
                    'if' => [
                        'site_json_ld_type' => 'equals organization',
                    ],
                    'validate' => [
                        'required_if:site_json_ld_type,organization',
                    ],
                ],
            ],
            [
                'handle' => 'organization_logo',
                'field' => [
                    'mode' => 'list',
                    'container' => 'seo',
                    'restrict' => false,
                    'allow_uploads' => true,
                    'max_files' => 1,
                    'type' => 'assets',
                    'localizable' => true,
                    'listable' => 'hidden',
                    'display' => 'Organization Logo',
                    'instructions' => 'Add an optional logo with a minimum size of `112x112px`.',
                    'validate' => [
                        'image',
                    ],
                    'if' => [
                        'site_json_ld_type' => 'equals organization',
                    ],
                ],
            ],
            [
                'handle' => 'person_name',
                'field' => [
                    'listable' => 'hidden',
                    'display' => 'Person Name',
                    'instructions' => 'Set the name of the person.',
                    'width' => 50,
                    'input_type' => 'text',
                    'type' => 'text',
                    'localizable' => true,
                    'if' => [
                        'site_json_ld_type' => 'equals person',
                    ],
                    'validate' => [
                        'required_if:site_json_ld_type,person',
                    ],
                ],
            ],
            [
                'handle' => 'site_json_ld',
                'field' => [
                    'theme' => 'material',
                    'mode' => 'javascript',
                    'indent_type' => 'tabs',
                    'indent_size' => 4,
                    'key_map' => 'default',
                    'line_numbers' => true,
                    'line_wrapping' => true,
                    'display' => 'JSON-LD Schema',
                    'instructions' => 'Add custom [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) you want to include on each entry. This will be wrapped in the appropriate script tag.',
                    'type' => 'code',
                    'icon' => 'code',
                    'listable' => 'hidden',
                    'if' => [
                        'site_json_ld_type' => 'equals custom',
                    ],
                    'validate' => [
                        'required_if:site_json_ld_type,custom',
                    ],
                ],
            ],
            [
                'handle' => 'section_breadcrumbs',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Add [breadcrumbs](https://developers.google.com/search/docs/data-types/breadcrumb) to your entries.',
                    'display' => 'Breadcrumbs',
                ],
            ],
            [
                'handle' => 'breadcrumbs',
                'field' => [
                    'type' => 'toggle',
                    'instructions' => 'Add breadcrumbs',
                    'listable' => false,
                    'localizable' => true,
                    'display' => 'Breadcrumbs',
                ],
            ],
        ];
    }
}
