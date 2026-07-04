<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Enums\ResponseCode;
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
                $this->main(),
            ],
            'sidebar' => [
                $this->sidebar(),
            ],
        ];
    }

    protected function main(): array
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
                        'new \\Aerni\\AdvancedSeo\\Rules\\NonCircularRedirectDestination()',
                    ],
                    'if' => ['response_code' => 'isnt '.ResponseCode::Gone->value],
                ],
            ],
            [
                'handle' => 'description',
                'field' => [
                    'type' => 'textarea',
                    'display' => __('advanced-seo::fields.redirect_description.display'),
                    'instructions' => __('advanced-seo::fields.redirect_description.instructions'),
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

    protected function sidebar(): array
    {
        return [
            'fields' => [
                [
                    'handle' => 'response_code',
                    'field' => [
                        'type' => 'select',
                        'display' => __('advanced-seo::fields.redirect_response_code.display'),
                        'instructions' => __('advanced-seo::fields.redirect_response_code.instructions'),
                        'options' => collect(ResponseCode::cases())
                            ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
                            ->all(),
                        'default' => ResponseCode::Permanent->value,
                        'clearable' => false,
                        'validate' => ['required'],
                    ],
                ],
                [
                    'handle' => 'preserve_query_string',
                    'field' => [
                        'type' => 'toggle',
                        'display' => __('advanced-seo::fields.redirect_preserve_query_string.display'),
                        'instructions' => __('advanced-seo::fields.redirect_preserve_query_string.instructions'),
                        'default' => true,
                        'if' => ['response_code' => 'isnt '.ResponseCode::Gone->value],
                    ],
                ],
            ],
        ];
    }
}
