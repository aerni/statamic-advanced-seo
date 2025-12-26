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
            ->filter(fn ($default) => User::current()->can('edit', [SeoDefaultSet::class, $default['set'], $site]))
            ->filter(fn ($default) => $default['set']->availableInSite($site))
            ->filter(fn ($default) => $this->canConfigure($default['set']) || $default['set']->enabled())
            ->map(fn ($default) => [
                ...$default,
                'enabled' => $default['set']->enabled(),
                'configurable' => $this->canConfigure($default['set']),
                'edit_url' => $default['set']->in(Sites::selected()->handle())->editUrl(),
                'config_url' => $default['set']->editUrl(),
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

        // TODO: The global feature enabled state. e.g. used by site defaults like favicons.
        // Might be able to get rid of it at some point. We already determine enabled state per locale for collections/taxonomies now.
        throw_unless($defaults['enabled'] ?? false, new NotFoundHttpException);

        $set = $defaults['set'];

        $this->authorize('edit', [SeoDefaultSet::class, $set, $site]);

        throw_unless($set->enabled(), new NotFoundHttpException);
        throw_unless($set->availableInSite($site->handle()), new NotFoundHttpException);

        $localization = $set->in($site->handle());

        $blueprint = $localization->blueprint();

        [$values, $meta] = $this->extractFromFields($localization, $blueprint);

        if ($hasOrigin = $localization->hasOrigin()) {
            [$originValues, $originMeta] = $this->extractFromFields($localization->origin(), $blueprint);
        }

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
            'initialLocalizations' => GetAuthorizedSites::handle($set)
                ->map(fn ($site) => [
                    'handle' => $site->handle(),
                    'name' => $site->name(),
                    'active' => $site->handle() === $localization->locale(),
                    'url' => $set->in($site->handle())->editUrl(),
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

        $this->authorize('edit', [SeoDefaultSet::class, $set, $site]);

        throw_unless($set->availableInSite($site->handle()), new NotFoundHttpException);

        $localization = $set->in($site->handle());

        throw_unless($localization->enabled(), new NotFoundHttpException);

        $blueprint = $localization->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        // TODO: I want to be able to purposely unlink a field even if its value is the same as the origin. We should save it to file.
        // dd($values->only($request->input('_localized')));
        $localization->hasOrigin()
            ? $localization->data($values->only($request->input('_localized')))
            : $localization->merge($values);

        // dd($localization->data());
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

    protected function canConfigure(SeoDefaultSet $set): bool
    {
        return User::current()->can('configure', [SeoDefaultSet::class, $set]);
    }
}
