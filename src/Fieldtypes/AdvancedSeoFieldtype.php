<?php

namespace Aerni\AdvancedSeo\Fieldtypes;

use Aerni\AdvancedSeo\Fields\OnPageSeoFields;
use Illuminate\Support\Collection;
use Statamic\Fields\Fieldtype;

class AdvancedSeoFieldtype extends Fieldtype
{
    protected static $title = 'Advanced SEO';
    protected $icon = 'seo-search-graph';

    protected function options(): Collection
    {
        return collect(OnPageSeoFields::make()->items())
            ->map(fn ($field, $handle) => __("advanced-seo::fields.$handle.display"))
            ->mapWithKeys(fn ($display, $handle) => [$handle => "$display ($handle)"]);
    }

    protected function configFieldItems(): array
    {
        return [
            'field' => [
                'display' => __('advanced-seo::fieldtypes.advanced_seo.config.field.display'),
                'instructions' => __('advanced-seo::fieldtypes.advanced_seo.config.field.instructions'),
                'type' => 'select',
                'width' => 50,
                'options' => $this->options(),
            ],
        ];
    }
}
