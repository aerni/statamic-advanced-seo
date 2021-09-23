<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Repositories\SiteDefaultsRepository;
use Aerni\AdvancedSeo\Traits\ValidateType;
use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Statamic\Facades\User;

class SiteDefaultsController extends BaseDefaultsController
{
    use ValidateType;

    // TODO: This should probably be put in a repository.
    protected array $allowedTypes = ['general', 'marketing'];

    public function edit(Request $request, string $handle)
    {
        $this->authorize("view $handle defaults");

        if (! $this->isValidType($handle)) {
            return $this->pageNotFound();
        };

        $repository = $this->repository($handle);

        $site = $request->site ?? Site::selected()->handle();

        $set = $repository->findOrMakeSeoSet();

        $variables = $repository->ensureSeoVariables()->get($site);

        $blueprint = $variables->blueprint();

        [$values, $meta] = $this->extractFromFields($variables, $blueprint);

        if ($hasOrigin = $variables->hasOrigin()) {
            [$originValues, $originMeta] = $this->extractFromFields($variables->origin(), $blueprint);
        }

        $user = User::fromUser($request->user());

        $viewData = [
            'defaultsUrl' => cp_route('advanced-seo.show', 'site'),
            'defaultsTitle' => __('advanced-seo::messages.site'),
            'reference' => $variables->reference(),
            'editing' => true,
            'actions' => [
                'save' => $variables->updateUrl(),
            ],
            'values' => $values,
            'meta' => $meta,
            'blueprint' => $blueprint->toPublishArray(),
            'locale' => $variables->locale(),
            'localizedFields' => $variables->data()->keys()->all(),
            'isRoot' => $variables->isRoot(),
            'hasOrigin' => $hasOrigin,
            'originValues' => $originValues ?? null,
            'originMeta' => $originMeta ?? null,
            'localizations' => $variables->seoSet()->localizations()->map(function ($localized) use ($variables) {
                return [
                    'handle' => $localized->locale(),
                    'name' => $localized->site()->name(),
                    'active' => $localized->locale() === $variables->locale(),
                    'origin' => ! $localized->hasOrigin(),
                    'url' => $localized->editUrl(),
                ];
            })->values()->all(),
            'canEdit' => $user->can("edit $handle defaults"),
        ];

        if ($request->wantsJson()) {
            return $viewData;
        }

        return view('advanced-seo::cp/edit', array_merge($viewData, [
            'set' => $set,
            'variables' => $variables,
        ]));
    }

    public function update(string $handle, Request $request)
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

    protected function repository(string $handle): SiteDefaultsRepository
    {
        return new SiteDefaultsRepository($handle);
    }
}
