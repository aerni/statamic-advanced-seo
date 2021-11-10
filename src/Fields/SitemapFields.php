<?php

namespace Aerni\AdvancedSeo\Fields;

class SitemapFields extends BaseFields
{
    public function sections(): array
    {
        return [
            $this->sitemap(),
        ];
    }

    protected function sitemap(): array
    {
        return [
            [
                'handle' => 'section_collections',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Select the collections and taxonomies you want to `exclude` from your sitemap.',
                    'display' => 'Excluded Content',
                ],
            ],
            [
                'handle' => 'excluded_collections',
                'field' => [
                    'mode' => 'stack',
                    'type' => 'collections',
                    'listable' => 'hidden',
                    'display' => 'Collections',
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
                    'localizable' => true,
                    'width' => 50,
                ],
            ],
        ];
    }
}
