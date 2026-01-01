<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Data\SeoSet;
use Aerni\AdvancedSeo\Contracts\SeoSetGroup;
use Inertia\Inertia;
use Inertia\Response;
use Statamic\CP\Column;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;

class SeoSetIndexController extends CpController
{
    public function __invoke(SeoSetGroup $seoSetGroup): Response
    {
        $this->authorize('viewAny', [SeoSet::class, $seoSetGroup]);

        $items = $seoSetGroup->seoSets()
            ->filter(function (SeoSet $seoSet) {
                $localization = $seoSet->in(Site::selected());

                if (! $localization) {
                    return false;
                }

                if (! User::current()->can('edit', [SeoSet::class, $localization])) {
                    return false;
                }

                return User::current()->can('configure', [SeoSet::class, $seoSet]) || $seoSet->enabled();
            })->values();

        return Inertia::render("advanced-seo::{$seoSetGroup->title()}/Index", [
            'title' => __("advanced-seo::messages.{$seoSetGroup->type()}"),
            'icon' => $seoSetGroup->icon(),
            'items' => $items,
            'columns' => [
                Column::make('title')->label(__('Title')),
                Column::make('status')->label(__('Status')),
            ],
        ]);
    }
}
