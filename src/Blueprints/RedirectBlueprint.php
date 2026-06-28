<?php

namespace Aerni\AdvancedSeo\Blueprints;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Statamic\Facades\Site;

class RedirectBlueprint extends BaseBlueprint
{
    protected ?string $exceptId = null;

    public function forRedirect(?Redirect $redirect = null): static
    {
        $this->exceptId = $redirect?->id();

        return $this;
    }

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
        $uniqueRule = $this->exceptId
            ? "new \\Aerni\\AdvancedSeo\\Rules\\UniqueRedirectSource('{$this->exceptId}')"
            : 'new \\Aerni\\AdvancedSeo\\Rules\\UniqueRedirectSource()';

        return [
            'fields' => [
                [
                    'handle' => 'source',
                    'field' => [
                        'type' => 'text',
                        'display' => __('advanced-seo::fields.redirect_source.display'),
                        'instructions' => __('advanced-seo::fields.redirect_source.instructions'),
                        'validate' => ['required', 'new \\Aerni\\AdvancedSeo\\Rules\\ValidRedirectSource()', $uniqueRule],
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
        ];
    }

    protected function sidebar(): array
    {
        $fields = [
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
