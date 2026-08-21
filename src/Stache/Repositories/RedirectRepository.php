<?php

namespace Aerni\AdvancedSeo\Stache\Repositories;

use Aerni\AdvancedSeo\Contracts\Redirect;
use Aerni\AdvancedSeo\Contracts\RedirectQueryBuilder;
use Aerni\AdvancedSeo\Contracts\RedirectRepository as Contract;
use Illuminate\Support\Collection;
use Statamic\Stache\Stache;
use Statamic\Stache\Stores\Store;

class RedirectRepository implements Contract
{
    protected Store $store;

    public function __construct(protected Stache $stache)
    {
        $this->store = $stache->store('redirects');
    }

    public function make(): Redirect
    {
        return app(Redirect::class);
    }

    public function find(string $id): ?Redirect
    {
        return $this->query()->where('id', $id)->first();
    }

    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function query(): RedirectQueryBuilder
    {
        return app(RedirectQueryBuilder::class);
    }

    public function save(Redirect $redirect): void
    {
        $this->store->save($redirect);
    }

    public function delete(Redirect $redirect): void
    {
        $this->store->delete($redirect);
    }

    public static function bindings(): array
    {
        return [
            Redirect::class => \Aerni\AdvancedSeo\Redirects\Redirect::class,
            RedirectQueryBuilder::class => \Aerni\AdvancedSeo\Stache\Query\RedirectQueryBuilder::class,
        ];
    }
}
