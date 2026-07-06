<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Blueprints\RedirectBlueprint;
use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Aerni\AdvancedSeo\Http\Resources\Cp\Redirects\Errors as ErrorsResource;
use Aerni\AdvancedSeo\Redirects\ErrorHandledChecker;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Scope;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\Statamic;

class RedirectErrorController extends CpController
{
    use QueriesFilters;

    public function index(FilteredRequest $request)
    {
        throw_unless(RedirectsFeature::enabled() && config('advanced-seo.redirects.errors.enabled'), new NotFoundHttpException);

        $this->authorize('manage', Redirect::class);

        $sites = Site::authorized()->map->handle()->all();

        if ($request->wantsJson()) {
            $checker = ErrorHandledChecker::for($sites);

            $query = RedirectFacade::errors()->query()->whereIn('site', $sites);

            if ($search = $request->input('search')) {
                $query->where('url', 'LIKE', '%'.$search.'%');
            }

            $activeFilterBadges = $this->queryFilters($query, $request->filters);

            $errors = $query->get();

            if ($status = Arr::get($request->filters, 'redirect_error_status.status')) {
                $errors = $errors->filter(fn ($error) => ErrorHandledChecker::status($checker->match($error->url(), $error->site())) === $status);
            }

            $errors = $this->sort($errors, $request->input('sort', 'url'), $request->input('order', 'asc'));

            $paginator = $this->paginate($errors, Statamic::cpPerPage($request->input('perPage')));

            $resource = new ErrorsResource($paginator);
            $resource->handledChecker = $checker;

            return $resource->additional(['meta' => ['activeFilterBadges' => $activeFilterBadges]]);
        }

        $canCreate = User::current()->can('manage', Redirect::class);
        $createBlueprint = $canCreate ? RedirectBlueprint::definition() : null;
        $createFields = $createBlueprint?->fields()->preProcess();

        $canClear = User::current()->can('manage', Redirect::class);

        return Inertia::render('advanced-seo::Redirects/Errors', [
            'title' => __('advanced-seo::messages.redirect_errors'),
            'listingUrl' => cp_route('advanced-seo.redirects.errors.index'),
            'actionUrl' => cp_route('advanced-seo.redirects.errors.actions.run'),
            'filters' => Scope::filters('redirect-errors'),
            'canCreate' => $canCreate,
            'createUrl' => $canCreate ? cp_route('advanced-seo.redirects.store') : null,
            'createBlueprint' => $createBlueprint?->toPublishArray(),
            'createValues' => $createFields?->values()->all(),
            'createMeta' => $createFields?->meta()->all(),
            'canClear' => $canClear,
            'clearUrl' => $canClear ? cp_route('advanced-seo.redirects.errors.clear') : null,
            'hasErrors' => RedirectFacade::errors()->query()->whereIn('site', $sites)->first() !== null,
        ]);
    }

    public function clear(): void
    {
        throw_unless(RedirectsFeature::enabled() && config('advanced-seo.redirects.errors.enabled'), new NotFoundHttpException);

        abort_unless(User::current()->can('manage', Redirect::class), 403);

        RedirectFacade::errors()->query()
            ->whereIn('site', Site::authorized()->map->handle()->all())
            ->get()
            ->each->delete();
    }

    /**
     * Sort the recorded errors in memory. The store is bounded by `max_records`,
     * and the status filter is derived from redirects, so the set is processed
     * here rather than through the query builder.
     */
    protected function sort(Collection $errors, string $field, string $direction): Collection
    {
        return $errors->sortBy(fn ($error) => match ($field) {
            'hits' => $error->count(),
            'first_seen_at' => $error->firstSeenAt() ?? 0,
            'last_seen_at' => $error->lastSeenAt() ?? 0,
            'site' => $error->site(),
            default => $error->url(),
        }, SORT_REGULAR, $direction === 'desc')->values();
    }

    protected function paginate(Collection $errors, ?int $perPage): LengthAwarePaginator
    {
        $perPage = $perPage ?: 50;

        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $errors->forPage($page, $perPage)->values(),
            $errors->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }
}
