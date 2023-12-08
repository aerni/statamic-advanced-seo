<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Features\Sitemap;
use Aerni\AdvancedSeo\Features\SiteVerification;
use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Support\Str;

class IndexingFields extends BaseFields
{
    protected function sections(): array
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
                    'instructions' => $this->trans('section_crawling.instructions', ['environments' => Str::makeSentenceList(config('advanced-seo.crawling.environments'))]),
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
        return [
            [
                'handle' => 'section_sitemap',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_sitemap.display'),
                    'instructions' => $this->trans('section_sitemap.instructions'),
                    'feature' => Sitemap::class,
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
                    'feature' => Sitemap::class,
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
                    'feature' => Sitemap::class,
                ],
            ],
        ];
    }

    protected function siteVerification(): array
    {
        return [
            [
                'handle' => 'section_verification',
                'field' => [
                    'type' => 'section',
                    'display' => $this->trans('section_verification.display'),
                    'instructions' => $this->trans('section_verification.instructions'),
                    'feature' => SiteVerification::class,
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
                    'width' => 50,
                    'feature' => SiteVerification::class,
                ],
            ],
        ];
    }
}
