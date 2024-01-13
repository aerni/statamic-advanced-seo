<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Statamic\Facades\Site;
use Illuminate\Http\Request;
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
    abstract public function index(): View;

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

    protected function hasDefaultsForSelectedSite(): bool
    {
        return Defaults::enabledInType($this->type)
            ->filter(fn ($default) => $default['set']->sites()->contains(Site::selected()->handle()))
            ->isNotEmpty();
    }

    protected function flashDefaultsUnavailable(): void
    {
        session()->now('error', __('There are no :type defaults available on site ":handle".', [
            'type' => str_singular($this->type),
            'handle' => Site::selected()->name()
        ]));

        throw new NotFoundHttpException();
    }

    protected function redirectToIndex(SeoDefaultSet $set, string $site): RedirectResponse
    {
        return redirect(cp_route("advanced-seo.{$set->type()}.index"))
            ->with('error', __('The ":set" defaults are not available on site ":handle".', ['set' => $set->title(), 'handle' => Site::get($site)->name()]));
    }
}
