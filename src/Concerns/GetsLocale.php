<?php

namespace Aerni\AdvancedSeo\Concerns;

use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

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
            return $data->get('locale', Site::current()->handle());
        }

        return Site::current()->handle();
    }
}
