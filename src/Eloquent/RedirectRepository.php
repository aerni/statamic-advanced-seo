<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Contracts\RedirectQueryBuilder;
use Aerni\AdvancedSeo\Contracts\RedirectRepository as Contract;
use Illuminate\Support\Collection;

class RedirectRepository implements Contract
{
    public function make(): Redirect
    {
        return app(Redirect::class);
    }

    public function find(string $id): ?Redirect
    {
        $model = app('statamic.eloquent.redirect.model')::query()->find($id);

        if (! $model) {
            return null;
        }

        return app(Redirect::class)::fromModel($model);
    }

    public function all(): Collection
    {
        return app('statamic.eloquent.redirect.model')::all()
            ->map(fn ($model) => app(Redirect::class)::fromModel($model));
    }

    public function query(): RedirectQueryBuilder
    {
        return app(RedirectQueryBuilder::class, [
            'builder' => app('statamic.eloquent.redirect.model')::query(),
        ]);
    }

    public function save(Redirect $redirect): void
    {
        $model = $redirect->toModel();

        $model->save();

        $redirect->model($model->fresh());
    }

    public function delete(Redirect $redirect): void
    {
        $redirect->model()->delete();
    }

    public static function bindings(): array
    {
        return [
            Redirect::class => \Aerni\AdvancedSeo\Eloquent\Redirect::class,
            RedirectQueryBuilder::class => \Aerni\AdvancedSeo\Eloquent\RedirectQueryBuilder::class,
        ];
    }
}
