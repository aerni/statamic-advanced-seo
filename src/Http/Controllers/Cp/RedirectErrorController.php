<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Aerni\AdvancedSeo\Http\Resources\Cp\Redirects\Errors as ErrorsResource;
use Aerni\AdvancedSeo\Redirects\ErrorHandledChecker;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Statamic;
use Statamic\Support\Str;

class RedirectErrorController extends CpController
{
    public function index(Request $request)
    {
        throw_unless(RedirectsFeature::enabled() && config('advanced-seo.redirects.errors.enabled'), new NotFoundHttpException);

        $this->authorize('viewAny', Redirect::class);

        $sites = Site::authorized()->map->handle()->all();

        if ($request->wantsJson()) {
            $checker = ErrorHandledChecker::for($sites);

            $errors = RedirectFacade::errors()->query()->whereIn('site', $sites)->get();

            if ($search = $request->input('search')) {
                $errors = $errors->filter(fn ($error) => Str::contains(Str::lower($error->url()), Str::lower($search)));
            }

            $errors = $this->sort(
                $errors,
                $checker,
                $request->input('sort', 'url'),
                $request->input('order', 'asc'),
            );

            $paginator = $this->paginate($errors, Statamic::cpPerPage($request->input('perPage')));

            $resource = new ErrorsResource($paginator);
            $resource->handledChecker = $checker;

            return $resource;
        }

        $hasErrors = RedirectFacade::errors()->query()->whereIn('site', $sites)->first() !== null;

        return Inertia::render('advanced-seo::Redirects/Errors', [
            'title' => __('advanced-seo::messages.redirect_errors'),
            'listingUrl' => cp_route('advanced-seo.redirects.errors.index'),
            'hasErrors' => $hasErrors,
        ]);
    }

    /**
     * Sort the recorded errors in memory. The store is bounded by `max_records`,
     * and the "redirect" column is derived from redirects rather than stored, so
     * every column is sorted here rather than through the query builder.
     */
    protected function sort(Collection $errors, ErrorHandledChecker $checker, string $field, string $direction): Collection
    {
        return $errors->sortBy(fn ($error) => match ($field) {
            'hits' => $error->count(),
            'first_seen_at' => $error->firstSeenAt() ?? 0,
            'last_seen_at' => $error->lastSeenAt() ?? 0,
            'site' => $error->site(),
            'redirect' => $this->statusRank($checker->match($error->url(), $error->site())),
            default => $error->url(),
        }, SORT_REGULAR, $direction === 'desc')->values();
    }

    protected function statusRank(?Redirect $redirect): int
    {
        if ($redirect === null) {
            return 0;
        }

        return $redirect->enabled() ? 2 : 1;
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
