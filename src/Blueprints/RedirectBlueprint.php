<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Statamic\Facades\Site;

class RedirectBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'redirect';
    }

    protected function tabs(): array
    {
        $sidebarFields = [
            [
                'handle' => 'enabled',
                'field' => [
                    'type' => 'toggle',
                    'display' => __('advanced-seo::fields.redirect_enabled.display'),
                    'instructions' => __('advanced-seo::fields.redirect_enabled.instructions'),
                    'default' => true,
                ],
            ],
        ];

        if (Site::multiEnabled()) {
            $sidebarFields[] = [
                'handle' => 'site',
                'field' => [
                    'type' => 'select',
                    'display' => __('advanced-seo::fields.redirect_site.display'),
                    'options' => Site::all()->mapWithKeys(fn ($site) => [$site->handle() => $site->name()])->all(),
                    'default' => Site::default()->handle(),
                    'clearable' => false,
                    'validate' => ['required'],
                ],
            ];
        }

        return [
            'main' => [
                'display' => __('advanced-seo::messages.redirect_tab_main'),
                'sections' => [
                    [
                        'fields' => [
                            [
                                'handle' => 'source',
                                'field' => [
                                    'type' => 'text',
                                    'display' => __('advanced-seo::fields.redirect_source.display'),
                                    'instructions' => __('advanced-seo::fields.redirect_source.instructions'),
                                    'validate' => ['required'],
                                    'width' => 50,
                                ],
                            ],
                            [
                                'handle' => 'destination',
                                'field' => [
                                    'type' => 'link',
                                    'display' => __('advanced-seo::fields.redirect_destination.display'),
                                    'instructions' => __('advanced-seo::fields.redirect_destination.instructions'),
                                    'validate' => ['required_unless:type,410'],
                                    'width' => 50,
                                    'if' => ['type' => 'isnt 410'],
                                ],
                            ],
                            [
                                'handle' => 'type',
                                'field' => [
                                    'type' => 'select',
                                    'display' => __('advanced-seo::fields.redirect_type.display'),
                                    'instructions' => __('advanced-seo::fields.redirect_type.instructions'),
                                    'options' => [
                                        301 => __('advanced-seo::fields.redirect_type.option_301'),
                                        302 => __('advanced-seo::fields.redirect_type.option_302'),
                                        410 => __('advanced-seo::fields.redirect_type.option_410'),
                                    ],
                                    'default' => 301,
                                    'clearable' => false,
                                    'validate' => ['required'],
                                    'width' => 50,
                                ],
                            ],
                            [
                                'handle' => 'description',
                                'field' => [
                                    'type' => 'textarea',
                                    'display' => __('advanced-seo::fields.redirect_description.display'),
                                    'width' => 100,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sidebar' => [
                'sections' => [
                    [
                        'fields' => $sidebarFields,
                    ],
                ],
            ],
        ];
    }
}
