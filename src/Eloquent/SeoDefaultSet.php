<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\SeoDefaultSet as SeoDefaultSetContract;
use Aerni\AdvancedSeo\Data\SeoDefaultSet as StacheSeoDefaultSet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class SeoDefaultSet extends StacheSeoDefaultSet
{
    protected Model $model;

    public static function fromModel(Model $model): SeoDefaultSetContract
    {
        $seoDefaultSet = (new static)
            ->type($model->type)
            ->handle($model->handle)
            ->model($model);

        $model->data->each(function ($data, $site) use ($seoDefaultSet) {
            $variables = $seoDefaultSet->makeLocalization($site);

            $variables
                ->merge(Arr::except($data, 'origin'))
                ->origin(Arr::get($data, 'origin'));

            $seoDefaultSet->addLocalization($variables);
        });

        return $seoDefaultSet;
    }

    public function toModel(): Model
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(SeoDefaultSetContract $source): Model
    {
        $class = app('statamic.eloquent.advanced_seo.model');

        return $class::firstOrNew([
            'type' => $source->type(),
            'handle' => $source->handle(),
        ])->fill([
            'data' => $source->localizations()->map->fileData(),
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
