<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Events\SeoDefaultSetSaved;
use Aerni\AdvancedSeo\Models\Defaults;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Statamic\CP\Breadcrumbs;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;
use Statamic\Facades\User;

abstract class ContentDefaultsController extends BaseDefaultsController
{
    public function index(): View
    {
        throw_unless(Defaults::enabledInType($this->type)->isNotEmpty(), new NotFoundHttpException);

        $this->authorize('index', [SeoVariables::class, $this->type]);

        return view("advanced-seo::cp.{$this->type}");
    }

    public function edit(Request $request, string $handle): mixed
    {
        throw_unless(Defaults::isEnabled("{$this->type}::{$handle}"), new NotFoundHttpException);

        $set = $this->set($handle);

        $this->authorize('view', [SeoVariables::class, $set]);

        $site = $request->site ?? Site::selected()->handle();

        $sites = $this->content($handle)->sites();

        // Create a localization for each of the provided sites. This triggers a save on the set.
        // TODO: Do we really need to create the localizations or can we simply ensure them with ensureLocalizations()?
        // Ensuring wouldn't save them to file. But maybe we don't even have to do that?
        // TODO: Probably don't need to pass the sites anymore as we are getting those in the seoDefaultsSet now.
        $set = $set->createLocalizations($sites);

        $localization = $set->in($site) ?? $set->inDefaultSite();

        $blueprint = $localization->blueprint();

        [$values, $meta] = $this->extractFromFields($localization, $blueprint);

        if ($hasOrigin = $localization->hasOrigin()) {
            [$originValues, $originMeta] = $this->extractFromFields($localization->origin(), $blueprint);
        }

        // This variable solely exists to prevent variable conflict in $viewData['localizations'].
        $requestLocalization = $localization;

        $viewData = [
            'title' => $set->title(),
            'reference' => $localization->reference(),
            'editing' => true,
            'actions' => [
                'save' => $localization->updateUrl(),
            ],
            // TODO: Make this work with $set->id()
            // 'values' => array_merge($values, ['id' => $set->id()]),
            'values' => array_merge($values, ['id' => "{$set->type()}::{$set->handle()}"]),
            'meta' => $meta,
            'blueprint' => $blueprint->toPublishArray(),
            'locale' => $localization->locale(),
            'localizedFields' => $localization->data()->keys()->all(),
            'isRoot' => $localization->isRoot(),
            'hasOrigin' => $hasOrigin,
            'originValues' => $originValues ?? null,
            'originMeta' => $originMeta ?? null,
            'localizations' => $this->authorizedSites($set)->map(function ($site) use ($set, $requestLocalization) {
                $localization = $set->in($site);
                $exists = $localization !== null;

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
            })->values()->all(),
            'breadcrumbs' => $this->breadcrumbs(),
            'readOnly' => User::current()->cant("edit seo {$handle} defaults"),
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
        $set = $this->set($handle);

        $this->authorize('edit', [SeoVariables::class, $set]);

        $site = $request->site ?? Site::selected()->handle();

        $sites = $this->content($handle)->sites();

        $localization = $set->in($site)->determineOrigin($sites);

        $blueprint = $localization->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        $localization->hasOrigin()
            ? $localization->data($values->only($request->input('_localized')))
            : $localization->merge($values);

        $localization = $localization->save();

        SeoDefaultSetSaved::dispatch($localization->seoSet());
    }

    protected function breadcrumbs(): Breadcrumbs
    {
        return new Breadcrumbs([
            [
                'text' => __("advanced-seo::messages.{$this->type}"),
                'url' => cp_route("advanced-seo.{$this->type}.index"),
            ],
        ]);
    }

    abstract protected function set(string $handle): mixed;

    abstract protected function content(string $handle): mixed;
}
