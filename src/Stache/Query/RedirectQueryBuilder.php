<?php

namespace Aerni\AdvancedSeo\Stache\Query;

use Aerni\AdvancedSeo\Concerns\QueriesRedirectSources;
use Aerni\AdvancedSeo\Contracts\RedirectQueryBuilder as Contract;
use Statamic\Facades\Stache;

class RedirectQueryBuilder extends Builder implements Contract
{
    use QueriesRedirectSources;

    protected function getOrderKeyValuesByIndex()
    {
        return collect($this->orderBys)->mapWithKeys(function ($orderBy) {
            if (in_array($orderBy->sort, ['hits', 'last_hit_at'], true)) {
                return [$orderBy->sort => $this->hitOrderValues($orderBy->sort)];
            }

            return [$orderBy->sort => $this->store->index($orderBy->sort)->items()->all()];
        });
    }

    protected function hitOrderValues(string $column): array
    {
        $index = $column === 'hits' ? 'count' : 'last_hit_at';
        $hitValues = Stache::store('redirect-hits')->index($index)->items();

        return $this->store->paths()->keys()
            ->mapWithKeys(fn ($id) => [$id => $hitValues[$id] ?? 0])
            ->all();
    }
}
