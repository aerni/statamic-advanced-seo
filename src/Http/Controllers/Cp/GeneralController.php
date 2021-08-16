<?php

namespace Aerni\AdvancedSeo\Http\Controllers\Cp;

use Aerni\AdvancedSeo\Blueprints\GeneralBlueprint;
use Aerni\AdvancedSeo\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Support\Arr;

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
        return Storage::inSelectedSite()->get('general');
    }

    public function storeData(array $data): void
    {
        Storage::inSelectedSite()->store('general', $data);
    }
}
