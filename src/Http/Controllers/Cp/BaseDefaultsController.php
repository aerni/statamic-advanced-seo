<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\GetAuthorizedSites;
use Aerni\AdvancedSeo\Contracts\SeoDefaultSet;
use Aerni\AdvancedSeo\Data\SeoDefault;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Registries\Defaults;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Statamic\CP\Column;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site as SiteFacade;
use Statamic\Facades\Site as Sites;
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
            ->filter(fn (SeoDefault $default) => User::current()->can('edit', [SeoDefaultSet::class, $default->set(), $site]))
            ->filter(fn (SeoDefault $default) => $default->set()->availableInSite($site))
            ->filter(fn (SeoDefault $default) => $this->canConfigure($default->set()) || $default->set()->enabled())
            ->map(fn (SeoDefault $default) => [
                ...$default->toArray(),
                'enabled' => $default->set()->enabled(),
                'configurable' => $this->canConfigure($default->set()),
                'edit_url' => $default->set()->in(Sites::selected()->handle())->editUrl(),
                'config_url' => $default->set()->editUrl(),
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
        $defaults = Defaults::first(fn ($row) => $row->id() === "{$this->type()}::{$handle}");

        $set = $defaults->set();

        // TODO: The global feature enabled state. e.g. used by site defaults like favicons.
        // Might be able to get rid of it at some point. We already determine enabled state per locale for collections/taxonomies now.
        throw_unless($defaults->enabled(), new NotFoundHttpException);
        throw_unless($set->enabled(), new NotFoundHttpException);
        throw_unless($set->availableInSite($site), new NotFoundHttpException);

        $this->authorize('edit', [SeoDefaultSet::class, $set, $site]);

        $localization = $set->in($site);

        $blueprint = $localization->blueprint();

        [$values, $meta] = $this->extractFromFields($localization, $blueprint);

        if ($hasOrigin = $localization->hasOrigin()) {
            [$originValues, $originMeta] = $this->extractFromFields($localization->origin(), $blueprint);
        }

        $viewData = [
            'title' => $defaults->title,
            'icon' => $defaults->icon,
            'blueprint' => $blueprint->toPublishArray(),
            'initialReference' => $localization->reference(),
            'initialValues' => $values,
            'initialMeta' => $meta,
            'initialSite' => $site->handle(),
            'initialHasOrigin' => $hasOrigin,
            'initialOriginValues' => $originValues ?? null,
            'initialOriginMeta' => $originMeta ?? null,
            'initialLocalizations' => GetAuthorizedSites::handle($set)
                ->map(fn ($site) => [
                    'handle' => $site->handle(),
                    'name' => $site->name(),
                    'active' => $site->handle() === $localization->locale(),
                    'url' => $set->in($site)->editUrl(),
                ])->filter()->values()->all(),
            'initialLocalizedFields' => $localization->data()->keys()->all(),
            'initialEditUrl' => $localization->editUrl(),
            'configUrl' => $set->editUrl(),
            'configurable' => $this->canConfigure($set),
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return Inertia::render('advanced-seo::'.ucfirst($this->type()).'/Edit', $viewData);
    }

    public function update(Request $request, string $handle, Site $site): void
    {
        $set = Seo::findOrMake($this->type(), $handle);

        throw_unless($set->enabled(), new NotFoundHttpException);
        throw_unless($set->availableInSite($site), new NotFoundHttpException);

        $this->authorize('edit', [SeoDefaultSet::class, $set, $site]);

        $localization = $set->in($site);

        $blueprint = $localization->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        $localization->hasOrigin()
            ? $localization->data($values->only($request->input('_localized')))
            : $localization->merge($values);

        $localization = $localization->save();
    }

    protected function extractFromFields(SeoVariables $localization, Blueprint $blueprint): array
    {
        $fields = $blueprint
            ->fields()
            ->addValues($localization->values()->all())
            ->preProcess();

        return [$fields->values()->all(), $fields->meta()->all()];
    }

    protected function canConfigure(SeoDefaultSet $set): bool
    {
        return User::current()->can('configure', [SeoDefaultSet::class, $set]);
    }
}
