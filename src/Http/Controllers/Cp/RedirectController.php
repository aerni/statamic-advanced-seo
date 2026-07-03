<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Blueprints\RedirectBlueprint;
use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\ResponseCode;
use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Aerni\AdvancedSeo\Http\Resources\Cp\Redirects\Redirects as RedirectsResource;
use Illuminate\Http\Request;
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
use Statamic\Query\OrderBy;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Statamic\Statamic;

class RedirectController extends CpController
{
    use QueriesFilters;

    public function index(FilteredRequest $request)
    {
        throw_unless(RedirectsFeature::enabled(), new NotFoundHttpException);

        $this->authorize('viewAny', Redirect::class);

        if ($request->wantsJson()) {
            $query = Redirects::query()
                ->whereIn('site', Site::authorized()->map->handle()->all());

            if ($search = request('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('source', 'LIKE', '%'.$search.'%')
                        ->orWhere('destination', 'LIKE', '%'.$search.'%');
                });
            }

            $activeFilterBadges = $this->queryFilters($query, $request->filters);

            $sortField = OrderBy::column(request('sort'));
            $sortDirection = request('order', 'asc');

            if ($sortField === 'status') {
                $sortField = 'enabled';
            }

            $hitsEnabled = config('advanced-seo.redirects.hits.enabled');
            $hitSortColumn = in_array($sortField, ['hits', 'last_hit_at'], true);

            if ($hitSortColumn && ! $hitsEnabled) {
                $sortField = null;
            }

            if (! $sortField && ! request('search')) {
                $sortField = 'source';
                $sortDirection = 'asc';
            }

            $perPage = Statamic::cpPerPage(request('perPage'));

            if ($hitSortColumn && $hitsEnabled) {
                [$redirects, $hits] = $this->paginateByHits($query, $sortField, $sortDirection, $perPage);
            } else {
                if ($sortField) {
                    $query->orderBy($sortField, $sortDirection);
                }

                $redirects = $query->paginate($perPage);

                $hits = $hitsEnabled ? $this->hitsForRedirects($redirects->items()) : null;
            }

            return (new RedirectsResource($redirects))
                ->hits($hits)
                ->additional(['meta' => ['activeFilterBadges' => $activeFilterBadges]]);
        }

        $hasRedirects = Redirects::query()
            ->whereIn('site', Site::authorized()->map->handle()->all())
            ->first() !== null;

        return Inertia::render('advanced-seo::Redirects/Index', [
            'title' => __('advanced-seo::messages.redirects'),
            'createUrl' => cp_route('advanced-seo.redirects.create'),
            'listingUrl' => cp_route('advanced-seo.redirects.index'),
            'actionUrl' => cp_route('advanced-seo.redirects.actions.run'),
            'canCreate' => User::current()->can('create', Redirect::class),
            'filters' => Scope::filters('redirects'),
            'hasRedirects' => $hasRedirects,
        ]);
    }

    protected function hitsForRedirects(array $redirects): Collection
    {
        return Redirects::hits()->query()
            ->whereIn('redirect', collect($redirects)->map->id()->all())
            ->get()
            ->keyBy(fn ($hit) => $hit->redirect());
    }

    /**
     * Sort the redirects by their hit data, which lives in a separate store, by
     * loading them all, merging the hit values, sorting, then paginating in memory.
     *
     * @return array{LengthAwarePaginator, Collection}
     */
    protected function paginateByHits($query, string $sortField, string $sortDirection, ?int $perPage): array
    {
        $perPage = $perPage ?: 50;

        $redirects = $query->get();

        $hits = $this->hitsForRedirects($redirects->all());

        $sorted = $redirects->sortBy(function ($redirect) use ($hits, $sortField) {
            $hit = $hits->get($redirect->id());

            return $sortField === 'hits' ? ($hit?->count() ?? 0) : ($hit?->lastHitAt() ?? 0);
        }, SORT_REGULAR, $sortDirection === 'desc')->values();

        $page = LengthAwarePaginator::resolveCurrentPage();

        $paginator = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return [$paginator, $hits];
    }

    public function create(): mixed
    {
        throw_unless(RedirectsFeature::enabled(), new NotFoundHttpException);

        $this->authorize('create', Redirect::class);

        $blueprint = RedirectBlueprint::definition();

        $fields = $blueprint->fields()->preProcess();

        return Inertia::render('advanced-seo::Redirects/Create', [
            'title' => __('advanced-seo::messages.redirect_create_title'),
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values()->all(),
            'meta' => $fields->meta()->all(),
            'enabled' => true,
            'submitUrl' => cp_route('advanced-seo.redirects.store'),
        ]);
    }

    public function edit(Redirect $redirect): mixed
    {
        throw_unless(RedirectsFeature::enabled(), new NotFoundHttpException);

        $this->authorize('edit', $redirect);

        $blueprint = RedirectBlueprint::definition();

        $fields = $blueprint->fields()->addValues([
            'source' => $redirect->source(),
            'destination' => $redirect->destination(),
            'response_code' => $redirect->responseCode()->value,
            'forward_query_string' => $redirect->forwardQueryString(),
            'description' => $redirect->description(),
            'site' => $redirect->site(),
        ])->preProcess();

        $hit = config('advanced-seo.redirects.hits.enabled') ? $redirect->hit() : null;

        return Inertia::render('advanced-seo::Redirects/Edit', [
            'title' => __('advanced-seo::messages.redirect_edit_title'),
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values()->all(),
            'meta' => $fields->meta()->all(),
            'enabled' => $redirect->enabled(),
            'submitUrl' => cp_route('advanced-seo.redirects.update', $redirect->id()),
            'testUrl' => $redirect->sourceUrl(),
            'hits' => config('advanced-seo.redirects.hits.enabled') ? [
                'count' => $hit?->count() ?? 0,
                'last_hit_at' => $hit?->lastHitAtIso(),
            ] : null,
        ]);
    }

    public function store(Request $request): array
    {
        throw_unless(RedirectsFeature::enabled(), new NotFoundHttpException);

        $this->authorize('create', Redirect::class);

        $values = $this->validateAndProcess($request, null);
        $values['enabled'] = $request->boolean('enabled', true);

        $redirect = $this->fill(Redirects::make(), $values)->save();

        return ['redirect' => $redirect->editUrl()];
    }

    public function update(Request $request, Redirect $redirect): array
    {
        throw_unless(RedirectsFeature::enabled(), new NotFoundHttpException);

        $this->authorize('edit', $redirect);

        $values = $this->validateAndProcess($request, $redirect);
        $values['enabled'] = $request->boolean('enabled', true);

        $this->fill($redirect, $values)->save();

        return ['redirect' => $redirect->editUrl()];
    }

    public function destroy(Redirect $redirect)
    {
        throw_unless(RedirectsFeature::enabled(), new NotFoundHttpException);

        $this->authorize('delete', $redirect);

        $redirect->delete();

        return response('', 200);
    }

    protected function validateAndProcess(Request $request, ?Redirect $redirect): array
    {
        $fields = RedirectBlueprint::definition()->fields()->addValues($request->all());

        $fields->validator()->withReplacements([
            'id' => $redirect?->id(),
            'site' => $request->input('site', Site::default()->handle()),
        ])->validate();

        return $fields->process()->values()->all();
    }

    protected function fill(Redirect $redirect, array $values): Redirect
    {
        return $redirect
            ->source(Arr::get($values, 'source'))
            ->destination(Arr::get($values, 'destination'))
            ->responseCode(ResponseCode::from(Arr::get($values, 'response_code') ?? ResponseCode::Permanent->value))
            ->enabled(Arr::get($values, 'enabled') ?? true)
            ->forwardQueryString((bool) Arr::get($values, 'forward_query_string', true))
            ->description(Arr::get($values, 'description'))
            ->site(Arr::get($values, 'site', Site::selected()->handle()));
    }
}
