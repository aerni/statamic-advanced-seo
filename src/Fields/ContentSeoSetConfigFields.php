<?php

namespace Aerni\AdvancedSeo\Fields;

use Aerni\AdvancedSeo\Actions\EvaluateFeature;
use Aerni\AdvancedSeo\Features\Sitemap;

class ContentSeoSetConfigFields extends BaseFields
{
    protected function sections(): array
    {
        return [
            $this->enabled(),
            $this->origins(),
            $this->features(),
        ];
    }

    protected function enabled(): array
    {
        return [
            'display' => __('Enabled'),
            'fields' => [
                [
                    'handle' => 'enabled',
                    'field' => [
                        'type' => 'toggle',
                        'display' => __('Enabled'),
                        'instructions' => __("Enables SEO for this {$this->typePlaceholder()}."),
                        'default' => true,
                    ],
                ],
            ],
        ];
    }

    protected function origins(): array
    {
        return [
            'display' => __('Origins'),
            'fields' => [
                [
                    'handle' => 'origins',
                    'field' => [
                        'type' => 'default_set_sites',
                        'display' => __('Origins'),
                        'instructions' => __('Choose to inherit values from selected origins.'),
                        'default' => [],
                        'if' => ['enabled' => 'true'],
                    ],
                ],
            ],
        ];
    }

    protected function features(): array
    {
        $fields = [];

        if (EvaluateFeature::handle(Sitemap::class, $this->data)) {
            $fields[] = [
                'handle' => 'sitemap',
                'field' => [
                    'type' => 'toggle',
                    'display' => __('Sitemap'),
                    'instructions' => __("Enables the sitemap for this {$this->typePlaceholder()}."),
                    'default' => true,
                    'if' => ['enabled' => 'true'],
                    'feature' => Sitemap::class,
                ],
            ];
        }

        if (empty($fields)) {
            return [];
        }

        return [
            'display' => __('Features'),
            'fields' => $fields,
        ];
    }

    protected function typePlaceholder(): string
    {
        return $this->data->type === 'collections' 
            ? __('collection')
            : __('taxonomy');
    }
}
