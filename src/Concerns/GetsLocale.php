<?php

namespace Aerni\AdvancedSeo\Concerns;

use Illuminate\Support\Arr;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

trait GetsLocale
{
    use ShouldHandleRoute;

    protected function getLocale(mixed $data): string
    {
        if ($data instanceof Entry) {
            return $data->locale();
        }

        if ($data instanceof Term) {
            return $this->isCpRoute() ? basename(request()->path()) : Site::current()->handle();
        }

        if (is_array($data)) {
            Arr::get($data, 'locale');
        }

        return Site::current()->handle();
    }
}
