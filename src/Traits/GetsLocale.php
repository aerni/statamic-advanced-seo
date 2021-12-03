<?php

namespace Aerni\AdvancedSeo\Traits;

use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Site;

trait GetsLocale
{
    protected function getLocale($data): ?string
    {
        if ($data instanceof Entry) {
            return $data->locale();
        }

        if ($data instanceof Term) {
            return str_contains(request()->path(), config('cp.route', 'cp'))
                ? basename(request()->path())
                : Site::current()->handle();
        }

        return null;
    }
}
