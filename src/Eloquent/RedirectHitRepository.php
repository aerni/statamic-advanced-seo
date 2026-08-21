<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectHit;
use Aerni\AdvancedSeo\Contracts\RedirectHitQueryBuilder;
use Aerni\AdvancedSeo\Contracts\RedirectHitRepository as Contract;
use Illuminate\Support\Collection;

class RedirectHitRepository implements Contract
{
    public function make(): RedirectHit
    {
        return app(RedirectHit::class);
    }

    public function find(string $redirect): ?RedirectHit
    {
        $model = app('statamic.eloquent.redirect_hit.model')::query()->find($redirect);

        if (! $model) {
            return null;
        }

        return app(RedirectHit::class)::fromModel($model);
    }

    public function all(): Collection
    {
        return app('statamic.eloquent.redirect_hit.model')::all()
            ->map(fn ($model) => app(RedirectHit::class)::fromModel($model));
    }

    public function query(): RedirectHitQueryBuilder
    {
        return app(RedirectHitQueryBuilder::class, [
            'builder' => app('statamic.eloquent.redirect_hit.model')::query(),
        ]);
    }

    public function save(RedirectHit $hit): void
    {
        $model = $hit->toModel();

        $model->save();

        $hit->model($model->fresh());
    }

    public function record(string $redirect): void
    {
        $model = app('statamic.eloquent.redirect_hit.model');

        $model::firstOrCreate(['redirect' => $redirect]);

        $model::whereKey($redirect)->increment('count', 1, [
            'last_hit_at' => now()->timestamp,
        ]);
    }

    public function delete(RedirectHit $hit): void
    {
        $hit->model()->delete();
    }

    public static function bindings(): array
    {
        return [
            RedirectHit::class => \Aerni\AdvancedSeo\Eloquent\RedirectHit::class,
            RedirectHitQueryBuilder::class => \Aerni\AdvancedSeo\Eloquent\RedirectHitQueryBuilder::class,
        ];
    }
}
