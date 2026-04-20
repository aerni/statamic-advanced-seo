<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\SeoSetConfig as Contract;
use Aerni\AdvancedSeo\SeoSets\SeoSetConfig as StacheSeoSetConfig;
use Illuminate\Database\Eloquent\Model;

class SeoSetConfig extends StacheSeoSetConfig
{
    protected ?Model $model = null;

    public static function fromModel(Model $model): Contract
    {
        return (new static)
            ->model($model)
            ->seoSet("{$model->type}::{$model->handle}")
            ->enabled($model->data->get('enabled', true))
            ->editable($model->data->get('editable', true))
            ->origins($model->data->get('origins', []))
            ->data($model->data->except(['enabled', 'editable', 'origins']));
    }

    public function toModel(): Model
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source): Model
    {
        $model = app('statamic.eloquent.seo_set_config.model');

        return $model::firstOrNew([
            'type' => $source->type(),
            'handle' => $source->handle(),
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
