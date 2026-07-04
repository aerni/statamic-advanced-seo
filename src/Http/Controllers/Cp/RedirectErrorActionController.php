<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Facades\Redirect as RedirectFacade;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Illuminate\Http\Request;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Http\Controllers\CP\ActionController;

class RedirectErrorActionController extends ActionController
{
    public function run(Request $request)
    {
        throw_unless(RedirectsFeature::enabled() && config('advanced-seo.redirects.errors.enabled'), new NotFoundHttpException);

        return parent::run($request);
    }

    public function bulkActions(Request $request)
    {
        throw_unless(RedirectsFeature::enabled() && config('advanced-seo.redirects.errors.enabled'), new NotFoundHttpException);

        return parent::bulkActions($request);
    }

    protected function getSelectedItems($items, $context)
    {
        return $items->map(fn ($id) => RedirectFacade::errors()->find($id))->filter();
    }
}
