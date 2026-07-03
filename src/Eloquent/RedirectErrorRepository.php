<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectError;
use Aerni\AdvancedSeo\Contracts\RedirectErrorQueryBuilder;
use Aerni\AdvancedSeo\Contracts\RedirectErrorRepository as Contract;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;

class RedirectErrorRepository implements Contract
{
    public function make(): RedirectError
    {
        return app(RedirectError::class);
    }

    public function find(string $id): ?RedirectError
    {
        $model = app('statamic.eloquent.redirect_error.model')::query()->find($id);

        return $model ? app(RedirectError::class)::fromModel($model) : null;
    }

    public function findByUrl(string $url, ?string $site = null): ?RedirectError
    {
        $site ??= Site::default()->handle();

        $model = app('statamic.eloquent.redirect_error.model')::query()
            ->where('url', $url)
            ->where('site', $site)
            ->first();

        return $model ? app(RedirectError::class)::fromModel($model) : null;
    }

    public function all(): Collection
    {
        return app('statamic.eloquent.redirect_error.model')::all()
            ->map(fn ($model) => app(RedirectError::class)::fromModel($model));
    }

    public function query(): RedirectErrorQueryBuilder
    {
        return app(RedirectErrorQueryBuilder::class, [
            'builder' => app('statamic.eloquent.redirect_error.model')::query(),
        ]);
    }

    public function save(RedirectError $error): void
    {
        $model = $error->toModel();

        $model->save();

        $error->model($model->fresh());
    }

    public function delete(RedirectError $error): void
    {
        $error->model()->delete();
    }

    public static function bindings(): array
    {
        return [
            RedirectError::class => \Aerni\AdvancedSeo\Eloquent\RedirectError::class,
            RedirectErrorQueryBuilder::class => \Aerni\AdvancedSeo\Eloquent\RedirectErrorQueryBuilder::class,
        ];
    }
}
