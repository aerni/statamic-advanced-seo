<?php

namespace Aerni\AdvancedSeo\Blueprints\Sections;

use Aerni\AdvancedSeo\Contracts\BlueprintSection;

class TrackersSection implements BlueprintSection
{
    public function contents(): array
    {
        $fields = $this->fields();

        if (empty($fields)) {
            return [];
        }

        return [
            'display' => 'Trackers',
            'fields' => $this->fields(),
        ];
    }

    public function fields(): array
    {
        $fields = collect();

        if (config('advanced-seo.trackers.site_verification', true)) {
            $fields->push($this->siteVerificationSection());
        }

        if (config('advanced-seo.trackers.fathom', true)) {
            $fields->push($this->fathomSection());
        }

        if (config('advanced-seo.trackers.cloudflare', true)) {
            $fields->push($this->cloudflareSection());
        }

        if (config('advanced-seo.trackers.google_tag_manager', true)) {
            $fields->push($this->googleTagManagerSection());
        }

        return $fields->flatten(1)->toArray();
    }

    protected function siteVerificationSection(): array
    {
        return [
            [
                'handle' => 'section_verification',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Verify your site ownership.',
                    'display' => 'Site verifications',
                ],
            ],
            [
                'handle' => 'use_google_site_verification',
                'field' => [
                    'type' => 'toggle',
                    'instructions' => 'Add the `google-site-verification` meta tag to your head.',
                    'listable' => false,
                    'display' => 'Google Site Verification',
                ],
            ],
            [
                'handle' => 'site_verification_code',
                'field' => [
                    'input_type' => 'text',
                    'type' => 'text',
                    'listable' => 'hidden',
                    'width' => 50,
                    'display' => 'Verification Code',
                    'instructions' => 'Add your site verification code.',
                    'validate' => [
                        'required_if:use_google_site_verification,true',
                    ],
                    'if' => [
                        'use_google_site_verification' => 'equals true',
                    ],
                ],
            ],
        ];
    }

    protected function fathomSection(): array
    {
        return [
            [
                'handle' => 'section_fathom',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Use [Fathom](https://usefathom.com) as a privacy-friendly alternative to Google Analytics.',
                    'display' => 'Fathom',
                ],
            ],
            [
                'handle' => 'use_fathom',
                'field' => [
                    'type' => 'toggle',
                    'instructions' => 'Add the Fathom tracking script to your head.',
                    'listable' => false,
                    'display' => 'Fathom',
                ],
            ],
            [
                'handle' => 'fathom_id',
                'field' => [
                    'width' => 50,
                    'display' => 'Site ID',
                    'instructions' => 'Add your Site ID.',
                    'input_type' => 'text',
                    'type' => 'text',
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
                    'width' => 50,
                    'display' => 'Custom Domain',
                    'instructions' => 'Add an optional Custom Domain.',
                    'input_type' => 'text',
                    'type' => 'text',
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
                    'display' => 'SPA Mode',
                    'type' => 'toggle',
                    'icon' => 'toggle',
                    'instructions' => 'Activate if your site is a single page application.',
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

    protected function cloudflareSection(): array
    {
        return [
            [
                'handle' => 'section_cloudflare_web_analytics',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Use [Cloudflare Web Analytics](https://www.cloudflare.com/web-analytics) as a privacy-friendly alternative to Google Analytics.',
                    'display' => 'Cloudflare Web Analytics',
                ],
            ],
            [
                'handle' => 'use_cloudflare_web_analytics',
                'field' => [
                    'type' => 'toggle',
                    'instructions' => 'Add the Cloudflare tracking script to your head.',
                    'listable' => false,
                    'display' => 'Cloudflare Web Analytics',
                ],
            ],
            [
                'handle' => 'cloudflare_web_analytics',
                'field' => [
                    'width' => 50,
                    'display' => 'Beacon Token',
                    'instructions' => 'Add your Beacon Token.',
                    'input_type' => 'text',
                    'type' => 'text',
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

    protected function googleTagManagerSection(): array
    {
        return [
            [
                'handle' => 'section_google_tag_manager',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Use [Google Tag Manager](https://marketingplatform.google.com/about/tag-manager) to track your users. You are `required by privacy law` to get your user\'s consent before loading any tracking scripts. You also need to inform them about what data you collect and what you intent to do with it.',
                    'display' => 'Google Tag Manager',
                ],
            ],
            [
                'handle' => 'use_google_tag_manager',
                'field' => [
                    'type' => 'toggle',
                    'instructions' => 'Add the Google Tag Manager tracking scripts.',
                    'listable' => false,
                    'display' => 'Google Tag Manager',
                ],
            ],
            [
                'handle' => 'google_tag_manager',
                'field' => [
                    'input_type' => 'text',
                    'type' => 'text',
                    'listable' => 'hidden',
                    'width' => 50,
                    'display' => 'Container ID',
                    'instructions' => 'Add your Container ID.',
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
