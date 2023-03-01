<?php

namespace Aerni\AdvancedSeo\Actions;

use Aerni\AdvancedSeo\Models\Defaults;
use Statamic\Contracts\Entries\Collection;
use Statamic\Contracts\Taxonomies\Taxonomy;

class IsEnabledModel
{
    public static function handle(mixed $model): bool
    {
        $parent = EvaluateModelParent::handle($model);

        if ($parent instanceof Collection) {
            return Defaults::isEnabled("collections::{$parent->handle()}");
        }

        if ($parent instanceof Taxonomy) {
            return Defaults::isEnabled("taxonomies::{$parent->handle()}");
        }

        return false;
    }
}
