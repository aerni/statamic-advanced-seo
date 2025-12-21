<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\GetAuthorizedSites;
use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Events\SeoDefaultSetSaved;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Statamic\CP\Column;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site as SiteFacade;
use Statamic\Facades\User;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Sites\Site;

abstract class BaseDefaultsController extends CpController
{
    abstract protected function type(): string;

    abstract protected function icon(): string;

    public function index(): Response
    {
        $this->authorize('viewAny', [SeoDefaultSet::class, $this->type()]);

        $site = SiteFacade::selected();

        $items = Defaults::enabledInType($this->type())
            ->filter(fn ($default) => User::current()->can('edit', [SeoDefaultSet::class, $default['set'], $site]))
            ->each(fn ($default) => $default['set']->ensureLocalizations()) // TODO: Should we ensure somewhere else? Maybe in the Defaults model class?
            ->filter(fn ($default) => $default['set']->availableInSite($site))
            ->map(fn ($default) => [
                ...$default,
                'enabled' => $default['set']->in($site->handle())->enabled(),
                'configurable' => $this->isConfigurable($default['set'], $site),
                'edit_url' => $default['set']->editUrl(),
                'config_url' => $default['set']->configUrl(),
            ])
            ->values();

        return Inertia::render('advanced-seo::'.ucfirst($this->type()).'/Index', [
            'title' => __("advanced-seo::messages.{$this->type()}"),
            'icon' => $this->icon(),
            'items' => $items,
            'columns' => [
                Column::make('title')->label(__('Title')),
                Column::make('status')->label(__('Status')),
            ],
        ]);
    }

    public function edit(Request $request, string $handle, Site $site): mixed
    {
        $defaults = Defaults::firstWhere('id', "{$this->type()}::{$handle}");

        // The global feature enabled state. e.g. used by site defaults like favicons.
        // TODO: Might be able to get rid of it at some point. We already determine enabled state per locale for collections/taxonomies now.
        throw_unless($defaults['enabled'] ?? false, new NotFoundHttpException);

        $set = $defaults['set']->ensureLocalizations();

        $this->authorize('edit', [SeoDefaultSet::class, $set, $site]);

        throw_unless($set->availableInSite($site->handle()), new NotFoundHttpException);

        $localization = $set->in($site->handle());

        throw_unless($localization->enabled(), new NotFoundHttpException);

        $blueprint = $localization->blueprint();

        [$values, $meta] = $this->extractFromFields($localization, $blueprint);

        if ($hasOrigin = $localization->hasOrigin()) {
            [$originValues, $originMeta] = $this->extractFromFields($localization->origin(), $blueprint);
        }

        // This variable solely exists to prevent variable conflict in $viewData['localizations'].
        $requestLocalization = $localization;

        $viewData = [
            'title' => $defaults['title'],
            'icon' => $defaults['icon'],
            'blueprint' => $blueprint->toPublishArray(),
            'initialReference' => $localization->reference(),
            'initialValues' => $values,
            'initialMeta' => $meta,
            'initialSite' => $site->handle(),
            'initialHasOrigin' => $hasOrigin,
            'initialOriginValues' => $originValues ?? null,
            'initialOriginMeta' => $originMeta ?? null,
            'initialLocalizations' => GetAuthorizedSites::handle($set)->map(function ($site) use ($localization, $set, $requestLocalization) {
                $localization = $set->in($site->handle());

                if (! $localization->enabled()) {
                    return;
                }

                return [
                    'handle' => $site->handle(),
                    'name' => $site->name(),
                    'active' => $site->handle() === $requestLocalization->locale(),
                    'url' => $localization->editUrl(),
                ];
            })->filter()->values()->all(),
            'initialLocalizedFields' => $localization->data()->keys()->all(),
            'initialEditUrl' => $localization->editUrl(),
            'initialConfigUrl' => $localization->configUrl(),
            // TODO: Probably should make readOnly and configurable also reactive.
            'readOnly' => User::current()->cant('edit', [SeoDefaultSet::class, $set, $site]),
            'configurable' => $this->isConfigurable($set, $site),
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return Inertia::render('advanced-seo::'.ucfirst($this->type()).'/Edit', $viewData);
    }

    public function update(Request $request, string $handle, Site $site): void
    {
        $set = Seo::findOrMake($this->type(), $handle)->ensureLocalization($site);

        $this->authorize('edit', [SeoDefaultSet::class, $set, $site]);

        throw_unless($set->availableInSite($site->handle()), new NotFoundHttpException);

        $localization = $set->in($site->handle());

        throw_unless($localization->enabled(), new NotFoundHttpException);

        $blueprint = $localization->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        $localization->hasOrigin()
            ? $localization->data($values->only($request->input('_localized')))
            : $localization->merge($values);

        $localization = $localization->save();

        // TODO: We should probably dispatch this event in the save method of the SeoDefaultSet class or the repository.
        SeoDefaultSetSaved::dispatch($localization->seoSet());
    }

    protected function extractFromFields(SeoVariables $localization, Blueprint $blueprint): array
    {
        $fields = $blueprint
            ->fields()
            ->addValues($localization->values()->all())
            ->preProcess();

        return [$fields->values()->all(), $fields->meta()->all()];
    }

    protected function isConfigurable(SeoDefaultSet $set, Site $site): bool
    {
        if (User::current()->cant('configure', [SeoDefaultSet::class, $set, $site])) {
            return false;
        }

        return BaseDefaultsConfigController::editFormBlueprint($set->in($site->handle()))
            ->fields()->items()->isNotEmpty();
    }
}
