<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Models\Defaults;

class GeneralFields extends BaseFields
{
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
                    'instructions' => 'Configure the appearance of your page titles.',
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
                    'display' => 'Site Name',
                    'instructions' => 'The site name is added to your meta titles.',
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
                    'instructions' => 'This separates the site name and page title.',
                    'width' => 50,
                    'listable' => 'hidden',
                    'display' => 'Title Separator',
                    'default' => Defaults::data('site::general')->get('title_separator'),
                ],
            ],
            [
                'handle' => 'section_knowledge_graph',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Add basic [JSON-LD](https://developers.google.com/search/docs/guides/intro-structured-data) information about this site.',
                    'display' => 'Basic Information',
                ],
            ],
            [
                'handle' => 'site_json_ld_type',
                'field' => [
                    'options' => [
                        'none' => 'None',
                        'organization' => 'Organization',
                        'person' => 'Person',
                        'custom' => 'Custom',
                    ],
                    'default' => Defaults::data('site::general')->get('site_json_ld_type'),
                    'localizable' => false,
                    'type' => 'button_group',
                    'instructions' => 'The type of content this site represents.',
                    'listable' => false,
                    'display' => 'Content Type',
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
                    'instructions' => 'The name of this site\'s organization.',
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
                'field' => $this->getAssetFieldConfig([
                    'display' => 'Organization Logo',
                    'instructions' => 'Add the logo with a minimum size of 112 x 112 pixels.',
                    'width' => 50,
                    'folder' => null,
                    'validate' => [
                        'image',
                    ],
                    'if' => [
                        'site_json_ld_type' => 'equals organization',
                    ],
                ]),
            ],
            [
                'handle' => 'person_name',
                'field' => [
                    'listable' => 'hidden',
                    'display' => 'Person Name',
                    'instructions' => 'The name of this site\'s person.',
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
                    'instructions' => 'Structured data that will be added to every page. This will be wrapped in the appropriate script tag.',
                    'type' => 'code',
                    'icon' => 'code',
                    'listable' => 'hidden',
                    'localizable' => true,
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
                    'instructions' => "Breadcrumbs help your users understand your site by indicating each page's position in the hierarchy.",
                    'display' => 'Breadcrumbs',
                ],
            ],
            [
                'handle' => 'use_breadcrumbs',
                'field' => [
                    'type' => 'toggle',
                    'instructions' => 'Add [breadcrumbs](https://developers.google.com/search/docs/data-types/breadcrumb) to your pages.',
                    'listable' => false,
                    'display' => 'Breadcrumbs',
                    'default' => Defaults::data('site::general')->get('use_breadcrumbs'),
                ],
            ],
        ];
    }
}
