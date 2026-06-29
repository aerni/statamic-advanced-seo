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
        ];
    }

    protected function redirect(): array
    {
        $fields = [
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
                    'validate' => [
                        'sometimes',
                        'required',
                        'new \\Aerni\\AdvancedSeo\\Rules\\ValidRedirectDestination()',
                        'new \\Aerni\\AdvancedSeo\\Rules\\PublishedRedirectDestination()',
                    ],
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
        ];

        if (Site::multiEnabled()) {
            $fields[] = [
                'handle' => 'site',
                'field' => [
                    'type' => 'hidden',
                    'default' => Site::selected()->handle(),
                    'validate' => ['required'],
                ],
            ];
        }

        return ['fields' => $fields];
    }
}
