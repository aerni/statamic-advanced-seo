<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\RemoveSeoValues;
use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Registries\Defaults;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Statamic\CP\PublishForm;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;

abstract class BaseDefaultsConfigController extends CpController
{
    abstract protected function type(): string;

    public function edit(string $handle)
    {
        $defaults = Defaults::find("{$this->type()}::{$handle}");

        throw_unless($defaults, new NotFoundHttpException);

        $set = $defaults->set();

        $this->authorize('configure', [SeoDefaultSet::class, $set]);

        $values = [
            'enabled' => $set->enabled(),
            'origins' => $set->origins(),
        ];

        return PublishForm::make(static::editFormBlueprint($set))
            ->parent($set)
            ->asConfig()
            ->icon('cog')
            ->title("Configure {$defaults->title}")
            ->values($values) // TODO: Don't use data. What does Collection do here?
            ->submittingTo($set->editUrl());
    }

    public function update(Request $request, string $handle): void
    {
        $defaults = Defaults::find("{$this->type()}::{$handle}");

        throw_unless($defaults, new NotFoundHttpException);

        $set = $defaults->set();

        $this->authorize('configure', [SeoDefaultSet::class, $set]);

        $values = PublishForm::make(static::editFormBlueprint($set))
            ->submit($request->all());

        if ($set->type() !== 'site') {
            $set->enabled(Arr::get($values, 'enabled'));
        }

        $set
            ->origins(Arr::get($values, 'origins'))
            ->save();
    }

    // TODO: Make this an actual blueprint class.
    public static function editFormBlueprint(SeoDefaultSet $set): Blueprint
    {
        $fields = [];

        if ($set->type() !== 'site') {
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
                    // 'visibility' => GetAuthorizedSites::handle($set)->count() > 1 ? 'visible' : 'hidden',
                    // There is no 'enabled' field for site defaults. This ensures we don't break the blueprint.
                    'if' => array_filter([
                        'enabled' => 'true',
                    ], fn () => $set->type() !== 'site'),
                    // 'if' => array_filter([
                    //     'enabled' => 'true',
                    // ], fn () => $set->type() !== 'site'),
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
