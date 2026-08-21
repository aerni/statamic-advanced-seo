<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Facades\Redirect;
use Aerni\AdvancedSeo\Features\Redirects as RedirectsFeature;
use Illuminate\Http\Request;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Http\Controllers\CP\ActionController;

class RedirectActionController extends ActionController
{
    public function run(Request $request)
    {
        throw_unless(RedirectsFeature::enabled(), new NotFoundHttpException);

        return parent::run($request);
    }

    public function bulkActions(Request $request)
    {
        throw_unless(RedirectsFeature::enabled(), new NotFoundHttpException);

        return parent::bulkActions($request);
    }

    protected function getSelectedItems($items, $context)
    {
        return $items->map(fn ($id) => Redirect::find($id))->filter();
    }
}
