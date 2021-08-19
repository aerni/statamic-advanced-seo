<?php

namespace Aerni\AdvancedSeo\Stache;

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
            'collections' => $this->store('collections'),
            'taxonomies' => $this->store('taxonomies'),
            'site' => $this->store('site'),
        ]);
    }
}
