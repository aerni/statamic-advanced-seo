<?php

namespace Aerni\AdvancedSeo\Stache\Stores;

use Statamic\Stache\Stores\AggregateStore;

class SeoStore extends AggregateStore
{
    protected $childStore = SeoDefaultsStore::class;

    public function key()
    {
        return 'seo';
    }

    public function discoverStores()
    {
        return collect([
            'site' => $this->store('site'),
            'collections' => $this->store('collections'),
            'taxonomies' => $this->store('taxonomies'),
        ]);
    }
}
