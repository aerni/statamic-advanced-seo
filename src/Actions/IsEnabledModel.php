<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Registries\Defaults;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;

class IsEnabledModel
{
    public static function handle(mixed $model): bool
    {
        $parent = EvaluateModelParent::handle($model);

        if ($parent instanceof Collection) {
            return Defaults::find("collections::{$parent->handle()}")?->enabled() ?? false;
        }

        if ($parent instanceof Taxonomy) {
            return Defaults::find("taxonomies::{$parent->handle()}")?->enabled() ?? false;
        }

        return false;
    }
}
