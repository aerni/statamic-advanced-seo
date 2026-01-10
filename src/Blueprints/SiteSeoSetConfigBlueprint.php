<?php

namespace Aerni\AdvancedSeo\Blueprints;

class SiteSeoSetConfigBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'site_config';
    }

    protected function tabs(): array
    {
        return [
            'main' => [
                $this->origins(),
            ],
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
