<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet as SeoDefaultSetContract;
use Aerni\AdvancedSeo\Data\SeoDefaultSet as StacheSeoDefaultSet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Statamic\Facades\Site;

class SeoDefaultSet extends StacheSeoDefaultSet
{
    protected Model $model;

    public static function fromModel(Model $model): SeoDefaultSetContract
    {
        $seoDefaultSet = (new static)
            ->type($model->type)
            ->handle($model->handle)
            ->model($model);

        $modelData = $model->data->all();

        // Load config data from the model (new format: config key at root)
        if (isset($modelData['config'])) {
            $seoDefaultSet->merge($modelData['config']);
        }

        // Get site-keyed localizations data (everything except config)
        $sitesData = Arr::except($modelData, 'config');

        // Iterate over site-keyed data (works for both single-site and multi-site)
        collect($sitesData)->each(function ($data, $site) use ($seoDefaultSet) {
            // Handle legacy format where each site had config/data structure (backward compatibility)
            if (isset($data['config']) && isset($data['data'])) {
                $localizationData = $data['data'];
                $origin = Arr::get($data['config'], 'origin');
            } else {
                // New format: flat data with optional origin key
                $localizationData = Arr::except($data, 'origin');
                $origin = Arr::get($data, 'origin');
            }

            $localization = $seoDefaultSet
                ->makeLocalization($site)
                ->merge($localizationData)
                ->origin($origin);

            $seoDefaultSet->addLocalization($localization);
        });

        return $seoDefaultSet;
    }

    public function toModel(): Model
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(SeoDefaultSetContract $source): Model
    {
        $class = app('advanced_seo.model');

        $localizationsData = $source->localizations()
            ->intersectByKeys($source->sites()->flip()) /* Only keep data of configured sites. */
            ->map->fileData()
            ->all();

        // Always use multi-site structure: config key + site-keyed data
        $data = array_merge(
            ['config' => $source->data()->all()], /* Config data from SeoDefaultSet */
            $localizationsData /* Site-keyed localization data */
        );

        return $class::firstOrNew([
            'type' => $source->type(),
            'handle' => $source->handle(),
        ])->fill([
            'data' => $data,
        ]);
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        return $this;
    }
}
