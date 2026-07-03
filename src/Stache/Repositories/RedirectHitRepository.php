<?php

namespace Aerni\AdvancedSeo\Stache\Repositories;

use Aerni\AdvancedSeo\Contracts\RedirectHit;
use Aerni\AdvancedSeo\Contracts\RedirectHitQueryBuilder;
use Aerni\AdvancedSeo\Contracts\RedirectHitRepository as Contract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Statamic\Stache\Stache;
use Statamic\Stache\Stores\Store;

class RedirectHitRepository implements Contract
{
    protected Store $store;

    public function __construct(protected Stache $stache)
    {
        $this->store = $stache->store('redirect-hits');
    }

    public function make(): RedirectHit
    {
        return app(RedirectHit::class);
    }

    public function find(string $redirect): ?RedirectHit
    {
        return $this->query()->where('id', $redirect)->first();
    }

    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function query(): RedirectHitQueryBuilder
    {
        return app(RedirectHitQueryBuilder::class);
    }

    public function save(RedirectHit $hit): void
    {
        $this->store->save($hit);
    }

    public function record(string $redirect): void
    {
        Cache::lock("advanced-seo::redirect-hit:{$redirect}", 10)
            ->block(5, function () use ($redirect) {
                $hit = $this->find($redirect) ?? $this->make()->redirect($redirect);

                $hit
                    ->count($hit->count() + 1)
                    ->lastHitAt(now()->timestamp)
                    ->save();
            });
    }

    public function delete(RedirectHit $hit): void
    {
        $this->store->delete($hit);
    }

    public static function bindings(): array
    {
        return [
            RedirectHit::class => \Aerni\AdvancedSeo\Redirects\RedirectHit::class,
            RedirectHitQueryBuilder::class => \Aerni\AdvancedSeo\Stache\Query\RedirectHitQueryBuilder::class,
        ];
    }
}
