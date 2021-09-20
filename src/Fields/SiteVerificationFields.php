<?php

namespace Aerni\AdvancedSeo\Fields;

class SiteVerificationFields extends BaseFields
{
    public function sections(): array
    {
        return [
            $this->siteVerification(),
        ];
    }

    protected function siteVerification(): array
    {
        if (! config('advanced-seo.trackers.site_verification', true)) {
            return [];
        }

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
                'handle' => 'google_site_verification_code',
                'field' => [
                    'input_type' => 'text',
                    'type' => 'text',
                    'listable' => 'hidden',
                    'width' => 50,
                    'display' => 'Google Verification Code',
                    'instructions' => 'Get your Google verification code in [Google Search Console](https://search.google.com/search-console).',
                ],
            ],
            [
                'handle' => 'bing_site_verification_code',
                'field' => [
                    'input_type' => 'text',
                    'type' => 'text',
                    'listable' => 'hidden',
                    'width' => 50,
                    'display' => 'Bing Verification Code',
                    'instructions' => 'Get your Bing verification code in [Bing Webmaster Tools](https://www.bing.com/toolbox/webmaster).',
                ],
            ],
        ];
    }
}
