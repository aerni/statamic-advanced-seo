<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Events\SeoDefaultSetSaved;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Statamic\CP\Column;
use Statamic\Exceptions\AuthorizationException;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;

abstract class BaseDefaultsController extends CpController
{
    abstract protected function type(): string;

    abstract protected function icon(): string;

    public function index(): Response
    {
        $this->authorize('index', [SeoVariables::class, $this->type()]);

        return Inertia::render('advanced-seo::' . ucfirst($this->type()) . '/Index', [
            'title' => __("advanced-seo::messages.{$this->type()}"),
            'icon' => $this->icon(),
            'items' => $this->items(),
            'columns' => [
                Column::make('title')->label(__('Title')),
                Column::make('status')->label(__('Status')),
            ],
        ]);
    }

    public function edit(Request $request, string $handle): mixed
    {
        throw_unless(Defaults::isEnabled("{$this->type()}::{$handle}"), new NotFoundHttpException);

        $defaults = Defaults::firstWhere('id', "{$this->type()}::{$handle}");

        $set = $defaults['set'];

        $site = $request->site?->handle() ?? Site::selected()->handle();

        throw_unless($this->canAccessEditView($set, $site), new AuthorizationException());

        // Create a localization for each of the provided sites. This triggers a save on the set.
        // TODO: Do we really need to create the localizations or can we simply ensure them with ensureLocalizations()?
        // Ensuring wouldn't save them to file. But maybe we don't even have to do that?
        // TODO: Probably don't need to pass the sites anymore as we are getting those in the seoDefaultsSet now.
        $set = $set->createLocalizations($set->sites());

        $localization = $set->in($site);

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
            'initialSite' => $site,
            'initialHasOrigin' => $hasOrigin,
            'initialOriginValues' => $originValues ?? null,
            'initialOriginMeta' => $originMeta ?? null,
            'initialLocalizations' => $this->authorizedSites($set)->map(function ($site) use ($localization, $set, $requestLocalization) {
                $localization = $set->in($site);
                $exists = $localization !== null;

                if (! $localization->enabled()) {
                    return;
                }

                return [
                    'handle' => $site,
                    'name' => Site::get($site)->name(),
                    'active' => $site === $requestLocalization->locale(),
                    'exists' => $exists,
                    'published' => true,
                    'root' => $exists ? $localization->isRoot() : false,
                    'origin' => $exists ? $localization->locale() === optional($requestLocalization->origin())->locale() : null,
                    'url' => $exists ? $localization->editUrl() : null,
                ];
            })->filter()->values()->all(),
            'initialLocalizedFields' => $localization->data()->keys()->all(),
            'initialConfigUrl' => $localization->configUrl(),
            'readOnly' => User::current()->cant('edit', [SeoVariables::class, $set]),
            'configurable' => User::current()->can('configure', [SeoVariables::class, $set]),
            'action' => $localization->updateUrl(),
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return Inertia::render('advanced-seo::SeoDefaults/Edit', $viewData);
    }

    public function update(Request $request, string $handle): void
    {
        $set = $this->set($handle);

        $this->authorize('edit', [SeoVariables::class, $set]);

        $site = $request->site ?? Site::selected()->handle();

        $localization = $set->in($site);

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

    protected function set(string $handle): SeoDefaultSet
    {
        return Seo::findOrMake($this->type(), $handle);
    }

    protected function extractFromFields(SeoVariables $localization, Blueprint $blueprint): array
    {
        $fields = $blueprint
            ->fields()
            ->addValues($localization->values()->all())
            ->preProcess();

        return [$fields->values()->all(), $fields->meta()->all()];
    }

    protected function authorizedSites(SeoDefaultSet $set): Collection
    {
        return $set->sites()->intersect(Site::authorized());
    }

    protected function items(): Collection
    {
        return Defaults::enabledInType($this->type())
            ->filter(fn ($default) => $default['set']->availableInSite(Site::selected()->handle()))
            ->filter(fn ($default) => User::current()->can('edit', [SeoVariables::class, $default['set']]))
            ->map(fn ($default) => [
                ...$default,
                'configurable' => User::current()->can('configure', [SeoVariables::class, $default['set']]),
                'edit_url' => $default['set']->in(Site::selected()->handle())?->editUrl(),
                'config_url' => $default['set']->configUrl(),
                'enabled_in_selected_site' => $default['set']->in(Site::selected()->handle())?->enabled() ?? false,
                'available_in_selected_site' => $this->availableInSelectedSite($default['set']),
            ])
            ->values();
    }

    // A site is unavailable to the user if it's disabled or the user doesn't have permissions to view the site.
    protected function availableInSelectedSite(SeoDefaultSet $set): bool
    {
        $localization = $set->in(Site::selected()->handle());

        if (! $localization) {
            return false;
        }

        return User::current()->can('view', $localization->site());
    }

    protected function canAccessEditView(SeoDefaultSet $set, string $site): bool
    {
        // User can't access if they don't have edit permission
        if (! User::current()->can('edit', [SeoVariables::class, $set])) {
            return false;
        }

        // User can't access if they don't have permission to view the requested site
        if (! Site::authorized()->contains($site)) {
            return false;
        }

        // User can't access if the set isn't available in the requested site
        if (! $set->availableInSite($site)) {
            return false;
        }

        // User can't access if the set is disabled for the requested site
        return $set->in($site)->enabled();
    }
}
