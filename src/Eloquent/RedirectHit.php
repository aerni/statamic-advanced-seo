<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectHit as Contract;
use Aerni\AdvancedSeo\Redirects\RedirectHit as StacheRedirectHit;
use Illuminate\Database\Eloquent\Model;

class RedirectHit extends StacheRedirectHit
{
    protected ?Model $model = null;

    public static function fromModel(Model $model): Contract
    {
        return (new static)
            ->model($model)
            ->redirect($model->redirect)
            ->count($model->count)
            ->lastHitAt($model->last_hit_at);
    }

    public function toModel(): Model
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source): Model
    {
        $model = app('statamic.eloquent.redirect_hit.model');

        return $model::firstOrNew(['redirect' => $source->redirect()])->fill([
            'count' => $source->count(),
            'last_hit_at' => $source->lastHitAt(),
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
