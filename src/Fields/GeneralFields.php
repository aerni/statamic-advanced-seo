<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Concerns\HasAssetField;
use Aerni\AdvancedSeo\Models\Defaults;

class GeneralFields extends BaseFields
{
    use HasAssetField;

    protected function sections(): array
    {
        return [
            $this->general(),
        ];
    }

    protected function general(): array
    {
        return [
            [
                'handle' => 'section_titles',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_titles.display'),
                    'instructions' => $this->trans('section_titles.instructions'),
                ],
            ],
            [
                'handle' => 'site_name',
                'field' => [
                    'type' => 'text',
                    'display' => $this->trans('site_name.display'),
                    'instructions' => $this->trans('site_name.instructions'),
                    'input_type' => 'text',
                    'localizable' => true,
                    'listable' => 'hidden',
                    'width' => 50,
                ],
            ],
            [
                'handle' => 'title_separator',
                'field' => [
                    'type' => 'select',
                    'display' => $this->trans('title_separator.display'),
                    'instructions' => $this->trans('title_separator.instructions'),
                    'options' => [
                        ' | ' => '|',
                        ' - ' => '-',
                        ' / ' => '/',
                        ' :: ' => '::',
                        ' > ' => '>',
                        ' ~ ' => '~',
                    ],
                    'default' => Defaults::data('site::general')->get('title_separator'),
                    'clearable' => false,
                    'multiple' => false,
                    'searchable' => true,
                    'localizable' => true,
                    'taggable' => false,
                    'push_tags' => false,
                    'cast_booleans' => false,
                    'width' => 50,
                    'listable' => 'hidden',
                ],
            ],
            [
                'handle' => 'section_knowledge_graph',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_knowledge_graph.display'),
                    'instructions' => $this->trans('section_knowledge_graph.instructions'),
                ],
            ],
            [
                'handle' => 'site_json_ld_type',
                'field' => [
                    'type' => 'button_group',
                    'display' => $this->trans('site_json_ld_type.display'),
                    'instructions' => $this->trans('site_json_ld_type.instructions'),
                    'options' => [
                        'none' => $this->trans('site_json_ld_type.none'),
                        'organization' => $this->trans('site_json_ld_type.organization'),
                        'person' => $this->trans('site_json_ld_type.person'),
                        'custom' => $this->trans('site_json_ld_type.custom'),
                    ],
                    'default' => Defaults::data('site::general')->get('site_json_ld_type'),
                    'localizable' => true,
                    'listable' => false,

                ],
            ],
            [
                'handle' => 'organization_name',
                'field' => [
                    'type' => 'text',
                    'display' => $this->trans('organization_name.display'),
                    'instructions' => $this->trans('organization_name.instructions'),
                    'input_type' => 'text',
                    'localizable' => true,
                    'listable' => 'hidden',
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
                    'display' => $this->trans('organization_logo.display'),
                    'instructions' => $this->trans('organization_logo.instructions'),
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
                    'type' => 'text',
                    'display' => $this->trans('person_name.display'),
                    'instructions' => $this->trans('person_name.instructions'),
                    'input_type' => 'text',
                    'listable' => 'hidden',
                    'width' => 50,
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
                    'type' => 'code',
                    'display' => $this->trans('site_json_ld.display'),
                    'instructions' => $this->trans('site_json_ld.instructions'),
                    'theme' => 'material',
                    'mode' => 'javascript',
                    'indent_type' => 'tabs',
                    'indent_size' => 4,
                    'key_map' => 'default',
                    'line_numbers' => true,
                    'line_wrapping' => true,
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
                    'display' => $this->trans('section_breadcrumbs.display'),
                    'instructions' => $this->trans('section_breadcrumbs.instructions'),
                ],
            ],
            [
                'handle' => 'use_breadcrumbs',
                'field' => [
                    'type' => 'toggle',
                    'display' => $this->trans('use_breadcrumbs.display'),
                    'instructions' => $this->trans('use_breadcrumbs.instructions'),
                    'default' => Defaults::data('site::general')->get('use_breadcrumbs'),
                    'localizable' => true,
                    'listable' => false,
                ],
            ],
        ];
    }
}
