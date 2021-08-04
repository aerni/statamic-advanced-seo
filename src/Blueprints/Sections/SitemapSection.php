<?php

namespace Aerni\AdvancedSeo\Blueprints\Sections;

use Aerni\AdvancedSeo\Contracts\BlueprintSection;

class SitemapSection implements BlueprintSection
{
    public function contents(): array
    {
        $fields = $this->fields();

        if (empty($fields)) {
            return [];
        }

        return [
            'display' => 'Sitemap',
            'fields' => $this->fields(),
        ];
    }

    public function fields(): array
    {
        $fields = collect();

        $fields->push($this->sitemapSection());

        return $fields->flatten(1)->toArray();
    }

    protected function sitemapSection(): array
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
