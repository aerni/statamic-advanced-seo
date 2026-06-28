<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Blueprints\RedirectBlueprint;
use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Facades\Redirects;
use Aerni\AdvancedSeo\Http\Resources\Cp\Redirects\Redirects as RedirectsResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Inertia\Inertia;
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

            if (! $sortField && ! request('search')) {
                $sortField = 'source';
                $sortDirection = 'asc';
            }

            if ($sortField) {
                $query->orderBy($sortField, $sortDirection);
            }

            $redirects = $query->paginate(Statamic::cpPerPage(request('perPage')));

            return (new RedirectsResource($redirects))
                ->additional(['meta' => ['activeFilterBadges' => $activeFilterBadges]]);
        }

        $hasRedirects = Redirects::query()
            ->whereIn('site', Site::authorized()->map->handle()->all())
            ->first() !== null;

        return Inertia::render('advanced-seo::Redirects/Index', [
            'title' => __('advanced-seo::messages.redirects'),
            'createUrl' => cp_route('advanced-seo.redirects.create'),
            'listingUrl' => cp_route('advanced-seo.redirects.index'),
            'canCreate' => User::current()->can('create', Redirect::class),
            'filters' => Scope::filters('redirects'),
            'hasRedirects' => $hasRedirects,
        ]);
    }

    public function create(): mixed
    {
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
        $this->authorize('edit', $redirect);

        $blueprint = RedirectBlueprint::definition();

        $fields = $blueprint->fields()->addValues([
            'source' => $redirect->source(),
            'destination' => $redirect->destination(),
            'type' => $redirect->type()->value,
            'description' => $redirect->description(),
            'site' => $redirect->site(),
        ])->preProcess();

        return Inertia::render('advanced-seo::Redirects/Edit', [
            'title' => __('advanced-seo::messages.redirect_edit_title'),
            'blueprint' => $blueprint->toPublishArray(),
            'values' => $fields->values()->all(),
            'meta' => $fields->meta()->all(),
            'enabled' => $redirect->enabled(),
            'submitUrl' => cp_route('advanced-seo.redirects.update', $redirect->id()),
            'testUrl' => $redirect->sourceUrl(),
        ]);
    }

    public function store(Request $request): array
    {
        $this->authorize('create', Redirect::class);

        $values = $this->validateAndProcess($request, null);
        $values['enabled'] = $request->boolean('enabled', true);

        $redirect = $this->fill(Redirects::make(), $values)->save();

        return ['redirect' => $redirect->editUrl()];
    }

    public function update(Request $request, Redirect $redirect): array
    {
        $this->authorize('edit', $redirect);

        $values = $this->validateAndProcess($request, $redirect);
        $values['enabled'] = $request->boolean('enabled', true);

        $this->fill($redirect, $values)->save();

        return ['redirect' => $redirect->editUrl()];
    }

    public function destroy(Redirect $redirect)
    {
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
            ->type(RedirectType::from(Arr::get($values, 'type') ?? RedirectType::Permanent->value))
            ->enabled(Arr::get($values, 'enabled') ?? true)
            ->description(Arr::get($values, 'description'))
            ->site(Arr::get($values, 'site', Site::selected()->handle()));
    }
}
