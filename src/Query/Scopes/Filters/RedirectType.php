<?php

namespace Aerni\AdvancedSeo\Query\Scopes\Filters;

use Statamic\Query\Scopes\Filter;

class RedirectType extends Filter
{
    protected static $handle = 'redirect_type';

    public static function title(): string
    {
        return __('Type');
    }

    protected function fieldItems(): array
    {
        return [
            'type' => [
                'display' => __('Type'),
                'type' => 'radio',
                'options' => [
                    '301' => __('advanced-seo::fields.redirect_type.option_301'),
                    '302' => __('advanced-seo::fields.redirect_type.option_302'),
                    '410' => __('advanced-seo::fields.redirect_type.option_410'),
                ],
            ],
        ];
    }

    public function apply($query, $values): void
    {
        $query->where('type', (int) $values['type']);
    }

    public function badge($values): string
    {
        return 'Type: '.__('advanced-seo::fields.redirect_type.option_'.$values['type']);
    }

    public function visibleTo($key): bool
    {
        return $key === 'redirects';
    }
}
