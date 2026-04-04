<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Features\Ai;
use Aerni\AdvancedSeo\Features\MultiSite;

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
                $this->ai(),
            ],
        ];
    }

    protected function origins(): array
    {
        return [
            'display' => __('advanced-seo::messages.origins'),
            'fields' => [
                [
                    'handle' => 'origins',
                    'field' => [
                        'type' => 'site_origins',
                        'display' => __('advanced-seo::messages.origins'),
                        'instructions' => __('advanced-seo::messages.origins_instructions'),
                        'default' => [],
                        'feature' => MultiSite::class,
                    ],
                ],
            ],
        ];
    }

    protected function ai(): array
    {
        return [
            'display' => $this->trans('section_ai.display'),
            'fields' => [
                [
                    'handle' => 'ai_instructions',
                    'field' => [
                        'type' => 'textarea',
                        'display' => $this->trans('ai_instructions.display'),
                        'instructions' => $this->trans('ai_instructions.instructions'),
                        'placeholder' => $this->trans('ai_instructions.placeholder'),
                        'feature' => Ai::class,
                    ],
                ],
            ],
        ];
    }
}
