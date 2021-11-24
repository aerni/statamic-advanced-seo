<?php

namespace Aerni\AdvancedSeo\Fields;

class CrawlingFields extends BaseFields
{
    public function sections(): array
    {
        return [
            $this->crawling(),
        ];
    }

    public function crawling(): array
    {
        return [
            [
                'handle' => 'section_crawling',
                'field' => [
                    'type' => 'section',
                    'display' => 'Crawling',
                    'instructions' => 'Configure the crawling settings for your site.',
                ],
            ],
            [
                'handle' => 'noindex',
                'field' => [
                    'type' => 'toggle',
                    'display' => 'Noindex',
                    'instructions' => 'Prevent this site from being indexed by search engines.',
                    'listable' => 'hidden',
                    'localizable' => true,
                    'width' => 50,
                ],
            ],
            [
                'handle' => 'nofollow',
                'field' => [
                    'type' => 'toggle',
                    'display' => 'Nofollow',
                    'instructions' => 'Prevent site crawlers from following links on your site.',
                    'listable' => 'hidden',
                    'localizable' => true,
                    'width' => 50,
                ],
            ],
        ];
    }
}
