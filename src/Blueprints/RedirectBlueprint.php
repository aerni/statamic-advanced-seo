<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Enums\RedirectType;
use Statamic\Facades\Site;

class RedirectBlueprint extends BaseBlueprint
{
    protected function handle(): string
    {
        return 'redirect';
    }

    protected function tabs(): array
    {
        return [
            'main' => [
                $this->redirect(),
            ],
            'sidebar' => [
                $this->sidebar(),
            ],
        ];
    }

    protected function redirect(): array
    {
        return [
            'fields' => [
                [
                    'handle' => 'source',
                    'field' => [
                        'type' => 'redirect_source',
                        'display' => __('advanced-seo::fields.redirect_source.display'),
                        'instructions' => __('advanced-seo::fields.redirect_source.instructions'),
                        'validate' => [
                            'required',
                            'new \\Aerni\\AdvancedSeo\\Rules\\ValidRedirectSource()',
                            'new \\Aerni\\AdvancedSeo\\Rules\\UniqueRedirectSource({site}, {id})',
                        ],
                    ],
                ],
                [
                    'handle' => 'destination',
                    'field' => [
                        'type' => 'link',
                        'display' => __('advanced-seo::fields.redirect_destination.display'),
                        'instructions' => __('advanced-seo::fields.redirect_destination.instructions'),
                        'select_across_sites' => true,
                        'validate' => ['sometimes', 'required'],
                        'if' => ['type' => 'isnt '.RedirectType::Gone->value],
                    ],
                ],
                [
                    'handle' => 'type',
                    'field' => [
                        'type' => 'select',
                        'display' => __('advanced-seo::fields.redirect_type.display'),
                        'instructions' => __('advanced-seo::fields.redirect_type.instructions'),
                        'options' => collect(RedirectType::cases())
                            ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
                            ->all(),
                        'default' => RedirectType::Permanent->value,
                        'clearable' => false,
                        'validate' => ['required'],
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
        ];
    }

    protected function sidebar(): array
    {
        $fields = [];

        if (Site::multiEnabled()) {
            $fields[] = [
                'handle' => 'site',
                'field' => [
                    'type' => 'select',
                    'display' => __('advanced-seo::fields.redirect_site.display'),
                    'options' => Site::authorized()->mapWithKeys(fn ($site) => [$site->handle() => $site->name()])->all(),
                    'default' => Site::selected()->handle(),
                    'clearable' => false,
                    'validate' => ['required'],
                ],
            ];
        }

        return ['fields' => $fields];
    }
}
