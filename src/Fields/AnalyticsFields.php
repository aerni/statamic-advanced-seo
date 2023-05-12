<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Features\Cloudflare;
use Aerni\AdvancedSeo\Features\Fathom;
use Aerni\AdvancedSeo\Features\GoogleTagManager;

class AnalyticsFields extends BaseFields
{
    protected function sections(): array
    {
        return [
            $this->fathom(),
            $this->cloudflare(),
            $this->googleTagManager(),
        ];
    }

    protected function fathom(): array
    {
        return [
            [
                'handle' => 'section_fathom',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_fathom.display'),
                    'instructions' => $this->trans('section_fathom.instructions'),
                    'feature' => Fathom::class,
                ],
            ],
            [
                'handle' => 'use_fathom',
                'field' => [
                    'type' => 'toggle',
                    'display' => $this->trans('use_fathom.display'),
                    'instructions' => $this->trans('use_fathom.instructions'),
                    'listable' => false,
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
        ];
    }

    protected function cloudflare(): array
    {
        return [
            [
                'handle' => 'section_cloudflare_web_analytics',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_cloudflare_web_analytics.display'),
                    'instructions' => $this->trans('section_cloudflare_web_analytics.instructions'),
                    'feature' => Cloudflare::class,
                ],
            ],
            [
                'handle' => 'use_cloudflare_web_analytics',
                'field' => [
                    'type' => 'toggle',
                    'display' => $this->trans('use_cloudflare_web_analytics.display'),
                    'instructions' => $this->trans('use_cloudflare_web_analytics.instructions'),
                    'listable' => false,
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
                    'feature' => Cloudflare::class,
                    'validate' => [
                        'required_if:use_cloudflare_web_analytics,true',
                    ],
                    'if' => [
                        'use_cloudflare_web_analytics' => 'equals true',
                    ],
                ],
            ],
        ];
    }

    protected function googleTagManager(): array
    {
        return [
            [
                'handle' => 'section_google_tag_manager',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_google_tag_manager.display'),
                    'instructions' => $this->trans('section_google_tag_manager.instructions'),
                    'feature' => GoogleTagManager::class,
                ],
            ],
            [
                'handle' => 'use_google_tag_manager',
                'field' => [
                    'type' => 'toggle',
                    'display' => $this->trans('use_google_tag_manager.display'),
                    'instructions' => $this->trans('use_google_tag_manager.instructions'),
                    'listable' => false,
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
                    'feature' => GoogleTagManager::class,
                    'validate' => [
                        'required_if:use_google_tag_manager,true',
                    ],
                    'if' => [
                        'use_google_tag_manager' => 'equals true',
                    ],
                ],
            ],
        ];
    }
}
