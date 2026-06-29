<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\Redirect as Contract;
use Aerni\AdvancedSeo\Enums\RedirectType;
use Aerni\AdvancedSeo\Redirects\Redirect as StacheRedirect;
use Illuminate\Database\Eloquent\Model;

class Redirect extends StacheRedirect
{
    protected ?Model $model = null;

    public static function fromModel(Model $model): Contract
    {
        return (new static)
            ->model($model)
            ->id($model->id)
            ->source($model->source)
            ->destination($model->destination)
            ->type($model->type)
            ->site($model->site)
            ->enabled($model->enabled)
            ->forwardQueryString($model->forward_query_string ?? true)
            ->description($model->description);
    }

    public function toModel(): Model
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source): Model
    {
        $model = app('statamic.eloquent.redirect.model');

        return $model::firstOrNew(['id' => $source->id()])->fill([
            'source' => $source->source(),
            'destination' => $source->destination(),
            'type' => $source->type(),
            'site' => $source->site(),
            'enabled' => $source->enabled(),
            'forward_query_string' => $source->type() === RedirectType::Gone ? null : $source->forwardQueryString(),
            'description' => $source->description(),
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
