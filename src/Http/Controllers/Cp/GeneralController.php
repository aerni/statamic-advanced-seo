<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Statamic\Support\Arr;
use Statamic\Facades\Site;
use Illuminate\Http\Request;
use Aerni\AdvancedSeo\Facades\Seo;
use Illuminate\Support\Collection;
use Aerni\AdvancedSeo\Facades\Storage;
use Statamic\Http\Controllers\CP\CpController;
use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;

class GeneralController extends CpController
{
    public function index()
    {
        $data = $this->getData()->all();

        // TODO: Can I also make this work without the with() method? I probably only have to pass data to determine which fields to show.
        $blueprint = GeneralBlueprint::make()->get();

        $fields = $blueprint
            ->fields()
            ->addValues($data)
            ->preProcess();

        return view('advanced-seo::cp/general', [
            'title' => 'General SEO Settings',
            'action' => cp_route('advanced-seo.general.store'),
            'blueprint' => $blueprint->toPublishArray(),
            'meta' => $fields->meta(),
            'values' => $fields->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $blueprint = GeneralBlueprint::make()->get();

        $fields = $blueprint->fields()->addValues($data);

        $fields->validate();

        $values = Arr::removeNullValues($fields->process()->values()->all());

        $this->storeData($values);
    }

    public function getData(): Collection
    {
        $set = Seo::find('site', 'general');

        if (! $set) {
            return collect();
        }

        return $set->inSelectedSite()->data();
    }

    public function storeData(array $data): void
    {
        $set = Seo::find('site', 'general');

        if ($set) {
            $set->inDefaultSite()->data($data);
        } else {
            $set = Seo::make()->type('site')->handle('general');
            $localization = $set->makeLocalization(Site::default()->handle())->data($data);
            $set->addLocalization($localization);
        }

        $set->save();
    }
}
