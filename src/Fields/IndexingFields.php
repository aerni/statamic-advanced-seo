<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Features\SiteVerification;

class IndexingFields extends BaseFields
{
    protected function sections(): array
    {
        return [
            $this->crawling(),
            $this->siteVerification(),
        ];
    }

    protected function crawling(): array
    {
        return [
            'display' => $this->trans('section_crawling.display'),
            'instructions' => $this->trans('section_crawling.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'noindex',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('noindex.display'),
                        'instructions' => $this->trans('noindex.instructions'),
                        'default' => false,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                    ],
                ],
                [
                    'handle' => 'nofollow',
                    'field' => [
                        'type' => 'toggle',
                        'display' => $this->trans('nofollow.display'),
                        'instructions' => $this->trans('nofollow.instructions'),
                        'default' => false,
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                    ],
                ],
            ],
        ];
    }

    protected function siteVerification(): array
    {
        return [
            'display' => $this->trans('section_verification.display'),
            'instructions' => $this->trans('section_verification.instructions'),
            'collapsible' => true,
            'fields' => [
                [
                    'handle' => 'google_site_verification_code',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('google_site_verification_code.display'),
                        'instructions' => $this->trans('google_site_verification_code.instructions'),
                        'input_type' => 'text',
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                        'feature' => SiteVerification::class,
                    ],
                ],
                [
                    'handle' => 'bing_site_verification_code',
                    'field' => [
                        'type' => 'text',
                        'display' => $this->trans('bing_site_verification_code.display'),
                        'instructions' => $this->trans('bing_site_verification_code.instructions'),
                        'input_type' => 'text',
                        'listable' => 'hidden',
                        'localizable' => true,
                        'width' => 50,
                        'feature' => SiteVerification::class,
                    ],
                ],
            ],
        ];
    }
}
