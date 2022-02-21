<?php

namespace Aerni\AdvancedSeo\Concerns;

use Statamic\Statamic;
use Statamic\Facades\Site;
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
            return Statamic::isCpRoute() ? basename(request()->path()) : Site::current()->handle();
        }

        if ($data instanceof Collection) {
            return Statamic::isCpRoute()
                ? $data->get('locale') ?? Site::selected()->handle()
                : Site::current()->handle();
        }

        return Statamic::isCpRoute() ? Site::selected()->handle() : Site::current()->handle();
    }
}
