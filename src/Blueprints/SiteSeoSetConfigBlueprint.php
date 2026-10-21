<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Actions\GetAuthorizedSites;
use Aerni\AdvancedSeo\Features\MultiSite;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class SiteSeoSetConfigBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'site_config';
    }

    protected function tabs(): array
    {
        return [
            'main' => [
                $this->origins(),
                $this->hreflang(),
            ],
        ];
    }

    protected function origins(): array
    {
        return [
            'display' => __('advanced-seo::messages.origins'),
            'fields' => [
                [
                    'handle' => 'origins',
                    'field' => [
                        'type' => 'site_origins',
                        'display' => __('advanced-seo::messages.origins'),
                        'instructions' => __('advanced-seo::messages.origins_instructions'),
                        'default' => [],
                        'feature' => MultiSite::class,
                    ],
                ],
            ],
        ];
    }

    protected function hreflang(): array
    {
        return [
            'display' => __('advanced-seo::messages.hreflang'),
            'fields' => [
                [
                    'handle' => 'hreflang_x_default',
                    'field' => [
                        'type' => 'select',
                        'display' => __('advanced-seo::messages.hreflang_x_default'),
                        'instructions' => __('advanced-seo::messages.hreflang_x_default_instructions'),
                        'options' => $this->hreflangSites(),
                        'default' => Site::default()->handle(),
                        'clearable' => false,
                        'searchable' => true,
                        'multiple' => false,
                        'validate' => ['required'],
                        'feature' => MultiSite::class,
                    ],
                ],
            ],
        ];
    }

    protected function hreflangSites(): array
    {
        $sites = User::current() && ($set = $this->context?->seoSet())
            ? GetAuthorizedSites::handle($set)
            : Site::all();

        return $sites->mapWithKeys(fn ($site) => [$site->handle() => $site->name()])->all();
    }
}
