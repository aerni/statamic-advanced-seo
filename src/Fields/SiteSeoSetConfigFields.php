<?php

namespace Aerni\AdvancedSeo\Fields;

class SiteSeoSetConfigFields extends BaseFields
{
    protected function sections(): array
    {
        return [
            $this->origins(),
        ];
    }

    protected function origins(): array
    {
        return [
            'display' => __('Origins'),
            'fields' => [
                [
                    'handle' => 'origins',
                    'field' => [
                        'type' => 'default_set_sites',
                        'display' => __('Origins'),
                        'instructions' => __('Choose to inherit values from selected origins.'),
                        'default' => [],
                    ],
                ],
            ],
        ];
    }
}
