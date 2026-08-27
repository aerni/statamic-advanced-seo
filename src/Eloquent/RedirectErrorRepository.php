<?php

namespace Aerni\AdvancedSeo\Eloquent;

use Aerni\AdvancedSeo\Concerns\HasRedirectErrorLimits;
use Aerni\AdvancedSeo\Contracts\RedirectError;
use Aerni\AdvancedSeo\Contracts\RedirectErrorQueryBuilder;
use Aerni\AdvancedSeo\Contracts\RedirectErrorRepository as Contract;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\Facades\Stache;

class RedirectErrorRepository implements Contract
{
    use HasRedirectErrorLimits;

    public function make(): RedirectError
    {
        return app(RedirectError::class);
    }

    public function find(string $id): ?RedirectError
    {
        $model = $this->model()::query()->find($id);

        return $model ? app(RedirectError::class)::fromModel($model) : null;
    }

    public function findByUrl(string $url, ?string $site = null): ?RedirectError
    {
        $site ??= Site::default()->handle();

        $model = $this->model()::query()
            ->where('url', $url)
            ->where('site', $site)
            ->first();

        return $model ? app(RedirectError::class)::fromModel($model) : null;
    }

    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function query(): RedirectErrorQueryBuilder
    {
        return app(RedirectErrorQueryBuilder::class, [
            'builder' => $this->model()::query(),
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

    public function deleteBySites(array $sites): void
    {
        $this->model()::query()->whereIn('site', $sites)->delete();
    }

    public function deleteByIds(array $ids): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            $this->model()::query()->whereIn('id', $chunk)->delete();
        }
    }

    public function record(string $url, string $site): void
    {
        $model = $this->model();

        $now = now()->timestamp;

        if (! $model::query()->where('url', $url)->where('site', $site)->exists()) {
            $this->makeRoomForNewRecord();

            try {
                $model::create([
                    'id' => Stache::generateId(),
                    'url' => $url,
                    'site' => $site,
                    'count' => 1,
                    'first_seen_at' => $now,
                    'last_seen_at' => $now,
                ]);

                return;
            } catch (UniqueConstraintViolationException) {
                // A concurrent request created it first; fall through to increment.
            }
        }

        $model::query()
            ->where('url', $url)
            ->where('site', $site)
            ->increment('count', 1, ['last_seen_at' => $now]);
    }

    /**
     * Evict lowest-value errors when at the record cap so a new one can be stored.
     */
    protected function makeRoomForNewRecord(): void
    {
        $max = $this->maxRecords();

        if ($max === null) {
            return;
        }

        $model = $this->model();

        $count = $model::query()->count();

        if ($count < $max) {
            return;
        }

        $ids = $model::query()
            ->orderBy('count')
            ->orderBy('last_seen_at')
            ->limit($count - $max + 1)
            ->pluck('id')
            ->all();

        $this->deleteByIds($ids);
    }

    protected function model(): string
    {
        return app('statamic.eloquent.redirect_error.model')::class;
    }

    public static function bindings(): array
    {
        return [
            RedirectError::class => \Aerni\AdvancedSeo\Eloquent\RedirectError::class,
            RedirectErrorQueryBuilder::class => \Aerni\AdvancedSeo\Eloquent\RedirectErrorQueryBuilder::class,
        ];
    }
}
