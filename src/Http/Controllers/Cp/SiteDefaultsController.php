<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Repositories\SiteDefaultsRepository;
use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;

class SiteDefaultsController extends CpController
{
    const DEFAULTS = [
        'general',
        'marketing',
    ];

    protected function getSiteRepository(string $handle): SiteDefaultsRepository
    {
        return new SiteDefaultsRepository($handle);
    }

    public function edit(Request $request, string $handle)
    {
        // We only want to continue if the requested default should exist.
        if (! in_array($handle, self::DEFAULTS)) {
            return $this->pageNotFound();
        }

        $repository = $this->getSiteRepository($handle);

        $site = $request->site ?? Site::selected()->handle();

        $siteDefaults = $repository->get($site)->all();

        $blueprint = $repository->blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues($siteDefaults)
            ->preProcess();

        return view('advanced-seo::cp/edit', [
            'breadcrumbTitle' => __('advanced-seo::messages.site'),
            'breadcrumbUrl' => cp_route('advanced-seo.site.index'),
            'title' => 'General SEO Settings',
            'action' => cp_route('advanced-seo.site.update', $handle),
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values(),
        ]);
    }

    public function update(string $handle, Request $request)
    {
        $site = $request->site ?? Site::selected()->handle();

        $repository = $this->getSiteRepository($handle);
        $blueprint = $repository->blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        $repository->save($site, $values);
    }
}
