<?php

namespace Aerni\AdvancedSeo\Query\Scopes\Filters;

use Statamic\Query\Scopes\Filter;

class ResponseCode extends Filter
{
    protected static $handle = 'redirect_response_code';

    public static function title(): string
    {
        return __('advanced-seo::fields.redirect_response_code.display');
    }

    protected function fieldItems(): array
    {
        return [
            'response_code' => [
                'display' => __('advanced-seo::fields.redirect_response_code.display'),
                'type' => 'radio',
                'options' => [
                    '301' => __('advanced-seo::fields.redirect_response_code.option_301'),
                    '302' => __('advanced-seo::fields.redirect_response_code.option_302'),
                    '410' => __('advanced-seo::fields.redirect_response_code.option_410'),
                ],
            ],
        ];
    }

    public function apply($query, $values): void
    {
        $query->where('response_code', (int) $values['response_code']);
    }

    public function badge($values): string
    {
        return __('advanced-seo::fields.redirect_response_code.display').': '.__('advanced-seo::fields.redirect_response_code.option_'.$values['response_code']);
    }

    public function visibleTo($key): bool
    {
        return $key === 'redirects';
    }
}
