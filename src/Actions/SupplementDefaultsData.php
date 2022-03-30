<?php

namespace Aerni\AdvancedSeo\Actions;

use Statamic\Contracts\Entries\Entry;

class SupplementDefaultsData
{
    public static function handle(Entry $model): Entry
    {
        return $model->setSupplement('defaults_data', GetDefaultsData::handle($model));
    }
}
