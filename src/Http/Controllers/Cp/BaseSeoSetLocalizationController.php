<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\GetAuthorizedSites;
use Aerni\AdvancedSeo\Contracts\SeoSet as SeoSetContract;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Facades\Seo;
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

abstract class BaseSeoSetLocalizationController extends CpController
{
    abstract protected function type(): string;

    abstract protected function icon(): string;

    public function index(): Response
    {
        $this->authorize('viewAny', [SeoSetContract::class, $this->type()]);

        $site = SiteFacade::selected();

        $items = Seo::whereType($this->type())
            ->filter(fn (SeoSetContract $seoSet) => User::current()->can('edit', [SeoSetContract::class, $seoSet, $site]))
            ->filter(fn (SeoSetContract $seoSet) => $seoSet->availableInSite($site))
            ->filter(fn (SeoSetContract $seoSet) => $this->canConfigure($seoSet) || $seoSet->enabled());

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

    public function edit(Request $request, SeoSetLocalization $localization, Site $site): mixed
    {
        $seoSet = $localization->seoSet();

        throw_unless($seoSet->enabled(), new NotFoundHttpException);

        $this->authorize('edit', [SeoSetContract::class, $seoSet, $site]);

        $blueprint = $localization->blueprint();

        [$values, $meta] = $this->extractFromFields($localization, $blueprint);

        if ($hasOrigin = $localization->hasOrigin()) {
            [$originValues, $originMeta] = $this->extractFromFields($localization->origin(), $blueprint);
        }

        $viewData = [
            'title' => $seoSet->title,
            'icon' => $seoSet->icon,
            'blueprint' => $blueprint->toPublishArray(),
            'initialReference' => $localization->id(),
            'initialValues' => $values,
            'initialMeta' => $meta,
            'initialSite' => $site->handle(),
            'initialHasOrigin' => $hasOrigin,
            'initialOriginValues' => $originValues ?? null,
            'initialOriginMeta' => $originMeta ?? null,
            'initialLocalizations' => GetAuthorizedSites::handle($seoSet)
                ->map(fn ($site) => [
                    'handle' => $site->handle(),
                    'name' => $site->name(),
                    'active' => $site->handle() === $localization->locale(),
                    'url' => $seoSet->in($site)->editUrl(),
                ])->filter()->values()->all(),
            'initialLocalizedFields' => $localization->data()->keys()->all(),
            'initialEditUrl' => $localization->editUrl(),
            'configUrl' => $seoSet->config()->editUrl(),
            'configurable' => $this->canConfigure($seoSet),
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return Inertia::render('advanced-seo::'.ucfirst($this->type()).'/Edit', $viewData);
    }

    public function update(Request $request, SeoSetLocalization $localization, Site $site): void
    {
        $seoSet = $localization->seoSet();

        throw_unless($seoSet->enabled(), new NotFoundHttpException);

        $this->authorize('edit', [SeoSetContract::class, $seoSet, $site]);

        $blueprint = $localization->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        $localization->hasOrigin()
            ? $localization->data($values->only($request->input('_localized')))
            : $localization->merge($values);

        $localization->save();
    }

    protected function extractFromFields(SeoSetLocalization $localization, Blueprint $blueprint): array
    {
        $fields = $blueprint
            ->fields()
            ->addValues($localization->values()->all())
            ->preProcess();

        return [$fields->values()->all(), $fields->meta()->all()];
    }

    protected function canConfigure(SeoSetContract $seoSet): bool
    {
        return User::current()->can('configure', [SeoSetContract::class, $seoSet]);
    }
}
