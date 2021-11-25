<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Aerni\AdvancedSeo\Events\SeoDefaultsSaved;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Http\Request;
use Statamic\CP\Breadcrumbs;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class SiteDefaultsController extends BaseDefaultsController
{
    public function edit(Request $request, string $handle): mixed
    {
        throw_unless(in_array($handle, $this->defaults()), new NotFoundHttpException);

        $this->authorize("view $handle defaults");

        $set = $this->set($handle);

        $site = $request->site ?? Site::selected()->handle();

        $sites = Site::all()->map->handle();

        $set = $set->createLocalizations($sites);

        $localization = $set->in($site);

        $blueprint = $localization->blueprint();

        [$values, $meta] = $this->extractFromFields($localization, $blueprint);

        if ($hasOrigin = $localization->hasOrigin()) {
            [$originValues, $originMeta] = $this->extractFromFields($localization->origin(), $blueprint);
        }

        $user = User::fromUser($request->user());

        // This variable solely exists to prevent variable conflict in $viewData['localizations'].
        $requestLocalization = $localization;

        $viewData = [
            'title' => $set->title(),
            'reference' => $localization->reference(),
            'editing' => true,
            'actions' => [
                'save' => $localization->updateUrl(),
            ],
            'values' => $values,
            'meta' => $meta,
            'blueprint' => $blueprint->toPublishArray(),
            'locale' => $localization->locale(),
            'localizedFields' => $localization->data()->keys()->all(),
            'isRoot' => $localization->isRoot(),
            'hasOrigin' => $hasOrigin,
            'originValues' => $originValues ?? null,
            'originMeta' => $originMeta ?? null,
            'localizations' => $sites->map(function ($site) use ($set, $requestLocalization) {
                $localization = $set->in($site);
                $exists = $localization !== null;

                return [
                    'handle' => $site,
                    'name' => Site::get($site)->name(),
                    'active' => $site === $requestLocalization->locale(),
                    'exists' => $exists,
                    'root' => $exists ? $localization->isRoot() : false,
                    'origin' => $exists ? $localization->locale() === optional($requestLocalization->origin())->locale() : null,
                    'url' => $exists ? $localization->editUrl() : null,
                ];
            })->sortBy('handle')->values()->all(),
            'breadcrumbs' => $this->breadcrumbs(),
            'readOnly' => $user->cant("edit $handle defaults"),
            'contentType' => 'site',
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return view('advanced-seo::cp/edit', array_merge($viewData, [
            'set' => $set,
            'variables' => $localization,
        ]));
    }

    public function update(string $handle, Request $request): void
    {
        $this->authorize("edit $handle defaults");

        $site = $request->site ?? Site::selected()->handle();

        $set = $this->set($handle);
        $sites = Site::all()->map->handle();

        $blueprint = $set->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        $localization = $set->in($site)->determineOrigin($sites);

        if ($localization->hasOrigin()) {
            $values = collect(array_map(
                'unserialize',
                array_diff(array_map('serialize', $values->toArray()), array_map('serialize', $localization->origin()->data()->toArray()))
            ));
        }

        $localization = $localization->data($values)->save();

        SeoDefaultsSaved::dispatch($localization);
    }

    protected function breadcrumbs(): Breadcrumbs
    {
        return new Breadcrumbs([
            [
                'text' => __('advanced-seo::messages.site'),
                'url' => cp_route('advanced-seo.show', 'site'),
            ],
        ]);
    }

    protected function defaults(): array
    {
        $defaults = ['general', 'indexing', 'marketing', 'social'];

        if (config('advanced-seo.favicons', false)) {
            $defaults[] = 'favicons';
        }

        return $defaults;
    }

    protected function set(string $handle): SeoDefaultSet
    {
        return Seo::findOrMake('site', $handle);
    }
}
