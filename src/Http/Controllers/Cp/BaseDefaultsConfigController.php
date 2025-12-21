<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\GetAuthorizedSites;
use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\User;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Sites\Site;

abstract class BaseDefaultsConfigController extends CpController
{
    abstract protected function type(): string;

    public function edit(Request $request, string $handle, Site $site)
    {
        $defaults = Defaults::firstWhere('id', "{$this->type()}::{$handle}");

        // The global feature enabled state. e.g. used by site defaults like favicons.
        // TODO: Might be able to get rid of it at some point. We already determine enabled state per locale for collections/taxonomies now.
        throw_unless($defaults['enabled'] ?? false, new NotFoundHttpException);

        $set = $defaults['set']->ensureLocalizations();

        $this->authorize('configure', [SeoDefaultSet::class, $set, $site]);

        throw_unless($set->availableInSite($site->handle()), new NotFoundHttpException);

        $localization = $set->in($site->handle());

        $blueprint = static::editFormBlueprint($localization);

        throw_unless($blueprint->fields()->items()->count(), NotFoundHttpException::class);

        [$values, $meta] = $this->extractFromFields($localization, $blueprint);

        $viewData = [
            'title' => "Configure {$defaults['title']}",
            'icon' => 'cog',
            'blueprint' => $blueprint->toPublishArray(),
            'initialReference' => $localization->reference(),
            'initialValues' => $values,
            'initialMeta' => $meta,
            'initialSite' => $site->handle(),
            'initialLocalizations' => GetAuthorizedSites::handle($set)
                ->map(fn ($site) => [
                    'handle' => $site->handle(),
                    'name' => $site->name(),
                    'active' => $site->handle() === $localization->locale(),
                    'url' => $set->in($site->handle())->configUrl(),
                ])->values(),
            'initialLocalizedFields' => $localization->config()->data()->keys()->all(),
            'initialConfigUrl' => $localization->configUrl(),
            // TODO: Probably should make readOnly also reactive.
            'readOnly' => User::current()->cant('configure', [SeoDefaultSet::class, $set, $site]),
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return Inertia::render('advanced-seo::'.ucfirst($this->type()).'/Config', $viewData);
    }

    public function update(Request $request, string $handle, Site $site): void
    {
        $defaults = Defaults::firstWhere('id', "{$this->type()}::{$handle}");

        $set = $defaults['set']->ensureLocalization($site);

        $this->authorize('configure', [SeoDefaultSet::class, $set, $site]);

        throw_unless($set->availableInSite($site->handle()), new NotFoundHttpException);

        $localization = $set->in($site->handle());

        $fields = static::editFormBlueprint($localization)->fields()->addValues($request->all());

        // TODO: Should we abort here if the blueprint doesn't have any items? Same as in the edit() method?

        $fields->validate();

        $values = $fields->process()->values();

        $localization
            ->origin($values->get('origin'))
            ->config()->merge($values->all());

        $localization->save();
    }

    protected function extractFromFields(SeoVariables $localization, Blueprint $blueprint): array
    {
        $fields = $blueprint
            ->fields()
            ->addValues($localization->config()->data()->all())
            ->preProcess();

        return [$fields->values()->all(), $fields->meta()->all()];
    }

    // TODO: Make this an actual blueprint class.
    public static function editFormBlueprint(SeoVariables $localization): Blueprint
    {
        $fields = [];

        if ($localization->type() !== 'site') {
            $fields['enabled'] = [
                'display' => __('Enabled'),
                'fields' => [
                    'enabled' => [
                        'display' => __('Enabled'),
                        'instructions' => __('You may disable SEO processing for this site.'),
                        'type' => 'toggle',
                        'default' => true,
                    ],
                ],
            ];
        }

        /**
         * TODO: Add a custom condition to hide the field if there are no origins to select
         * https://v6.statamic.dev/control-panel/conditional-fields#custom-logic
         */
        $fields['origin'] = [
            'display' => __('Origin'),
            'fields' => [
                'origin' => [
                    'display' => __('Origin'),
                    'instructions' => __('Values will be inherited from the selected site.'),
                    'type' => 'origin',
                    // There is no 'enabled' field for site defaults. This ensures we don't break the blueprint.
                    'if' => array_filter([
                        'enabled' => 'true',
                    ], fn () => $localization->type() !== 'site'),
                ],
            ],
        ];

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
            ->setParent($localization)
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
