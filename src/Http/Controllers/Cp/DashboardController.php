<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Facades\Seo;
use Aerni\AdvancedSeo\Features\Redirects;
use Aerni\AdvancedSeo\SeoSets\SeoSet;
use Aerni\AdvancedSeo\SeoSets\SeoSetGroup;
use Inertia\Inertia;
use Inertia\Response;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

class DashboardController extends CpController
{
    public function __invoke(): Response
    {
        $groups = Seo::groups()->filter(fn (SeoSetGroup $group) => User::current()->can('viewAny', [SeoSet::class, $group]));

        $canViewRedirects = Redirects::enabled() && User::current()->can('viewAny', Redirect::class);

        throw_unless($groups->isNotEmpty() || $canViewRedirects, new NotFoundHttpException);

        return Inertia::render('advanced-seo::Dashboard', [
            'groups' => $groups,
            'redirects' => $canViewRedirects ? ['url' => cp_route('advanced-seo.redirects.index'), 'icon' => 'moved'] : null,
        ]);
    }
}
