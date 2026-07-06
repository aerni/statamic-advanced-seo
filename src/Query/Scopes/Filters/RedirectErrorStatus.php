<?php

namespace Aerni\AdvancedSeo\Query\Scopes\Filters;

use Aerni\AdvancedSeo\Enums\RedirectErrorStatus as Status;
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
                'options' => collect(Status::cases())
                    ->mapWithKeys(fn (Status $status) => [$status->value => $status->label()])
                    ->all(),
            ],
        ];
    }

    public function apply($query, $values): void
    {
        // Status is derived from redirects, so the controller filters it in memory.
    }

    public function badge($values): string
    {
        return __('advanced-seo::messages.redirect_error_redirect').': '.Status::from($values['status'])->label();
    }

    public function visibleTo($key): bool
    {
        return $key === 'redirect-errors';
    }
}
