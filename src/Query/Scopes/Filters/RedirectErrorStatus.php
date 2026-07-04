<?php

namespace Aerni\AdvancedSeo\Query\Scopes\Filters;

use Statamic\Query\Scopes\Filter;

class RedirectErrorStatus extends Filter
{
    protected static $handle = 'redirect_error_status';

    public static function title(): string
    {
        return __('advanced-seo::messages.redirect_error_redirect');
    }

    protected function fieldItems(): array
    {
        return [
            'status' => [
                'display' => __('advanced-seo::messages.redirect_error_redirect'),
                'type' => 'radio',
                'options' => [
                    'handled' => __('advanced-seo::messages.redirect_error_status_handled'),
                    'disabled' => __('advanced-seo::messages.redirect_error_status_disabled'),
                    'unhandled' => __('advanced-seo::messages.redirect_error_status_unhandled'),
                ],
            ],
        ];
    }

    public function apply($query, $values): void
    {
        // Status is derived from redirects, so the controller filters it in memory.
    }

    public function badge($values): string
    {
        return __('advanced-seo::messages.redirect_error_redirect').': '.__("advanced-seo::messages.redirect_error_status_{$values['status']}");
    }

    public function visibleTo($key): bool
    {
        return $key === 'redirect-errors';
    }
}
