<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Models\Defaults;

class IndexingFields extends BaseFields
{
    public function sections(): array
    {
        return [
            $this->crawling(),
            $this->sitemap(),
            $this->siteVerification(),
        ];
    }

    protected function crawling(): array
    {
        return [
            [
                'handle' => 'section_crawling',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_crawling.display'),
                    'instructions' => $this->trans('section_crawling.instructions'),
                ],
            ],
            [
                'handle' => 'noindex',
                'field' => [
                    'type' => 'toggle',
                    'display' => $this->trans('noindex.display'),
                    'instructions' => $this->trans('noindex.instructions'),
                    'default' => Defaults::data('site::indexing')->get('noindex'),
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
                    'default' => Defaults::data('site::indexing')->get('nofollow'),
                    'listable' => 'hidden',
                    'localizable' => true,
                    'width' => 50,
                ],
            ],
        ];
    }

    protected function sitemap(): array
    {
        if (! config('advanced-seo.sitemap.enabled', true)) {
            return [];
        }

        return [
            [
                'handle' => 'section_sitemap',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_sitemap.display'),
                    'instructions' => $this->trans('section_sitemap.instructions'),
                ],
            ],
            [
                'handle' => 'excluded_collections',
                'field' => [
                    'type' => 'collections',
                    'display' => $this->trans('excluded_collections.display'),
                    'instructions' => $this->trans('excluded_collections.instructions'),
                    'mode' => 'stack',
                    'listable' => 'hidden',
                    'localizable' => true,
                    'width' => 50,
                ],
            ],
            [
                'handle' => 'excluded_taxonomies',
                'field' => [
                    'type' => 'taxonomies',
                    'display' => $this->trans('excluded_taxonomies.display'),
                    'instructions' => $this->trans('excluded_taxonomies.instructions'),
                    'mode' => 'stack',
                    'listable' => 'hidden',
                    'localizable' => true,
                    'width' => 50,
                ],
            ],
        ];
    }

    protected function siteVerification(): array
    {
        if (! config('advanced-seo.site_verification', true)) {
            return [];
        }

        return [
            [
                'handle' => 'section_verification',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_verification.display'),
                    'instructions' => $this->trans('section_verification.instructions'),
                ],
            ],
            [
                'handle' => 'google_site_verification_code',
                'field' => [
                    'type' => 'text',
                    'display' => $this->trans('google_site_verification_code.display'),
                    'instructions' => $this->trans('google_site_verification_code.instructions'),
                    'input_type' => 'text',
                    'listable' => 'hidden',
                    'width' => 50,
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
                    'width' => 50,
                ],
            ],
        ];
    }
}
