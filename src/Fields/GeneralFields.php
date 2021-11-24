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
        ];
    }
}
