<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Data\DefaultsData;
use Aerni\AdvancedSeo\Data\SeoDefaultSet;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Taxonomy;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\TermBlueprintFound;
use Statamic\Facades\Site;
use Statamic\Statamic;
use Statamic\Tags\Context;

class EvaluateModelLocale
{
    public static function handle(mixed $model): ?string
    {
        return match (true) {
            ($model instanceof SeoDefaultSet) => request()->get('site') ?? Site::selected()->handle(),
            ($model instanceof Collection) => Statamic::isCpRoute() ? basename(request()->path()) : Site::current()->handle(),
            ($model instanceof Entry) => $model->locale(),
            ($model instanceof EntryBlueprintFound) => Statamic::isCpRoute() ? basename(request()->path()) : Site::current()->handle(),
            ($model instanceof Taxonomy) => Statamic::isCpRoute() ? basename(request()->path()) : Site::current()->handle(),
            ($model instanceof Term) => Statamic::isCpRoute() ? basename(request()->path()) : $model->locale(),
            ($model instanceof TermBlueprintFound) => Statamic::isCpRoute() ? basename(request()->path()) : Site::current()->handle(),
            ($model instanceof Context) => $model->get('site')->handle(),
            ($model instanceof DefaultsData) => $model->locale,
            default => null
        };
    }
}
