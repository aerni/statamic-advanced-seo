<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Statamic\Facades\Site;
use Statamic\Facades\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Statamic\CP\Breadcrumbs;
use Aerni\AdvancedSeo\Traits\ValidateType;
use Aerni\AdvancedSeo\Repositories\SiteDefaultsRepository;

class SiteDefaultsController extends BaseDefaultsController
{
    use ValidateType;

    // TODO: This should probably be put in a repository.
    protected array $allowedTypes = ['general', 'marketing'];

    public function edit(Request $request, string $handle): mixed
    {
        $this->authorize("view $handle defaults");

        if (! $this->isValidType($handle)) {
            return $this->pageNotFound();
        };

        $repository = $this->repository($handle);

        $site = $request->site ?? Site::selected()->handle();

        $sites = Site::all()->map->handle();

        $set = $repository->ensureLocalizations($sites)->set();

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
            'breadcrumbs' => $this->breadcrumbs($handle),
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

        $repository = $this->repository($handle);

        $blueprint = $repository->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        $repository->save($site, $values);
    }

    protected function breadcrumbs(string $handle): Breadcrumbs
    {
        return new Breadcrumbs([
            [
                'text' => __('advanced-seo::messages.site'),
                'url' => cp_route('advanced-seo.show', 'site'),
            ]
        ]);
    }

    protected function repository(string $handle): SiteDefaultsRepository
    {
        return new SiteDefaultsRepository($handle);
    }
}
