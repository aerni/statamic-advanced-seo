<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectError as Contract;
use Aerni\AdvancedSeo\Redirects\RedirectError as StacheRedirectError;
use Illuminate\Database\Eloquent\Model;

class RedirectError extends StacheRedirectError
{
    protected ?Model $model = null;

    public static function fromModel(Model $model): Contract
    {
        return (new static)
            ->model($model)
            ->id($model->id)
            ->url($model->url)
            ->site($model->site)
            ->count($model->count)
            ->firstSeenAt($model->first_seen_at)
            ->lastSeenAt($model->last_seen_at);
    }

    public function toModel(): Model
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source): Model
    {
        $model = app('statamic.eloquent.redirect_error.model');

        return $model::firstOrNew(['id' => $source->id()])->fill([
            'url' => $source->url(),
            'site' => $source->site(),
            'count' => $source->count(),
            'first_seen_at' => $source->firstSeenAt(),
            'last_seen_at' => $source->lastSeenAt(),
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
