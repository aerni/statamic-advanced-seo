<?php

namespace Aerni\AdvancedSeo\Fields;

class IndexingFields extends BaseFields
{
    public function sections(): array
    {
        return [
            $this->indexing(),
        ];
    }

    public function indexing(): array
    {
        return [
            [
                'handle' => 'section_indexing',
                'field' => [
                    'type' => 'section',
                    'display' => 'Indexing',
                    'instructions' => 'Configure the indexing settings for your site.',
                ],
            ],
            [
                'handle' => 'noindex',
                'field' => [
                    'type' => 'toggle',
                    'display' => 'Noindex',
                    'instructions' => 'Prevent this site from being indexed by search engines.',
                    'listable' => 'hidden',
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
                    'width' => 50,
                ],
            ],
        ];
    }
}
