<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Actions\GetAuthorizedSites;
use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Contracts\SeoSetGroup;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\User;
use Statamic\Fields\Blueprint;
use Statamic\Http\Controllers\CP\CpController;

class SeoSetLocalizationController extends CpController
{
    public function edit(Request $request, SeoSetGroup $seoSetGroup, SeoSet $seoSet, SeoSetLocalization $localization): mixed
    {
        throw_unless($seoSet->enabled(), new NotFoundHttpException);

        $this->authorize('edit', [SeoSet::class, $localization]);

        $blueprint = $localization->blueprint();

        [$values, $meta] = $this->extractFromFields($localization, $blueprint);

        if ($hasOrigin = $localization->hasOrigin()) {
            [$originValues, $originMeta] = $this->extractFromFields($localization->origin(), $blueprint);
        }

        $viewData = [
            'title' => $seoSet->title(),
            'icon' => $seoSet->icon(),
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

        return Inertia::render("advanced-seo::{$seoSetGroup->title()}/Edit", $viewData);
    }

    public function update(Request $request, SeoSetGroup $seoSetGroup, SeoSet $seoSet, SeoSetLocalization $localization): void
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
