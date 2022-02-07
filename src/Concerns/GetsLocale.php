<?php

namespace Aerni\AdvancedSeo\Concerns;

use Illuminate\Support\Arr;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

trait GetsLocale
{
    use ShouldHandleRoute;

    protected function getLocale(Entry|Term|array $data): ?string
    {
        if ($data instanceof Entry) {
            return $data->locale();
        }

        if ($data instanceof Term) {
            return $this->isCpRoute() ? basename(request()->path()) : Site::current()->handle();
        }

        return Arr::get($data, 'locale');
    }
}
