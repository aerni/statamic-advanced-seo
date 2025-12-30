<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Contracts\SeoSet;
use Aerni\AdvancedSeo\Contracts\SeoSetGroup;
use Aerni\AdvancedSeo\Facades\Seo;
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

        throw_unless($groups->isNotEmpty(), new NotFoundHttpException);

        return Inertia::render('advanced-seo::Dashboard', [
            'groups' => $groups,
        ]);
    }
}
