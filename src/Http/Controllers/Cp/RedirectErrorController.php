<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Aerni\AdvancedSeo\Http\Resources\Cp\Redirects\Errors as ErrorsResource;
use Aerni\AdvancedSeo\Redirects\ErrorHandledChecker;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Query\OrderBy;
use Statamic\Statamic;

class RedirectErrorController extends CpController
{
    public function index(Request $request)
    {
        throw_unless(RedirectsFeature::enabled() && config('advanced-seo.redirects.errors.enabled'), new NotFoundHttpException);

        $this->authorize('viewAny', Redirect::class);

        $sites = Site::authorized()->map->handle()->all();

        if ($request->wantsJson()) {
            $query = RedirectFacade::errors()->query()->whereIn('site', $sites);

            if ($search = $request->input('search')) {
                $query->where('url', 'LIKE', '%'.$search.'%');
            }

            $sortField = OrderBy::column($request->input('sort', 'hits'));
            $sortDirection = $request->input('order', 'desc');

            $query->orderBy($sortField === 'hits' ? 'count' : $sortField, $sortDirection);

            $errors = $query->paginate(Statamic::cpPerPage($request->input('perPage')));

            $resource = new ErrorsResource($errors);
            $resource->handledChecker = ErrorHandledChecker::for($sites);

            return $resource;
        }

        $hasErrors = RedirectFacade::errors()->query()->whereIn('site', $sites)->first() !== null;

        return Inertia::render('advanced-seo::Redirects/Errors', [
            'title' => __('advanced-seo::messages.redirect_errors'),
            'listingUrl' => cp_route('advanced-seo.redirects.errors.index'),
            'hasErrors' => $hasErrors,
        ]);
    }
}
