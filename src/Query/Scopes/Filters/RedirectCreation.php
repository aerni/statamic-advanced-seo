<?php

namespace Aerni\AdvancedSeo\Query\Scopes\Filters;

use Statamic\Query\Scopes\Filter;

class RedirectCreation extends Filter
{
    protected static $handle = 'redirect_creation';

    public static function title(): string
    {
        return __('advanced-seo::messages.redirect_creation');
    }

    protected function fieldItems(): array
    {
        return [
            'creation' => [
                'display' => __('advanced-seo::messages.redirect_creation'),
                'type' => 'radio',
                'options' => [
                    'automatic' => __('advanced-seo::messages.redirect_automatic'),
                    'manual' => __('advanced-seo::messages.redirect_manual'),
                ],
            ],
        ];
    }

    public function apply($query, $values): void
    {
        $query->where('automatic', $values['creation'] === 'automatic');
    }

    public function badge($values): string
    {
        $creation = $values['creation'] === 'automatic'
            ? __('advanced-seo::messages.redirect_automatic')
            : __('advanced-seo::messages.redirect_manual');

        return __('advanced-seo::messages.redirect_creation').': '.$creation;
    }

    public function visibleTo($key): bool
    {
        return $key === 'redirects';
    }
}
