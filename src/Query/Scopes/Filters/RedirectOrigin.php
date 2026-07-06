<?php

namespace Aerni\AdvancedSeo\Query\Scopes\Filters;

use Aerni\AdvancedSeo\Enums\Origin;
use Statamic\Query\Scopes\Filter;

class RedirectOrigin extends Filter
{
    protected static $handle = 'redirect_origin';

    public static function title(): string
    {
        return __('advanced-seo::messages.redirect_origin');
    }

    protected function fieldItems(): array
    {
        return [
            'origin' => [
                'display' => __('advanced-seo::messages.redirect_origin'),
                'type' => 'radio',
                'options' => collect(Origin::cases())
                    ->mapWithKeys(fn (Origin $origin) => [$origin->value => $origin->label()])
                    ->all(),
            ],
        ];
    }

    public function apply($query, $values): void
    {
        $query->where('origin', Origin::from($values['origin'])->value);
    }

    public function badge($values): string
    {
        return __('advanced-seo::messages.redirect_origin').': '.Origin::from($values['origin'])->label();
    }

    public function visibleTo($key): bool
    {
        return $key === 'redirects';
    }
}
