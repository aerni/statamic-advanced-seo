<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Inertia\Inertia;
use Inertia\Response;
use Statamic\CP\Column;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Illuminate\Http\Request;
use Statamic\Fields\Blueprint;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Contracts\SeoSet;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Http\Controllers\CP\CpController;
use Aerni\AdvancedSeo\Actions\GetAuthorizedSites;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;

abstract class BaseSeoSetLocalizationController extends CpController
{
    abstract protected function type(): string;

    abstract protected function icon(): string;

    // TODO: Maybe we can accept the SeoSetType group as argument. Also bind in in the route. Then we don't need $this->type().
    public function index(): Response
    {
        $this->authorize('viewAny', [SeoSet::class, $this->type()]);

        $items = Seo::whereType($this->type())
            ->filter(function (SeoSet $seoSet) {
                $localization = $seoSet->in(Site::selected());

                if (! $localization) {
                    return false;
                }

                if (! User::current()->can('edit', [SeoSet::class, $localization])) {
                    return false;
                }

                // Include if user can configure, or if the seoSet is enabled
                return User::current()->can('configure', [SeoSet::class, $seoSet]) || $seoSet->enabled();
            })->values();

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

    public function edit(Request $request, SeoSet $seoSet, SeoSetLocalization $localization): mixed
    {
        throw_unless($seoSet->enabled(), new NotFoundHttpException);

        $this->authorize('edit', [SeoSet::class, $localization]);

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
            'initialSite' => $localization->locale(),
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
            'configurable' => User::current()->can('configure', [SeoSet::class, $seoSet]),
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return Inertia::render('advanced-seo::'.ucfirst($this->type()).'/Edit', $viewData);
    }

    public function update(Request $request, SeoSet $seoSet, SeoSetLocalization $localization): void
    {
        throw_unless($seoSet->enabled(), new NotFoundHttpException);

        $this->authorize('edit', [SeoSet::class, $localization]);

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
}
