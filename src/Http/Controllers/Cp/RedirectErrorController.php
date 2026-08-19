<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Blueprints\RedirectBlueprint;
use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Aerni\AdvancedSeo\Http\Resources\Cp\Redirects\Errors as ErrorsResource;
use Aerni\AdvancedSeo\Redirects\RedirectErrorMatcher;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\OrderBy;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\Statamic;

class RedirectErrorController extends CpController
{
    use QueriesFilters;

    public function index(FilteredRequest $request)
    {
        throw_unless(RedirectsFeature::enabled() && config('advanced-seo.redirects.errors.enabled'), new NotFoundHttpException);

        $this->authorize('manage', Redirect::class);

        if ($request->wantsJson()) {
            return $this->json($request);
        }

        $sites = Site::authorized()->map->handle()->all();

        $createBlueprint = RedirectBlueprint::definition();
        $createFields = $createBlueprint->fields()->preProcess();

        return Inertia::render('advanced-seo::Redirects/Errors', [
            'title' => __('advanced-seo::messages.redirect_errors'),
            'listingUrl' => cp_route('advanced-seo.redirects.errors.index'),
            'actionUrl' => cp_route('advanced-seo.redirects.errors.actions.run'),
            'filters' => Scope::filters('redirect-errors'),
            'createUrl' => cp_route('advanced-seo.redirects.store'),
            'createBlueprint' => $createBlueprint->toPublishArray(),
            'createValues' => $createFields->values()->all(),
            'createMeta' => $createFields->meta()->all(),
            'clearUrl' => cp_route('advanced-seo.redirects.errors.clear'),
            'hasErrors' => RedirectFacade::errors()->query()->whereIn('site', $sites)->first() !== null,
        ]);
    }

    protected function json(FilteredRequest $request)
    {
        $query = $this->indexQuery();

        $activeFilterBadges = $this->queryFilters($query, $request->filters);

        $sortField = OrderBy::column(request('sort'));
        $sortDirection = request('order', 'asc');

        if ($sortField === 'hits') {
            $sortField = 'count';
        }

        if (! $sortField && ! request('search')) {
            $sortField = 'url';
            $sortDirection = 'asc';
        }

        if ($sortField) {
            $query->orderBy($sortField, $sortDirection);
        }

        $errors = $query->paginate(Statamic::cpPerPage(request('perPage')));

        $resource = new ErrorsResource($errors);
        $resource->matcher = RedirectErrorMatcher::for(
            $errors->getCollection()->map->site()->unique()->all()
        );

        return $resource->additional(['meta' => [
            'activeFilterBadges' => $activeFilterBadges,
        ]]);
    }

    protected function indexQuery()
    {
        $query = RedirectFacade::errors()->query()
            ->whereIn('site', Site::authorized()->map->handle()->all());

        if ($search = request('search')) {
            // Stored urls are lowercased, so lower the needle to match on both drivers.
            $query->where('url', 'LIKE', '%'.Str::lower($search).'%');
        }

        return $query;
    }

    public function clear(): void
    {
        throw_unless(RedirectsFeature::enabled() && config('advanced-seo.redirects.errors.enabled'), new NotFoundHttpException);

        $this->authorize('manage', Redirect::class);

        RedirectFacade::errors()->query()
            ->whereIn('site', Site::authorized()->map->handle()->all())
            ->get()
            ->each->delete();
    }
}
