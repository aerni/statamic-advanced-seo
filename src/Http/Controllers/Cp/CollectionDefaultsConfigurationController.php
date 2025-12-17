<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Inertia\Inertia;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Illuminate\Http\Request;
use Statamic\Fields\Blueprint;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Statamic\Http\Controllers\CP\CpController;

class CollectionDefaultsConfigurationController extends CpController
{
    public function edit(Request $request, string $handle)
    {
        $defaults = Defaults::firstWhere('id', "{$this->type()}::{$handle}");

        $set = $defaults['set'];

        $site = $request->site?->handle() ?? Site::selected()->handle();

        // Implement this or a similar guard.
        // if (! $set->availableInSite($site)) {
        //     return $this->redirectToIndex($set, $site);
        // }

        $this->authorize('edit', [SeoVariables::class, $set]);

        $set = $set->createLocalizations($set->sites());

        $localization = $set->in($site);

        $blueprint = $this->editFormBlueprint($localization);

        [$values, $meta] = $this->extractFromFields($localization, $blueprint);

        $viewData = [
            'title' => "Configure {$defaults['title']} Defaults",
            'icon' => 'cog',
            'blueprint' => $blueprint->toPublishArray(),
            'initialReference' => $localization->reference(),
            'initialValues' => $values,
            'initialMeta' => $meta,
            'initialSite' => $site,
            'initialLocalizations' => $set->sites()
                ->intersect(Site::authorized())
                ->map(fn ($site) => [
                    'handle' => $site,
                    'name' => Site::get($site)->name(),
                    'active' => $site === $localization->locale(),
                    'url' => $set->in($site)->configUrl(),
                ]),
            'initialLocalizedFields' => $localization->config()->data()->keys()->all(),
            'readOnly' => User::current()->cant('edit', [SeoVariables::class, $set]),
            'action' => cp_route('advanced-seo.collections.config.update', [$set->handle(), $site])
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return Inertia::render('advanced-seo::Collections/Config', $viewData);
    }

    public function update(Request $request, string $handle): void
    {
        $defaults = Defaults::firstWhere('id', "{$this->type()}::{$handle}");

        $set = $defaults['set'];

        $this->authorize('edit', [SeoVariables::class, $set]);

        $site = $request->site ?? Site::selected()->handle();

        $localization = $set->in($site);

        $fields = $this->editFormBlueprint($localization)->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        $localization
            ->origin($values->get('origin'))
            ->config()->merge($values->all());

        $localization->save();
    }

    protected function type(): string
    {
        $segments = request()->segments();
        $key = array_search('advanced-seo', $segments) + 1;

        return $segments[$key];
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
    protected function editFormBlueprint(SeoVariables $localization)
    {
        $fields = [
            'enabled' => [
                'display' => __('Enabled'),
                'fields' => [
                    'enabled' => [
                        'display' => __('Enabled'),
                        'instructions' => __('You may disable SEO processing for this site.'),
                        'type' => 'toggle',
                        'default' => true,
                    ],
                ],
            ],
        ];

        // TODO: This should probably also filter out any sites a user isn't authorized for.
        if ($localization->sites()->count() > 1) {
            $fields['origin'] = [
                'display' => __('Origin'),
                'fields' => [
                    'origin' => [
                        'display' => __('Origin'),
                        'instructions' => __('Values will be inherited from the selected site.'),
                        'type' => 'origin',
                        'if' => [
                            'enabled' => 'true',
                        ],
                    ],
                ],
            ];
        }

        // TODO: Bring these fields back later.
        // $fields['indexing'] = [
        //     'display' => __('Indexing'),
        //     'fields' => [
        //         'noindex' => [
        //             'type' => 'toggle',
        //             'display' => 'Noindex',
        //             'instructions' => 'Prevent your site from being indexed by search engines.',
        //             'default' => Defaults::data('site::indexing')->get('noindex'),
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
        //             'default' => Defaults::data('site::indexing')->get('nofollow'),
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
