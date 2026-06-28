<?php

namespace Aerni\AdvancedSeo\Query\Scopes\Filters;

use Statamic\Facades\Site;
use Statamic\Query\Scopes\Filter;

class RedirectSite extends Filter
{
    protected static $handle = 'redirect_site';

    public static function title(): string
    {
        return __('Site');
    }

    protected function fieldItems(): array
    {
        return [
            'site' => [
                'display' => __('Site'),
                'type' => 'radio',
                'options' => Site::authorized()->mapWithKeys(
                    fn ($site) => [$site->handle() => $site->name()]
                )->all(),
            ],
        ];
    }

    public function autoApply(): array
    {
        return ['site' => Site::selected()->handle()];
    }

    public function apply($query, $values): void
    {
        $query->where('site', $values['site']);
    }

    public function badge($values): string
    {
        $site = Site::authorized()->first(fn ($site) => $site->handle() === $values['site']);

        return 'Site: '.($site?->name() ?? $values['site']);
    }

    public function visibleTo($key): bool
    {
        return $key === 'redirects' && Site::multiEnabled();
    }
}
