<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Contracts\SeoSet as SeoSetContract;
use Aerni\AdvancedSeo\Contracts\SeoSetConfig;
use Aerni\AdvancedSeo\Data\SeoSet;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Statamic\CP\PublishForm;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;

abstract class BaseDefaultsConfigController extends CpController
{
    abstract protected function type(): string;

    public function edit(SeoSet $seoSet): PublishForm
    {
        $this->authorize('configure', [SeoSetContract::class, $seoSet]);

        $config = $seoSet->config();

        return PublishForm::make(static::editFormBlueprint($config))
            ->parent($seoSet)
            ->asConfig()
            ->icon('cog')
            ->title("Configure {$seoSet->title}")
            ->values([
                'enabled' => $config->enabled(),
                'origins' => $config->origins(),
            ])
            ->submittingTo($config->editUrl());
    }

    public function update(Request $request, SeoSet $seoSet): void
    {
        $this->authorize('configure', [SeoSetContract::class, $seoSet]);

        $config = $seoSet->config();

        $values = PublishForm::make(static::editFormBlueprint($config))
            ->submit($request->all());

        if ($seoSet->type() !== 'site') {
            $config->enabled(Arr::get($values, 'enabled'));
        }

        /**
         * We save the seoSet instead of the config directly as the set also manages localizations.
         * E.g. if the seoSet was disabled the localizations get deleted.
         */
        $config
            ->origins(Arr::get($values, 'origins'))
            ->seoSet()
            ->save();
    }

    // TODO: Make this an actual blueprint class.
    public static function editFormBlueprint(SeoSetConfig $config): Blueprint
    {
        $fields = [];

        if ($config->type() !== 'site') {
            $fields['enabled'] = [
                'display' => __('Enabled'),
                'fields' => [
                    'enabled' => [
                        'display' => __('Enabled'),
                        'instructions' => __('Choose to enable/disable SEO processing for this item.'),
                        'type' => 'toggle',
                        'default' => true,
                    ],
                ],
            ];
        }

        // TODO: If there is only one site, we should hide the field. But make sure we always save.
        // Else, a user with only access to one site will override the configuration of a user with access to more sites.
        $fields['origins'] = [
            'display' => __('Origins'),
            'fields' => [
                'origins' => [
                    'display' => __('Origins'),
                    'instructions' => __('Choose to inherit values from the selected origin.'),
                    'type' => 'default_set_sites',
                    // There is no 'enabled' field for site defaults. This ensures we don't break the blueprint.
                    'if' => array_filter([
                        'enabled' => 'true',
                    ], fn () => $config->type() !== 'site'),
                ],
            ],
        ];

        /**
         * TODO: Add a custom condition to hide the field if there are no origins to select
         * https://v6.statamic.dev/control-panel/conditional-fields#custom-logic
         */
        // $fields['origin'] = [
        //     'display' => __('Origin'),
        //     'fields' => [
        //         'origin' => [
        //             'display' => __('Origin'),
        //             'instructions' => __('Values will be inherited from the selected site.'),
        //             'type' => 'origin',
        //             // There is no 'enabled' field for site defaults. This ensures we don't break the blueprint.
        //             'if' => array_filter([
        //                 'enabled' => 'true',
        //             ], fn () => $set->type() !== 'site'),
        //         ],
        //     ],
        // ];

        // TODO: Bring these fields back later.
        // $fields['indexing'] = [
        //     'display' => __('Indexing'),
        //     'fields' => [
        //         'noindex' => [
        //             'type' => 'toggle',
        //             'display' => 'Noindex',
        //             'instructions' => 'Prevent your site from being indexed by search engines.',
        //             'default' => Defaults::data('SiteFacade::indexing')->get('noindex'),
        //             'listable' => 'hidden',
        //             'localizable' => true,
        //             'width' => 50,
        //             'if' => [
        //                 'enabled' => 'true',
        //             ],
        //         ],
        //         'nofollow' => [
        //             'type' => 'toggle',
        //             'display' => 'Nofollow',
        //             'instructions' => 'Prevent site crawlers from following any links on your site.',
        //             'default' => Defaults::data('SiteFacade::indexing')->get('nofollow'),
        //             'listable' => 'hidden',
        //             'localizable' => true,
        //             'width' => 50,
        //             'if' => [
        //                 'enabled' => 'true',
        //             ],
        //         ],
        //         'sitemap' => [
        //             'type' => 'toggle',
        //             'display' => 'Sitemap',
        //             'instructions' => 'Enable the sitemap for this collection.',
        //             'default' => true,
        //             'listable' => 'hidden',
        //             'localizable' => true,
        //             'width' => 50,
        //             'if' => [
        //                 'enabled' => 'true',
        //                 'noindex' => 'false',
        //             ],
        //         ],
        //     ],
        // ];

        return \Statamic\Facades\Blueprint::make()
            ->setContents(collect([
                'tabs' => [
                    'main' => [
                        'sections' => collect($fields)->map(fn ($section) => [
                            'display' => $section['display'],
                            'instructions' => $section['instructions'] ?? null,
                            'fields' => collect($section['fields'])->map(fn ($field, $handle) => [
                                'handle' => $handle,
                                'field' => $field,
                            ])->values()->all(),
                        ])->values()->all(),
                    ],
                ],
            ])
                ->all());
    }
}
