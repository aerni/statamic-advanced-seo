<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Features\Cloudflare;
use Aerni\AdvancedSeo\Features\Fathom;
use Aerni\AdvancedSeo\Features\GoogleTagManager;

class AnalyticsBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'analytics';
    }

    protected function tabs(): array
    {
        return [
            'analytics' => [
                $this->fathom(),
                $this->cloudflare(),
                $this->googleTagManager(),
            ],
        ];
    }

    protected function fathom(): array
    {
        return [
            'display' => $this->trans('section_fathom.display'),
            'instructions' => $this->trans('section_fathom.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'use_fathom',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('use_fathom.display'),
                        'instructions' => $this->trans('use_fathom.instructions'),
                        'default' => false,
                        'listable' => false,
                        'localizable' => true,
                        'feature' => Fathom::class,
                    ],
                ],
                [
                    'handle' => 'fathom_id',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('fathom_id.display'),
                        'instructions' => $this->trans('fathom_id.instructions'),
                        'input_type' => 'text',
                        'width' => 50,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'antlers' => true,
                        'feature' => Fathom::class,
                        'validate' => [
                            'required_if:use_fathom,true',
                        ],
                        'if' => [
                            'use_fathom' => 'equals true',
                        ],
                    ],
                ],
                [
                    'handle' => 'fathom_spa',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('fathom_spa.display'),
                        'instructions' => $this->trans('fathom_spa.instructions'),
                        'icon' => 'toggle',
                        'listable' => 'hidden',
                        'default' => false,
                        'localizable' => true,
                        'width' => 50,
                        'feature' => Fathom::class,
                        'validate' => [
                            'required_if:use_fathom,true',
                        ],
                        'if' => [
                            'use_fathom' => 'equals true',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function cloudflare(): array
    {
        return [
            'display' => $this->trans('section_cloudflare_web_analytics.display'),
            'instructions' => $this->trans('section_cloudflare_web_analytics.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'use_cloudflare_web_analytics',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('use_cloudflare_web_analytics.display'),
                        'instructions' => $this->trans('use_cloudflare_web_analytics.instructions'),
                        'default' => false,
                        'listable' => false,
                        'localizable' => true,
                        'feature' => Cloudflare::class,
                    ],
                ],
                [
                    'handle' => 'cloudflare_web_analytics',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('cloudflare_web_analytics.display'),
                        'instructions' => $this->trans('cloudflare_web_analytics.instructions'),
                        'input_type' => 'text',
                        'width' => 50,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'feature' => Cloudflare::class,
                        'validate' => [
                            'required_if:use_cloudflare_web_analytics,true',
                        ],
                        'if' => [
                            'use_cloudflare_web_analytics' => 'equals true',
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function googleTagManager(): array
    {
        return [
            'display' => $this->trans('section_google_tag_manager.display'),
            'instructions' => $this->trans('section_google_tag_manager.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'use_google_tag_manager',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('use_google_tag_manager.display'),
                        'instructions' => $this->trans('use_google_tag_manager.instructions'),
                        'default' => false,
                        'listable' => false,
                        'localizable' => true,
                        'feature' => GoogleTagManager::class,
                    ],
                ],
                [
                    'handle' => 'google_tag_manager',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('google_tag_manager.display'),
                        'instructions' => $this->trans('google_tag_manager.instructions'),
                        'input_type' => 'text',
                        'width' => 50,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'feature' => GoogleTagManager::class,
                        'validate' => [
                            'required_if:use_google_tag_manager,true',
                        ],
                        'if' => [
                            'use_google_tag_manager' => 'equals true',
                        ],
                    ],
                ],
            ],
        ];
    }
}
