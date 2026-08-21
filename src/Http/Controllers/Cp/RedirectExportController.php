<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Contracts\RedirectQueryBuilder;
use Aerni\AdvancedSeo\Enums\RedirectExportFormat;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Features\RedirectImportExport;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Http\Requests\FilteredRequest;
use Statamic\Query\OrderBy;
use Statamic\Query\Scopes\Filters\Concerns\QueriesFilters;
use Symfony\Component\HttpFoundation\Response;

class RedirectExportController extends CpController
{
    use QueriesFilters;

    public function __invoke(FilteredRequest $request, string $format): Response
    {
        throw_unless(RedirectImportExport::enabled(), new NotFoundHttpException);

        $this->authorize('manage', Redirect::class);

        $format = RedirectExportFormat::from($format);

        $query = $this->shouldApplyFilteredScope($request)
            ? $this->filteredRedirectsQuery($request)
            : null;

        return response()->streamDownload(
            fn () => print RedirectFacade::export($format, $query),
            'redirects-'.now()->format('Y-m-d-His').".{$format->value}",
            ['Content-Type' => $format->contentType()],
        );
    }

    protected function shouldApplyFilteredScope(FilteredRequest $request): bool
    {
        return $request->hasAny(['filters', 'search', 'sort', 'order']);
    }

    protected function filteredRedirectsQuery(FilteredRequest $request): RedirectQueryBuilder
    {
        $query = RedirectFacade::query();

        if ($search = $request->input('search')) {
            $query->where(function ($query) use ($search) {
                $query->where('source', 'LIKE', '%'.$search.'%')
                    ->orWhere('destination', 'LIKE', '%'.$search.'%');
            });
        }

        $this->queryFilters($query, $request->filters);

        $sortField = OrderBy::column($request->input('sort'));
        $sortDirection = $request->input('order', 'asc');

        if ($sortField === 'status') {
            $sortField = 'enabled';
        }

        if (in_array($sortField, ['hits', 'last_hit_at'], true) && ! config('advanced-seo.redirects.hits.enabled')) {
            $sortField = null;
        }

        if (! $sortField && ! $search) {
            $sortField = 'source';
            $sortDirection = 'asc';
        }

        if ($sortField) {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query;
    }
}
