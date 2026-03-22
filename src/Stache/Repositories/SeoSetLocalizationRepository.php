<?php

namespace Aerni\AdvancedSeo\Stache\Repositories;

use Aerni\AdvancedSeo\Contracts\SeoSetLocalization;
use Aerni\AdvancedSeo\Contracts\SeoSetLocalizationRepository as Contract;
use Illuminate\Support\Collection;
use Statamic\Stache\Stache;
use Statamic\Stache\Stores\Store;

class SeoSetLocalizationRepository implements Contract
{
    protected Store $store;

    public function __construct(protected Stache $stache)
    {
        $this->store = $stache->store('seo-set-localizations');
    }

    public function make(): SeoSetLocalization
    {
        return app(SeoSetLocalization::class);
    }

    public function find(string $id): ?SeoSetLocalization
    {
        return $this->store->getItem($id);
    }

    public function all(): Collection
    {
        $keys = $this->store->paths()->keys();

        return $this->store->getItems($keys);
    }

    public function whereSeoSet(string $id): Collection
    {
        $keys = $this->store
            ->index('seoSet')
            ->items()
            ->filter(fn ($seoSet) => $seoSet === $id)
            ->keys();

        return $this->store->getItems($keys);
    }

    public function save(SeoSetLocalization $localization): void
    {
        $this->store->save($localization);
    }

    public function delete(SeoSetLocalization $localization): void
    {
        $this->store->delete($localization);
    }

    public static function bindings(): array
    {
        return [
            SeoSetLocalization::class => \Aerni\AdvancedSeo\SeoSets\SeoSetLocalization::class,
        ];
    }
}
