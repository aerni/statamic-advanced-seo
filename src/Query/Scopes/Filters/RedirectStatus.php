<?php

namespace Aerni\AdvancedSeo\Query\Scopes\Filters;

use Statamic\Query\Scopes\Filter;

class RedirectStatus extends Filter
{
    protected static $handle = 'redirect_status';

    public static function title(): string
    {
        return __('Status');
    }

    protected function fieldItems(): array
    {
        return [
            'enabled' => [
                'display' => __('Status'),
                'type' => 'radio',
                'options' => [
                    'true' => __('Enabled'),
                    'false' => __('Disabled'),
                ],
            ],
        ];
    }

    public function apply($query, $values): void
    {
        $query->where('enabled', $values['enabled'] === 'true');
    }

    public function badge($values): string
    {
        return 'Status: '.($values['enabled'] === 'true' ? __('Enabled') : __('Disabled'));
    }

    public function visibleTo($key): bool
    {
        return $key === 'redirects';
    }
}
