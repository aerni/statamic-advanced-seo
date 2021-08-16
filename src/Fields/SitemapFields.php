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
                'handle' => 'section_sitemap',
                'field' => [
                    'type' => 'section',
                    'instructions' => 'Configure your `sitemap.xml`',
                    'display' => 'Sitemap',
                ],
            ],
            [
                'handle' => 'sitemap_collections',
                'field' => [
                    'mode' => 'select',
                    'type' => 'collections',
                    'instructions' => 'Select the collections you want to include in your sitemap.',
                    'listable' => 'hidden',
                    'display' => 'Collections',
                    'width' => 50,
                ],
            ],
        ];
    }
}
