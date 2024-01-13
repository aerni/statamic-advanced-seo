<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Statamic\Facades\Site;
use Illuminate\Http\Request;
use Statamic\CP\Breadcrumbs;
use Statamic\Fields\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Aerni\AdvancedSeo\Models\Defaults;
use Aerni\AdvancedSeo\Data\SeoVariables;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Http\Controllers\CP\CpController;

abstract class BaseDefaultsController extends CpController
{
    public function index(): View
    {
        $defaults = $this->defaults();

        if ($defaults->isEmpty()) {
            $this->flashDefaultsUnavailable();
        }

        $this->authorize('index', [SeoVariables::class, $this->type]);

        return view("advanced-seo::cp.{$this->type}", [
            'defaults' => $defaults,
        ]);
    }

    abstract public function edit(Request $request, string $handle): mixed;

    abstract public function update(string $handle, Request $request): void;

    abstract protected function set(string $handle): mixed;

    protected function extractFromFields(SeoVariables $localization, Blueprint $blueprint): array
    {
        $fields = $blueprint
            ->fields()
            ->addValues($localization->values()->all())
            ->preProcess();

        return [$fields->values()->all(), $fields->meta()->all()];
    }

    protected function authorizedSites(SeoDefaultSet $set): Collection
    {
        return $set->sites()->intersect(Site::authorized());
    }

    protected function defaults(): Collection
    {
        return Defaults::enabledInType($this->type)
            ->filter(fn ($default) => $default['set']->availableOnSite(Site::selected()->handle()));
    }

    protected function flashDefaultsUnavailable(): void
    {
        session()->now('error', __('There are no :type defaults available for the selected site.', [
            'type' => str_singular($this->type),
        ]));

        throw new NotFoundHttpException();
    }

    protected function redirectToIndex(SeoDefaultSet $set, string $site): RedirectResponse
    {
        return redirect(cp_route("advanced-seo.{$set->type()}.index"))
            ->with('error', __('The :set :type is not available in the selected site.', [
                'set' => $set->title(),
                'type' => str_singular($this->type),
            ]));
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
}
