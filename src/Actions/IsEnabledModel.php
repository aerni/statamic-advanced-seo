<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Facades\Seo;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;

class IsEnabledModel
{
    public static function handle(mixed $model): bool
    {
        $parent = EvaluateModelParent::handle($model);

        if ($parent instanceof Collection) {
            return Seo::find("collections::{$parent->handle()}")?->enabled() ?? false;
        }

        if ($parent instanceof Taxonomy) {
            return Seo::find("taxonomies::{$parent->handle()}")?->enabled() ?? false;
        }

        return false;
    }
}
