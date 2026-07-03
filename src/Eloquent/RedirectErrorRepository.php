<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Contracts\RedirectError;
use Aerni\AdvancedSeo\Contracts\RedirectErrorQueryBuilder;
use Aerni\AdvancedSeo\Contracts\RedirectErrorRepository as Contract;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;

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

    public function record(string $url, string $site): void
    {
        $model = app('statamic.eloquent.redirect_error.model');

        $now = now()->timestamp;

        if (! $model::query()->where('url', $url)->where('site', $site)->exists()) {
            $this->evictIfAtCapacity();

            $model::create([
                'id' => Stache::generateId(),
                'url' => $url,
                'site' => $site,
                'count' => 0,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);
        }

        $model::query()
            ->where('url', $url)
            ->where('site', $site)
            ->increment('count', 1, ['last_seen_at' => $now]);
    }

    protected function evictIfAtCapacity(): void
    {
        $max = (int) config('advanced-seo.redirects.errors.max_records', 1000);

        $model = app('statamic.eloquent.redirect_error.model');

        $count = $model::query()->count();

        if ($count < $max) {
            return;
        }

        $ids = $model::query()
            ->orderBy('count')
            ->orderBy('last_seen_at')
            ->limit($count - $max + 1)
            ->pluck('id');

        $model::query()->whereIn('id', $ids)->delete();
    }

    public static function bindings(): array
    {
        return [
            RedirectError::class => \Aerni\AdvancedSeo\Eloquent\RedirectError::class,
            RedirectErrorQueryBuilder::class => \Aerni\AdvancedSeo\Eloquent\RedirectErrorQueryBuilder::class,
        ];
    }
}
