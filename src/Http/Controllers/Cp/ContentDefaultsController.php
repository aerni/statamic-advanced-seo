<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Statamic\CP\Breadcrumbs;
use Statamic\Facades\Site;
use Statamic\Facades\User;

abstract class ContentDefaultsController extends BaseDefaultsController
{
    public function edit(Request $request, string $handle): mixed
    {
        $this->authorize("view $this->type defaults");

        $repository = $this->repository($handle);

        $content = $this->content($handle);

        $site = $request->site ?? Site::selected()->handle();

        // Select the requested site if it exists in the sites configuration of the content
        // or fall back to the origin if it doesn't.
        $site = $repository->determineOrigin($content->sites(), $site);

        $set = $repository->ensureLocalizations($content->sites())->set();

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
            'localizations' => $content->sites()->map(function ($site) use ($set, $requestLocalization) {
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
            'readOnly' => $user->cant("edit $this->type defaults"),
            'contentType' => $this->type,
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
        $this->authorize("edit $this->type defaults");

        $repository = $this->repository($handle);

        $site = $request->site ?? Site::selected()->handle();

        $blueprint = $repository->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        $repository->save($site, $values);
    }

    protected function breadcrumbs(): Breadcrumbs
    {
        return new Breadcrumbs([
            [
                'text' => __('advanced-seo::messages.content'),
                'url' => cp_route('advanced-seo.show', 'content'),
            ],
            [
                'text' => str_plural(Str::title($this->type)),
                'url' => cp_route('advanced-seo.show', 'content'),
            ],
        ]);
    }

    abstract protected function repository(string $handle): mixed;

    abstract protected function content(string $handle): mixed;
}
