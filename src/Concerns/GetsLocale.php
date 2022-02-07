<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Facades\Site;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;

trait GetsLocale
{
    use ShouldHandleRoute;

    protected function getLocale(Entry|Term|Collection $data = null): string
    {
        if ($data instanceof Entry) {
            return $data->locale();
        }

        if ($data instanceof Term) {
            return $this->isCpRoute() ? basename(request()->path()) : Site::current()->handle();
        }

        if ($data instanceof Collection) {
            return $data->get('locale');
        }

        return Site::current()->handle();
    }
}
