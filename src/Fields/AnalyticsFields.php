<?php

namespace Aerni\AdvancedSeo\Fields;

class AnalyticsFields extends BaseFields
{
    public function sections(): array
    {
        return [
            $this->fathom(),
            $this->cloudflare(),
            $this->googleTagManager(),
        ];
    }

    protected function fathom(): array
    {
        if (! config('advanced-seo.analytics.fathom', true)) {
            return [];
        }

        return [
            [
                'handle' => 'section_fathom',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_fathom.display'),
                    'instructions' => $this->trans('section_fathom.instructions'),
                ],
            ],
            [
                'handle' => 'use_fathom',
                'field' => [
                    'type' => 'toggle',
                    'display' => $this->trans('use_fathom.display'),
                    'instructions' => $this->trans('use_fathom.instructions'),
                    'listable' => false,
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
                    'validate' => [
                        'required_if:use_fathom,true',
                    ],
                    'if' => [
                        'use_fathom' => 'equals true',
                    ],
                ],
            ],
            [
                'handle' => 'fathom_domain',
                'field' => [
                    'type' => 'text',
                    'display' => $this->trans('fathom_domain.display'),
                    'instructions' => $this->trans('fathom_domain.instructions'),
                    'input_type' => 'text',
                    'width' => 50,
                    'listable' => 'hidden',
                    'antlers' => true,
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
        if (! config('advanced-seo.analytics.cloudflare_analytics', true)) {
            return [];
        }

        return [
            [
                'handle' => 'section_cloudflare_web_analytics',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_cloudflare_web_analytics.display'),
                    'instructions' => $this->trans('section_cloudflare_web_analytics.instructions'),
                ],
            ],
            [
                'handle' => 'use_cloudflare_web_analytics',
                'field' => [
                    'type' => 'toggle',
                    'display' => $this->trans('use_cloudflare_web_analytics.display'),
                    'instructions' => $this->trans('use_cloudflare_web_analytics.instructions'),
                    'listable' => false,
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
        if (! config('advanced-seo.analytics.google_tag_manager', true)) {
            return [];
        }

        return [
            [
                'handle' => 'section_google_tag_manager',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_google_tag_manager.display'),
                    'instructions' => $this->trans('section_google_tag_manager.instructions'),
                ],
            ],
            [
                'handle' => 'use_google_tag_manager',
                'field' => [
                    'type' => 'toggle',
                    'display' => $this->trans('use_google_tag_manager.display'),
                    'instructions' => $this->trans('use_google_tag_manager.instructions'),
                    'listable' => false,
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
