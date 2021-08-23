<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Facades\Aerni\AdvancedSeo\Repositories\SiteDefaultsRepository;
use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Statamic\Http\Controllers\CP\CpController;

class SiteDefaultsController extends CpController
{
    public function edit(Request $request)
    {
        $site = $request->site ?? Site::selected()->handle();

        $siteDefaults = SiteDefaultsRepository::get($site)->all();

        $blueprint = SiteDefaultsRepository::blueprint();

        $fields = $blueprint
            ->fields()
            ->addValues($siteDefaults)
            ->preProcess();

        return view('advanced-seo::cp/edit', [
            'title' => 'General SEO Settings',
            'action' => cp_route('advanced-seo.site.general.update'),
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values(),
        ]);
    }

    public function update(Request $request)
    {
        $site = $request->site ?? Site::selected()->handle();

        $blueprint = SiteDefaultsRepository::blueprint();

        $fields = $blueprint->fields()->addValues($request->all());

        $fields->validate();

        $values = $fields->process()->values();

        SiteDefaultsRepository::save($site, $values);
    }
}
