<?php

namespace Aerni\AdvancedSeo\Stache\Repositories;

use Aerni\AdvancedSeo\Concerns\HasRedirectErrorLimits;
use Aerni\AdvancedSeo\Contracts\RedirectError;
use Aerni\AdvancedSeo\Contracts\RedirectErrorQueryBuilder;
use Aerni\AdvancedSeo\Contracts\RedirectErrorRepository as Contract;
use Aerni\AdvancedSeo\Stache\Stores\RedirectErrorsStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Statamic\Facades\Site;
use Statamic\Stache\Stache;

class RedirectErrorRepository implements Contract
{
    use HasRedirectErrorLimits;

    protected RedirectErrorsStore $store;

    public function __construct(protected Stache $stache)
    {
        $this->store = $stache->store('redirect-errors');
    }

    public function make(): RedirectError
    {
        return app(RedirectError::class);
    }

    public function find(string $id): ?RedirectError
    {
        return $this->query()->where('id', $id)->first();
    }

    public function findByUrl(string $url, ?string $site = null): ?RedirectError
    {
        $site ??= Site::default()->handle();

        return $this->query()->where('url', $url)->where('site', $site)->first();
    }

    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function query(): RedirectErrorQueryBuilder
    {
        return app(RedirectErrorQueryBuilder::class);
    }

    public function save(RedirectError $error): void
    {
        $this->store->save($error);
    }

    public function delete(RedirectError $error): void
    {
        $this->store->delete($error);
    }

    public function deleteBySites(array $sites): void
    {
        $this->lock(function () use ($sites) {
            $keys = $this->store->index('site')->load()->items()
                ->filter(fn ($site) => in_array($site, $sites, true))
                ->keys()
                ->all();

            $this->store->deleteKeys($keys);
        });
    }

    public function deleteByIds(array $ids): void
    {
        $this->lock(fn () => $this->store->deleteKeys($ids));
    }

    /**
     * Concurrent writers race on the Stache path index: one process can delete a
     * file while another writes a stale index that still lists it (or the reverse),
     * leaving ghost or orphan error records. One global lock prevents that; locking
     * per url is not enough because different urls still create and evict in parallel.
     */
    public function record(string $url, string $site): void
    {
        $this->lock(function () use ($url, $site) {
            $error = $this->findByUrl($url, $site);

            if (! $error) {
                $this->makeRoomForNewRecord();

                $error = $this->make()->url($url)->site($site)->firstSeenAt(now()->timestamp);
            }

            $error
                ->count($error->count() + 1)
                ->lastSeenAt(now()->timestamp)
                ->save();
        });
    }

    /**
     * Evict lowest-value errors when at the record cap so a new one can be stored.
     * Must be called while holding the redirect-error lock.
     */
    protected function makeRoomForNewRecord(): void
    {
        $max = $this->maxRecords();

        if ($max === null) {
            return;
        }

        $count = $this->query()->count();

        if ($count < $max) {
            return;
        }

        $ids = $this->query()
            ->orderBy('count')
            ->orderBy('last_seen_at')
            ->limit($count - $max + 1)
            ->get()
            ->map->id()
            ->all();

        /**
         * Already holding the lock from record(). Call the store directly —
         * deleteByIds() would try to acquire the same lock again and deadlock.
         */
        $this->store->deleteKeys($ids);
    }

    protected function lock(callable $callback): mixed
    {
        return Cache::lock('advanced-seo::redirect-error', 10)->block(5, $callback);
    }

    public static function bindings(): array
    {
        return [
            RedirectError::class => \Aerni\AdvancedSeo\Redirects\RedirectError::class,
            RedirectErrorQueryBuilder::class => \Aerni\AdvancedSeo\Stache\Query\RedirectErrorQueryBuilder::class,
        ];
    }
}
