<?php

namespace Aerni\AdvancedSeo\Stache\Repositories;

use Aerni\AdvancedSeo\Contracts\RedirectError;
use Aerni\AdvancedSeo\Contracts\RedirectErrorQueryBuilder;
use Aerni\AdvancedSeo\Contracts\RedirectErrorRepository as Contract;
use Illuminate\Support\Collection;
use Statamic\Facades\Site;
use Statamic\Stache\Stache;
use Statamic\Stache\Stores\Store;

class RedirectErrorRepository implements Contract
{
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
        $site ??= Site::current()->handle();

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

    public static function bindings(): array
    {
        return [
            RedirectError::class => \Aerni\AdvancedSeo\Redirects\RedirectError::class,
            RedirectErrorQueryBuilder::class => \Aerni\AdvancedSeo\Stache\Query\RedirectErrorQueryBuilder::class,
        ];
    }
}
