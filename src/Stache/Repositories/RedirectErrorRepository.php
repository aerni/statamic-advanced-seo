<?php

namespace Aerni\AdvancedSeo\Stache\Repositories;

use Aerni\AdvancedSeo\Concerns\HasRedirectErrorLimits;
use Aerni\AdvancedSeo\Contracts\RedirectError;
use Aerni\AdvancedSeo\Contracts\RedirectErrorQueryBuilder;
use Aerni\AdvancedSeo\Contracts\RedirectErrorRepository as Contract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Statamic\Facades\Site;
use Statamic\Stache\Stache;
use Statamic\Stache\Stores\Store;

class RedirectErrorRepository implements Contract
{
    use HasRedirectErrorLimits;

    protected Store $store;

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

    public function record(string $url, string $site): void
    {
        Cache::lock("advanced-seo::redirect-error:{$site}:{$url}", 10)->block(5, function () use ($url, $site) {
            $error = $this->findByUrl($url, $site);

            if (! $error) {
                $this->ensureCapacityForError();

                $error = $this->make()->url($url)->site($site)->firstSeenAt(now()->timestamp);
            }

            $error
                ->count($error->count() + 1)
                ->lastSeenAt(now()->timestamp)
                ->save();
        });
    }

    protected function ensureCapacityForError(): void
    {
        $max = $this->maxRecords();

        if ($max === null) {
            return;
        }

        $count = $this->query()->count();

        if ($count < $max) {
            return;
        }

        $this->query()
            ->orderBy('count')
            ->orderBy('last_seen_at')
            ->limit($count - $max + 1)
            ->get()
            ->each->delete();
    }

    public static function bindings(): array
    {
        return [
            RedirectError::class => \Aerni\AdvancedSeo\Redirects\RedirectError::class,
            RedirectErrorQueryBuilder::class => \Aerni\AdvancedSeo\Stache\Query\RedirectErrorQueryBuilder::class,
        ];
    }
}
