<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization as Contract;
use Aerni\AdvancedSeo\SeoSets\SeoSetLocalization as StacheSeoSetLocalization;
use Illuminate\Database\Eloquent\Model;

class SeoSetLocalization extends StacheSeoSetLocalization
{
    protected ?Model $model = null;

    public static function fromModel(Model $model): Contract
    {
        return (new static)
            ->model($model)
            ->seoSet("{$model->type}::{$model->handle}")
            ->locale($model->locale)
            ->data($model->data);
    }

    public function toModel(): Model
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source): Model
    {
        $model = app('statamic.eloquent.seo_set_localization.model');

        return $model::firstOrNew([
            'type' => $source->type(),
            'handle' => $source->handle(),
            'locale' => $source->locale(),
        ])->fill([
            'data' => $source->fileData(),
        ]);
    }

    public function model(?Model $model = null): Model|static|null
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        return $this;
    }
}
