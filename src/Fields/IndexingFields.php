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
                    'display' => 'Crawling',
                    'instructions' => 'Configure the crawling settings for your site. Activated toggles override their counterpart on entries and terms.',
                ],
            ],
            [
                'handle' => 'noindex',
                'field' => [
                    'type' => 'toggle',
                    'display' => 'Noindex',
                    'instructions' => 'Prevent the site from being indexed by search engines.',
                    'listable' => 'hidden',
                    'localizable' => true,
                    'width' => 33,
                    'default' => Defaults::data('site::indexing')->get('noindex'),
                ],
            ],
            [
                'handle' => 'nofollow',
                'field' => [
                    'type' => 'toggle',
                    'display' => 'Nofollow',
                    'instructions' => 'Prevent site crawlers from following any links on this site.',
                    'listable' => 'hidden',
                    'localizable' => true,
                    'width' => 33,
                    'default' => Defaults::data('site::indexing')->get('nofollow'),
                ],
            ],
            [
                'handle' => 'use_breadcrumbs',
                'field' => [
                    'type' => 'toggle',
                    'instructions' => "Indicate each page's position in the site's hierarchy.",
                    'listable' => false,
                    'localizable' => true,
                    'display' => 'Breadcrumbs',
                    'width' => 33,
                    'default' => Defaults::data('site::indexing')->get('use_breadcrumbs'),
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
                    'instructions' => 'Configure the sitemap settings for your site.',
                    'display' => 'Sitemap',
                ],
            ],
            [
                'handle' => 'excluded_collections',
                'field' => [
                    'mode' => 'stack',
                    'type' => 'collections',
                    'listable' => 'hidden',
                    'display' => 'Collections',
                    'instructions' => 'Collections you want to exclude from the sitemap.',
                    'localizable' => true,
                    'width' => 50,
                ],
            ],
            [
                'handle' => 'excluded_taxonomies',
                'field' => [
                    'mode' => 'stack',
                    'type' => 'taxonomies',
                    'listable' => 'hidden',
                    'display' => 'Taxonomies',
                    'instructions' => 'Taxonomies you want to exclude from the sitemap.',
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
                    'instructions' => 'Verify your ownership of this site.',
                    'display' => 'Site verification',
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
